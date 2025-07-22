import mysql.connector
import re
from typing import Dict, Any, Optional
import traceback # Import traceback for detailed error logging

INPUT_KEYS = ["grade_level", "learning_speed", "custom_instructions", "text_type", "text_length"]

def get_db_connection():
    """Establishes and returns a database connection."""
    try:
        return mysql.connector.connect(
            host="127.0.0.1",
            user="root",
            password="",
            database="ck_agent"
        )
    except mysql.connector.Error as err:
        print(f"Error connecting to database: {err}")
        traceback.print_exc()
        raise

def insert_message(cursor, user_id, agent_id, session_id, parameter_inputs_id, sender, topic, agent_prompt_id):
    """Inserts a message (human or AI) into the messages table."""
    try:
        cursor.execute(
            """
            INSERT INTO messages (user_id, agent_id, message_id, parameter_inputs, sender, topic, created_at, updated_at, agent_prompt_id)
            VALUES (%s, %s, %s, %s, %s, %s, NOW(), NOW(), %s)
            """,
            (user_id, agent_id, session_id, parameter_inputs_id, sender, topic, agent_prompt_id)
        )
    except mysql.connector.Error as err:
        print(f"Error inserting message: {err}")
        traceback.print_exc()
        raise

def insert_title(session_id: int, text: str):
    """Generates and inserts a title for a conversation session."""
    from langchain_ollama import OllamaLLM
    from langchain_core.prompts import ChatPromptTemplate

    db = get_db_connection()
    cursor = db.cursor()

    try:
        # Title generation model and prompt
        model = OllamaLLM(model="gemma:2b")
        prompt_template = ChatPromptTemplate.from_template("""
        You are an expert title generator. Your job is to create a concise, relevant, and engaging title based only on the provided conversation.

        Guidelines:
        - Concise: Ideally 3–7 words.
        - Relevant: Reflect the main topic or goal.
        - Engaging: Make it interesting and informative.
        - Unique: Avoid generic titles like "Chat with user" or "Conversation summary".

        Only return the title. Do not include explanations, formatting, or extra text.

        Conversation:
        {text}
        """)
        chain = prompt_template | model

        # Generate the title
        result = chain.invoke({"text": text})
        
        title = result.strip()

        # Clean the title from markdown and extra characters
        title = re.sub(r"\*\*(.*?)\*\*", r"\1", title) # Remove bold markdown
        title = re.sub(r"\*(.*?)\*", r"\1", title)     # Remove italic markdown
        title = re.sub(r"^\s*[\*\-]\s*", "", title, flags=re.MULTILINE) # Remove leading bullet markers
        title = title.strip('"\'') # Remove leading/trailing quotation marks

        # Insert into conversation_title table
        cursor.execute(
            """
            INSERT INTO conversation_title (message_id, title, created_at, updated_at)
            VALUES (%s, %s, NOW(), NOW())
            """,
            (session_id, title)
        )

        db.commit()
        return title

    except Exception as e:
        db.rollback()
        print(f"Error inserting title: {e}")
        traceback.print_exc()
        # Do not re-raise, title generation is secondary
        return f"Failed to generate title: {e}" # Return an error string for title
    finally:
        cursor.close()
        db.close()


