import mysql.connector
from datetime import datetime
from langchain_core.prompts import ChatPromptTemplate
from langchain_community.llms import Ollama
from langchain_core.runnables import Runnable, RunnableLambda

# ---------------------
# DB FUNCTIONS
# ---------------------

def get_db_connection():
    return mysql.connector.connect(
        host="127.0.0.1",
        user="root",
        password="",
        database="ck_agent"
    )

def insert_session(user_id="terminal_user"):
    conn = get_db_connection()
    cursor = conn.cursor()
    cursor.execute("INSERT INTO sessions () VALUES ()")
    conn.commit()
    session_id = cursor.lastrowid
    cursor.close()
    conn.close()
    return session_id

def insert_message(session_id, sender, message):
    conn = get_db_connection()
    cursor = conn.cursor()
    cursor.execute(
        "INSERT INTO chat_messages (message_id, sender, topic) VALUES (%s, %s, %s)",
        (session_id, sender, message)
    )
    conn.commit()
    cursor.close()
    conn.close()

def get_chat_history(session_id):
    conn = get_db_connection()
    cursor = conn.cursor()
    cursor.execute(
        "SELECT sender, topic FROM chat_messages WHERE message_id = %s ORDER BY id ASC",
        (session_id,)
    )
    history = cursor.fetchall()
    cursor.close()
    conn.close()
    return history

# ---------------------
# LANGCHAIN SETUP
# ---------------------

llm = Ollama(model="llama3")
base_prompt = ChatPromptTemplate.from_template(
    """You are a helpful assistant. Use the chat history and user input to respond.

Chat History:
{history}

User: {question}
"""
)

# Define a Runnable that fetches history and formats input
def build_input_with_history(data):
    session_id = data["session_id"]
    user_input = data["question"]
    history = get_chat_history(session_id)
    history_str = "\n".join(f"{sender.capitalize()}: {msg}" for sender, msg in history)
    return {"history": history_str, "question": user_input}

with_history: Runnable = RunnableLambda(build_input_with_history)
chain: Runnable = with_history | base_prompt | llm

# ---------------------
# MAIN TERMINAL CHAT LOOP
# ---------------------

if __name__ == "__main__":
    print("🧠 LangChain DB-Aware Terminal Chat (type 'exit' to quit)\n")
    session_id = insert_session()

    while True:
        user_input = input("You: ").strip()
        if user_input.lower() in ("exit", "quit"):
            print("👋 Goodbye!")
            break

        insert_message(session_id, "human", user_input)

        try:
            response = chain.invoke({
                "question": user_input,
                "session_id": session_id
            })
            print("Bot:", response)
            insert_message(session_id, "ai", response)
        except Exception as e:
            print("❌ Error during chat:", e)
