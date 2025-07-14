from docling.chunking import HybridChunker
from docling.document_converter import DocumentConverter
from transformers import AutoTokenizer
from sentence_transformers import SentenceTransformer
import faiss
import numpy as np
import mysql.connector
import os

# --- Config ---
DB = dict(host="127.0.0.1", user="root", password="", database="ck_agent")
# PDF_URL = "sample_robotics.pdf"
PDF_URL = "https://arxiv.org/pdf/2408.09869"

TOKENIZER = "NousResearch/Llama-2-7b-chat-hf"
EMBED_MODEL = "all-MiniLM-L6-v2"
FAISS_FILE = "faiss_index/faiss_all.faiss"  

# --- Setup ---
os.makedirs("faiss_index", exist_ok=True)
tokenizer = AutoTokenizer.from_pretrained(TOKENIZER)
embedder = SentenceTransformer(EMBED_MODEL)

# --- Step 1: Convert and Chunk PDF ---
print("📄 Converting PDF...")
doc = DocumentConverter().convert(PDF_URL)
chunker = HybridChunker(tokenizer=tokenizer, max_tokens=8192, merge_peers=True)
chunks = list(chunker.chunk(dl_doc=doc.document))
print(f"✅ Total chunks: {len(chunks)}")

# --- Step 2: Setup DB ---
conn = mysql.connector.connect(**DB)
cursor = conn.cursor()
cursor.execute("DROP TABLE IF EXISTS chapter_chunks")
cursor.execute("""
CREATE TABLE IF NOT EXISTS chapter_chunks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    global_faiss_id INT,
    chapter_number INT,
    chapter_title VARCHAR(255),
    page_numbers TEXT,
    text LONGTEXT
)
""")
cursor.execute("DELETE FROM chapter_chunks")
conn.commit()

# --- Step 3: Embed and Store Globally ---
texts = []
records = []

heading_to_number = {}
chapter_counter = 1

for global_faiss_id, chunk in enumerate(chunks):
    text = chunk.text
    texts.append(text)

    heading = chunk.meta.headings[0] if chunk.meta.headings else "Unknown"
    heading = heading.strip()

    # Assign a unique chapter number per unique heading
    if heading not in heading_to_number:
        heading_to_number[heading] = chapter_counter
        chapter_counter += 1

    chapter_num = heading_to_number[heading]

    pages = sorted({p.page_no for d in chunk.meta.doc_items for p in d.prov})
    page_str = ",".join(map(str, pages)) if pages else None

    records.append((global_faiss_id, chapter_num, heading, page_str, text))

# --- Embedding ---
print("🔎 Embedding all chunks...")
embeddings = embedder.encode(texts, convert_to_numpy=True).astype("float32")

# --- FAISS Index ---
print("💾 Saving FAISS index...")
index = faiss.IndexFlatL2(embeddings.shape[1])
index.add(embeddings)
faiss.write_index(index, FAISS_FILE)

# --- Insert to MySQL ---
cursor.executemany("""
    INSERT INTO chapter_chunks (global_faiss_id, chapter_number, chapter_title, page_numbers, text)
    VALUES (%s, %s, %s, %s, %s)
""", records)
conn.commit()

cursor.close()
conn.close()
print("\n🎉 Done! Global FAISS + Metadata stored.")
