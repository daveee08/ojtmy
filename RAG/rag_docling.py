from docling.chunking import HybridChunker
from docling.document_converter import DocumentConverter
from transformers import AutoTokenizer
from sentence_transformers import SentenceTransformer
import faiss, mysql.connector, numpy as np, json, requests

# --- Config ---
DB = dict(host="127.0.0.1", user="root", password="", database="ck_agent")
PDF_URL = "https://arxiv.org/pdf/2408.09869"
TOKENIZER = "NousResearch/Llama-2-7b-chat-hf"
EMBED_MODEL = "all-MiniLM-L6-v2"
OLLAMA_MODEL = "gemma3:1b"
OLLAMA_URL = "http://localhost:11434/api/generate"
FAISS_INDEX_FILE = "faiss_index.idx"

# --- Setup ---
tokenizer = AutoTokenizer.from_pretrained(TOKENIZER)
embedder = SentenceTransformer(EMBED_MODEL)

# --- Step 1: Load & Chunk PDF ---
print("📄 Downloading and chunking document...")
doc = DocumentConverter().convert(PDF_URL)
chunker = HybridChunker(tokenizer=tokenizer, max_tokens=8192, merge_peers=True)
chunks = list(chunker.chunk(dl_doc=doc.document))
print(f"✅ Chunks created: {len(chunks)}")

# --- Step 2: Embed Chunks ---
texts = [c.text for c in chunks]
embeddings = embedder.encode(texts, convert_to_numpy=True).astype('float32')

# --- Step 3: Save to MySQL ---
print("💾 Saving chunks to MySQL...")
conn = mysql.connector.connect(**DB)
cur = conn.cursor()

cur.execute("""
CREATE TABLE IF NOT EXISTS book_chunks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    text LONGTEXT,
    book_id INT DEFAULT 1,
    chapter_number INT DEFAULT 1
)
""")
cur.execute("DELETE FROM book_chunks")

cur.executemany(
    "INSERT INTO book_chunks (text) VALUES (%s)",
    [(t,) for t in texts]
)
conn.commit()
cur.close()
conn.close()
print("✅ Chunks saved to DB.")

# --- Step 4: Save FAISS index ---
print("📦 Building and saving FAISS index...")
index = faiss.IndexFlatL2(embeddings.shape[1])
index.add(embeddings)
faiss.write_index(index, FAISS_INDEX_FILE)
print("✅ FAISS index saved.")

# --- Step 5: Query FAISS + Ollama ---
print("\n🔍 Ready! Ask a question below.\n")
query = input("❓ Your question: ")

query_vec = embedder.encode([query]).astype('float32')
index = faiss.read_index(FAISS_INDEX_FILE)
distances, indices = index.search(query_vec, k=5)

# --- Step 6: Fetch matching chunks from DB ---
conn = mysql.connector.connect(**DB)
cur = conn.cursor(dictionary=True)

matched_ids = [int(i) + 1 for i in indices[0]]  # MySQL IDs are 1-based
placeholders = ','.join(['%s'] * len(matched_ids))
cur.execute(f"SELECT * FROM book_chunks WHERE id IN ({placeholders})", matched_ids)
matches = cur.fetchall()
conn.close()

# --- Step 7: Build prompt and call Ollama ---
context = "\n\n".join([m['text'] for m in matches])
prompt = f"Context:\n{context}\n\nQuestion:\n{query}\n\nAnswer:"

response = requests.post(OLLAMA_URL, json={
    "model": OLLAMA_MODEL,
    "prompt": prompt,
    "stream": False
})
answer = response.json().get("response")

print("\n💬 Gemma's Answer:\n")
print(answer)