def create_session_and_parameter_inputs(user_id: int, agent_id: int, scope_vars: Dict, human_topic: str, ai_output: str, agent_prompt: str) -> int:
    """
    Creates a new session, inserts parameter inputs, and logs human and AI messages.
    Returns the new session_id (which is the message_id for the conversation).
    """
    db = get_db_connection()
    cursor = db.cursor()

    try:
        # 🔹 Step 1: Dynamically get parameter keys for this agent
        cursor.execute("SELECT parameter FROM agent_parameters WHERE agent_id = %s", (agent_id,))
        param_rows = cursor.fetchall()
        input_keys = [row[0] for row in param_rows]  # Extract just the parameter names

        print(f"Agent {agent_id} input keys: {input_keys}")

        # 🔹 Step 2: Build input_data dynamically from scope_vars
        input_data = {key: str(scope_vars.get(key, "")).strip() for key in input_keys}
        available_data = {k: v for k, v in input_data.items() if v}
        combined_input = "; ".join(f"{k}={v}" for k, v in available_data.items())
        print(f"Combined input for parameter_inputs: {combined_input}")

        # 🔹 Step 3: Insert session (this creates the unique session_id/message_id)
        cursor.execute("INSERT INTO sessions () VALUES ()")
        session_id = cursor.lastrowid
        print(f"New session_id created: {session_id}")

        # 🔹 Step 4: Get parameter_reference ID
        cursor.execute("SELECT id FROM parameter_reference WHERE agent_id = %s LIMIT 1", (agent_id,))
        param = cursor.fetchone()
        if not param:
            raise Exception(f"No parameter_reference found for agent_id {agent_id}. Please ensure agent_parameters and parameter_reference tables are correctly set up for this agent.")
        parameter_id = param[0]
        print(f"Parameter ID for agent {agent_id}: {parameter_id}")

        # 🔹 Step 4.5: Insert original agent prompt for referencing the original agent prompt
        cursor.execute(
            """
            INSERT INTO agent_prompts (agent_id, prompt, created_at, updated_at)
            VALUES (%s, %s, NOW(), NOW())
            """,
            (agent_id, agent_prompt)
        )
        agent_prompt_id = cursor.lastrowid
        print(f"Agent prompt ID created: {agent_prompt_id}")

        # 🔹 Step 5: Insert into parameter_inputs with message_id (aka session_id)
        cursor.execute(
            """
            INSERT INTO parameter_inputs (input, parameter_id, agent_id, message_id)
            VALUES (%s, %s, %s, %s)
            """,
            (combined_input, parameter_id, agent_id, session_id)
        )
        parameter_inputs_id = cursor.lastrowid
        print(f"Parameter inputs ID created: {parameter_inputs_id}")

        # 🔹 Step 6: Insert both human and AI messages
        insert_message(cursor, user_id, agent_id, session_id, parameter_inputs_id, "human", human_topic, agent_prompt_id)
        insert_message(cursor, user_id, agent_id, session_id, parameter_inputs_id, "ai", ai_output, agent_prompt_id)
        
        db.commit() # Commit all changes to the database

        # Then generate and insert title
        initial_convo = (human_topic + "\n" + ai_output)
        try:
            insert_title(session_id=session_id, text=initial_convo)
        except Exception as e:
            print(f"Failed to generate title for session {session_id}: {e}") # Log, but don't prevent return
            traceback.print_exc()

        return session_id

    except Exception as e:
        db.rollback() # Rollback changes if any error occurs
        print(f"Error in create_session_and_parameter_inputs: {e}")
        traceback.print_exc()
        raise # Re-raise the exception to be caught by FastAPI
    finally:
        cursor.close()
        db.close()

# --- NEW FUNCTION: get_session_details ---
def get_session_details(message_id: str) -> Optional[Dict[str, Any]]:
    """
    Retrieves the details of a specific session (human_topic, ai_output, scope_vars)
    based on the message_id (which is the session_id).
    This function queries the 'messages' and 'parameter_inputs' tables.
    """
    db = get_db_connection()
    cursor = db.cursor(dictionary=True) # Use dictionary=True to get results as dicts

    try:
        # Fetch human_topic and ai_output from messages table
        # We need to distinguish between human and AI messages for this session_id
        cursor.execute(
            """
            SELECT topic, sender FROM messages
            WHERE message_id = %s AND (sender = 'human' OR sender = 'ai')
            ORDER BY created_at ASC
            """,
            (message_id,)
        )
        messages = cursor.fetchall()

        human_topic = None
        ai_output = None
        for msg in messages:
            if msg['sender'] == 'human':
                human_topic = msg['topic']
            elif msg['sender'] == 'ai':
                ai_output = msg['topic'] # 'topic' column stores the message content for both

        # Fetch scope_vars from parameter_inputs table
        cursor.execute(
            """
            SELECT input FROM parameter_inputs
            WHERE message_id = %s
            LIMIT 1
            """,
            (message_id,)
        )
        param_input_row = cursor.fetchone()

        scope_vars = {}
        if param_input_row and param_input_row['input']:
            # Parse the 'input' string (e.g., "grade_level=1st Grade; topic=Heartbreak")
            input_str = param_input_row['input']
            pairs = input_str.split(';')
            for pair in pairs:
                if '=' in pair:
                    key, value = pair.split('=', 1)
                    scope_vars[key.strip()] = value.strip()

        if human_topic is not None and ai_output is not None:
            return {
                "human_topic": human_topic,
                "ai_output": ai_output,
                "scope_vars": scope_vars
            }
        return None # Return None if essential data is missing

    except Exception as e:
        print(f"Error in get_session_details for message_id {message_id}: {e}")
        traceback.print_exc()
        return None
    finally:
        cursor.close()
        db.close()

