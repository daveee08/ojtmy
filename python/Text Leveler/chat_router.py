from fastapi import APIRouter, Depends, Form, HTTPException
from pydantic import BaseModel, Field
from typing import List, Optional
from datetime import datetime

from langchain_ollama import OllamaLLM as Ollama
from langchain_core.prompts import ChatPromptTemplate, MessagesPlaceholder
from langchain_core.runnables import RunnableWithMessageHistory
from langchain_core.messages import HumanMessage, AIMessage, BaseMessage
from langchain_core.runnables.history import BaseChatMessageHistory

from db_utils import get_db_connection

chat_router = APIRouter()
llm = Ollama(model="llama3")

# -------------------------------
# Request/Response Schemas
# -------------------------------

class ChatRequestForm(BaseModel):
    message_id: int = Field(..., description="Session ID")
    input: str = Field(..., description="User message")
    @classmethod
    def as_form(cls, message_id: int = Form(...), input: str = Form(...)) -> "ChatRequestForm":
        return cls(message_id=message_id, input=input)

class ChatResponse(BaseModel):
    response: str

class ChatMessage(BaseModel):
    sender: str
    topic: str
    created_at: Optional[datetime]

class ChatHistory(BaseModel):
    session_id: int
    conversation: List[ChatMessage]

# -------------------------------
# Message History Handler
# -------------------------------

class MySQLChatMessageHistory(BaseChatMessageHistory):
    def __init__(self, message_id: int):
        self.message_id = message_id
        self._ensure_session()

    def _ensure_session(self):
        db = get_db_connection()
        try:
            with db.cursor() as cursor:
                cursor.execute("INSERT IGNORE INTO sessions (id, created_at, updated_at) VALUES (%s, NOW(), NOW())", (self.message_id,))
                db.commit()
        finally:
            db.close()

    @property
    def messages(self) -> List[BaseMessage]:
        db = get_db_connection()
        try:
            with db.cursor(dictionary=True) as cursor:
                cursor.execute("SELECT sender, topic FROM messages WHERE message_id = %s ORDER BY id ASC", (self.message_id,))
                return [HumanMessage(m["topic"]) if m["sender"] == "human" else AIMessage(m["topic"]) for m in cursor.fetchall()]
        finally:
            db.close()

    def add_message(self, message: BaseMessage) -> None:
        db = get_db_connection()
        try:
            with db.cursor(dictionary=True) as cursor:
                cursor.execute("SELECT agent_id, parameter_inputs FROM messages WHERE message_id = %s ORDER BY id DESC LIMIT 1", (self.message_id,))
                row = cursor.fetchone() or {}
                agent_id = row.get("agent_id", 1)
                parameter_inputs = row.get("parameter_inputs")

                if not parameter_inputs:
                    parameter_id = 1
                    cursor.execute("""
                        INSERT INTO parameter_inputs (message_id, agent_id, parameter_id, input, created_at, updated_at)
                        VALUES (%s, %s, %s, %s, NOW(), NOW())
                    """, (self.message_id, agent_id, parameter_id, "default"))
                    parameter_inputs = cursor.lastrowid

                sender = "human" if isinstance(message, HumanMessage) else "ai"
                cursor.execute("""
                    INSERT INTO messages (user_id, agent_id, message_id, parameter_inputs, sender, topic, created_at, updated_at)
                    VALUES (1, %s, %s, %s, %s, %s, NOW(), NOW())
                """, (agent_id, self.message_id, parameter_inputs, sender, message.content))
                db.commit()
        finally:
            db.close()

    def clear(self):
        db = get_db_connection()
        try:
            with db.cursor() as cursor:
                cursor.execute("DELETE FROM messages WHERE message_id = %s", (self.message_id,))
                db.commit()
        finally:
            db.close()

def get_history_by_message_id(message_id: str) -> MySQLChatMessageHistory:
    return MySQLChatMessageHistory(int(message_id))

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
# Chat Endpoints
# -------------------------------

@chat_router.post("/chat", response_model=ChatResponse)
async def chat_api(request: ChatRequestForm = Depends(ChatRequestForm.as_form)) -> ChatResponse:
    result = await chat_chain.ainvoke(
        {"input": request.input},
        config={"configurable": {"session_id": request.message_id}}
    )
    return ChatResponse(response=result)

@chat_router.get("/sessions/{user_id}", response_model=List[int])
def get_session_ids_by_user(user_id: int) -> List[int]:
    db = get_db_connection()
    try:
        with db.cursor() as cursor:
            cursor.execute("SELECT DISTINCT message_id FROM messages WHERE user_id = %s ORDER BY message_id ASC", (user_id,))
            return [row[0] for row in cursor.fetchall()]
    finally:
        db.close()

@chat_router.get("/chat/history/{message_id}", response_model=ChatHistory)
def get_chat_history(message_id: int) -> ChatHistory:
    db = get_db_connection()
    try:
        with db.cursor(dictionary=True) as cursor:
            cursor.execute("SELECT sender, topic, created_at FROM messages WHERE message_id = %s ORDER BY id ASC", (message_id,))
            rows = cursor.fetchall()
            if not rows:
                raise HTTPException(status_code=404, detail="No conversation found.")
            return ChatHistory(
                session_id=message_id,
                conversation=[ChatMessage(**r) for r in rows]
            )
    finally:
        db.close()
