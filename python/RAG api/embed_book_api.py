from fastapi import FastAPI, UploadFile, File, Form
from fastapi.responses import JSONResponse
from docling.document_converter import DocumentConverter
from transformers import AutoTokenizer
from sentence_transformers import SentenceTransformer
import faiss, os, mysql.connector, requests
import numpy as np
import re
import tempfile
# from query_api import router as query_router
from pydantic import BaseModel



app = FastAPI()

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

# --- DB Connection ---
def get_connection():
    return mysql.connector.connect(**DB)

# --- Insert book metadata ---
def insert_book(title, original_filename, faiss_path, desc=None):
    conn = get_connection()
    cursor = conn.cursor()
    cursor.execute("""
        INSERT INTO books (title, original_filename, faiss_index_path, description)
        VALUES (%s, %s, %s, %s)
    """, (title, original_filename, faiss_path, desc))
    conn.commit()
    book_id = cursor.lastrowid
    cursor.close()
    conn.close()
    return book_id

# --- Insert chapter metadata ---
def insert_chapter(book_id, chapter_number, chapter_title, start_page=None, end_page=None):
    conn = get_connection()
    cursor = conn.cursor()
    cursor.execute("""
        INSERT INTO chapters (book_id, chapter_number, chapter_title, start_page, end_page)
        VALUES (%s, %s, %s, %s, %s)
    """, (book_id, chapter_number, chapter_title, start_page, end_page))
    conn.commit()
    chapter_id = cursor.lastrowid
    cursor.close()
    conn.close()
    return chapter_id

# --- Insert chunk content ---
def insert_chunk(book_id, chapter_id, global_faiss_id, text):
    conn = get_connection()
    cursor = conn.cursor()
    cursor.execute("""
        INSERT INTO chunks (book_id, chapter_id, global_faiss_id, text)
        VALUES (%s, %s, %s, %s)
    """, (book_id, chapter_id, global_faiss_id, text))
    conn.commit()
    cursor.close()
    conn.close()

# --- Main Endpoint ---
@app.post("/chunk-and-embed/")
async def chunk_pdf(
    file: UploadFile = File(...),
    title: str = Form(None),
    desc: str = Form(None)):

    try:
        # Save temp file
        with tempfile.NamedTemporaryFile(delete=False, suffix=".pdf") as tmp:
            tmp.write(await file.read())
            tmp_path = tmp.name

        # Convert PDF
        doc = DocumentConverter().convert(tmp_path)
        markdown = doc.document.export_to_markdown()
        filename_no_ext = os.path.splitext(file.filename)[0]
        faiss_path = f"faiss_index/{filename_no_ext}.faiss"

        # Extract chapter page ranges from provenance
        chapter_page_map = {}
        current_chapter = None

        for item in doc.document.texts:
            heading = item.text.strip() if item.text else ""
            page_nos = {prov.page_no for prov in item.prov}

            match = re.match(r"^Chapter (\d+):", heading, re.IGNORECASE)
            if match:
                current_chapter = int(match.group(1))
                chapter_page_map.setdefault(current_chapter, set()).update(page_nos)
            elif current_chapter is not None:
                # Continue collecting pages for the current chapter
                chapter_page_map[current_chapter].update(page_nos)

        # Split by chapter in Markdown
        chapters = re.split(r"(?=^## Chapter \d+:)", markdown, flags=re.MULTILINE)

        records = []
        texts = []
        global_id = 0
        chapter_map = {}

        if title:
            filename_no_ext = title

        # Insert book metadata first
        book_id = insert_book(filename_no_ext, file.filename, faiss_path, desc)

        for chapter in chapters:
            match = re.match(r"^## Chapter (\d+):\s*(.+)", chapter.strip(), re.IGNORECASE)
            if not match:
                continue

            chapter_number = int(match.group(1))
            chapter_title = match.group(2)
            content = "\n".join(chapter.strip().splitlines()[1:]).strip()
            sentences = re.split(r'\n\s*\n', content)

            # Get page start and end
            pages = sorted(chapter_page_map.get(chapter_number, []))
            page_start = pages[0] if pages else None
            page_end = pages[-1] if pages else None

            chapter_id = insert_chapter(book_id, chapter_number, chapter_title, page_start, page_end)
            chapter_map[chapter_number] = chapter_id

            buffer = ""
            for sentence in sentences:
                candidate = buffer + "\n\n" + sentence if buffer else sentence
                token_count = len(tokenizer.tokenize(candidate))

                if token_count <= 512:
                    buffer = candidate
                else:
                    texts.append(buffer)
                    records.append((global_id, chapter_id, book_id, buffer))
                    global_id += 1
                    buffer = sentence

            if buffer.strip():
                texts.append(buffer.strip())
                records.append((global_id, chapter_id, book_id, buffer.strip()))
                global_id += 1

        # Create FAISS index
        embeddings = embedder.encode(texts, convert_to_numpy=True).astype("float32")
        index = faiss.IndexFlatL2(embeddings.shape[1])
        index.add(embeddings)
        faiss.write_index(index, faiss_path)

        # Insert chunks
        for rec in records:
            global_faiss_id, chapter_id, book_id, text = rec
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

# class QueryRequest(BaseModel):
#     : str
#     chapter_number: int
#     question: str


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
        conn = get_connection()

        # db = mysql.connector.connect(
        #     host="127.0.0.1",
        #     user="root",
        #     password="",
        #     database="your_database_name"  # Replace with your DB name
        # )

        cursor = conn.cursor(dictionary=True)
        cursor.execute("SELECT id AS book_id, title, description FROM books")
        books = cursor.fetchall()

        return {"status": "success", "books": books}

    except Exception as e:
        return JSONResponse(status_code=500, content={"error": str(e)})
    

@app.get("/chapters")
def get_chapters(book_id: int = Form(...)):
    try:
        conn = get_connection()
        cursor = conn.cursor(dictionary=True)
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
        chapters = cursor.fetchall()

        return {
            "status": "success",
            "book_id": book_id,
            "chapters": chapters
        }

    except Exception as e:
        return JSONResponse(status_code=500, content={"error": str(e)})