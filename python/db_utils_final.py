import mysql.connector

INPUT_KEYS = ["grade_level", "learning_speed", "custom_instructions", "text_type", "text_length"]

def get_db_connection():
    return mysql.connector.connect(
        host="127.0.0.1",
        user="root",
        password="",
        database="ck_agent"
    )

def insert_message(cursor, user_id, agent_id, session_id, parameter_inputs_id, sender, topic, agent_prompt_id):
    cursor.execute(
        """
        INSERT INTO messages (user_id, agent_id, message_id, parameter_inputs, sender, topic, created_at, updated_at, agent_prompt_id)
        VALUES (%s, %s, %s, %s, %s, %s, NOW(), NOW(), %s)
        """,
        (user_id, agent_id, session_id, parameter_inputs_id, sender, topic, agent_prompt_id)
    )
def create_session_and_parameter_inputs(user_id, agent_id, scope_vars, human_topic, ai_output, agent_prompt):
    db = get_db_connection()
    cursor = db.cursor()

    try:
        # 🔹 Step 1: Dynamically get parameter keys for this agent
        cursor.execute("SELECT parameter FROM agent_parameters WHERE agent_id = %s", (agent_id,))
        param_rows = cursor.fetchall()
        input_keys = [row[0] for row in param_rows]  # Extract just the parameter names

        print(input_keys)

        # 🔹 Step 2: Build input_data dynamically from scope_vars
        input_data = {key: scope_vars.get(key, "").strip() for key in input_keys}
        available_data = {k: v for k, v in input_data.items() if v}
        combined_input = "; ".join(f"{k}={v}" for k, v in available_data.items())

        # 🔹 Step 3: Insert session
        cursor.execute("INSERT INTO sessions () VALUES ()")
        session_id = cursor.lastrowid

        # 🔹 Step 4: Get parameter_reference ID
        cursor.execute("SELECT id FROM parameter_reference WHERE agent_id = %s LIMIT 1", (agent_id,))
        param = cursor.fetchone()
        if not param:
            raise Exception("No parameter_reference found for this agent.")
        parameter_id = param[0]

        #step 4.5 insert orignal agent prompt for referencing the original agent prompt

        cursor.execute(
            """
            INSERT INTO agent_prompts (agent_id, prompt, created_at, updated_at)
            VALUES (%s, %s, NOW(), NOW())
            """,
            (agent_id, agent_prompt)
        )

        agent_prompt_id = cursor.lastrowid

        # 🔹 Step 5: Insert into parameter_inputs with message_id (aka session_id)
        cursor.execute(
            """
            INSERT INTO parameter_inputs (input, parameter_id, agent_id, message_id)
            VALUES (%s, %s, %s, %s)
            """,
            (combined_input, parameter_id, agent_id, session_id)
        )
        parameter_inputs_id = cursor.lastrowid

        # 🔹 Step 6: Insert both human and AI messages
        insert_message(cursor, user_id, agent_id, session_id, parameter_inputs_id, "human", human_topic, agent_prompt_id)
        insert_message(cursor, user_id, agent_id, session_id, parameter_inputs_id, "ai", ai_output, agent_prompt_id)

        db.commit()
        return session_id

    finally:
        db.close()
