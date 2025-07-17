from fastapi import FastAPI, UploadFile, File, Form, Query
from fastapi.responses import JSONResponse, HTMLResponse
from docling.document_converter import DocumentConverter
from transformers import AutoTokenizer
from sentence_transformers import SentenceTransformer
from fastapi.middleware.cors import CORSMiddleware
from fastapi.responses import FileResponse, Response
from utils.pdf_utils import extract_chapter_page_map, parse_chapter, get_page_range, chunk_texts, build_faiss_index
from utils.db_utils import insert_book, insert_chapter, insert_chunk, get_connection
import faiss, os, mysql.connector, requests
import numpy as np
import re
import tempfile
from pydantic import BaseModel
import fitz
import base64

app = FastAPI()

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # Or limit to ["http://localhost:8000"]
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# --- Constants ---
OLLAMA_MODEL = "gemma3:1b"
OLLAMA_URL = "http://localhost:11434/api/generate"
TOKENIZER = "NousResearch/Llama-2-7b-chat-hf"
EMBED_MODEL = "all-MiniLM-L6-v2"
DB = dict(host="127.0.0.1", user="root", password="", database="ck_agent")
os.makedirs("faiss_index", exist_ok=True)

# --- Init ---
tokenizer = AutoTokenizer.from_pretrained(TOKENIZER)
embedder = SentenceTransformer(EMBED_MODEL)

# --- Main Endpoint ---
@app.post("/chunk-and-embed/")
async def chunk_pdf(
    file: UploadFile = File(...),
    title: str = Form(None),
    desc: str = Form(None),
    source: str = Form(None),
    grade_lvl: str = Form(None)):

    try:
        # Step 1: Save uploaded PDF
        with tempfile.NamedTemporaryFile(delete=False, suffix=".pdf") as tmp:
            tmp.write(await file.read())
            tmp_path = tmp.name

        # Step 2: Convert to markdown
        doc = DocumentConverter().convert(tmp_path)
        markdown = doc.document.export_to_markdown()
        filename_no_ext = title or os.path.splitext(file.filename)[0]
        faiss_path = f"faiss_index/{filename_no_ext}.faiss"

        # Step 3: Map chapters to page numbers
        chapter_page_map = extract_chapter_page_map(doc)

        # Step 4: Insert book into DB
        book_id = insert_book(filename_no_ext, source, file.filename, faiss_path, grade_level=grade_lvl, desc=desc)

        # Step 5: Split by chapters and process
        texts, records = [], []
        chapters = re.split(r"(?=^## Chapter \d+:)", markdown, flags=re.MULTILINE)
        global_id = 0

        for chapter in chapters:
            parsed = parse_chapter(chapter)
            if not parsed:
                continue
            chapter_number, chapter_title, content = parsed

            chapter_id = insert_chapter(
                book_id, chapter_number, chapter_title,
                *get_page_range(chapter_page_map.get(chapter_number, []))
            )

            # Chunk and tokenize
            chapter_records, chapter_texts, global_id = chunk_texts(content, chapter_id, book_id, global_id, tokenizer)
            records.extend(chapter_records)
            texts.extend(chapter_texts)

        # Step 6: Create FAISS index
        index = build_faiss_index(texts, faiss_path, embedder)

        # Step 7: Store chunks in DB
        for global_faiss_id, chapter_id, book_id, text in records:
            insert_chunk(book_id, chapter_id, global_faiss_id, text)

        return {
            "status": "success",
            "book_id": book_id,
            "book_title": filename_no_ext,
            "chunks_stored": len(records),
            "description": desc
        }

    except Exception as e:
        return JSONResponse(status_code=500, content={"error": str(e)})

