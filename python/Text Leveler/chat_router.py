from fastapi import APIRouter, HTTPException
from fastapi.responses import JSONResponse
from pydantic import BaseModel, Field
from fastapi import Form, Depends
from typing import List, Literal

from langchain_ollama import OllamaLLM as Ollama
from langchain_core.prompts import ChatPromptTemplate
from langchain_core.runnables import RunnableWithMessageHistory
from langchain_core.messages import HumanMessage, AIMessage, BaseMessage
from langchain_core.runnables.history import BaseChatMessageHistory

from db_utils import (
    fetch_messages,
    add_message as db_add_message,
    clear_messages_by_session_id,
    get_all_session_ids,
    get_messages_by_session_id
)

chat_router = APIRouter()
llm = Ollama(model="llama3")

# -------------------------------
# Pydantic Models
# -------------------------------

class ChatRequestForm(BaseModel):
    session_id: int
    input: str

    @classmethod
    def as_form(
        cls,
        session_id: int = Form(...),
        input: str = Form(...)
    ):
        return cls(session_id=session_id, input=input)

class ChatResponse(BaseModel):
    response: str

class SessionIDsResponse(BaseModel):
    session_ids: List[int]

class MessageEntry(BaseModel):
    sender: Literal["human", "ai"]
    topic: str

class ChatHistoryResponse(BaseModel):
    session_id: int
    conversation: List[MessageEntry]

# -------------------------------
# Custom Chat Message History
# -------------------------------

class MySQLChatMessageHistory(BaseChatMessageHistory):
    def __init__(self, session_id: int):
        self.session_id = session_id

    @property
    def messages(self) -> List[BaseMessage]:
        return [
            HumanMessage(content=m["topic"]) if m["sender"] == "human" else AIMessage(content=m["topic"])
            for m in fetch_messages(self.session_id)
        ]

    def add_message(self, message: BaseMessage) -> None:
        db_add_message(session_id=self.session_id, message=message, user_id=1)

    def clear(self) -> None:
        clear_messages_by_session_id(self.session_id)

def get_history_by_message_id(session_id: str) -> MySQLChatMessageHistory:
    return MySQLChatMessageHistory(session_id=int(session_id))

chat_prompt = ChatPromptTemplate.from_messages([
    ("system", "You are a helpful assistant."),
    ("placeholder", "{chat_history}"),
    ("human", "{input}")
])

chat_chain = RunnableWithMessageHistory(
    chat_prompt | llm,
    get_session_history=get_history_by_message_id,
    input_messages_key="input",
    history_messages_key="chat_history"
)

# -------------------------------
# Routes with Pydantic Responses
# -------------------------------

@chat_router.get("/chat/sessions", response_model=SessionIDsResponse)
def get_existing_sessions():
    try:
        return {"session_ids": get_all_session_ids()}
    except Exception:
        raise HTTPException(status_code=500, detail="Failed to fetch session IDs.")

@chat_router.get("/chat/history/{session_id}", response_model=ChatHistoryResponse)
def get_chat_history(session_id: int):
    try:
        messages = get_messages_by_session_id(session_id)
        return {
            "session_id": session_id,
            "conversation": [{"sender": m["sender"], "topic": m["topic"]} for m in messages]
        }
    except Exception:
        raise HTTPException(status_code=500, detail="Failed to fetch conversation history.")

@chat_router.post("/chat", response_model=ChatResponse)
async def chat_api(request: ChatRequestForm = Depends(ChatRequestForm.as_form)) -> ChatResponse:
    result = await chat_chain.ainvoke(
        {"input": request.input},
        config={"configurable": {"session_id": request.session_id}}
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
