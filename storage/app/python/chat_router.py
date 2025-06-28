from fastapi import APIRouter, Form, HTTPException
from fastapi.responses import JSONResponse
from langchain_community.llms import Ollama
from langchain_core.prompts import ChatPromptTemplate, MessagesPlaceholder
from langchain_core.runnables.history import RunnableWithMessageHistory
from langchain_core.messages import HumanMessage, AIMessage, BaseMessage
from langchain_core.chat_history import BaseChatMessageHistory
import os, json, traceback
from pydantic import BaseModel, Field

chat_router = APIRouter()

HISTORY_DIR = "chat_histories"
os.makedirs(HISTORY_DIR, exist_ok=True)

# --- Chat Prompt ---
chat_prompt = ChatPromptTemplate.from_messages([
    ("system", "You are a helpful assistant. Keep responses clear and concise."),
    MessagesPlaceholder(variable_name="history"),
    ("human", "{topic}")
])

model = Ollama(model="llama3")
chat_chain = RunnableWithMessageHistory(
    runnable=chat_prompt | model,
    get_session_history=get_history_by_session_id,
    input_messages_key="topic",
    history_messages_key="history"
)

# --- Chat History File Store ---
class FileChatMessageHistory(BaseChatMessageHistory, BaseModel):
    session_id: str
    messages: list[BaseMessage] = Field(default_factory=list)

    def __init__(self, **data):
        super().__init__(**data)
        self._load_from_json()

    @property
    def filepath(self) -> str:
        return os.path.join(HISTORY_DIR, f"{self.session_id}.json")

    def _load_from_json(self):
        if not os.path.exists(self.filepath):
            self.messages = []
            return
        try:
            with open(self.filepath, 'r', encoding='utf-8') as f:
                data = json.load(f)
                if isinstance(data, dict) and "conversation" in data:
                    self.messages = [
                        HumanMessage(content=msg["content"]) if msg["type"] == "human"
                        else AIMessage(content=msg["content"]) for msg in data["conversation"]
                    ]
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

def get_history_by_session_id(session_id: str) -> FileChatMessageHistory:
    return FileChatMessageHistory(session_id=session_id)

@chat_router.post("/chat")
async def chat_api(
    topic: str = Form(...),
    session_id: str = Form(...)
):
    try:
        result = chat_chain.invoke(
            {"topic": topic},
            config={"configurable": {"session_id": session_id}}
        )
        return JSONResponse(content={"response": result})
    except Exception as e:
        traceback_str = traceback.format_exc()
        print(f"[Chat Error] {e}\n{traceback_str}")
        raise HTTPException(status_code=500, detail="Chat processing failed.")

@chat_router.get("/chat/history/{session_id}")
async def get_chat_history(session_id: str):
    try:
        history = get_history_by_session_id(session_id)
        # Convert messages to a more easily consumable format if needed
        formatted_messages = [
            {"type": "human" if isinstance(msg, HumanMessage) else "ai", "content": msg.content}
            for msg in history.messages
        ]
        return JSONResponse(content={"session_id": session_id, "history": formatted_messages})
    except Exception as e:
        traceback_str = traceback.format_exc()
        print(f"[Get History Error] {e}\n{traceback_str}")
        raise HTTPException(status_code=500, detail="Failed to retrieve chat history.")
