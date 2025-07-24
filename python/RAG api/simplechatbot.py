from fastapi import FastAPI, HTTPException, UploadFile, Form, File
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
import json

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
OLLAMA_MODEL = "gemma3:4b"
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

# === Step 2: Rewrite user query using LLM (Ollama) ===
def get_standalone_question(history: list, user_prompt: str, first: bool) -> str:
    
    if first:
        return user_prompt

    conversation = ""
    for turn in history[-5:]:  # Limit to last 5 messages
        role = "User" if turn["role"] == "user" else "AI"
        conversation += f"{role}: {turn['message']}\n"

    prompt = f"""You are a helpful assistant. Your task is to rewrite the user's latest message into a standalone question or query that is context-independent and complete on its own.

This is critical for retrieval systems that use semantic similarity. Avoid using vague pronouns like "it", "this", "that" — be specific. Include relevant context from the conversation, but only what’s necessary to make the question clear and self-contained.

Conversation history:
{conversation.strip()}
User: {user_prompt.strip()}

Standalone version:"""

    payload = {
        "model": OLLAMA_MODEL,
        "prompt": prompt,
        "stream": False
    }

    try:
        response = requests.post(OLLAMA_URL, json=payload, headers=HEADERS)
        response.raise_for_status()
        return response.json().get("response", "").strip()
    except Exception as e:
        print(f"[Query Rewrite Failed] {e}")
        return user_prompt  # fallback
    
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

        # Step 1: Get latest turn
        cursor.execute("""
            SELECT IFNULL(MAX(turn), 0) + 1 AS next_turn
            FROM chat_rag_history
            WHERE session_id = %s
        """, (session_id,))
        next_turn = cursor.fetchone()[0]

        # Step 2: Insert new message with next turn
        cursor.execute("""
            INSERT INTO chat_rag_history (session_id, turn, role, message)
            VALUES (%s, %s, %s, %s)
        """, (session_id, next_turn, role, message))

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

# @app.post("/chat")
# def chat(input: ChatInput):
#     try:
#         session_id = input.session_id
#         user_prompt = input.prompt.strip()
#         book_id = input.book_id
#         chapter_id = input.chapter_id
#         unit_id = input.unit_id
#         lesson_id = input.lesson_id

#         # Save user input to DB
#         save_chat_to_db(session_id, "user", user_prompt)

#         # === Step 1: RAG - Get context from FAISS ===
#         index_path = f"{book_id}_chapter_{chapter_id}.faiss"
#         if not os.path.exists(index_path):
#             return JSONResponse(status_code=404, content={"error": "FAISS index not found for this chapter."})

#         index = faiss.read_index(index_path)
#         embedding = EMBED_MODEL.encode([user_prompt]).astype("float32")
#         D, I = index.search(embedding, k=10)
#         raw_matches = [(int(idx), float(dist)) for idx, dist in zip(I[0], D[0]) if idx != -1]

#         if not raw_matches:
#             rag_context = "No relevant content found for this chapter."
#         else:
#             # Fetch matched chunks
#             conn = mysql.connector.connect(**DB_CONFIG)
#             try:
#                 cursor = conn.cursor(dictionary=True)
#                 ids = [idx for idx, _ in raw_matches]
#                 placeholder = ','.join(['%s'] * len(ids))
#                 cursor.execute(f"""
#                     SELECT global_faiss_id, text FROM chunks
#                     WHERE book_id = %s AND chapter_id = %s AND unit_id = %s AND lesson_id = %s AND global_faiss_id IN ({placeholder})
#                 """, (book_id, chapter_id, unit_id, lesson_id, *ids))
#                 chunks = cursor.fetchall()
#             finally:
#                 cursor.close()
#                 conn.close()

#             # Rerank by keyword overlap with user prompt (hybrid boost)
#             def keyword_score(chunk_text):
#                 chunk_tokens = set(chunk_text.lower().split())
#                 prompt_tokens = set(user_prompt.lower().split())
#                 return len(chunk_tokens & prompt_tokens)

#             reranked_chunks = sorted(
#                 chunks,
#                 key=lambda c: keyword_score(c["text"]),
#                 reverse=True
#             )

#             top_k = min(10, len(reranked_chunks))
#             rag_context = "\n".join([c["text"] for c in reranked_chunks[:top_k]])

