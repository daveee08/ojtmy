from fastapi import FastAPI, HTTPException, UploadFile, Form, File
from fastapi.responses import JSONResponse
from pydantic import BaseModel, Field
from langchain_community.llms import Ollama
from langchain_core.prompts import PromptTemplate, ChatPromptTemplate, MessagesPlaceholder
from langchain_core.messages import BaseMessage, messages_from_dict, messages_to_dict, HumanMessage, AIMessage
from langchain_core.runnables.history import RunnableWithMessageHistory
from langchain_core.chat_history import BaseChatMessageHistory
from langchain_community.document_loaders.pdf import PyPDFLoader

import json, os, re, tempfile, traceback, uvicorn

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
        """
        Loads chat history from a JSON file, reconstructing BaseMessage objects
        from simplified 'type' and 'content' data.
        """
        if not os.path.exists(self.filepath):
            self.messages = []
            return
        try:
            with open(self.filepath, 'r', encoding='utf-8') as f:
                data = json.load(f)
                if isinstance(data, dict) and "conversation" in data:
                    loaded_messages = []
                    for msg_data in data["conversation"]:
                        msg_type = msg_data.get("type")
                        msg_content = msg_data.get("content")
                        if msg_type == "human":
                            loaded_messages.append(HumanMessage(content=msg_content))
                        elif msg_type == "ai":
                            loaded_messages.append(AIMessage(content=msg_content))
                        # Add other message types if necessary (e.g., SystemMessage, FunctionMessage)
                    self.messages = loaded_messages
        except Exception as e:
            print(f"Error loading history for session {self.session_id}: {e}")
            self.messages = []

    def add_messages(self, messages: list[BaseMessage]) -> None:
        """Adds new messages to the history and saves to file."""
        self.messages.extend(messages)
        self._save_to_json()

    def _save_to_json(self) -> None:
        """
        Saves the current chat history to a JSON file, storing only
        'type' and 'content' for each message.
        """
        simplified_conversation = []
        for msg in self.messages:
            # Ensure the message has a type and content attribute
            if hasattr(msg, 'type') and hasattr(msg, 'content'):
                simplified_conversation.append({
                    "type": msg.type,
                    "content": msg.content
                })
            else:
                # Fallback for unexpected message types if necessary, or log a warning
                print(f"Warning: Message {msg} does not have expected 'type' or 'content' attributes. Skipping save.")

        data = {
            "session_id": self.session_id,
            "conversation": simplified_conversation
        }
        with open(self.filepath, 'w', encoding='utf-8') as f:
            json.dump(data, f, indent=2, ensure_ascii=False)

    def clear(self) -> None:
        """Clears all messages from the history and deletes the history file."""
        self.messages = []
        if os.path.exists(self.filepath):
            os.remove(self.filepath)

def get_history_by_session_id(session_id: str) -> FileChatMessageHistory:
    """Retrieves or creates a FileChatMessageHistory instance for a given session ID."""
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
    ("system", "You are a helpful assistant. Keep your responses concise and focused on the current conversation."),
    MessagesPlaceholder(variable_name="history"),
    ("human", "{topic}") # This now consistently refers to the user's message in the chat
])

# Initialize Ollama LLM
model = Ollama(model="llama3")

# Create the chat chain with message history
chat_chain = RunnableWithMessageHistory(
    runnable=chat_prompt | model, # The runnable should be the prompt piped to the LLM
    get_session_history=get_history_by_session_id,
    input_messages_key="topic", # Key in the input dict for the user's message
    history_messages_key="history" # Key in the prompt template for the history messages
)

def clean_output(text: str) -> str:
    text = re.sub(r"\*\*(.*?)\*\*", r"\1", text)
    text = re.sub(r"\*(.*?)\*", r"\1", text)
    text = re.sub(r"^\s*[\*\-]\s*", "", text, flags=re.MULTILINE)
    return text.strip()

