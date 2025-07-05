# db_utils.py

import mysql.connector
from datetime import datetime

# Adjust these to your environment
DB_CONFIG = {
    "host": "127.0.0.1",
    "user": "root",
    "password": "",
    "database": "ck_agent",
    "port": 3306,
}

def get_db_connection():
    return mysql.connector.connect(**DB_CONFIG)


def insert_message(agent_id: int, user_id: int, parameter_inputs: int, sender: str, topic: str, message_id: int = 0):
    """
    Inserts a message into the messages table. Returns the inserted message's ID.
    """
    conn = get_db_connection()
    cursor = conn.cursor()

    sql = """
        INSERT INTO messages (agent_id, user_id, parameter_inputs, sender, message_id, topic, created_at, updated_at)
        VALUES (%s, %s, %s, %s, %s, %s, %s, %s)
    """
    now = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
    cursor.execute(sql, (
        agent_id,
        user_id,
        parameter_inputs,
        sender,
        message_id,
        topic,
        now, now
    ))
    conn.commit()
    inserted_id = cursor.lastrowid

    cursor.close()
    conn.close()

    return inserted_id
