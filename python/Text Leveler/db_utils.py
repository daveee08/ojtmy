import mysql.connector
from langchain_core.messages import HumanMessage, AIMessage, BaseMessage

# Input keys for scope_vars
INPUT_KEYS = ["grade_level", "learning_speed", "custom_instructions", "text_type", "text_length"]

# -----------------------
# DB Connection
# -----------------------
def get_db_connection():
    return mysql.connector.connect(
        host="127.0.0.1",
        user="root",
        password="",
        database="ck_agent"
    )

def add_message(session_id: int, message: BaseMessage, user_id: int):
    sender = "human" if isinstance(message, HumanMessage) else "ai"
    topic = message.content

    conn = get_db_connection()
    cursor = conn.cursor()
    query = "INSERT INTO messages (session_id, sender, topic, user_id) VALUES (%s, %s, %s, %s)"
    cursor.execute(query, (session_id, sender, topic, user_id))
    conn.commit()
    cursor.close()
    conn.close()

# -----------------------
# Clear all messages in a session
# -----------------------
def clear_messages_by_session_id(session_id: int):
    delete_messages(session_id)

def get_all_session_ids():
    db = get_db_connection()
    try:
        with db.cursor(dictionary=True) as cursor:
            cursor.execute("SELECT DISTINCT message_id FROM messages ORDER BY message_id DESC")
            sessions = cursor.fetchall()
            return [row["message_id"] for row in sessions]
    finally:
        db.close()

def get_messages_by_session_id(session_id: int):
    db = get_db_connection()
    try:
        with db.cursor(dictionary=True) as cursor:
            cursor.execute(
                "SELECT sender, topic FROM messages WHERE message_id = %s ORDER BY id ASC",
                (session_id,)
            )
            return cursor.fetchall()
    finally:
        db.close()

# -----------------------
# Insert a single message
# -----------------------
def insert_message(cursor, user_id, agent_id, message_id, parameter_inputs, sender, topic):
    cursor.execute(
        """
        INSERT INTO messages (user_id, agent_id, message_id, parameter_inputs, sender, topic)
        VALUES (%s, %s, %s, %s, %s, %s)
        """,
        (user_id, agent_id, message_id, parameter_inputs, sender, topic)
    )

# -----------------------
# Delete all messages by message_id (session)
# -----------------------
def delete_messages(message_id: int):
    db = get_db_connection()
    try:
        with db.cursor() as cursor:
            cursor.execute("DELETE FROM messages WHERE message_id = %s", (message_id,))
            db.commit()
    finally:
        db.close()

# -----------------------
# Fetch all messages by message_id (session)
# -----------------------
def fetch_messages(session_id: int):
    db = get_db_connection()
    try:
        with db.cursor(dictionary=True) as cursor:
            cursor.execute("""
                SELECT sender, topic, created_at
                FROM messages
                WHERE message_id = %s
                ORDER BY created_at ASC
            """, (session_id,))
            return cursor.fetchall()
    finally:
        db.close()
        
# -----------------------
# Create session, param_input, and insert both messages
# -----------------------
def create_session_and_parameter_inputs(user_id, agent_id, scope_vars, human_topic, ai_output):
    input_data = {key: scope_vars.get(key, "").strip() for key in INPUT_KEYS}
    available_data = {k: v for k, v in input_data.items() if v}
    combined_input = ",".join(available_data.values())

    db = get_db_connection()

    try:
        with db.cursor() as cursor:
            # Create new session
            cursor.execute("INSERT INTO sessions () VALUES ()")
            message_id = cursor.lastrowid

            # Get parameter_reference.id for the given agent
            cursor.execute("SELECT id FROM parameter_reference WHERE agent_id = %s LIMIT 1", (agent_id,))
            param = cursor.fetchone()

            if not param:
                raise Exception("No parameter_reference found for this agent.")

            parameter_id = param[0]

            # Insert parameter_inputs
            cursor.execute(
                "INSERT INTO parameter_inputs (input, parameter_id, agent_id) VALUES (%s, %s, %s)",
                (combined_input, parameter_id, agent_id)
            )
            parameter_inputs = cursor.lastrowid

            # Insert both messages
            insert_message(cursor, user_id, agent_id, message_id, parameter_inputs, "human", human_topic)
            insert_message(cursor, user_id, agent_id, message_id, parameter_inputs, "ai", ai_output)

            db.commit()
            return message_id  # Optional: return parameter_inputs if needed
    finally:
        db.close()