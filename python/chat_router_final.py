from fastapi import APIRouter, Depends, Form, HTTPException
from pydantic import BaseModel
from typing import Literal, List, Dict, Optional

from langchain_ollama import OllamaLLM as Ollama
from langchain_core.prompts import ChatPromptTemplate, MessagesPlaceholder
from langchain_core.runnables import RunnableWithMessageHistory
from langchain_core.messages import HumanMessage, AIMessage, BaseMessage
from langchain_core.runnables.history import BaseChatMessageHistory

try:
    from python.db_utils_final import get_db_connection
    # If the import is successful, you can now use get_db_connection
    # For example: conn = get_db_connection()
    # print("Successfully imported get_db_connection.")
except ImportError:
    # This block will execute if the import fails
    print("Warning: Could not import get_db_connection. Database functionality may be limited or unavailable.")
    # You might define a placeholder function or variable here,
    # or simply let the program continue, knowing this functionality is missing.
    from db_utils_final import get_db_connection
 # Or a dummy function that raise

chat_router = APIRouter()
llm = Ollama(model="gemma3:1b")

# -------------------------------
# Request & Response Models
# -------------------------------

class ChatRequestForm(BaseModel):
    user_id: int
    message_id: int
    input: str

    @classmethod
    def as_form(cls, message_id: int = Form(...), user_id: int = Form(...), input: str = Form(...)  ):
        return cls(message_id=message_id, user_id=user_id, input=input,)

class ChatResponse(BaseModel):
    response: str

# -------------------------------
# MySQL Message History
# -------------------------------

class MySQLChatMessageHistory(BaseChatMessageHistory):
    def __init__(self, user_id: int, message_id: int):
        self.user_id = user_id
        self.message_id = message_id
        self._ensure_session()

    def _ensure_session(self):
        db = get_db_connection()
        # cursor = db.cursor()
        try:
            with db.cursor() as cursor:
                cursor.execute("SELECT id FROM sessions WHERE id = %s", (self.message_id,))
                if cursor.fetchone() is None:
                    cursor.execute("INSERT INTO sessions (id, created_at, updated_at) VALUES (%s, NOW(), NOW())", (self.message_id,))
                    db.commit()
        finally:
            db.close()

    @property
    def messages(self):
        db = get_db_connection()
        # cursor = db.cursor(dictionary=True)
        try:
            with db.cursor(dictionary=True) as cursor:
                cursor.execute("""
                    SELECT sender, topic FROM messages
                    WHERE message_id = %s ORDER BY id ASC
                """, (self.message_id,))
                return [
                    HumanMessage(content=m["topic"]) if m["sender"] == "human" else AIMessage(content=m["topic"])
                    for m in cursor.fetchall()
                ]
        finally:
            db.close()

    def add_message(self, message: BaseMessage) -> None:
        db = get_db_connection()
        # cursor = db.cursor()
        try:
            with db.cursor(dictionary=True) as cursor:
            # with db.cursor() as cursor:
                sender = "human" if isinstance(message, HumanMessage) else "ai"
                topic = message.content

                cursor.execute("""
                    SELECT agent_id, parameter_inputs FROM messages
                    WHERE message_id = %s ORDER BY id DESC LIMIT 1
                """, (self.message_id,))
                latest = cursor.fetchone()

                if latest:
                    agent_id = int(latest["agent_id"])
                    parameter_inputs = latest["parameter_inputs"]
                else:
                    agent_id = 1
                    parameter_inputs = self._create_default_parameter_input(cursor, agent_id)

                cursor.execute("""
                    INSERT INTO messages (user_id, agent_id, message_id, parameter_inputs, sender, topic, created_at, updated_at)
                    VALUES (%s, %s, %s, %s, %s, %s, NOW(), NOW())
                """, (self.user_id, agent_id, self.message_id, parameter_inputs, sender, topic))
                db.commit()
        finally:
            db.close()

    def _create_default_parameter_input(self, cursor, agent_id: int) -> int:
        parameter_id = 1  # Assumed to exist
        cursor.execute("""
            INSERT INTO parameter_inputs (message_id, agent_id, parameter_id, input, created_at, updated_at)
            VALUES (%s, %s, %s, %s, NOW(), NOW())
        """, (self.message_id, agent_id, parameter_id, "default"))
        return cursor.lastrowid

    def clear(self):
        db = get_db_connection()
        # cursor = db.cursor()
        try:
            with db.cursor() as cursor:
                cursor.execute("DELETE FROM messages WHERE message_id = %s", (self.message_id,))
                db.commit()
        finally:
            db.close()