#         # === Step 2: Add recent chat context ===
#         history = get_recent_chat_context(session_id)
#         chat_context = ""
#         for turn in history:
#             role_label = "User" if turn['role'] == 'user' else "AI"
#             chat_context += f"{role_label}: {turn['message']}\n"

#         # === Step 3: Final Prompt ===
#         final_prompt = f"Use the following context to answer the user's question:\n{rag_context}\n\n{chat_context}AI:"
#         # final_prompt = f"Use the following context to answer the user's question:\n{rag_context}\nAI:"

#         # === Step 4: Query Ollama ===
#         payload = {
#             "model": OLLAMA_MODEL,
#             "prompt": final_prompt,
#             "stream": False
#         }
#         response = requests.post(OLLAMA_URL, json=payload, headers=HEADERS)
#         response.raise_for_status()
#         ai_reply = response.json().get("response", "").strip()

#         # Save AI reply
#         save_chat_to_db(session_id, "ai", ai_reply)

#         return {"response": ai_reply}

#     except Exception as e:
#         return {"error": str(e)}

@app.post("/chat")
def chat(input: ChatInput):
    try:
        session_id = input.session_id
        user_prompt = input.prompt.strip()
        book_id = input.book_id
        chapter_id = input.chapter_id
        unit_id = input.unit_id
        lesson_id = input.lesson_id

        # Save user message to DB first
        save_chat_to_db(session_id, "user", user_prompt)

        # === Step 1: Retrieve chat history ===
        history = get_recent_chat_context(session_id)


        # Step 2: Determine if it's the first message (i.e., only 1 in history = user message just saved)
        is_first_message = len(history) <= 1

        print("Is it first message?", is_first_message)

        # Step 3: Rewrite prompt conditionally
        rewritten_prompt = get_standalone_question(history, user_prompt, is_first_message)

        print(f"[Rewritten Prompt] {rewritten_prompt}")

        # === Step 3: Retrieve FAISS context using rewritten prompt ===
        index_path = f"{book_id}_chapter_{chapter_id}.faiss"
        if not os.path.exists(index_path):
            return JSONResponse(status_code=404, content={"error": "FAISS index not found for this chapter."})

        index = faiss.read_index(index_path)
        embedding = EMBED_MODEL.encode([rewritten_prompt]).astype("float32")
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
                    WHERE book_id = %s AND chapter_id = %s AND unit_id = %s AND lesson_id = %s
                    AND global_faiss_id IN ({placeholder})
                """, (book_id, chapter_id, unit_id, lesson_id, *ids))
                chunks = cursor.fetchall()
            finally:
                cursor.close()
                conn.close()

            # Hybrid reranking: keyword overlap
            def keyword_score(chunk_text):
                chunk_tokens = set(chunk_text.lower().split())
                prompt_tokens = set(rewritten_prompt.lower().split())
                return len(chunk_tokens & prompt_tokens)

            reranked_chunks = sorted(
                chunks,
                key=lambda c: keyword_score(c["text"]),
                reverse=True
            )

            top_k = min(10, len(reranked_chunks))
            rag_context = "\n".join([c["text"] for c in reranked_chunks[:top_k]])

        # === Step 4: Prepare chat history for final prompt ===
        chat_context = ""
        for turn in history:
            role_label = "User" if turn['role'] == 'user' else "AI"
            chat_context += f"{role_label}: {turn['message']}\n"

        # === Step 5: Final prompt construction ===
        final_prompt = (
        "You are a helpful educational assistant. You are only allowed to answer questions "
        "based on the context provided from the current chapter. If the user's question is not answerable "
        "using the context below, you must respond by saying that the question is outside the scope of this chapter.\n\n"
        "Only answer based on the context. Do not guess or add outside information.\n\n"

        "Context:\n"
        f"{rag_context}\n\n"
        "Conversation so far:\n"
        f"{chat_context}"
        "AI:"
    )


        # === Step 6: Send to Ollama ===
        payload = {
            "model": OLLAMA_MODEL,
            "prompt": final_prompt,
            "stream": False
        }

        response = requests.post(OLLAMA_URL, json=payload, headers=HEADERS)
        response.raise_for_status()
        ai_reply = response.json().get("response", "").strip()

        # Save AI response to DB
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

        # ✅ Format output as Markdown
        formatted = "\n\n".join([
            f"**Q{i+1}.** {qa['question'].strip()}\n\n**Answer:** {qa.get('answer', 'N/A').strip()}"
            for i, qa in enumerate(answers)
        ])

        # ✅ Save raw JSON for record, send Markdown to frontend
        markdown_quiz = format_quiz_to_markdown(answers)
        save_generated_quiz_to_db(input.book_id, input.chapter_number, markdown_quiz)

        return {"quiz": markdown_quiz}
    except Exception as e:
        import traceback
        return JSONResponse(
            status_code=500,
            content={"error": str(e), "details": traceback.format_exc()}
        )
class QuizExist(BaseModel):
    book_id: int
    chapter_number: int

@app.post("/quiz-check")
def quiz_check(input: QuizExist):
    try:
        with closing(mysql.connector.connect(**DB_CONFIG)) as conn:
            with conn.cursor(dictionary=True, buffered=True) as cursor:
                cursor.execute("""
                    SELECT * FROM generated_quiz
                    WHERE book_id = %s AND chapter_id = %s
                """, (input.book_id, input.chapter_number))
                quiz = cursor.fetchone()

                if quiz:
                    return JSONResponse(content={
                        "quiz": {
                            "message": quiz["message"],
                        }
                    })
                else:
                    return JSONResponse(content={
                        "status": "success",
                        "exists": False,
                        "quiz": None
                    })
    except mysql.connector.Error as err:
        raise HTTPException(status_code=500, detail=f"MySQL Error: {err}")
    
@app.post("/delete-quiz")
def quiz_delete(input: QuizExist):
    try:
        with closing(mysql.connector.connect(**DB_CONFIG)) as conn:
            with conn.cursor(buffered=True) as cursor:
                cursor.execute("""
                    DELETE FROM generated_quiz
                    WHERE book_id = %s AND chapter_id = %s
                """, (input.book_id, input.chapter_number))
                conn.commit()

                if cursor.rowcount == 0:
                    return JSONResponse(content={
                        "status": "fail",
                        "message": "No quiz found to delete for the given book_id and chapter_number."
                    }, status_code=404)

                return JSONResponse(content={
                    "status": "success",
                    "message": "Quiz deleted successfully."
                })

    except mysql.connector.Error as err:
        raise HTTPException(status_code=500, detail=f"MySQL Error: {err}")

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
You are a strict quiz generator.

🎯 TASK:
Based ONLY on the context provided below, generate exactly **{input.number_of_questions} {input.quiz_type.lower()}** questions.
- Grade Level: {input.grade_level}
- Difficulty: {input.difficulty_level.capitalize()}

📌 RULES:
- All questions must strictly derive from the given context.
- Each question must be numbered starting from 1.
- Each question must be followed by exactly **4 options**, labeled **A) to D)**.
- Each option must be on its own line.
- No explanations, hints, or reasoning after any question.
- Separate each full question block (Q + 4 options) with a **single blank line**.
- Do NOT repeat or paraphrase the context.
- Do NOT include the correct answers directly after the questions.

📌 FINAL SECTION:
After all questions, include a section labeled exactly:
**Answer Key:**  
- List the answers on separate lines, in the format:  
  `1. A`  
  `2. C`  
  `3. D`  
  (Use only the number and the correct option letter. No extra text.)

✅ OUTPUT FORMAT EXAMPLE:

1. What is the capital of France?  
A) Berlin  
B) Madrid  
C) Paris  
D) Rome  

2. What color do you get by mixing red and blue?  
A) Yellow  
B) Purple  
C) Green  
D) Orange  

Answer Key:  
1. C  
2. B  

❌ DO NOT:
- Do not include explanations.
- Do not generate content outside the context.
- Do not change formatting or answer key structure.

--- BEGIN CONTEXT ---
{context}
--- END CONTEXT ---
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

def format_quiz_to_markdown(qa_list):
    lines = [f"{i+1}. {q['question'].strip()}" for i, q in enumerate(qa_list)]
    lines.append("\nAnswer Key:")
    lines.extend(f"{i+1}. {q['answer'].strip()}" for i, q in enumerate(qa_list) if q.get("answer", "").strip())
    return "\n".join(lines)