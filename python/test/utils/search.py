from sentence_transformers import SentenceTransformer
import faiss, mysql.connector, numpy as np

# --- Config ---
DB = dict(host="127.0.0.1", user="root", password="", database="ck_agent")
MODEL = "all-MiniLM-L6-v2"
FAISS_FILE = "vector_index.faiss"

# --- Load model/index ---
embedder = SentenceTransformer(MODEL)
index = faiss.read_index(FAISS_FILE)

# --- Search ---
query = input("🔍 Ask something: ")
query_vec = embedder.encode([query])
D, I = index.search(np.array(query_vec), k=1)

# --- Fetch and Display ---
conn = mysql.connector.connect(**DB)
cur = conn.cursor(dictionary=True)
cur.execute("SELECT title, page_numbers, text FROM doc_chunks WHERE chunk_index = %s", (int(I[0][0]),))
r = cur.fetchone()
print(f"\n✅ Match (Score: {D[0][0]:.4f})\nTitle: {r['title']}\nPages: {r['page_numbers']}\nText: {r['text'][:500]}...\n")
cur.close()
conn.close()
