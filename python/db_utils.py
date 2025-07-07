# # db_utils.py

# import mysql.connector
# from datetime import datetime

# # Adjust these to your environment
# DB_CONFIG = {
#     "host": "127.0.0.1",
#     "user": "root",
#     "password": "",
#     "database": "ck_agent",
#     "port": 3306,
# }

# def get_db_connection():
#     return mysql.connector.connect(**DB_CONFIG)


# def insert_message(agent_id: int, user_id: int, parameter_inputs: int, sender: str, topic: str, message_id: int = 0):
#     """
#     Inserts a message into the messages table. Returns the inserted message's ID.
#     """
#     conn = get_db_connection()
#     cursor = conn.cursor()

#     sql = """
#         INSERT INTO messages (agent_id, user_id, parameter_inputs, sender, message_id, topic, created_at, updated_at)
#         VALUES (%s, %s, %s, %s, %s, %s, %s, %s)
#     """
#     now = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
#     cursor.execute(sql, (
#         agent_id,
#         user_id,
#         parameter_inputs,
#         sender,
#         message_id,
#         topic,
#         now, now
#     ))
#     conn.commit()
#     inserted_id = cursor.lastrowid

#     cursor.close()
#     conn.close()

#     return inserted_id


# def insert_session():
#     """
#     Inserts a new session into the sessions table using the given message_id.
#     Returns the inserted session's ID.
#     """
#     conn = get_db_connection()
#     cursor = conn.cursor()

#     sql = """
#         INSERT INTO sessions ()
#         VALUES ()
#     """
#     now = datetime.now().strftime('%Y-%m-%d %H:%M:%S')

#     try:
#         cursor.execute(sql, ())
#         conn.commit()
#         inserted_id = cursor.lastrowid
#     except mysql.connector.Error as e:
#         print(f"[DB ERROR] Failed to insert session with message_id={message_id}: {e}", flush=True)
#         inserted_id = None
#     finally:
#         cursor.close()
#         conn.close()

#     return inserted_id


# def insert_dynamic_parameter_input(input_data: dict, agent_id: int):
#     """
#     Dynamically inserts a parameter_input for the agent using its defined parameters.
#     input_data: dictionary of key-value input (e.g., {"language": "English", "mode": "step-by-step"})
#     agent_id: the agent performing the request

#     Returns the inserted parameter_inputs.id, or None on failure
#     """
#     conn = get_db_connection()
#     cursor = conn.cursor()

#     try:
#         print(f"[DEBUG] insert_dynamic_parameter_input: input={input_data}, agent_id={agent_id}", flush=True)
#         # Step 1: Get parameter names defined for this agent
#         cursor.execute("""
#             SELECT parameter FROM agent_parameters
#             WHERE agent_id = %s
#         """, (agent_id,))
#         param_keys = [row[0] for row in cursor.fetchall()]

#         print(f"[DEBUG] Matching parameters from agent_parameters table: {param_keys}", flush=True)

#         # Step 2: Filter input_data based on these parameters
#         filtered = {k: v for k, v in input_data.items() if k in param_keys}

#         print(f"[DEBUG] Filtered input: {filtered}", flush=True)

#         if not filtered:
#             print(f"[WARN] No matching parameters found in input for agent_id={agent_id}", flush=True)
#             return None

#         # Step 3: Format as "key=value" joined string
#         combined_input = "; ".join(f"{k}={v}" for k, v in filtered.items())

#         # Step 4: Insert parameter_input using parameter_reference (used for linking)
#         cursor.execute("""
#             SELECT id FROM parameter_reference WHERE agent_id = %s LIMIT 1
#         """, (agent_id,))
#         ref = cursor.fetchone()

#         if not ref:
#             print(f"[WARN] No parameter_reference found for agent_id={agent_id}", flush=True)
#             return None

#         parameter_reference_id = ref[0]

#         cursor.execute("""
#             INSERT INTO parameter_inputs (input, parameter_id, agent_id)
#             VALUES (%s, %s, %s)
#         """, (combined_input, parameter_reference_id, agent_id))
#         conn.commit()

#         return cursor.lastrowid

#     except Exception as e:
#         print(f"[ERROR] No parameter_reference found for agent_id={agent_id}", flush=True)
#         print(f"[ERROR] Failed to insert dynamic parameter_input: {e}", flush=True)
#         return None

#     finally:
#         cursor.close()
#         conn.close()

import mysql.connector

def insert_session_and_message(user_id, agent_id, sender, topic, scope_vars, message_id=None):
    try:
        db = mysql.connector.connect(
            host="127.0.0.1",
            user="root",
            password="",
            database="ck_agent",
        )
        with db.cursor() as cursor:
            # 🔹 Step 1: Dynamically get parameter keys for this agent
            
            cursor.execute("SELECT parameter FROM agent_parameters WHERE agent_id = %s", (agent_id,))
            param_rows = cursor.fetchall()
            print(param_rows)

            input_keys = [row[0] for row in param_rows]

            # 🔹 Step 2: Build input_data dynamically from scope_vars
            input_data = {key: scope_vars.get(key, "").strip() for key in input_keys}
            available_data = {k: v for k, v in input_data.items() if v}
            combined_input = "; ".join(f"{k}={v}" for k, v in available_data.items())

            # 🔹 Step 3: Insert session
            if message_id is None:
                cursor.execute("INSERT INTO sessions () VALUES ()")
                message_id = cursor.lastrowid
            else:
                # Optional: validate session exists
                cursor.execute("SELECT id FROM sessions WHERE id = %s", (message_id,))
                if not cursor.fetchone():
                    raise Exception(f"Provided message_id {message_id} does not exist in sessions table.")
            
            # 🔹 Step 4: Get parameter_reference.id
            cursor.execute("SELECT id FROM parameter_reference WHERE agent_id = %s LIMIT 1", (agent_id,))
            param = cursor.fetchone()
            if not param:
                raise Exception("No parameter_reference found for this agent.")
            parameter_id = param[0]

            # 🔹 Step 5: Insert parameter input
            cursor.execute(
                "INSERT INTO parameter_inputs (input, parameter_id, agent_id) VALUES (%s, %s, %s)",
                (combined_input, parameter_id, agent_id)
            )
            parameter_inputs = cursor.lastrowid

            # 🔹 Step 6: Insert message
            cursor.execute("""
                INSERT INTO messages (user_id, agent_id, message_id, parameter_inputs, sender, topic)
                VALUES (%s, %s, %s, %s, %s, %s)
            """, (user_id, agent_id, message_id, parameter_inputs, sender, topic))

            db.commit()
            return message_id  # Return for reference if needed

    finally:
        db.close()


# scope_vars = {
#             "target_language": "bisaya"
#         }

# print(insert_session_and_message(
#             user_id=1,
#             agent_id=16,
#             sender="human",
#             topic="Laravel is a web application framework with expressive",
#             scope_vars=scope_vars,
#         ))