def get_history_by_message_id(session_id: str) -> MySQLChatMessageHistory:
    user_id_str, message_id_str = session_id.split(":")
    return MySQLChatMessageHistory(int(user_id_str), int(message_id_str))

# -------------------------------
# LangChain Setup
# -------------------------------

chat_prompt = ChatPromptTemplate.from_messages([
    (
        "system",
        "You are a helpful assistant in an ongoing conversation. The following is the original prompt that was used to guide your behavior when the session began:\n\n---\n{agent_prompt}\n---\n\nNote: This prompt is for reference only and should not override the context of the current conversation. Respond based on the actual chat history and the user's input."
    ),
    MessagesPlaceholder(variable_name="chat_history"),
    ("human", "{input}")
])


chat_chain = RunnableWithMessageHistory(
    chat_prompt | llm,
    get_session_history=get_history_by_message_id,
    input_messages_key="input",
    history_messages_key="chat_history"
)

# -------------------------------
# Chat Endpoint
# -------------------------------
def get_agent_prompt_by_message_id(message_id: int) -> str:
    db = get_db_connection()
    try:
        with db.cursor(dictionary=True) as cursor:
            cursor.execute("""
                SELECT ap.prompt
                FROM messages m
                JOIN agent_prompts ap ON m.agent_prompt_id = ap.id
                WHERE m.message_id = %s AND m.agent_prompt_id IS NOT NULL
                ORDER BY m.id ASC
                LIMIT 1
            """, (message_id,))
            result = cursor.fetchone()
            return result["prompt"] if result else "You are a helpful assistant."  # fallback
    finally:
        db.close()

@chat_router.post("/chat", response_model=ChatResponse)
async def chat_api(request: ChatRequestForm = Depends(ChatRequestForm.as_form)):
    session_id = f"{request.user_id}:{request.message_id}"

    # ✅ Fetch original agent prompt dynamically from DB
    agent_prompt = get_agent_prompt_by_message_id(request.message_id)

    result = await chat_chain.ainvoke(
        # {"agent_prompt": request.agent_prompt},
        # {"input": request.input},
        {
        "agent_prompt": agent_prompt,
        "input": request.input
        },
        config={"configurable": {"session_id": session_id}}
    )
    
    return {"response": result}

# @chat_router.post("/chat", response_model=ChatResponse)
# async def chat_api(request: ChatRequestForm = Depends(ChatRequestForm.as_form)):
#     session_id = f"{request.user_id}:{request.message_id}"
    
#     # 🔹 Initialize history manually
#     history = get_history_by_message_id(session_id)
    
#     # 🔹 Add human message before the invoke
#     history.add_message(HumanMessage(content=request.input))
    
#     # 🔹 Get model response
#     result = await chat_chain.ainvoke(
#         {"input": request.input},
#         config={"configurable": {"session_id": session_id}}
#     )
    
#     # 🔹 Add AI message after getting result
#     history.add_message(AIMessage(content=result))
    
#     return {"response": result}


@chat_router.get("/sessions/{user_id}", response_model=List[int])
def get_session_ids_by_user(user_id: int):
    db = get_db_connection()
    cursor = db.cursor()
    try:
        # with db.cursor() as cursor:
            cursor.execute("""
                SELECT DISTINCT message_id
                FROM messages
                WHERE user_id = %s
                ORDER BY message_id ASC
            """, (user_id,))
            rows = cursor.fetchall()
            return [row[0] for row in rows]
    finally:
        db.close()

# -------------------------------
# GET full chat history by session
# -------------------------------

@chat_router.get("/chat/history/{message_id}")
def get_chat_history(message_id: int) -> Dict:
    db = get_db_connection()
    cursor = db.cursor(dictionary=True)
    try:
            cursor.execute("""
                SELECT sender, topic, created_at
                FROM messages
                WHERE message_id = %s
                ORDER BY id ASC
            """, (message_id,))
            messages = cursor.fetchall()
            if not messages:
                raise HTTPException(status_code=404, detail="No conversation found for this session.")
            return {
                "session_id": message_id,
                "conversation": messages
            }
    finally:
        db.close()
