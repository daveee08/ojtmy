from fastapi import APIRouter, HTTPException, Form, Depends
from fastapi.responses import JSONResponse
from pydantic import BaseModel, Field
from typing import Literal, List
from langchain_community.llms import Ollama
from langchain_core.prompts import ChatPromptTemplate, MessagesPlaceholder
from langchain_core.runnables.history import RunnableWithMessageHistory
from langchain_core.messages import HumanMessage, AIMessage, BaseMessage
from langchain_core.chat_history import BaseChatMessageHistory
import os, json, traceback

chat_router = APIRouter()
HISTORY_DIR = "chat_histories"
os.makedirs(HISTORY_DIR, exist_ok=True)

# --- LangChain Model ---
model = Ollama(model="llama3")

# --- Request Model ---
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


# --- Message Item Model ---
class MessageItem(BaseModel):
    type: Literal["human", "ai"]
    content: str

# --- Response Model ---
class ChatHistoryResponse(BaseModel):
    session_id: str
    history: List[MessageItem]

# --- Chat History Store ---
class FileChatMessageHistory(BaseChatMessageHistory, BaseModel):
    session_id: str
    messages: List[BaseMessage] = Field(default_factory=list)

    @property
    def filepath(self) -> str:
        return os.path.join(HISTORY_DIR, f"{self.session_id}.json")

    def _load_from_json(self):
        self.messages = []
        if not os.path.exists(self.filepath):
            return

        try:
            with open(self.filepath, 'r', encoding='utf-8') as f:
                data = json.load(f)

            if isinstance(data, dict) and "conversation" in data:
                for msg in data["conversation"]:
                    msg_type = msg.get("type")
                    content = msg.get("content")

                    if not content or msg_type not in {"human", "ai"}:
                        print(f"[Skipped Invalid Message] {msg}")
                        continue

                    if msg_type == "human":
                        self.messages.append(HumanMessage(content=content))
                    elif msg_type == "ai":
                        self.messages.append(AIMessage(content=content))

        except Exception as e:
            print(f"[History Load Error] {e}")
            self.messages = []

    def add_messages(self, messages: list[BaseMessage]):
        self.messages.extend(messages)
        self._save_to_json()

    def _save_to_json(self):
        data = {
            "session_id": self.session_id,
            "conversation": [
                {"type": "human" if isinstance(msg, HumanMessage) else "ai", "content": msg.content}
                for msg in self.messages
            ]
        }
        with open(self.filepath, 'w', encoding='utf-8') as f:
            json.dump(data, f, indent=2, ensure_ascii=False)

    def clear(self):
        self.messages = []
        self._save_to_json()

    @classmethod
    def from_session_id(cls, session_id: str):
        instance = cls(session_id=session_id)
        instance._load_from_json()
        return instance

def get_history_by_session_id(session_id: str) -> FileChatMessageHistory:
    return FileChatMessageHistory.from_session_id(session_id)

# --- Chat Prompt Setup ---
chat_prompt = ChatPromptTemplate.from_messages([
    ("system", "You are a helpful assistant. Keep responses clear and concise."),
    MessagesPlaceholder(variable_name="history"),
    ("human", "{topic}")
])

chat_chain = RunnableWithMessageHistory(
    runnable=chat_prompt | model,
    get_session_history=get_history_by_session_id,
    input_messages_key="topic",
    history_messages_key="history"
)

# --- Routes ---
@chat_router.post("/chat")
async def chat_api(request: ChatRequestForm = Depends(ChatRequestForm.as_form)):
    try:
        result = chat_chain.invoke(
            {"topic": request.topic},
            config={"configurable": {"session_id": request.session_id}}
        )
        return JSONResponse(content={"response": result})
    except Exception as e:
        traceback_str = traceback.format_exc()
        print(f"[Chat Error] {e}\n{traceback_str}")
        raise HTTPException(status_code=500, detail="Chat processing failed.")

@chat_router.get("/chat/history/{session_id}", response_model=ChatHistoryResponse)
async def get_chat_history(session_id: str):
    try:
        history = get_history_by_session_id(session_id)
        formatted_messages = [
            MessageItem(
                type="human" if isinstance(msg, HumanMessage) else "ai",
                content=msg.content
            ) for msg in history.messages
        ]
        return ChatHistoryResponse(session_id=session_id, history=formatted_messages)
    except Exception as e:
        traceback_str = traceback.format_exc()
        print(f"[Get History Error] {e}\n{traceback_str}")
        raise HTTPException(status_code=500, detail="Failed to retrieve chat history.")
