from fastapi import FastAPI, UploadFile, File, Form, HTTPException
from fastapi.responses import JSONResponse
from pydantic import BaseModel, Field
from docling.document_converter import DocumentConverter
from transformers import AutoTokenizer
from sentence_transformers import SentenceTransformer
import faiss, os, mysql.connector.pooling, requests, tempfile, re, logging
import numpy as np
from typing import List

app = FastAPI()

# --- Logger ---
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

# --- Config ---
DB = dict(host="127.0.0.1", user="root", password="", database="ck_agent")
TOKENIZER = "NousResearch/Llama-2-7b-chat-hf"
EMBED_MODEL = "all-MiniLM-L6-v2"
OLLAMA_MODEL = "gemma3:1b"
OLLAMA_URL = "http://localhost:11434/api/generate"
tokenizer = AutoTokenizer.from_pretrained(TOKENIZER)
embedder = SentenceTransformer(EMBED_MODEL)
os.makedirs("faiss_index", exist_ok=True)

# --- DB Pool ---
db_pool = mysql.connector.pooling.MySQLConnectionPool(pool_name="mypool", pool_size=5, **DB)

# --- Models ---
class QueryRequest(BaseModel):
    book_title: str = Field(..., pattern=r"^[\w-]+$")
    chapter_number: int = Field(..., ge=1)
    question: str


@app.get("/health")
def health():
    return {"status": "ok"}


@app.post("/chunk-and-embed/")
async def chunk_pdf(file: UploadFile = File(...)):
    try:
        with tempfile.NamedTemporaryFile(delete=False, suffix=".pdf") as tmp:
            tmp.write(await file.read())
            path = tmp.name

        doc = DocumentConverter().convert(path)
        chapters = re.split(r"(?=^## Chapter \d+:)", doc.document.export_to_markdown(), flags=re.MULTILINE)

        conn = db_pool.get_connection()
        cur = conn.cursor()
        cur.execute("DROP TABLE IF EXISTS chapter_chunks")
        cur.execute("""
            CREATE TABLE chapter_chunks (
                id INT AUTO_INCREMENT PRIMARY KEY,
                fais_id INT,
                chapter_number INT,
                chapter_title VARCHAR(255),
                text LONGTEXT
            )
        """)
        conn.commit()

        records, texts, global_id = [], [], 0
        for ch in chapters:
            match = re.match(r"^## Chapter (\d+):\s*(.+)", ch.strip())
            if not match:
                continue
            ch_num, ch_title = int(match.group(1)), match.group(2)
            content = "\n".join(ch.strip().splitlines()[1:])
            buffer = ""

            for para in re.split(r'\n\s*\n', content):
                segment = buffer + "\n\n" + para if buffer else para
                if len(tokenizer.tokenize(segment)) <= 512:
                    buffer = segment
                else:
                    records.append((global_id, ch_num, ch_title, buffer))
                    texts.append(buffer)
                    global_id += 1
                    buffer = para

            if buffer.strip():
                records.append((global_id, ch_num, ch_title, buffer.strip()))
                texts.append(buffer.strip())
                global_id += 1

        emb = embedder.encode(texts, convert_to_numpy=True).astype("float32")
        index = faiss.IndexFlatL2(emb.shape[1])
        index.add(emb)
        index_path = f"faiss_index/{os.path.splitext(file.filename)[0]}.faiss"
        faiss.write_index(index, index_path)

        cur.executemany("""
            INSERT INTO chapter_chunks (fais_id, chapter_number, chapter_title, text)
            VALUES (%s, %s, %s, %s)
        """, records)
        conn.commit()
        cur.close()
        conn.close()

        return {"status": "success", "chunks_stored": len(records)}

    except Exception as e:
        logger.exception("Failed to process PDF")
        raise HTTPException(status_code=500, detail=str(e))


@app.post("/ask-question")
def ask_question(query: QueryRequest):
    book_title = query.book_title
    chapter_number = query.chapter_number
    question = query.question

    path = f"faiss_index/{book_title}.faiss"
    if not os.path.exists(path):
        raise HTTPException(status_code=404, detail=f"FAISS index for '{book_title}' not found.")

    index = faiss.read_index(path)
    qvec = embedder.encode([question]).astype("float32")
    _, idxs = index.search(qvec, k=10)

    conn = db_pool.get_connection()
    cur = conn.cursor(dictionary=True)
    cur.execute(f"""
        SELECT * FROM chapter_chunks WHERE fais_id IN ({','.join(['%s']*len(idxs[0]))})
    """, list(map(int, idxs[0])))
    results = [r for r in cur.fetchall() if r["chapter_number"] == chapter_number]
    conn.close()

    if not results:
        return {"message": f"No relevant chunks found for Chapter {chapter_number}."}

    context = "\n\n".join([r["text"] for r in results])
    prompt = f"""You are a helpful tutor.

Use the context to answer. If the answer is not in the context, say so.

---

Context:
{context}

---

Question:
{question}

Answer:"""

    res = requests.post(OLLAMA_URL, json={
        "model": OLLAMA_MODEL,
        "prompt": prompt,
        "stream": False
    })

    if res.status_code != 200:
        logger.error(f"Ollama failed: {res.text}")
        raise HTTPException(status_code=502, detail="LLM response failed")

    return {
        "chapter": chapter_number,
        "book": book_title,
        "question": question,
        "answer": res.json().get("response")
    }
