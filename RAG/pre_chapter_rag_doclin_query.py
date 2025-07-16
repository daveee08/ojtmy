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
PDF_URL = "sample_robotics.pdf"
TOKENIZER = "NousResearch/Llama-2-7b-chat-hf"
EMBED_MODEL = "all-MiniLM-L6-v2"
OUTPUT_DIR = "faiss_chapters"

# --- Setup ---
os.makedirs(OUTPUT_DIR, exist_ok=True)
tokenizer = AutoTokenizer.from_pretrained(TOKENIZER)
embedder = SentenceTransformer(EMBED_MODEL)

# --- Step 1: Load & Chunk ---
print("📄 Converting and chunking PDF...")
doc = DocumentConverter().convert(PDF_URL)
chunker = HybridChunker(tokenizer=tokenizer, max_tokens=8192, merge_peers=True)
chunks = list(chunker.chunk(dl_doc=doc.document))
print(f"✅ Total chunks: {len(chunks)}")

# --- Step 2: Group by heading (chapter title) ---
chapter_groups = {}
for chunk in chunks:
    heading = chunk.meta.headings[0] if chunk.meta.headings else "Unknown"
    if heading not in chapter_groups:
        chapter_groups[heading] = []
    chapter_groups[heading].append(chunk)

print(f"📚 Detected chapters: {len(chapter_groups)}")

# --- Step 3: Setup DB ---
conn = mysql.connector.connect(**DB)
cursor = conn.cursor()

# Create table for chapter chunks
cursor.execute("""
CREATE TABLE IF NOT EXISTS chapter_chunks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    faiss_id INT,
    chapter_number INT,
    chapter_title VARCHAR(255),
    page_numbers TEXT,
    text LONGTEXT
)
""")
cursor.execute("DELETE FROM chapter_chunks")
conn.commit()

# --- Step 4: Process each chapter ---
for chapter_number, (chapter_title, chapter_chunks) in enumerate(chapter_groups.items(), 1):
    print(f"\n📘 Processing Chapter {chapter_number}: {chapter_title}")

    # Prepare embeddings
    texts = [c.text for c in chapter_chunks]
    embeddings = embedder.encode(texts, convert_to_numpy=True).astype("float32")

    # Build and save FAISS index
    index = faiss.IndexFlatL2(embeddings.shape[1])
    index.add(embeddings)
    faiss_path = os.path.join(OUTPUT_DIR, f"faiss_chapter_{chapter_number}.faiss")
    faiss.write_index(index, faiss_path)
    print(f"✅ Saved FAISS index → {faiss_path}")

    # Store each chunk with FAISS ID and metadata in MySQL
    records = []
    for faiss_id, chunk in enumerate(chapter_chunks):
        pages = sorted({p.page_no for d in chunk.meta.doc_items for p in d.prov})
        page_str = ",".join(map(str, pages)) if pages else None
        records.append((faiss_id, chapter_number, chapter_title, page_str, chunk.text))

    cursor.executemany("""
        INSERT INTO chapter_chunks (faiss_id, chapter_number, chapter_title, page_numbers, text)
        VALUES (%s, %s, %s, %s, %s)
    """, records)
    conn.commit()

    print(f"✅ Inserted {len(records)} chunks into MySQL for Chapter {chapter_number}")

cursor.close()
conn.close()
print("\n🎉 All chapters processed and stored.")
