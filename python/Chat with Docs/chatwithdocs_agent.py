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

prompt_template = """
You are a helpful and student-friendly tutor. Your job is to explain the following topics clearly and simply, as if you're teaching a student.

Topics:
- Topic A: {topic}
- Topic B: {topic_1}

Instructions:
- Use simple language appropriate for the student's level.
- Break down complex ideas into understandable parts.
- Be concise, but ensure the explanation is complete.

Only provide the explanation. Do not include introductions, conclusions, or phrases like "Sure, here's the explanation".
"""

app = FastAPI(debug=True)
app.include_router(chat_router)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # or Laravel origin like "http://localhost:8000"
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

class InformationalInput(BaseModel):
    user_id: int
    input_type: str
    input_type_1: Optional[str] = None
    topic: str
    topic_1: Optional[str] = None
    message_id: Optional[str] = None

    @classmethod
    def as_form(
        cls,
        user_id: int = Form(...),
        input_type: str = Form(...),
        input_type_1: Optional[str] = Form(None),
        topic: str = Form(""),
        topic_1: Optional[str] = Form(None),
        message_id: Optional[str] = Form(None),
    ):
        return cls(
            user_id=user_id,
            input_type=input_type,
            input_type_1=input_type_1,
            topic=topic,
            topic_1=topic_1,
            message_id=message_id
        )

model = Ollama(model="llama3")
prompt_template = ChatPromptTemplate.from_template(prompt_template)

def load_pdf_content(pdf_path: str) -> str:
    if not os.path.exists(pdf_path):
        raise FileNotFoundError("PDF file not found.")
    loader = PyPDFLoader(pdf_path)
    documents = loader.load()
    return "\n".join(doc.page_content for doc in documents)

# Function to clean the output from formatting artifacts
def clean_output(text: str) -> str:
    return text.strip()

async def generate_output(
    input_type: str,
    input_type_1: str,
    topic: str = "",
    topic_1: str = "",
    pdf_file: UploadFile = None,
    pdf_file_1: UploadFile = None,
):
    if input_type == "pdf":
        with tempfile.NamedTemporaryFile(delete=False, suffix=".pdf") as tmp:
            content = await pdf_file.read()
            tmp.write(content)
            tmp_path = tmp.name
        topic = load_pdf_content(tmp_path)
        os.unlink(tmp_path)
    elif not topic.strip():
        raise ValueError("Text input is required")

    if input_type_1 == "pdf_1":
        if not pdf_file_1:
            raise ValueError("PDF file required for second input")
        with tempfile.NamedTemporaryFile(delete=False, suffix=".pdf") as tmp:
            content = await pdf_file_1.read()
            tmp.write(content)
            tmp_path = tmp.name
        topic_1 = load_pdf_content(tmp_path)
        os.unlink(tmp_path)
    elif input_type_1 == "topic_1":
        if not topic_1.strip():
            raise ValueError("Text input is required for second input")
    else:
        topic_1 = ""

    # Compose input dict for prompt
    prompt_input = {
    "topic": topic,
    "topic_1": topic_1,
    }
    
    chain = prompt_template | model
    result = chain.invoke(prompt_input)
    return clean_output(result)

@app.post("/chatwithdocs")
async def chatwithdocs_api(
    form_data: InformationalInput = Depends(InformationalInput.as_form),
    pdf_file: UploadFile = File(None),
    pdf_file_1: UploadFile = File(None)
):
    
    try:
        if form_data.input_type == "pdf" and not pdf_file:
            raise HTTPException(status_code=400, detail="PDF file required for PDF input_type")

        output = await generate_output(
            input_type=form_data.input_type,
            input_type_1=form_data.input_type_1,
            topic=form_data.topic,
            topic_1=form_data.topic_1,
            pdf_file=pdf_file,
            pdf_file_1=pdf_file_1
        )

        scope_vars = {}
        
        filled_prompt = prompt_template.format(
            topic=form_data.topic.strip(),
            topic_1=form_data.topic_1.strip(),
        )

        session_id = create_session_and_parameter_inputs(
            user_id=form_data.user_id,
            agent_id=7,
            scope_vars=scope_vars,
            human_topic=form_data.topic,
            ai_output=output,
            agent_prompt=filled_prompt
        )

        return {"output": output, 'message_id': session_id}
    except Exception as e:
        traceback_str = traceback.format_exc()
        print(traceback_str)
        return JSONResponse(status_code=500, content={"detail": str(e), "trace": traceback_str})

if __name__ == "__main__":
    uvicorn.run("chatwithdocs_agent:app", host="127.0.0.1", port=5001, reload=True)