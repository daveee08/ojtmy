import mysql.connector

# All expected parameter names — used for ordering and filtering
INPUT_KEYS = ["grade_level", 
              "learning_speed", 
              "custom_instructions", 
              "text_type", 
              "text_length"]

def insert_session_and_message(user_id, agent_id, sender, topic, scope_vars):
    # Build input_data from declared variables in scope_vars
    input_data = {key: scope_vars.get(key, "").strip() for key in INPUT_KEYS}
    available_data = {k: v for k, v in input_data.items() if v}
    combined_input = ",".join(available_data.values())

    try:
        db = mysql.connector.connect(
            host="127.0.0.1",
            user="root",
            password="",
            database="ck_agent",
        )

        with db.cursor() as cursor:
            # Create session
            cursor.execute("INSERT INTO sessions () VALUES ()")
            message_id = cursor.lastrowid

            # Get parameter_reference.id for this agent
            cursor.execute("SELECT id FROM parameter_reference WHERE agent_id = %s LIMIT 1", (agent_id,))
            param = cursor.fetchone()

            if not param:
                raise Exception("No parameter_reference found for this agent.")

            parameter_id = param[0]

            # Insert parameter input
            cursor.execute(
                "INSERT INTO parameter_inputs (input, parameter_id, agent_id) VALUES (%s, %s, %s)",
                (combined_input, parameter_id, agent_id)
            )
            parameter_inputs = cursor.lastrowid

            # Insert message
            cursor.execute(
                """
                INSERT INTO messages (user_id, agent_id, message_id, parameter_inputs, sender, topic)
                VALUES (%s, %s, %s, %s, %s, %s)
                """,
                (user_id, agent_id, message_id, parameter_inputs, sender, topic)
            )

            db.commit()
            return message_id  # Return for reference if needed
      
    finally:
        db.close()