# from docling.chunking import HybridChunker
# from docling.document_converter import DocumentConverter
# from transformers import AutoTokenizer
# from sentence_transformers import SentenceTransformer
# import faiss
# import numpy as np
# import mysql.connector
# import os

# import re

# # --- Config ---
# DB = dict(host="127.0.0.1", user="root", password="", database="ck_agent")
# PDF_URL = "test_book.pdf"
# # PDF_URL = "https://arxiv.org/pdf/2408.09869"

# TOKENIZER = "NousResearch/Llama-2-7b-chat-hf"
# EMBED_MODEL = "all-MiniLM-L6-v2"
# FAISS_FILE = "faiss_index/faiss_all.faiss"  

# # --- Setup ---
# os.makedirs("faiss_index", exist_ok=True)
# tokenizer = AutoTokenizer.from_pretrained(TOKENIZER)
# embedder = SentenceTransformer(EMBED_MODEL)

# # --- Step 1: Convert and Chunk PDF ---
# print("📄 Converting PDF...")
# doc = DocumentConverter().convert(PDF_URL)
# chunker = HybridChunker(tokenizer=tokenizer, max_tokens=8192, merge_peers=True)
# chunks = list(chunker.chunk(dl_doc=doc.document))
# print(f"✅ Total chunks: {len(chunks)}")

# # --- Step 2: Setup DB ---
# conn = mysql.connector.connect(**DB)
# cursor = conn.cursor()
# cursor.execute("DROP TABLE IF EXISTS chapter_chunks")
# cursor.execute("""
# CREATE TABLE IF NOT EXISTS chapter_chunks (
#     id INT AUTO_INCREMENT PRIMARY KEY,
#     global_faiss_id INT,
#     chapter_number INT,
#     chapter_title VARCHAR(255),
#     page_numbers TEXT,
#     text LONGTEXT
# )
# """)
# cursor.execute("DELETE FROM chapter_chunks")
# conn.commit()

# import re

# records = []
# texts = []
# current_chapter_num = 0
# current_chapter_title = "Unknown"

# for global_faiss_id, chunk in enumerate(chunks):
#     print(chunk)
#     text = chunk.text
#     texts.append(text)

#     heading = chunk.meta.headings[0] if chunk.meta.headings else "Unknown"
#     heading = heading.strip()

#     # Check for pattern: Chapter 1: Title
#     match = re.match(r"^Chapter (\d+):\s*(.+)", heading)
#     if match:
#         current_chapter_num = int(match.group(1))
#         current_chapter_title = match.group(2)

#     # Otherwise, inherit the most recent valid chapter
#     chapter_num = current_chapter_num
#     chapter_title = current_chapter_title

#     # Get page numbers
#     pages = sorted({p.page_no for d in chunk.meta.doc_items for p in d.prov})
#     page_str = ",".join(map(str, pages)) if pages else None

#     records.append((global_faiss_id, chapter_num, chapter_title, page_str, text))


# # --- Embedding ---
# print("🔎 Embedding all chunks...")
# embeddings = embedder.encode(texts, convert_to_numpy=True).astype("float32")

# # --- FAISS Index ---
# print("💾 Saving FAISS index...")
# index = faiss.IndexFlatL2(embeddings.shape[1])
# index.add(embeddings)
# faiss.write_index(index, FAISS_FILE)

# # --- Insert to MySQL ---
# cursor.executemany("""
#     INSERT INTO chapter_chunks (global_faiss_id, chapter_number, chapter_title, page_numbers, text)
#     VALUES (%s, %s, %s, %s, %s)
# """, records)
# conn.commit()

# cursor.close()
# conn.close()
# print("\n🎉 Done! Global FAISS + Metadata stored.")



import re
from docling.document_converter import DocumentConverter
from transformers import AutoTokenizer
from sentence_transformers import SentenceTransformer
import faiss, os, mysql.connector
import numpy as np

# --- Config ---
PDF_PATH = "test_book.pdf"
TOKENIZER = "NousResearch/Llama-2-7b-chat-hf"
EMBED_MODEL = "all-MiniLM-L6-v2"
FAISS_FILE = "faiss_index/faiss_all_manual.faiss"
DB = dict(host="127.0.0.1", user="root", password="", database="ck_agent")

# --- Setup ---
os.makedirs("faiss_index", exist_ok=True)
tokenizer = AutoTokenizer.from_pretrained(TOKENIZER)
embedder = SentenceTransformer(EMBED_MODEL)

# --- Convert PDF ---
print("📄 Converting PDF to Markdown...")
doc = DocumentConverter().convert(PDF_PATH)
markdown = doc.document.export_to_markdown()

# --- Regex: Split Chapters ---
chapters = re.split(r"(?=^## Chapter \d+:)", markdown, flags=re.MULTILINE)

# --- Database Setup ---
conn = mysql.connector.connect(**DB)
cursor = conn.cursor()
cursor.execute("DROP TABLE IF EXISTS chapter_chunks")
cursor.execute("""
CREATE TABLE chapter_chunks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    global_faiss_id INT,
    chapter_number INT,
    chapter_title VARCHAR(255),
    text LONGTEXT
)
""")
conn.commit()

records = []
texts = []
global_id = 0

# --- Process Chapters ---
for chapter in chapters:
    # match = re.match(r"^## Chapter (\d+):\s*(.+)", chapter.strip())
    match = re.match(r"^## Chapter (\d+):\s*(.+)", chapter.strip(), re.IGNORECASE)

    if not match:
        continue

    chapter_number = int(match.group(1))
    chapter_title = match.group(2)

    # Remove the heading line
    content = "\n".join(chapter.strip().splitlines()[1:]).strip()

    # Split into subchunks by tokens (e.g. 512 tokens max)
    # sentences = content.split("\n\n")  # loose paragraph split
    sentences = re.split(r'\n\s*\n', content)

    buffer = ""
    for sentence in sentences:
        candidate = buffer + "\n\n" + sentence if buffer else sentence
        token_count = len(tokenizer.tokenize(candidate))

        if token_count <= 512:
            buffer = candidate
        else:
            # Store the current buffer
            texts.append(buffer)
            records.append((global_id, chapter_number, chapter_title, buffer))
            global_id += 1
            buffer = sentence

    # Last leftover
    if buffer.strip():
        texts.append(buffer.strip())
        records.append((global_id, chapter_number, chapter_title, buffer.strip()))
        global_id += 1

print(f"✅ Total chunks: {len(records)}")

# --- Embedding ---
print("🔎 Embedding...")
embeddings = embedder.encode(texts, convert_to_numpy=True).astype("float32")

# --- Save FAISS Index ---
index = faiss.IndexFlatL2(embeddings.shape[1])
index.add(embeddings)
faiss.write_index(index, FAISS_FILE)
print("💾 Saved FAISS index.")

# --- Insert to MySQL ---
cursor.executemany("""
    INSERT INTO chapter_chunks (global_faiss_id, chapter_number, chapter_title, text)
    VALUES (%s, %s, %s, %s)
""", records)
conn.commit()
cursor.close()
conn.close()
print("🎉 Done!")
