from fastapi import FastAPI, UploadFile, Form, File
from fastapi.responses import JSONResponse
from pydantic import BaseModel
from sentence_transformers import SentenceTransformer
from transformers import AutoTokenizer
import fitz  # PyMuPDF
import faiss
import os
import numpy as np
import tempfile
import mysql.connector
import requests
from fastapi.middleware.cors import CORSMiddleware
from contextlib import closing
import re 

app = FastAPI()

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # Or limit to ["http://localhost:8000"]
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# === Config ===
EMBED_MODEL = SentenceTransformer("all-MiniLM-L6-v2")
TOKENIZER = AutoTokenizer.from_pretrained("bert-base-uncased")
OLLAMA_URL = "http://localhost:11434/api/generate"
OLLAMA_MODEL = "llama3:latest"
# OLLAMA_MODEL = "gemma3:latest"

HEADERS = {"Content-Type": "application/json"}

DB_CONFIG = {
    "host": "localhost",
    "user": "root",
    "password": "",
    "database": "ck_agent"
}

# === Token-based chunking ===
def chunk_text_token_based(text: str, max_tokens: int = 512) -> list:
    tokens = TOKENIZER.encode(text, add_special_tokens=False)
    chunks = []
    for i in range(0, len(tokens), max_tokens):
        chunk = tokens[i:i + max_tokens]
        chunk_text = TOKENIZER.decode(chunk, skip_special_tokens=True)
        chunks.append(chunk_text.strip())
    return chunks


# === Upload + Embed Endpoint ===
@app.post("/upload-and-embed")
async def upload_pdf(
    book_id: int = Form(...),
    unit_id: int = Form(...),
    chapter_id: int = Form(...),
    lesson_id: int = Form(...),
    file: UploadFile = File(...)
):
    try:
        # Save PDF temporarily
        with tempfile.NamedTemporaryFile(delete=False, suffix=".pdf") as tmp:
            tmp.write(await file.read())
            pdf_path = tmp.name

        # Extract text from PDF
        doc = fitz.open(pdf_path)
        full_text = "".join([page.get_text() for page in doc])

        if not full_text.strip():
            return JSONResponse(status_code=400, content={"error": "PDF contains no extractable text."})

        chunks = chunk_text_token_based(full_text, max_tokens=100)
        embeddings = EMBED_MODEL.encode(chunks)

        # === FAISS index logic ===
        index_path = f"{book_id}_chapter_{chapter_id}.faiss"
        if os.path.exists(index_path):
            index = faiss.read_index(index_path)
        else:
            index = faiss.IndexFlatL2(EMBED_MODEL.get_sentence_embedding_dimension())

        vector_np = np.array(embeddings).astype("float32")
        start_id = index.ntotal
        index.add(vector_np)
        faiss.write_index(index, index_path)

        # === Insert chunks into DB in batches ===
        conn = mysql.connector.connect(**DB_CONFIG)
        conn.autocommit = False  # Manual commit mode

        try:
            cursor = conn.cursor()
            batch_size = 20
            for i, chunk in enumerate(chunks):
                faiss_id = start_id + i
                cursor.execute("""
                    INSERT INTO chunks (book_id, chapter_id, unit_id, lesson_id, global_faiss_id, text)
                    VALUES (%s, %s, %s, %s, %s, %s)
                """, (book_id, chapter_id, unit_id, lesson_id, faiss_id, chunk))

                if (i + 1) % batch_size == 0:
                    conn.commit()

            conn.commit()


        except Exception as db_err:
            conn.rollback()
            raise db_err
        finally:
            cursor.close()
            conn.close()

        return {
            "status": "success",
            "chunks_added": len(chunks),
            "index_path": index_path
        }

    except Exception as e:
        return JSONResponse(status_code=500, content={"error": str(e)})


# === Chat History DB Functions ===
def save_chat_to_db(session_id: int, role: str, message: str):
    conn = mysql.connector.connect(**DB_CONFIG)
    try:
        cursor = conn.cursor()
        cursor.execute("""
            INSERT INTO chat_rag_history (session_id, turn, role, message)
            VALUES (%s, 
                (SELECT IFNULL(MAX(turn), 0) + 1 FROM chat_rag_history WHERE session_id = %s),
                %s, %s
            )
        """, (session_id, session_id, role, message))
        conn.commit()
    finally:
        cursor.close()
        conn.close()

