import faiss
import os
from sentence_transformers import SentenceTransformer
import requests
from typing import Optional
from langchain_core.messages import HumanMessage, AIMessage, BaseMessage
from typing import List


# --- Constants ---
OLLAMA_MODEL = "llama3"
OLLAMA_URL = "http://localhost:11434/api/generate"
CONTEXTULIZER_OLLAMA_MODEL = "gemma3:1b"

EMBED_MODEL = "all-MiniLM-L6-v2"
# DB = dict(host="127.0.0.1", user="root", password="", database="ck_agent")
os.makedirs("faiss_index", exist_ok=True)

# --- Init ---
embedder = SentenceTransformer(EMBED_MODEL)


def get_context_from_faiss(faiss_path, question, book_id, chapter_id, cur, k=100):
    if not os.path.exists(faiss_path):
        raise FileNotFoundError("FAISS file not found.")

    index = faiss.read_index(faiss_path)
    query_vec = embedder.encode([question]).astype("float32")
    distances, indices = index.search(query_vec, k=k)

    faiss_ids = [int(i) for i in indices[0]]
    if not faiss_ids:
        return []

    placeholders = ",".join(["%s"] * len(faiss_ids))
    cur.execute(f"""
        SELECT text FROM chunks
        WHERE book_id = %s AND chapter_id = %s AND global_faiss_id IN ({placeholders})
    """, (book_id, chapter_id, *faiss_ids))

    return cur.fetchall()

def get_book_and_chapter(cur, book_id, chapter_number):
    cur.execute("SELECT id, title, faiss_index_path FROM books WHERE id = %s", (book_id,))
    book = cur.fetchone()

    cur.execute("SELECT id, chapter_title FROM chapters WHERE book_id = %s AND chapter_number = %s", (book_id, chapter_number))
    chapter = cur.fetchone()

    return book, chapter

def retrieve_book_chapter_and_context(cur, book_id, chapter_number, question):
    book, chapter = get_book_and_chapter(cur, book_id, chapter_number)
    if not book or not chapter:
        return None, None, None, "Invalid book or chapter"

    try:
        chunks = get_context_from_faiss(book["faiss_index_path"], question, book_id, chapter["id"], cur)
    except Exception as e:
        return book, chapter, None, str(e)

    if not chunks:
        return book, chapter, None, "No relevant context found."

    context = "\n\n".join([c["text"] for c in chunks])
    return book, chapter, context, None

# def retrieve_book_chapter_and_context(cur, book_id, chapter_number, question, chat_history: Optional[str] = ""):
#     book, chapter = get_book_and_chapter(cur, book_id, chapter_number)
#     if not book or not chapter:
#         return None, None, None, "Invalid book or chapter"

#     combined_query = f"{chat_history.strip()} {question.strip()}".strip()

#     try:
#         chunks = get_context_from_faiss(book["faiss_index_path"], combined_query, book_id, chapter["id"], cur)
#     except Exception as e:
#         return book, chapter, None, str(e)

#     if not chunks:
#         return book, chapter, None, "No relevant context found."

#     context = "\n\n".join([c["text"] for c in chunks])
#     return book, chapter, context, None


def build_prompt(context, question):
    return f"""You are a helpful and concise tutor.

Use the following context to answer the question. If the answer is not in the context, say "The answer is not available in the provided material."

---

Context:
{context}

---

Question:
{question}

Answer:"""

def call_llm_with_context(context, question):
    prompt = build_prompt(context, question)
    response = requests.post(OLLAMA_URL, json={
        "model": OLLAMA_MODEL,
        "prompt": prompt,
        "stream": False
    })

    if response.status_code != 200:
        raise RuntimeError("Ollama call failed")

    return response.json().get("response"), prompt


contextualize_system_prompt = (
"""Given a chat history and the latest user question \
which might reference context in the chat history, formulate a standalone question which can be understood \
without the chat history. Do NOT answer the question, just reformulate it if needed and otherwise return it as is."""
)

def get_standalone_question(history: List[BaseMessage], latest_input: str) -> str:
    history_text = "\n".join([
        f"{'User' if isinstance(m, HumanMessage) else 'AI'}: {m.content}" for m in history
    ])

    prompt = f"""{contextualize_system_prompt}

---

Chat History:
{history_text}

Latest Question:
{latest_input}

Standalone Reformulated Question:"""

    response = requests.post(OLLAMA_URL, json={
        "model": CONTEXTULIZER_OLLAMA_MODEL,
        "prompt": prompt,
        "stream": False
    })

    if response.status_code != 200:
        return latest_input  # Fallback to original

    return response.json().get("response", latest_input).strip()
