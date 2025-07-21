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

app = FastAPI()

# === Config ===
EMBED_MODEL = SentenceTransformer("all-MiniLM-L6-v2")
TOKENIZER = AutoTokenizer.from_pretrained("bert-base-uncased")
OLLAMA_URL = "http://localhost:11434/api/generate"
OLLAMA_MODEL = "gemma3:1b"
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
    chunks = [tokens[i:i+max_tokens] for i in range(0, len(tokens), max_tokens)]
    return [TOKENIZER.decode(chunk) for chunk in chunks]

# === Upload + Embed Endpoint ===
@app.post("/upload-and-embed")
async def upload_pdf(
    book_id: int = Form(...),
    unit_id: int = Form(...),
    chapter_number: int = Form(...),
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
        chunks = chunk_text_token_based(full_text, max_tokens=100)
        embeddings = EMBED_MODEL.encode(chunks)

        # FAISS index logic
        index_path = f"{book_id}-{chapter_number}.faiss"
        if os.path.exists(index_path):
            index = faiss.read_index(index_path)
        else:
            index = faiss.IndexFlatL2(EMBED_MODEL.get_sentence_embedding_dimension())

        vector_np = np.array(embeddings).astype("float32")
        start_id = index.ntotal
        index.add(vector_np)
        faiss.write_index(index, index_path)

        # Insert chunks into DB
        conn = mysql.connector.connect(**DB_CONFIG)
        try:
            cursor = conn.cursor()
            for i, chunk in enumerate(chunks):
                faiss_id = start_id + i
                cursor.execute("""
                    INSERT INTO chunks (book_id, chapter_number, unit_id, lesson_id, global_faiss_id, text)
                    VALUES (%s, %s, %s, %s, %s, %s)
                """, (book_id, chapter_number,unit_id, lesson_id, faiss_id, chunk))
            conn.commit()
        finally:
            cursor.close()
            conn.close()

        return {"status": "success", "chunks_added": len(chunks), "index_path": index_path}

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
    chapter_number: int

@app.post("/chat")
def chat(input: ChatInput):
    try:
        session_id = input.session_id
        user_prompt = input.prompt.strip()
        book_id = input.book_id
        chapter_number = input.chapter_number

        # Save user input to DB
        save_chat_to_db(session_id, "user", user_prompt)

        # === Step 1: RAG - Get context from FAISS ===
        index_path = f"{book_id}-{chapter_number}.faiss"
        if not os.path.exists(index_path):
            return JSONResponse(status_code=404, content={"error": "FAISS index not found for this chapter."})

        index = faiss.read_index(index_path)
        embedding = EMBED_MODEL.encode([user_prompt]).astype("float32")
        D, I = index.search(embedding, k=5)  # top 5 matches

        # Convert numpy.int64 to native int for SQL query
        matched_ids = [int(idx) for idx in I[0] if idx != -1]
        if not matched_ids:
            rag_context = "No relevant content found for this chapter."
        else:
            # Get matched texts from DB using global_faiss_id
            conn = mysql.connector.connect(**DB_CONFIG)
            try:
                cursor = conn.cursor(dictionary=True)
                placeholder = ','.join(['%s'] * len(matched_ids))
                cursor.execute(f"""
                    SELECT global_faiss_id, text FROM chunks
                    WHERE book_id = %s AND chapter_number = %s AND global_faiss_id IN ({placeholder})
                """, (book_id, chapter_number, *matched_ids))
                chunks = cursor.fetchall()
            finally:
                cursor.close()
                conn.close()

            # Order chunks in same order as retrieved from FAISS
            rag_context = "\n".join(
                [c["text"] for c in sorted(chunks, key=lambda x: matched_ids.index(int(x["global_faiss_id"])))]
            )

        # === Step 2: Add recent chat context ===
        history = get_recent_chat_context(session_id)
        chat_context = ""
        for turn in history:
            role_label = "User" if turn['role'] == 'user' else "AI"
            chat_context += f"{role_label}: {turn['message']}\n"

        # === Step 3: Final Prompt ===
        final_prompt = f"Use the following context to answer the user's question:\n{rag_context}\n\n{chat_context}AI:"

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
    session_id: int
    book_id: int
    chapter_number: int
    faiss_file: str
    quiz_type: str
    number_of_questions: int
    difficulty_level: str
    grade_level: str
    answer_key: bool

# === Make Quiz Endpoint ===
@app.post("/make-quiz")
def make_quiz(input: QuizInput):
    try:
        # Load FAISS index
        index_path = input.faiss_file
        if not os.path.exists(index_path):
            return JSONResponse(status_code=404, content={"error": "FAISS index not found."})

        index = faiss.read_index(index_path)

        # Step 1: Generate questions ONLY using Ollama
        context_instruction = f"""
Generate {input.number_of_questions} {input.quiz_type} questions for a quiz.
Difficulty: {input.difficulty_level}, Grade: {input.grade_level}.
Do NOT include answers.
Return only numbered questions.
"""

        payload = {
            "model": OLLAMA_MODEL,
            "prompt": context_instruction,
            "stream": False
        }

        response = requests.post(OLLAMA_URL, json=payload, headers=HEADERS)
        response.raise_for_status()
        raw_questions = response.json().get("response", "").strip()

        # Step 2: Parse questions (assume 1. 2. 3. format)
        import re
        questions = re.findall(r"\d+\.\s+(.*)", raw_questions)

        answers = []

        # Step 3: Search FAISS for each question
        for q in questions:
            embedding = EMBED_MODEL.encode([q]).astype("float32")
            D, I = index.search(embedding, k=1)
            top_id = int(I[0][0])

            # Fetch corresponding chunk from DB
            conn = mysql.connector.connect(**DB_CONFIG)
            try:
                cursor = conn.cursor(dictionary=True)
                cursor.execute("""
                    SELECT text FROM chunks
                    WHERE book_id = %s AND chapter_number = %s AND global_faiss_id = %s
                """, (input.book_id, input.chapter_number, top_id))
                result = cursor.fetchone()
                answer = result["text"] if result else "Answer not found."
            finally:
                cursor.close()
                conn.close()

            answers.append({"question": q, "answer": answer})

        # Save as AI output
        save_chat_to_db(input.session_id, "ai", str(answers))

        return {"quiz": answers}

    except Exception as e:
        import traceback
        traceback.print_exc()
        return JSONResponse(
            status_code=500,
            content={
                "error": str(e),
                "details": traceback.format_exc()
            }
        )
