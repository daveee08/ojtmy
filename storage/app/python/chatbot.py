from fastapi import FastAPI, HTTPException, Form
from fastapi.responses import JSONResponse
from pydantic import BaseModel, Field
from langchain_community.chat_models import ChatOllama
from langchain_core.prompts import ChatPromptTemplate, MessagesPlaceholder
from langchain_core.messages import BaseMessage, messages_from_dict, messages_to_dict
from langchain_core.runnables.history import RunnableWithMessageHistory
from langchain_core.chat_history import BaseChatMessageHistory
import json, os, traceback, uvicorn # Removed unnecessary imports if not used

# --- Configuration ---
HISTORY_DIR = "chat_histories"
os.makedirs(HISTORY_DIR, exist_ok=True)

# --- File-based history store ---
class FileChatMessageHistory(BaseChatMessageHistory, BaseModel):
    session_id: str
    messages: list[BaseMessage] = Field(default_factory=list)

    def __init__(self, **data):
        super().__init__(**data)
        self._load_from_json()

    @property
    def filepath(self) -> str:
        return os.path.join(HISTORY_DIR, f"{self.session_id}.json")

    def _load_from_json(self) -> None:
        if not os.path.exists(self.filepath):
            self.messages = []
            return
        try:
            with open(self.filepath, 'r', encoding='utf-8') as f:
                data = json.load(f)
                if isinstance(data, dict) and "conversation" in data and isinstance(data["conversation"], list):
                    self.messages = messages_from_dict(data["conversation"])
                else:
                    print(f"Warning: Unexpected history structure in {self.filepath}. Starting fresh.")
                    self.messages = []
        except (json.JSONDecodeError, KeyError, TypeError) as e:
            print(f"Error loading history from {self.filepath}: {e}. Starting fresh.")
            self.messages = []

    def add_messages(self, messages: list[BaseMessage]) -> None:
        self.messages.extend(messages)
        self._save_to_json()

    def _save_to_json(self) -> None:
        data_to_save = {
            "session_id": self.session_id,
            "conversation": messages_to_dict(self.messages)
        }
        try:
            with open(self.filepath, 'w', encoding='utf-8') as f:
                json.dump(data_to_save, f, indent=2, ensure_ascii=False)
        except Exception as e:
            print(f"Error saving history to {self.filepath}: {e}")

    def clear(self) -> None:
        self.messages = []
        if os.path.exists(self.filepath):
            try:
                os.remove(self.filepath)
                print(f"Cleared history file: {self.filepath}")
            except OSError as e:
                print(f"Error deleting history file {self.filepath}: {e}")

# --- History accessor ---
def get_history_by_session_id(session_id: str) -> FileChatMessageHistory:
    return FileChatMessageHistory(session_id=session_id)

# --- Prompt & Model ---
chat_prompt = ChatPromptTemplate.from_messages([
    ("system", "You are a helpful assistant."),
    MessagesPlaceholder(variable_name="history"),
    ("human", "{question}")
])

llm = ChatOllama(model="llama3")

# --- Chain with message history ---
chat_chain = RunnableWithMessageHistory(
    runnable=chat_prompt | llm,
    get_session_history=get_history_by_session_id,
    input_messages_key="question",
    history_messages_key="history"
)

# --- FastAPI setup ---
app = FastAPI(title="Chatbot API") # Shorter title/description

@app.post("/chat", summary="Send a message to the chatbot.")
async def chat(
    question: str = Form(..., description="User's message."),
    session_id: str = Form(..., description="Unique session ID.")
):
    try:
        result = chat_chain.invoke(
            {"question": question},
            config={"configurable": {"session_id": session_id}}
        )
        return JSONResponse(status_code=200, content={"response": result.content})
    except Exception as e:
        traceback_str = traceback.format_exc()
        print(f"Error for session '{session_id}':\n{traceback_str}")
        raise HTTPException(
            status_code=500,
            detail=f"Internal server error: {e}. Trace: {traceback_str}"
        )

if __name__ == "__main__":
    uvicorn.run("chatbot:app", host="127.0.0.1", port=5002, reload=True, log_level="info")

