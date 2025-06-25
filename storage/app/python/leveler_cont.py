from fastapi import FastAPI, HTTPException, UploadFile, Form, File
from fastapi.responses import JSONResponse
from pydantic import BaseModel, Field
from langchain_community.llms import Ollama
from langchain_core.prompts import PromptTemplate, ChatPromptTemplate, MessagesPlaceholder
from langchain_core.messages import BaseMessage, messages_from_dict, messages_to_dict
from langchain_core.runnables.history import RunnableWithMessageHistory
from langchain_core.chat_history import BaseChatMessageHistory
from langchain_community.document_loaders.pdf import PyPDFLoader

import json, os, re, tempfile, traceback

# --- Config ---
HISTORY_DIR = "chat_histories"
os.makedirs(HISTORY_DIR, exist_ok=True)

# --- File-based History Store ---
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
                if isinstance(data, dict) and "conversation" in data:
                    self.messages = messages_from_dict(data["conversation"])
        except Exception as e:
            print(f"Error loading history: {e}")
            self.messages = []

    def add_messages(self, messages: list[BaseMessage]) -> None:
        self.messages.extend(messages)
        self._save_to_json()

    def _save_to_json(self) -> None:
        data = {
            "session_id": self.session_id,
            "conversation": messages_to_dict(self.messages)
        }
        with open(self.filepath, 'w', encoding='utf-8') as f:
            json.dump(data, f, indent=2, ensure_ascii=False)

    def clear(self) -> None:
        self.messages = []
        if os.path.exists(self.filepath):
            os.remove(self.filepath)

def get_history_by_session_id(session_id: str) -> FileChatMessageHistory:
    return FileChatMessageHistory(session_id=session_id)

# --- Prompt Templates ---
manual_topic_template = """
You are a helpful tutor.
Grade Level: {grade_level}
Learning speed: {learning_speed}
Topic: {topic}
Explain the topic in detail with examples. Don't just define, focus on building understanding.
"""

pdf_topic_template = """
You are a helpful tutor.
Grade Level: {grade_level}
Learning speed: {learning_speed}
Extracted Content: {topic}
Explain the content clearly for the student’s level. Avoid summarizing.
"""

manual_prompt = PromptTemplate.from_template(manual_topic_template)
pdf_prompt = PromptTemplate.from_template(pdf_topic_template)

chat_prompt = ChatPromptTemplate.from_messages([
    ("system", "You are a helpful assistant."),
    MessagesPlaceholder(variable_name="history"),
    ("human", "{question}")
])

llm = Ollama(model="llama3")

chat_chain = RunnableWithMessageHistory(
    runnable=chat_prompt | llm,
    get_session_history=get_history_by_session_id,
    input_messages_key="question",
    history_messages_key="history"
)

# --- Helper Functions ---
def clean_output(text: str) -> str:
    text = re.sub(r"\*\*(.*?)\*\*", r"\1", text)
    text = re.sub(r"\*(.*?)\*", r"\1", text)
    text = re.sub(r"^\s*[\*\-]\s*", "", text, flags=re.MULTILINE)
    return text.strip()

def load_pdf_content(pdf_path: str) -> str:
    loader = PyPDFLoader(pdf_path)
    documents = loader.load()
    return "\n".join(doc.page_content for doc in documents)

async def generate_initial_explanation(
    input_type: str, grade_level: str, learning_speed: str, topic: str = "", pdf_file: UploadFile = None
) -> str:
    if input_type == "pdf":
        if not pdf_file:
            raise ValueError("PDF file is required.")
        with tempfile.NamedTemporaryFile(delete=False, suffix=".pdf") as tmp:
            content = await pdf_file.read()
            tmp.write(content)
            tmp_path = tmp.name
        topic_content = load_pdf_content(tmp_path)
        os.unlink(tmp_path)
        selected_prompt = pdf_prompt
    else:
        if not topic.strip():
            raise ValueError("Manual topic input is required.")
        topic_content = topic
        selected_prompt = manual_prompt

    prompt_input = {
        "topic": topic_content,
        "grade_level": grade_level,
        "learning_speed": learning_speed
    }
    result = selected_prompt | llm
    return clean_output(result.invoke(prompt_input))

# --- FastAPI App ---
app = FastAPI(title="Integrated Chat + Leveler API")

@app.post("/start", summary="Initial explanation with leveler.")
async def start_leveler(
    input_type: str = Form(...),
    topic: str = Form(""),
    pdf_file: UploadFile = File(None),
    grade_level: str = Form(...),
    learning_speed: str = Form(...),
    session_id: str = Form(...)
):
    try:
        explanation = await generate_initial_explanation(
            input_type=input_type,
            topic=topic,
            pdf_file=pdf_file,
            grade_level=grade_level,
            learning_speed=learning_speed
        )
        # Save explanation as first message in history
        history = get_history_by_session_id(session_id)
        from langchain_core.messages import HumanMessage, AIMessage
        history.add_messages([HumanMessage(content=topic), AIMessage(content=explanation)])
        return {"output": explanation}
    except Exception as e:
        traceback_str = traceback.format_exc()
        raise HTTPException(status_code=500, detail=f"Startup error: {e}. Trace: {traceback_str}")

@app.post("/chat", summary="Follow-up chat using persistent context.")
async def chat_followup(
    topic: str = Form(..., description="Original topic used as the base context."),
    session_id: str = Form(..., description="Same session ID used in /start."),
):
    try:
        result = chat_chain.invoke(
            {"question": topic},
            config={"configurable": {"session_id": session_id}}
        )
        return JSONResponse(status_code=200, content={"response": result.content})
    except Exception as e:
        traceback_str = traceback.format_exc()
        raise HTTPException(status_code=500, detail=f"Chat error: {e}. Trace: {traceback_str}")

# --- Run ---
if __name__ == "__main__":
    import uvicorn
    uvicorn.run("leveler_cont:app", host="127.0.0.1", port=5000, reload=True)
