from fastapi import APIRouter, HTTPException, Form, Depends
from fastapi.responses import JSONResponse
from pydantic import BaseModel
from typing import Literal, List
import traceback

from langchain_ollama import OllamaLLM as Ollama  # updated import
from langchain_core.prompts import ChatPromptTemplate
from langchain_core.runnables import RunnableWithMessageHistory
from langchain_core.messages import HumanMessage, AIMessage, BaseMessage
from langchain_core.runnables.history import BaseChatMessageHistory

from db_utils import (
    fetch_messages,
    insert_message,
    delete_messages,
    get_db_connection,
    create_session_and_parameter_inputs
)

# ---------------------
# CONSTANTS
# ---------------------
USER_ID = "api_user"
AGENT_ID = 1
SCOPE_VARS = {
    "grade_level": "college",
    "learning_speed": "average",
}

# ---------------------
# ROUTER SETUP
# ---------------------
chat_router = APIRouter()

# ---------------------
# REQUEST MODEL
# ---------------------
class ChatRequestForm(BaseModel):
    topic: str
    session_id: str

    @classmethod
    def as_form(
        cls,
        topic: str = Form(...),
        session_id: str = Form(...)
    ):
        return cls(topic=topic, session_id=session_id)

# ---------------------
# MESSAGE MEMORY
# ---------------------
class MySQLChatMessageHistory(BaseChatMessageHistory):
    def __init__(self, session_id: int):
        self.session_id = session_id

    @property
    def messages(self) -> list[BaseMessage]:
        history = fetch_messages(self.session_id)
        return [
            HumanMessage(content=row["topic"]) if row["sender"] == "human" else AIMessage(content=row["topic"])
            for row in history
        ]

    def add_message(self, message: BaseMessage) -> None:
        from db_utils import get_db_connection  # local import to avoid circular
        db = get_db_connection()
        try:
            with db.cursor() as cursor:
                sender = "human" if isinstance(message, HumanMessage) else "ai"
                insert_message(
                    cursor=cursor,
                    user_id=USER_ID,
                    agent_id=AGENT_ID,
                    message_id=self.session_id,
                    parameter_inputs=None,  # optional, use NULL if unknown
                    sender=sender,
                    topic=message.content
                )
                db.commit()
        finally:
            db.close()

    def clear(self) -> None:
        delete_messages(self.session_id)
        
# ---------------------
# HISTORY ACCESSOR (for external imports like leveler_agent.py)
# ---------------------
def get_history_by_message_id(session_id: str) -> MySQLChatMessageHistory:
    return MySQLChatMessageHistory(session_id=int(session_id))

# ---------------------
# LANGCHAIN SETUP
# ---------------------
llm = Ollama(model="llama3")

chat_prompt = ChatPromptTemplate.from_template(
    "You are a helpful assistant. Continue the conversation.\n\n{chat_history}\nUser: {input}"
)

chat_chain = RunnableWithMessageHistory(
    chat_prompt | llm,
    get_session_history=get_history_by_message_id,
    input_messages_key="input",
    history_messages_key="chat_history"
)