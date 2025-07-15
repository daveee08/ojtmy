import faiss, mysql.connector, numpy as np, requests
from sentence_transformers import SentenceTransformer

# --- Config ---
DB = dict(host="127.0.0.1", user="root", password="", database="ck_agent")
EMBED_MODEL = "all-MiniLM-L6-v2"
OLLAMA_URL = "http://localhost:11434/api/generate"
FAISS_FILE = "faiss_index/faiss_all.faiss"
MODEL = "gemma3:1b"

# --- Load ---
embedder = SentenceTransformer(EMBED_MODEL)
index = faiss.read_index(FAISS_FILE)

# --- Input ---
chapter = int(input("📘 Chapter number: "))
question = input("❓ Question: ")
query_vec = embedder.encode([question]).astype("float32")

# --- Search ---
D, I = index.search(query_vec, k=100)
faiss_ids = [int(i) for i in I[0]]

# --- Fetch Results ---
conn = mysql.connector.connect(**DB)
cur = conn.cursor(dictionary=True)
placeholders = ",".join(["%s"] * len(faiss_ids))
cur.execute(f"""
    SELECT text FROM chapter_chunks
    WHERE faiss_id IN ({placeholders}) AND chapter_number = %s
""", faiss_ids + [chapter])
context_chunks = [row["text"] for row in cur.fetchall()]
conn.close()

if not context_chunks:
    print(f"⚠️ No results for Chapter {chapter}")
    exit()

# --- Prompt & Call ---
prompt = f"Context:\n{'\n\n'.join(context_chunks)}\n\nQuestion:\n{question}\n\nAnswer:"
res = requests.post(OLLAMA_URL, json={"model": MODEL, "prompt": prompt, "stream": False})
print("\n💬 Gemma's Answer:\n" + res.json().get("response", "⚠️ No answer."))
