# chat_history.py
import os, json
from pydantic import BaseModel, Field
from langchain_core.chat_history import BaseChatMessageHistory
from langchain_core.messages import HumanMessage, AIMessage, BaseMessage

HISTORY_DIR = "chat_histories"
os.makedirs(HISTORY_DIR, exist_ok=True)

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
                        HumanMessage(content=m["content"]) if m["type"] == "human"
                        else AIMessage(content=m["content"]) for m in data["conversation"]
                    ]
        except Exception:
            self.messages = []

    def add_messages(self, messages: list[BaseMessage]):
        self.messages.extend(messages)
        self._save_to_json()

    def _save_to_json(self):
        simplified = [{"type": m.type, "content": m.content} for m in self.messages]
        with open(self.filepath, 'w', encoding='utf-8') as f:
            json.dump({"session_id": self.session_id, "conversation": simplified}, f, indent=2, ensure_ascii=False)

    def clear(self):
        self.messages = []
        if os.path.exists(self.filepath):
            os.remove(self.filepath)

def get_history_by_session_id(session_id: str) -> FileChatMessageHistory:
    return FileChatMessageHistory(session_id=session_id)