def get_recent_chat_context(session_id: str, limit: int = 10):
    conn = mysql.connector.connect(**DB_CONFIG)
    try:
        cursor = conn.cursor(dictionary=True)
        cursor.execute("""
            SELECT role, message FROM chat_rag_history
            WHERE session_id = %s
            ORDER BY turn DESC
            LIMIT %s
        """, (session_id, limit))
        messages = cursor.fetchall()
        return list(reversed(messages))
    finally:
        cursor.close()
        conn.close()

# === Chat Endpoint ===
class ChatInput(BaseModel):
    session_id: int
    prompt: str
    book_id: int
    chapter_id: int
    unit_id: int
    lesson_id: int

@app.post("/chat")
def chat(input: ChatInput):
    try:
        session_id = input.session_id
        user_prompt = input.prompt.strip()
        book_id = input.book_id
        chapter_id = input.chapter_id
        unit_id = input.unit_id
        lesson_id = input.lesson_id

        # Save user input to DB
        save_chat_to_db(session_id, "user", user_prompt)

        # === Step 1: RAG - Get context from FAISS ===
        index_path = f"{book_id}_chapter_{chapter_id}.faiss"
        if not os.path.exists(index_path):
            return JSONResponse(status_code=404, content={"error": "FAISS index not found for this chapter."})

        index = faiss.read_index(index_path)
        embedding = EMBED_MODEL.encode([user_prompt]).astype("float32")
        D, I = index.search(embedding, k=10)
        raw_matches = [(int(idx), float(dist)) for idx, dist in zip(I[0], D[0]) if idx != -1]

        if not raw_matches:
            rag_context = "No relevant content found for this chapter."
        else:
            # Fetch matched chunks
            conn = mysql.connector.connect(**DB_CONFIG)
            try:
                cursor = conn.cursor(dictionary=True)
                ids = [idx for idx, _ in raw_matches]
                placeholder = ','.join(['%s'] * len(ids))
                cursor.execute(f"""
                    SELECT global_faiss_id, text FROM chunks
                    WHERE book_id = %s AND chapter_id = %s AND unit_id = %s AND lesson_id = %s AND global_faiss_id IN ({placeholder})
                """, (book_id, chapter_id, unit_id, lesson_id, *ids))
                chunks = cursor.fetchall()
            finally:
                cursor.close()
                conn.close()

            # Rerank by keyword overlap with user prompt (hybrid boost)
            def keyword_score(chunk_text):
                chunk_tokens = set(chunk_text.lower().split())
                prompt_tokens = set(user_prompt.lower().split())
                return len(chunk_tokens & prompt_tokens)

            reranked_chunks = sorted(
                chunks,
                key=lambda c: keyword_score(c["text"]),
                reverse=True
            )

            top_k = min(10, len(reranked_chunks))
            rag_context = "\n".join([c["text"] for c in reranked_chunks[:top_k]])

        # === Step 2: Add recent chat context ===
        history = get_recent_chat_context(session_id)
        chat_context = ""
        for turn in history:
            role_label = "User" if turn['role'] == 'user' else "AI"
            chat_context += f"{role_label}: {turn['message']}\n"

        # === Step 3: Final Prompt ===
        final_prompt = f"Use the following context to answer the user's question:\n{rag_context}\n\n{chat_context}AI:"
        # final_prompt = f"Use the following context to answer the user's question:\n{rag_context}\nAI:"

        # === Step 4: Query Ollama ===
        payload = {
            "model": OLLAMA_MODEL,
            "prompt": final_prompt,
            "stream": False
        }
        response = requests.post(OLLAMA_URL, json=payload, headers=HEADERS)
        response.raise_for_status()
        ai_reply = response.json().get("response", "").strip()

        # Save AI reply
        save_chat_to_db(session_id, "ai", ai_reply)

        return {"response": ai_reply}

    except Exception as e:
        return {"error": str(e)}
    
