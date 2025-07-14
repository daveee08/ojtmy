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
FAISS_FILE = "faiss_index/faiss_all.faiss"

# --- Load Embedder ---
embedder = SentenceTransformer(EMBED_MODEL)

# --- Input ---
chapter_number = int(input("📘 Enter chapter number: "))
question = input("❓ Enter your question: ")

# --- Load FAISS Index ---
index = faiss.read_index(FAISS_FILE)

# --- Embed Query ---
query_vec = embedder.encode([question]).astype("float32")
distances, indices = index.search(query_vec, k=100)

# --- MySQL Fetch with Post-Filtering ---
conn = mysql.connector.connect(**DB)
cur = conn.cursor(dictionary=True)

faiss_ids = [int(i) for i in indices[0]]
placeholders = ",".join(["%s"] * len(faiss_ids))

cur.execute(f"""
    SELECT * FROM chapter_chunks
    WHERE global_faiss_id IN ({placeholders})
""", faiss_ids)

results = [r for r in cur.fetchall() if r["chapter_number"] == chapter_number]
conn.close()

if not results:
    print("⚠️ No results found for Chapter", chapter_number)
    exit()

# --- Build Prompt ---
context = "\n\n".join([r["text"] for r in results])
prompt = f"Context:\n{context}\n\nQuestion:\n{question}\n\nAnswer:"

# --- Query Ollama ---
response = requests.post(OLLAMA_URL, json={
    "model": OLLAMA_MODEL,
    "prompt": prompt,
    "stream": False
})
answer = response.json().get("response")

# --- Output ---
print("\n💬 Gemma's Answer:\n")
print(answer)