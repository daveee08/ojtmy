import mysql.connector

INPUT_KEYS = ["grade_level", "learning_speed", "custom_instructions", "text_type", "text_length"]

def get_db_connection():
    return mysql.connector.connect(
        host="127.0.0.1",
        user="root",
        password="",
        database="ck_agent"
    )

def insert_message(cursor, user_id, agent_id, session_id, parameter_inputs_id, sender, topic):
    cursor.execute("""
        INSERT INTO messages (user_id, agent_id, message_id, parameter_inputs, sender, topic)
        VALUES (%s, %s, %s, %s, %s, %s)
    """, (user_id, agent_id, session_id, parameter_inputs_id, sender, topic))

def create_session_and_parameter_inputs(user_id, agent_id, scope_vars, human_topic, ai_output):
    combined_input = ",".join(
        v.strip() for k, v in scope_vars.items() if k in INPUT_KEYS and v.strip()
    )

    db = get_db_connection()
    try:
        with db.cursor() as cursor:
            # Create session
            cursor.execute("INSERT INTO sessions () VALUES ()")
            session_id = cursor.lastrowid

            # Get parameter_reference ID
            cursor.execute("SELECT id FROM parameter_reference WHERE agent_id = %s LIMIT 1", (agent_id,))
            param = cursor.fetchone()
            if not param:
                raise ValueError(f"No parameter_reference found for agent_id={agent_id}")
            parameter_id = param[0]

            # Insert parameter_inputs
            cursor.execute("""
                INSERT INTO parameter_inputs (input, parameter_id, agent_id, message_id)
                VALUES (%s, %s, %s, %s)
            """, (combined_input, parameter_id, agent_id, session_id))
            parameter_inputs_id = cursor.lastrowid

            # Insert both human and AI messages
            for sender, topic in [("human", human_topic), ("ai", ai_output)]:
                insert_message(cursor, user_id, agent_id, session_id, parameter_inputs_id, sender, topic)

            db.commit()
            return session_id
    finally:
        db.close()
