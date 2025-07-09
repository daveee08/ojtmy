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
    cursor.execute(
        """
        INSERT INTO messages (user_id, agent_id, message_id, parameter_inputs, sender, topic)
        VALUES (%s, %s, %s, %s, %s, %s)
        """,
        (user_id, agent_id, session_id, parameter_inputs_id, sender, topic)
    )

def create_session_and_parameter_inputs(user_id, agent_id, scope_vars, human_topic, ai_output):
    input_data = {key: scope_vars.get(key, "").strip() for key in INPUT_KEYS}
    available_data = {k: v for k, v in input_data.items() if v}
    combined_input = ",".join(available_data.values())

    db = get_db_connection()
    cursor = db.cursor()

    try:
            # Create a new session
            cursor.execute("INSERT INTO sessions () VALUES ()")
            session_id = cursor.lastrowid

            # Get parameter_reference ID
            cursor.execute("SELECT id FROM parameter_reference WHERE agent_id = %s LIMIT 1", (agent_id,))
            param = cursor.fetchone()
            if not param:
                raise Exception("No parameter_reference found for this agent.")
            parameter_id = param[0]

            # ✅ Insert into parameter_inputs, now includes message_id (aka session_id)
            cursor.execute(
                """
                INSERT INTO parameter_inputs (input, parameter_id, agent_id, message_id)
                VALUES (%s, %s, %s, %s)
                """,
                (combined_input, parameter_id, agent_id, session_id)
            )
            parameter_inputs_id = cursor.lastrowid

            # Insert messages
            insert_message(cursor, user_id, agent_id, session_id, parameter_inputs_id, "human", human_topic)
            insert_message(cursor, user_id, agent_id, session_id, parameter_inputs_id, "ai", ai_output)

            db.commit()
            return session_id
    finally:
        db.close()
