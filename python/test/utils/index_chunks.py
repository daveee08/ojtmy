from docling.chunking import HybridChunker
from docling.document_converter import DocumentConverter
from transformers import AutoTokenizer
from sentence_transformers import SentenceTransformer
import faiss, mysql.connector, numpy as np, json, re
from pathlib import Path

# --- Config ---
DB = dict(host="127.0.0.1", user="root", password="", database="ck_agent")
PDF_URL = Path("python/test/sample (1).pdf")
TOKENIZER = "NousResearch/Llama-2-7b-chat-hf"
EMBED_MODEL = "all-MiniLM-L6-v2"
FAISS_FILE = "vector_index.faiss"

# --- Chunk PDF ---
tokenizer = AutoTokenizer.from_pretrained(TOKENIZER)
doc = DocumentConverter().convert(PDF_URL)
chunker = HybridChunker(tokenizer=tokenizer, max_tokens=8192, merge_peers=True)
chunks = list(chunker.chunk(dl_doc=doc.document))

# --- Embedding ---
texts = [c.text for c in chunks]
embedder = SentenceTransformer(EMBED_MODEL)
embeddings = embedder.encode(texts, convert_to_numpy=True)

# --- Save to MySQL ---
conn = mysql.connector.connect(**DB)
cur = conn.cursor()

# Update table schema to include faiss_id
cur.execute("""CREATE TABLE IF NOT EXISTS doc_chunks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chunk_index INT,
    faiss_id INT,
    text LONGTEXT,
    filename VARCHAR(255),
    title VARCHAR(255),
    page_numbers TEXT
)""")

# Clear table for fresh insertion
cur.execute("DELETE FROM doc_chunks")

# Build insert records with faiss_id
records = []
for i, c in enumerate(chunks):
    meta = {
        "filename": c.meta.origin.filename,
        "title": c.meta.headings[0] if c.meta.headings else None,
        "pages": json.dumps(list({p.page_no for d in c.meta.doc_items for p in d.prov}))
    }
    records.append((i, i, c.text, meta["filename"], meta["title"], meta["pages"]))
    #         ^   ^  faiss_id same as chunk_index

# Insert records into MySQL
cur.executemany("""
    INSERT INTO doc_chunks (chunk_index, faiss_id, text, filename, title, page_numbers)
    VALUES (%s, %s, %s, %s, %s, %s)
""", records)

conn.commit()

# --- Save FAISS ---
index = faiss.IndexFlatL2(embeddings.shape[1])
index.add(embeddings)
faiss.write_index(index, FAISS_FILE)

print("Metadata and FAISS index saved")

cur.close()
conn.close()
