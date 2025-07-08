import mysql.connector
from contextlib import closing
from langchain_core.prompts import ChatPromptTemplate
from langchain_community.llms import Ollama
from langchain_core.runnables import RunnableWithMessageHistory
from langchain_core.messages import AIMessage, HumanMessage, BaseMessage
from langchain_core.runnables.history import BaseChatMessageHistory
from db_utils import get_db_connection, fetch_messages, delete_messages, create_session_and_parameter_inputs

# ---------------------
# DB HANDLER
# ---------------------

class ChatDB:
    @staticmethod
    def connect():
        return get_db_connection()

    @staticmethod
    def insert_session(user_id="terminal_user"):
        with ChatDB.connect() as conn, closing(conn.cursor()) as cur:
            cur.execute("INSERT INTO sessions () VALUES ()")
            conn.commit()
            return cur.lastrowid

    @staticmethod
    def insert_message(session_id, sender, message, user_id=1, agent_id=1):
        # Using db_utils structure - insert into messages table
        with ChatDB.connect() as conn, closing(conn.cursor()) as cur:
            # Insert a basic parameter_inputs record if it doesn't exist
            cur.execute("SELECT id FROM parameter_inputs WHERE agent_id = %s LIMIT 1", (agent_id,))
            param_input = cur.fetchone()
            
            if not param_input:
                # Create a basic parameter_inputs record
                cur.execute("SELECT id FROM parameter_reference WHERE agent_id = %s LIMIT 1", (agent_id,))
                param_ref = cur.fetchone()
                
                if param_ref:
                    parameter_id = param_ref[0]
                    cur.execute(
                        "INSERT INTO parameter_inputs (input, parameter_id, agent_id) VALUES (%s, %s, %s)",
                        ("", parameter_id, agent_id)
                    )
                    parameter_inputs = cur.lastrowid
                else:
                    parameter_inputs = 1  # Default fallback
            else:
                parameter_inputs = param_input[0]
            
            # Insert into messages table
            cur.execute(
                "INSERT INTO messages (user_id, agent_id, message_id, parameter_inputs, sender, topic) VALUES (%s, %s, %s, %s, %s, %s)",
                (user_id, agent_id, session_id, parameter_inputs, sender, message)
            )
            conn.commit()

    @staticmethod
    def fetch_messages(session_id):
        # Using db_utils fetch_messages function
        messages = fetch_messages(session_id)
        # Convert to the expected format (sender, topic)
        return [(msg['sender'], msg['topic']) for msg in messages]

    @staticmethod
    def clear_messages(session_id):
        # Using db_utils delete_messages function
        delete_messages(session_id)

# ---------------------
# MESSAGE MEMORY
# ---------------------

class MySQLChatMessageHistory(BaseChatMessageHistory):
    def __init__(self, session_id: str):
        self.session_id = session_id

    @property
    def messages(self) -> list[BaseMessage]:
        return [
            HumanMessage(content=msg) if sender == "human" else AIMessage(content=msg)
            for sender, msg in ChatDB.fetch_messages(self.session_id)
        ]

    def add_message(self, message: BaseMessage) -> None:
        sender = "human" if isinstance(message, HumanMessage) else "ai"
        ChatDB.insert_message(self.session_id, sender, message.content)

    def clear(self) -> None:
        ChatDB.clear_messages(self.session_id)

# ---------------------
# LANGCHAIN SETUP
# ---------------------

llm = Ollama(model="llama3")
prompt = ChatPromptTemplate.from_template(
    "You are a helpful assistant. Continue the conversation.\n\n{chat_history}\nUser: {input}"
)
chain = prompt | llm

chat_chain = RunnableWithMessageHistory(
    chain,
    lambda session_id: MySQLChatMessageHistory(session_id=session_id),
    input_messages_key="input",
    history_messages_key="chat_history"
)

# ---------------------
# TERMINAL CHAT
# ---------------------

if __name__ == "__main__":
    print("🧠 LangChain DB Chat (type 'exit' to quit)\n")
    session_id = str(ChatDB.insert_session())

    while True:
        user_input = input("You: ").strip()
        if user_input.lower() in {"exit", "quit"}:
            print("👋 Goodbye!")
            break
        try:
            response = chat_chain.invoke(
                {"input": user_input},
                config={"configurable": {"session_id": session_id}}
            )
            print("Bot:", response)
        except Exception as e:
            print("❌ Error:", e)