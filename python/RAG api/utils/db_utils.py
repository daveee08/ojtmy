import mysql

DB = dict(host="127.0.0.1", user="root", password="", database="ck_agent")

# --- DB Connection ---
def get_connection():
    return mysql.connector.connect(**DB)

# --- Generic Insert Function ---
def insert_and_return_id(query: str, params: tuple):
    conn = get_connection()
    cursor = conn.cursor()
    cursor.execute(query, params)
    conn.commit()
    new_id = cursor.lastrowid
    cursor.close()
    conn.close()
    return new_id

def insert_book(title, source, original_filename, faiss_path, grade_level, desc):
    return insert_and_return_id("""
        INSERT INTO books (title, source, original_filename, faiss_index_path, description, grade_level)
        VALUES (%s, %s, %s, %s, %s, %s)
    """, (title, source, original_filename, faiss_path, desc, grade_level))

def insert_chapter(book_id, chapter_number, chapter_title, start_page=None, end_page=None):
    return insert_and_return_id("""
        INSERT INTO chapters (book_id, chapter_number, chapter_title, start_page, end_page)
        VALUES (%s, %s, %s, %s, %s)
    """, (book_id, chapter_number, chapter_title, start_page, end_page))

def insert_chunk(book_id, chapter_id, global_faiss_id, text):
    return insert_and_return_id("""
        INSERT INTO chunks (book_id, chapter_id, global_faiss_id, text)
        VALUES (%s, %s, %s, %s)
    """, (book_id, chapter_id, global_faiss_id, text))