from fastapi import FastAPI, UploadFile, File, Form, Query
from fastapi.responses import JSONResponse, HTMLResponse
from docling.document_converter import DocumentConverter
from transformers import AutoTokenizer
from sentence_transformers import SentenceTransformer
from fastapi.middleware.cors import CORSMiddleware
from fastapi.responses import FileResponse, Response
from fastapi.staticfiles import StaticFiles

from utils.pdf_utils import extract_chapter_page_map, parse_chapter, get_page_range, chunk_texts, build_faiss_index
from utils.db_utils import insert_book, insert_chapter, insert_chunk, get_connection, get_agent_prompt_by_message_id, get_scope_vars_by_message_id
import faiss, os, mysql.connector, requests
import numpy as np
import re
import tempfile
from pydantic import BaseModel
import fitz
import os, sys

current_script_dir = os.path.dirname(os.path.abspath(__file__))
project_root = os.path.join(current_script_dir, '..', '..')
sys.path.insert(0, project_root)


print(f"Adding to sys.path: {project_root}") 

from python.db_utils_final import create_session_and_parameter_inputs, insert_message
from python.chat_router_final import chat_router
from utils.rag_utils import call_llm_with_context, retrieve_book_chapter_and_context

app = FastAPI()

app.include_router(chat_router)

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

        seen_chapters = set()

        for chapter in chapters:
            parsed = parse_chapter(chapter)
            if not parsed:
                continue
            chapter_number, chapter_title, content = parsed

            if chapter_number in seen_chapters:
                continue
            seen_chapters.add(chapter_number)

            chapter_id = insert_chapter(
                book_id, chapter_number, chapter_title,
                *chapter_page_map.get(chapter_number, (None, None))
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
    
@app.post("/rag-initial")
def rag_initial(
    book_id: int = Form(...),
    chapter_number: int = Form(...),
    question: str = Form(...)
):
    conn = mysql.connector.connect(**DB)
    cur = conn.cursor(dictionary=True)

    book, chapter, context, error = retrieve_book_chapter_and_context(cur, book_id, chapter_number, question)
    conn.close()

    if error:
        return {"error": error}

    answer, prompt = call_llm_with_context(context, question)

    session_id = create_session_and_parameter_inputs(
        user_id=1,
        agent_id=25,
        scope_vars={"book_id": book_id, "chapter_number": chapter_number},
        human_topic=question,
        ai_output=answer.strip(),
        agent_prompt=prompt
    )

    return {
        "book_id": book_id,
        "book_title": book["title"],
        "chapter_number": chapter_number,
        "chapter_title": chapter["chapter_title"],
        "question": question,
        "answer": answer.strip(),
        "message_id": session_id
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
#         conn = get_connection()
#         cursor = conn.cursor(dictionary=True)

#         # 1. Get book source file
#         cursor.execute("SELECT source FROM books WHERE id = %s", (book_id,))
#         book = cursor.fetchone()
#         if not book:
#             return JSONResponse(status_code=404, content={"error": "Book not found"})

#         file_path = book["source"]
#         if not os.path.exists(file_path):
#             return JSONResponse(status_code=404, content={"error": "PDF file not found on disk."})

#         # 2. Get chapter start and end pages
#         cursor.execute("""
#             SELECT start_page, end_page, chapter_title
#             FROM chapters 
#             WHERE chapter_number = %s AND book_id = %s
#         """, (chapter_number, book_id))
#         chapter = cursor.fetchone()
#         cursor.close()
#         conn.close()

#         if not chapter or chapter["start_page"] is None or chapter["end_page"] is None:
#             return JSONResponse(status_code=404, content={"error": "Chapter page range not found"})

#         # 3. Extract chapter pages
#         pdf_in = fitz.open(file_path)
#         pdf_out = fitz.open()

#         for i in range(chapter["start_page"] - 1, chapter["end_page"]):
#             pdf_out.insert_pdf(pdf_in, from_page=i, to_page=i)

#         tmp_path = f"temp_chapter_{book_id}_{chapter_number}.pdf"
#         pdf_out.set_metadata({
#             "title": f"Chapter {chapter_number} - {chapter['chapter_title']}"
#         })
#         pdf_out.save(tmp_path)
#         pdf_out.close()
#         pdf_in.close()

#         # 4. Read file and return inline
#         with open(tmp_path, "rb") as f:
#             pdf_data = f.read()

#         # headers = {
#         #     "Content-Type": "application/pdf",
#         #     "Content-Disposition": f'inline; filename="chapter_{book_id}_{chapter_number}.pdf"'
#         # }

#         headers = {
#             "Content-Type": "application/pdf",
#             "Content-Disposition": "inline"
#         }

#         # Optional cleanup after read
#         os.remove(tmp_path)

#         return Response(content=pdf_data, media_type="application/pdf", headers=headers)

#     except Exception as e:
#         return JSONResponse(status_code=500, content={"error": str(e)})


# Make sure this is added once in your app
app.mount("/static", StaticFiles(directory="static"), name="static")

@app.get("/view-chapter-url")
def view_chapter_url(book_id: int = Query(...), chapter_number: int = Query(...)):
    try:
        conn = get_connection()
        cursor = conn.cursor(dictionary=True)

        # Get book file path
        cursor.execute("SELECT source FROM books WHERE id = %s", (book_id,))
        book = cursor.fetchone()
        if not book:
            return JSONResponse(status_code=404, content={"error": "Book not found"})

        file_path = book["source"]
        if not os.path.exists(file_path):
            return JSONResponse(status_code=404, content={"error": "PDF file not found"})

        # Get chapter pages
        cursor.execute("""
            SELECT start_page, end_page, chapter_title
            FROM chapters 
            WHERE chapter_number = %s AND book_id = %s
        """, (chapter_number, book_id))
        chapter = cursor.fetchone()
        conn.close()

        if not chapter or chapter["start_page"] is None or chapter["end_page"] is None:
            return JSONResponse(status_code=404, content={"error": "Chapter page range not found"})

        # Extract pages
        pdf_in = fitz.open(file_path)
        pdf_out = fitz.open()

        for i in range(chapter["start_page"] - 1, chapter["end_page"]):
            pdf_out.insert_pdf(pdf_in, from_page=i, to_page=i)

        filename = f"chapter_{book_id}_{chapter_number}.pdf"
        output_path = f"static/chapters"
        os.makedirs(output_path, exist_ok=True)
        final_path = os.path.join(output_path, filename)

        pdf_out.save(final_path)
        pdf_in.close()
        pdf_out.close()

        return {
            "url": f"http://localhost:8000/static/chapters/{filename}"
        }

    except Exception as e:
        return JSONResponse(status_code=500, content={"error": str(e)})