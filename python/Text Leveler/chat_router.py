from fastapi import APIRouter, Depends, Form, HTTPException
from pydantic import BaseModel
from typing import Literal, List, Dict

from langchain_ollama import OllamaLLM as Ollama
from langchain_core.prompts import ChatPromptTemplate, MessagesPlaceholder
from langchain_core.runnables import RunnableWithMessageHistory
from langchain_core.messages import HumanMessage, AIMessage, BaseMessage
from langchain_core.runnables.history import BaseChatMessageHistory

from db_utils import get_db_connection

chat_router = APIRouter()
llm = Ollama(model="llama3")

# -------------------------------
# Request & Response Models
# -------------------------------

class ChatRequestForm(BaseModel):
    user_id: int
    message_id: int
    input: str

    @classmethod
    def as_form(cls, message_id: int = Form(...), user_id: int = Form(...), input: str = Form(...)):
        return cls(message_id=message_id, user_id=user_id, input=input)

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
        try:
            with db.cursor(dictionary=True) as cursor:
                sender = "human" if isinstance(message, HumanMessage) else "ai"
                topic = message.content

                cursor.execute("""
                    SELECT agent_id, parameter_inputs FROM messages
                    WHERE message_id = %s ORDER BY id DESC LIMIT 1
                """, (self.message_id,))
                latest = cursor.fetchone()

                if latest:
                    agent_id = latest["agent_id"]
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
    ("system", "You are a helpful assistant."),
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

@chat_router.post("/chat", response_model=ChatResponse)
async def chat_api(request: ChatRequestForm = Depends(ChatRequestForm.as_form)):
    session_id = f"{request.user_id}:{request.message_id}"
    result = await chat_chain.ainvoke(
        {"input": request.input},
        config={"configurable": {"session_id": session_id}}
    )
    return {"response": result}

@chat_router.get("/sessions/{user_id}", response_model=List[int])
def get_session_ids_by_user(user_id: int):
    db = get_db_connection()
    try:
        with db.cursor() as cursor:
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
    try:
        with db.cursor(dictionary=True) as cursor:
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
