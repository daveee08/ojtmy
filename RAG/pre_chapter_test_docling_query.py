import faiss
import mysql.connector
import numpy as np
import requests
from sentence_transformers import SentenceTransformer

# --- Config ---
DB = dict(host="127.0.0.1", user="root", password="", database="ck_agent")
EMBED_MODEL = "all-MiniLM-L6-v2"
OLLAMA_MODEL = "gemma3:1b"
OLLAMA_URL = "http://localhost:11434/api/generate"
FAISS_INDEX_TEMPLATE = "faiss_chapters/faiss_chapter_{n}.faiss"

# --- Load Embedder ---
embedder = SentenceTransformer(EMBED_MODEL)

# --- Get User Input ---
chapter_number = int(input("📘 Enter chapter number: "))
question = input("❓ Enter your question: ")

# --- Load FAISS Index ---
faiss_path = FAISS_INDEX_TEMPLATE.format(n=chapter_number)
index = faiss.read_index(faiss_path)

# --- Embed the Query ---
query_vec = embedder.encode([question]).astype("float32")
distances, indices = index.search(query_vec, k=5)

# --- Lookup Corresponding Chunks from DB ---
conn = mysql.connector.connect(**DB)
cur = conn.cursor(dictionary=True)

# Convert FAISS IDs to DB lookup
faiss_ids = [int(i) for i in indices[0]]
placeholders = ",".join(["%s"] * len(faiss_ids))
cur.execute(f"""
    SELECT * FROM chapter_chunks
    WHERE chapter_number = %s AND faiss_id IN ({placeholders})
""", [chapter_number] + faiss_ids)

results = cur.fetchall()
conn.close()

if not results:
    print("⚠️ No matches found for this chapter.")
    exit()

# --- Build Prompt for Ollama ---
context = "\n\n".join([r["text"] for r in results])
prompt = f"Context:\n{context}\n\nQuestion:\n{question}\n\nAnswer:"

# --- Call Ollama ---
response = requests.post(OLLAMA_URL, json={
    "model": OLLAMA_MODEL,
    "prompt": prompt,
    "stream": False
})
answer = response.json().get("response")

# --- Output ---
print("\n💬 Gemma's Answer:\n")
print(answer)