def load_pdf_content(pdf_path: str) -> str:
    if not os.path.exists(pdf_path):
        raise FileNotFoundError("PDF file not found.")
    loader = PyPDFLoader(pdf_path)
    documents = loader.load()
    return "\n".join(doc.page_content for doc in documents)

async def generate_output(
    input_type: str,
    grade_level: str,
    learning_speed: str,
    topic: str = "", # This is the user-provided topic/context
    pdf_file: UploadFile = None
) -> str:
    # This variable will hold the content that goes into the LLM's 'topic' slot
    llm_topic_content = ""
    # This variable will hold the message to be displayed as the human message in chat history
    human_history_message = ""

    if input_type == "pdf":
        if not pdf_file:
            raise ValueError("PDF file is required for PDF input type.")

        human_history_message = topic if topic.strip() else f"Uploaded PDF: {pdf_file.filename}"

        # Create a temporary file to save the uploaded PDF
        with tempfile.NamedTemporaryFile(delete=False, suffix=".pdf") as tmp:
            content = await pdf_file.read()
            tmp.write(content)
            tmp_path = tmp.name

        llm_topic_content = load_pdf_content(tmp_path)
        os.unlink(tmp_path) # Clean up the temporary PDF file
        prompt = pdf_prompt

    else: # input_type is "topic" (manual text input)
        if not topic.strip():
            raise ValueError("Topic input is required for topic input type.")
        llm_topic_content = topic
        human_history_message = topic # For manual input, the topic IS the human message
        prompt = manual_prompt

    # Prepare input for the selected prompt
    prompt_input = {
        "topic": llm_topic_content, # This is the content for the LLM prompt
        "grade_level": grade_level,
        "learning_speed": learning_speed
    }

    # Invoke the chain to get the explanation
    result = prompt | model
    explanation = result.invoke(prompt_input)

    return clean_output(explanation), human_history_message # Return both explanation and human message


# --- FastAPI App ---
app = FastAPI()

@app.post("/start")
async def leveler_api(
    input_type: str = Form(...),
    topic: str = Form(""), # This is the user-provided topic or filename for PDF
    pdf_file: UploadFile = File(None),
    grade_level: str = Form(...),
    learning_speed: str = Form(...),
    session_id: str = Form(...)
):
    try:
        explanation, initial_human_message = await generate_output( # Capture both return values
            input_type=input_type,
            topic=topic, # Pass the user-provided topic/filename
            pdf_file=pdf_file,
            grade_level=grade_level,
            learning_speed=learning_speed
        )

        # Save the initial user query and the generated explanation to the history
        history = get_history_by_session_id(session_id)
        history.add_messages([
            HumanMessage(content=initial_human_message), # Use the more descriptive human message
            AIMessage(content=explanation)
        ])

        return {"output": explanation}
    except ValueError as ve:
        raise HTTPException(status_code=400, detail=f"Input validation error: {ve}")
    except Exception as e:
        traceback_str = traceback.format_exc()
        print(f"Startup error: {e}. Trace: {traceback_str}") # Log the error
        raise HTTPException(status_code=500, detail=f"Startup error: An unexpected error occurred. Trace: {traceback_str}")

@app.post("/chat")
async def chat_api(
    topic: str = Form(...), # Changed from user_message to topic
    session_id: str = Form(...),
):
    """
    Continues a conversation based on the persistent chat history for a given session ID.
    """
    try:
        # The 'topic' variable now directly holds the user's message for the chat chain
        result = chat_chain.invoke(
            {"topic": topic}, # Pass the 'topic' directly
            config={"configurable": {"session_id": session_id}}
        )

        return JSONResponse(status_code=200, content={"response": result})
    except Exception as e:
        traceback_str = traceback.format_exc()
        print(f"Chat error: {e}. Trace: {traceback_str}") # Log the error
        raise HTTPException(status_code=500, detail=f"Chat error: An unexpected error occurred. Trace: {traceback_str}")

if __name__ == "__main__":
    uvicorn.run("leveler_cont:app", host="127.0.0.1", port=5002, reload=True)
