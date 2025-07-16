from fastapi import APIRouter, Form
from pydantic import BaseModel
import faiss, mysql.connector, requests
import numpy as np
from sentence_transformers import SentenceTransformer
import os

router = APIRouter()

# --- Static Config ---
EMBED_MODEL = "all-MiniLM-L6-v2"
OLLAMA_MODEL = "gemma3:1b"
OLLAMA_URL = "http://localhost:11434/api/generate"
DB = dict(host="127.0.0.1", user="root", password="", database="ck_agent")
embedder = SentenceTransformer(EMBED_MODEL)

class QueryRequest(BaseModel):
    book_title: str
    chapter_number: int
    question: str

@router.post("/ask-question")
def ask_question(
    book_title: str = Form(...),
    chapter_number: int = Form(...),
    question: str = Form(...)
):
    faiss_file = f"faiss_index/{book_title}.faiss"
    if not os.path.exists(faiss_file):
        return {"error": f"FAISS file for '{book_title}' not found."}

    # --- Load FAISS index ---
    index = faiss.read_index(faiss_file)

    # --- Embed the question ---
    query_vec = embedder.encode([question]).astype("float32")
    distances, indices = index.search(query_vec, k=100)

    # --- Fetch matching chunks from MySQL ---
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
        return {"message": f"No chunks found for Chapter {chapter_number} in {book_title}."}

    # --- Build Prompt ---
    context = "\n\n".join([r["text"] for r in results])
    prompt = f"Context:\n{context}\n\nQuestion:\n{question}\n\nAnswer:"

    # --- Query Ollama ---
    response = requests.post(OLLAMA_URL, json={
        "model": OLLAMA_MODEL,
        "prompt": prompt,
        "stream": False
    })

    if response.status_code != 200:
        return {"error": f"Ollama failed: {response.text}"}

    answer = response.json().get("response")
    return {
        "chapter": chapter_number,
        "book": book_title,
        "question": question,
        "answer": answer
    }
