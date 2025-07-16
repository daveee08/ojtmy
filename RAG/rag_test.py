import mysql.connector
from sentence_transformers import SentenceTransformer
import numpy as np
import requests
import json  # Required for converting to JSON string


# === CONFIG ===
DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'ck_agent'
}
EMBED_MODEL_NAME = 'all-MiniLM-L6-v2'  # 384 dimensions

# === Load Embedder ===
model = SentenceTransformer(EMBED_MODEL_NAME)

def embed_text(text: str) -> list:
    return model.encode(text).tolist()

# import json

def insert_chunk(book_id, chapter_number, chapter_title, content):
    embedding = embed_text(content)  # list of floats
    embedding_json = json.dumps([round(x, 6) for x in embedding])

    conn = mysql.connector.connect(**DB_CONFIG)
    cursor = conn.cursor()

    # Use VEC_FromText inline in SQL
    query = f"""
        INSERT INTO book_chunks
        (book_id, chapter_number, chapter_title, content, embedding)
        VALUES (%s, %s, %s, %s,
                VEC_FromText(%s)
        )
    """

    cursor.execute(query, (book_id, chapter_number, chapter_title, content, embedding_json))
    conn.commit()
    conn.close()

    print(f"✅ Inserted Chapter {chapter_number} - {chapter_title}")





def search_similar_chunks(book_id, chapter_number, query_text):
    query_vector = embed_text(query_text)
    embedding_json = json.dumps([round(x, 6) for x in query_vector])

    conn = mysql.connector.connect(**DB_CONFIG)
    cursor = conn.cursor(dictionary=True)

    sql = f"""
        SELECT *, VEC_DISTANCE_EUCLIDEAN(
            embedding,
            VEC_FromText(%s)
        ) AS similarity
        FROM book_chunks
        WHERE book_id = %s AND chapter_number = %s
        ORDER BY similarity ASC
        LIMIT 5
    """

    cursor.execute(sql, (embedding_json, book_id, chapter_number))
    results = cursor.fetchall()
    conn.close()
    return results




def query_gemma_rag(prompt, retrieved_chunks):
    context = "\n\n".join(chunk['content'] for chunk in retrieved_chunks)

    payload = {
        "model": "gemma3:1b",
        "prompt": f"Context:\n{context}\n\nQuestion:\n{prompt}\n\nAnswer:",
        "stream": False
    }

    response = requests.post("http://localhost:11434/api/generate", json=payload)
    return response.json().get("response")

if __name__ == "__main__":
    # Step 1: Insert sample chapters
    # insert_chunk(
    #     book_id=1,
    #     chapter_number=1,
    #     chapter_title="Introduction to Philosophy",
    #     content="""
    #     Philosophy is the study of fundamental questions about existence, knowledge, values, reason, mind, and language.
    #     It has been practiced for millennia in various cultures and traditions. Philosophers seek to understand the world and our place in it.
    #     """
    # )

    # insert_chunk(
    #     book_id=1,
    #     chapter_number=1,
    #     chapter_title="Introduction to Philosophy",
    #     content="""
    #     Ancient Greek philosophy laid the groundwork for Western thought. Socrates, Plato, and Aristotle explored ethics, logic, and metaphysics.
    #     These thinkers shaped the way we ask and answer questions about reality and morality.
    #     """
    # )

    # Step 2: Search for most relevant chunks in Chapter 1
    user_question = "Who are the key figures in ancient Greek philosophy?"
    results = search_similar_chunks(book_id=1, chapter_number=2, query_text=user_question)

    print("\n🔎 Top Retrieved Chunks:")
    for idx, chunk in enumerate(results):
        print(f"{idx+1}. {chunk['chapter_title']} - Similarity: {chunk['similarity']:.4f}")
        print(chunk['content'][:150] + "...\n")

    # Step 3: Ask Gemma for the answer using the retrieved context
    answer = query_gemma_rag(user_question, results)
    print("\n🤖 Gemma's Answer:\n", answer)
