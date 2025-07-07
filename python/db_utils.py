# # db_utils.py

import mysql.connector
from datetime import datetime

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

              # Step 6: Insert message with current timestamp
            now = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
            cursor.execute("""
                INSERT INTO messages (user_id, agent_id, message_id, parameter_inputs, sender, topic, created_at, updated_at)
                VALUES (%s, %s, %s, %s, %s, %s, %s, %s)
            """, (user_id, agent_id, message_id, parameter_inputs, sender, topic, now, now))

            db.commit()
            return message_id  # Return for reference if needed

    finally:
        db.close()

def load_messages_by_agent_and_user(agent_id, user_id, limit=None, order='asc'):
    """
    Load all messages for a given user and agent (i.e., all messages from a conversation history).
    
    Args:
        agent_id (int): The ID of the agent.
        user_id (int): The ID of the user.
        limit (int, optional): Limit number of messages.
        order (str, optional): 'asc' or 'desc' for chronological order.
        
    Returns:
        list of dict: Message records.
    """
    try:
        db = mysql.connector.connect(
            host="127.0.0.1",
            user="root",
            password="",
            database="ck_agent",
        )
        with db.cursor(dictionary=True) as cursor:
            query = """
                SELECT 
                    id, user_id, agent_id, message_id, parameter_inputs, sender, topic, created_at, updated_at
                FROM 
                    messages
                WHERE 
                    user_id = %s AND agent_id = %s
                ORDER BY 
                    created_at {}
            """.format("ASC" if order.lower() == "asc" else "DESC")

            if limit:
                query += " LIMIT %s"
                cursor.execute(query, (user_id, agent_id, limit))
            else:
                cursor.execute(query, (user_id, agent_id))

            return cursor.fetchall()
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