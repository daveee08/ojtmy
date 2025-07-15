from docling.chunking import HybridChunker
from docling.document_converter import DocumentConverter
from transformers import AutoTokenizer
from sentence_transformers import SentenceTransformer
import faiss, numpy as np, mysql.connector, os, re

# --- Config ---
DB = dict(host="127.0.0.1", user="root", password="", database="ck_agent")
PDF_URL = "cut_book.pdf"
TOKENIZER = "NousResearch/Llama-2-7b-chat-hf"
EMBED_MODEL = "all-MiniLM-L6-v2"
FAISS_FILE = "faiss_index/faiss_all.faiss"
os.makedirs("faiss_index", exist_ok=True)

# --- Init ---
tokenizer = AutoTokenizer.from_pretrained(TOKENIZER)
embedder = SentenceTransformer(EMBED_MODEL)
result = DocumentConverter().convert(PDF_URL)
doc = result.document
chunks = HybridChunker(tokenizer=tokenizer, max_tokens=8192, merge_peers=True).chunk(dl_doc=doc)
# --- DB Setup ---
conn = mysql.connector.connect(**DB)
cursor = conn.cursor()
cursor.execute("DROP TABLE IF EXISTS chapter_chunks")
cursor.execute("""
CREATE TABLE chapter_chunks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    faiss_id INT,
    chapter_number INT,
    chapter_title VARCHAR(255),
    page_numbers TEXT,
    text LONGTEXT
)
""")

# --- Chunk Processing ---
texts, records = [], []
current_chapter, last_heading = 0, None

for i, chunk in enumerate(chunks):
    text = chunk.text
    texts.append(text)

    heading = chunk.meta.headings[0].strip() if chunk.meta.headings else None
    title = heading if heading else "Unknown"

    if heading and heading.lower().startswith("chapter") and heading != last_heading:
        current_chapter += 1
        last_heading = heading
    if current_chapter == 0:
        current_chapter = 1

    pages = sorted({p.page_no for d in chunk.meta.doc_items for p in d.prov})
    page_str = ",".join(map(str, pages)) if pages else None

    records.append((i, current_chapter, title, page_str, text))

# --- Embedding & FAISS Index ---
embeddings = embedder.encode(texts, convert_to_numpy=True).astype("float32")
index = faiss.IndexFlatL2(embeddings.shape[1])
index.add(embeddings)
faiss.write_index(index, FAISS_FILE)

# --- Insert to DB ---
cursor.executemany("""
INSERT INTO chapter_chunks (faiss_id, chapter_number, chapter_title, page_numbers, text)
VALUES (%s, %s, %s, %s, %s)
""", records)
conn.commit()
cursor.close()
conn.close()

print("\n🎉 Done! FAISS index and metadata stored in MySQL.")