class QuizInput(BaseModel):
    book_id: int
    chapter_number: int
    unit_id: int
    quiz_type: str
    number_of_questions: int
    difficulty_level: str
    grade_level: str
    answer_key: bool

# === Make Quiz Endpoint ===
@app.post("/make-quiz")
def make_quiz(input: QuizInput):
    try:
        index = validate_faiss_index(input.book_id, input.chapter_number)
        chunks = fetch_chunks(input.book_id, input.chapter_number, input.unit_id)

        if not chunks:
            return JSONResponse(status_code=404, content={"error": "No content found."})

        context = "\n".join([c["text"] for c in chunks])[:8000]
        raw_questions = generate_questions_with_ollama(context, input)
        questions = re.findall(r"\d+\.\s+(.*)", raw_questions, re.DOTALL)

        if not questions:
            return JSONResponse(status_code=500, content={"error": "Failed to parse questions."})

        answers = search_faiss_for_answers(index, questions, input)

        if not input.answer_key:
            answers = [{"question": qa["question"]} for qa in answers]

        save_generated_quiz_to_db(input.book_id, input.chapter_number, str(answers))
        return {"quiz": answers}

    except Exception as e:
        import traceback
        return JSONResponse(status_code=500, content={"error": str(e), "details": traceback.format_exc()})

def validate_faiss_index(book_id, chapter_number):
    index_path = f"{book_id}_chapter_{chapter_number}.faiss"
    if not os.path.exists(index_path):
        raise FileNotFoundError("Missing FAISS index.")
    return faiss.read_index(index_path)

def fetch_chunks(book_id, chapter, unit):
    with closing(mysql.connector.connect(**DB_CONFIG)) as conn:
        with conn.cursor(dictionary=True) as cursor:
            cursor.execute("""
                SELECT text FROM chunks
                WHERE book_id = %s AND chapter_id = %s AND unit_id = %s
            """, (book_id, chapter, unit))
            return cursor.fetchall()

def generate_questions_with_ollama(context, input):
    prompt = f"""
Using the following context from a PDF, generate {input.number_of_questions} {input.quiz_type} questions.
Difficulty: {input.difficulty_level}, Grade: {input.grade_level}.
Return only numbered questions in the format "1. Question text".
Do NOT include answers.
Context:
{context}
"""
    return send_ollama_prompt(prompt)

def search_faiss_for_answers(index, questions, input):
    answers = []
    for q in questions:
        embedding = EMBED_MODEL.encode([q]).astype("float32")
        D, I = index.search(embedding, k=1)
        top_id = int(I[0][0])

        if top_id == -1:
            answers.append({"question": q, "answer": "No relevant content found."})
            continue

        chunk_text = fetch_chunk_by_faiss_id(input, top_id)
        answer_prompt = f"""
Given the following question and context from a PDF, provide a concise answer (1-2 sentences).
Question: {q}
Context: {chunk_text}
"""
        answer = send_ollama_prompt(answer_prompt)
        answers.append({"question": q, "answer": answer.strip()})
    return answers

def fetch_chunk_by_faiss_id(input, faiss_id):
    with closing(mysql.connector.connect(**DB_CONFIG)) as conn:
        with conn.cursor(dictionary=True) as cursor:
            cursor.execute("""
                SELECT text FROM chunks
                WHERE book_id = %s AND chapter_id = %s AND unit_id = %s AND global_faiss_id = %s
            """, (input.book_id, input.chapter_number, input.unit_id, faiss_id))
            result = cursor.fetchone()
            return result["text"] if result else "Answer not found."

def send_ollama_prompt(prompt):
    payload = {
        "model": OLLAMA_MODEL,
        "prompt": prompt,
        "stream": False
    }
    response = requests.post(OLLAMA_URL, json=payload, headers=HEADERS)
    response.raise_for_status()
    return response.json().get("response", "")

def save_generated_quiz_to_db(book_id, chapter_id, message):
    with closing(mysql.connector.connect(**DB_CONFIG)) as conn:
        with conn.cursor() as cursor:
            cursor.execute("""
                INSERT INTO generated_quiz (book_id, chapter_id, message, created_at, updated_at)
                VALUES (%s, %s, %s, NOW(), NOW())
            """, (book_id, chapter_id, message))
        conn.commit()