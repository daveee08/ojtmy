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
llm = Ollama(model="gemma3:1b")

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
        cursor = db.cursor()
        try:
            cursor.execute("SELECT id FROM sessions WHERE id = %s", (self.message_id,))
            if cursor.fetchone() is None:
                cursor.execute("INSERT INTO sessions (id, created_at, updated_at) VALUES (%s, NOW(), NOW())", (self.message_id,))
                db.commit()
        finally:
            cursor.close()
            db.close()

    @property
    def messages(self):
        db = get_db_connection()
        cursor = db.cursor(dictionary=True)
        try:
            cursor.execute("""
                SELECT sender, topic FROM messages
                WHERE message_id = %s ORDER BY id ASC
            """, (self.message_id,))
            return [
                HumanMessage(content=m["topic"]) if m["sender"] == "human" else AIMessage(content=m["topic"])
                for m in cursor.fetchall()
            ]
        finally:
            cursor.close()
            db.close()

    def add_message(self, message: BaseMessage) -> None:
        db = get_db_connection()
        cursor = db.cursor(dictionary=True)
        try:
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
            cursor.close()
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
        cursor = db.cursor()
        try:
            cursor.execute("DELETE FROM messages WHERE message_id = %s", (self.message_id,))
            db.commit()
        finally:
            cursor.close()
            db.close()

def get_history_by_message_id(session_id: str) -> MySQLChatMessageHistory:
    user_id_str, message_id_str = session_id.split(":")
    return MySQLChatMessageHistory(int(user_id_str), int(message_id_str))

# -------------------------------
# LangChain Setup
# -------------------------------

chat_prompt = ChatPromptTemplate._