@app.post("/ask-question")
def ask_question(
    book_id: int = Form(...),
    chapter_number: int = Form(...),
    question: str = Form(...)
):
    # --- Connect to DB ---
    conn = mysql.connector.connect(**DB)
    cur = conn.cursor(dictionary=True)

    # --- Step 1: Get Book Info by ID ---
    cur.execute("SELECT id, title, faiss_index_path FROM books WHERE id = %s", (book_id,))
    book = cur.fetchone()
    if not book:
        conn.close()
        return {"error": f"No book found with ID {book_id}."}

    book_title = book["title"]
    faiss_file = book["faiss_index_path"]

    if not os.path.exists(faiss_file):
        conn.close()
        return {"error": f"FAISS file not found at path: {faiss_file}"}

    # --- Step 2: Get Chapter ID ---
    cur.execute("""
        SELECT id, chapter_title FROM chapters 
        WHERE book_id = %s AND chapter_number = %s
    """, (book_id, chapter_number))
    chapter = cur.fetchone()
    if not chapter:
        conn.close()
        return {"error": f"No Chapter {chapter_number} found for book ID {book_id}."}

    chapter_id = chapter["id"]
    chapter_title = chapter["chapter_title"]

    # --- Step 3: Load FAISS and Search ---
    index = faiss.read_index(faiss_file)
    query_vec = embedder.encode([question]).astype("float32")
    distances, indices = index.search(query_vec, k=10)

    # --- Step 4: Fetch Matching Chunks ---
    faiss_ids = [int(i) for i in indices[0]]
    placeholders = ",".join(["%s"] * len(faiss_ids))

    cur.execute(f"""
        SELECT text FROM chunks
        WHERE book_id = %s AND chapter_id = %s AND global_faiss_id IN ({placeholders})
    """, (book_id, chapter_id, *faiss_ids))
    results = cur.fetchall()
    conn.close()

    if not results:
        return {"message": f"No chunks found for Chapter {chapter_number} in book ID {book_id}."}

    # --- Step 5: Build Prompt ---
    context = "\n\n".join([r["text"] for r in results])
    prompt = f"""You are a helpful and concise tutor.

Use the following context to answer the question. If the answer is not in the context, say "The answer is not available in the provided material."

---

Context:
{context}

---

Question:
{question}

Answer:"""

    # --- Step 6: Query Ollama ---
    response = requests.post(OLLAMA_URL, json={
        "model": OLLAMA_MODEL,
        "prompt": prompt,
        "stream": False
    })

    if response.status_code != 200:
        return {"error": f"Ollama failed: {response.text}"}

    answer = response.json().get("response")

    return {
        "book_id": book_id,
        "book_title": book_title,
        "chapter_number": chapter_number,
        "chapter_title": chapter_title,
        "question": question,
        "answer": answer
    }

@app.get("/books")
def get_books():
    try:
        with get_connection() as conn:
            with conn.cursor(dictionary=True) as cursor:
                cursor.execute("SELECT id AS book_id, title, description, grade_level FROM books")
                return {"status": "success", "books": cursor.fetchall()}
    except Exception as e:
        return JSONResponse(status_code=500, content={"error": str(e)})
    
@app.post("/chapters")
def get_chapters(book_id: int = Form(...)):
    try:
        with get_connection() as conn:
            with conn.cursor(dictionary=True) as cursor:
                cursor.execute("""
                    SELECT 
                        id AS chapter_id,
                        chapter_number,
                        chapter_title,
                        start_page,
                        end_page
                    FROM chapters
                    WHERE book_id = %s
                    ORDER BY chapter_number ASC
                """, (book_id,))
                return {
                    "status": "success",
                    "book_id": book_id,
                    "chapters": cursor.fetchall()
                }
    except Exception as e:
        return JSONResponse(status_code=500, content={"error": str(e)})

# @app.get("/view-chapter")
# def view_chapter(book_id: int = Query(...), chapter_number: int = Query(...)):
#     try:
#         with get_connection() as conn, conn.cursor(dictionary=True) as cursor:
#             cursor.execute("SELECT source FROM books WHERE id = %s", (book_id,))
#             book = cursor.fetchone()
#             if not book:
#                 return JSONResponse(status_code=404, content={"error": "Book not found"})

#             file_path = book["source"]
#             if not os.path.exists(file_path):
#                 return JSONResponse(status_code=404, content={"error": "PDF file not found on disk."})

#             cursor.execute("""
#                 SELECT start_page, end_page, chapter_title
#                 FROM chapters 
#                 WHERE chapter_number = %s AND book_id = %s
#             """, (chapter_number, book_id))
#             chapter = cursor.fetchone()

#         if not chapter or chapter["start_page"] is None or chapter["end_page"] is None:
#             return JSONResponse(status_code=404, content={"error": "Chapter page range not found"})

#         pdf_in = fitz.open(file_path)
#         pdf_out = fitz.open()
#         for i in range(chapter["start_page"] - 1, chapter["end_page"]):
#             pdf_out.insert_pdf(pdf_in, from_page=i, to_page=i)

#         tmp_path = f"temp_chapter_{book_id}_{chapter_number}.pdf"
#         pdf_out.set_metadata({
#             "title": f"Chapter {chapter_number} - {chapter['chapter_title']}"
#         })
#         pdf_out.save(tmp_path)
#         pdf_in.close()
#         pdf_out.close()

#         with open(tmp_path, "rb") as f:
#             pdf_data = f.read()
#         os.remove(tmp_path)

#         return Response(
#             content=pdf_data,
#             media_type="application/pdf",
#             headers={
#                 "Content-Type": "application/pdf",
#                 "Content-Disposition": f'inline; filename="chapter_{book_id}_{chapter_number}.pdf"'
#             }
#         )

#     except Exception as e:
#         return JSONResponse(status_code=500, content={"error": str(e)})
