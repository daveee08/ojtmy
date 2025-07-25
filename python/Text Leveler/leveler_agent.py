from fastapi import FastAPI, HTTPException, UploadFile, Form, File, Depends
from fastapi.responses import JSONResponse
from pydantic import BaseModel
from langchain_community.llms import Ollama
from langchain_core.prompts import ChatPromptTemplate
from langchain_community.document_loaders.pdf import PyPDFLoader
import shutil, os, re, tempfile, uvicorn, traceback, sys
from typing import Optional
from langchain_core.messages import HumanMessage, AIMessage
from fastapi.middleware.cors import CORSMiddleware

current_script_dir = os.path.dirname(os.path.abspath(__file__))
project_root = os.path.join(current_script_dir, '..', '..')
sys.path.insert(0, project_root)

from python.chat_router_final import chat_router
from python.db_utils_final import create_session_and_parameter_inputs, insert_message

# --- Prompt Templates ---
prompt_template = """
You are an experienced and friendly virtual tutor who helps students understand academic topics clearly and effectively.

Your goal is to explain the topic in a way that matches the student's grade level and learning needs.

Parameters:
- Grade Level: {grade_level}
- Learning speed: {learning_speed}
- Topic: {topic}

Instructions:
- Provide a detailed explanation of the topic.
- Use examples, analogies, or simple breakdowns appropriate for the student's grade level.
- Address any specific learning needs or context provided.
- Do NOT just define terms — aim to build understanding.
- Do NOT summarize — explain thoroughly.
- Use clear language, step-by-step logic, and relevant examples.
- Adapt your explanation based on any additional learning notes provided.

Respond ONLY with the explanation text (no extra commentary).
"""

# --- FastAPI App Initialization ---
app = FastAPI(debug=True)
app.include_router(chat_router)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # or Laravel origin like "http://localhost:8000"
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# --- Pydantic Model for Form Input ---
class LevelerFormInput(BaseModel):
    user_id: int
    input_type: str
    topic: str
    grade_level: str
    learning_speed: str
    message_id: Optional[str] = None

    @classmethod
    def as_form(
        cls,
        user_id: int = Form(...),
        input_type: str = Form(...),
        topic: str = Form(""),
        grade_level: str = Form(...),
        learning_speed: str = Form(...),
        message_id: Optional[str] = Form(default=None)
    ):
        return cls(
            user_id=user_id,
            input_type=input_type,
            topic=topic,
            grade_level=grade_level,
            learning_speed=learning_speed,
            message_id=message_id
        )

# --- LangChain Setup ---
model = Ollama(model="gemma3:1b")
prompt_template = ChatPromptTemplate.from_template(prompt_template)

# --- PDF Loader ---
def load_pdf_content(pdf_path: str) -> str:
    if not os.path.exists(pdf_path):
        raise FileNotFoundError("PDF file not found.")
    loader = PyPDFLoader(pdf_path)
    documents = loader.load()
    return "\n".join(doc.page_content for doc in documents)

# --- Output Cleaner ---
def clean_output(text: str) -> str:
    return text.strip()

# --- Main Output Generation Function ---
async def generate_output(
    input_type: str,
    grade_level: str,
    learning_speed: str,
    topic: str = "",
    pdf_file: UploadFile = None,
):
    if input_type == "pdf":
        # Save and load PDF
        with tempfile.NamedTemporaryFile(delete=False, suffix=".pdf") as tmp:
            content = await pdf_file.read()
            tmp.write(content)
            tmp_path = tmp.name

        topic = load_pdf_content(tmp_path)
        os.unlink(tmp_path)
    else:
        if not topic.strip():
            raise ValueError("Text input is required.")

    prompt_input = {    
        "topic": topic,
        "grade_level": grade_level,
        "learning_speed": learning_speed
    }

    chain = prompt_template | model
    result = chain.invoke(prompt_input)
    return clean_output(result)

@app.post("/leveler")
async def leveler_api(
    form_data: LevelerFormInput = Depends(LevelerFormInput.as_form),
    pdf_file: UploadFile = File(None)
):
    try:
        if form_data.input_type == "pdf" and not pdf_file:
            raise HTTPException(status_code=400, detail="PDF file required for PDF input_type")

        output = await generate_output(
            input_type=form_data.input_type,
            topic=form_data.topic,
            pdf_file=pdf_file,
            grade_level=form_data.grade_level,
            learning_speed=form_data.learning_speed,
        )

        scope_vars = {
            "grade_level": form_data.grade_level,
            "learning_speed": form_data.learning_speed,
        }

        filled_prompt = prompt_template.format(
            topic=form_data.topic.strip(), 
            grade_level=form_data.grade_level.strip(), 
            learning_speed=form_data.learning_speed.strip()
        )

        session_id = create_session_and_parameter_inputs(
            user_id=form_data.user_id,
            agent_id=4,
            scope_vars=scope_vars,
            human_topic=form_data.topic,
            ai_output=output,
            agent_prompt=filled_prompt
        )

        return {"output": output, "message_id": session_id}
    except Exception as e:
        traceback_str = traceback.format_exc()
        print(traceback_str)
        return JSONResponse(status_code=500, content={"detail": str(e), "trace": traceback_str})

# --- Uvicorn entrypoint ---
if __name__ == "__main__":
    uvicorn.run("leveler_agent:app", host="127.0.0.1", port=5001, reload=True)