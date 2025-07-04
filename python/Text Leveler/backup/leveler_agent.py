from fastapi import FastAPI, HTTPException, UploadFile, Form, File, Depends
from fastapi.responses import JSONResponse
from pydantic import BaseModel
from langchain_community.llms import Ollama
from langchain_core.prompts import ChatPromptTemplate
from langchain_community.document_loaders.pdf import PyPDFLoader
import shutil, os, re, tempfile, uvicorn, traceback
from typing import Optional
from uuid import uuid4
from chat_router import chat_router, get_history_by_message_id
from langchain_core.messages import HumanMessage, AIMessage

# --- Prompt Templates ---
manual_topic_template = """
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

pdf_topic_template = """
You are a knowledgeable and supportive virtual tutor.

You will receive content extracted from a textbook or document (such as a PDF). Your task is to explain this content in a way that is understandable to a student at the given grade level.

Parameters:
- Grade Level: {grade_level}
- Learning speed: {learning_speed}
- Extracted Content: {topic}

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

# --- LangChain Setup ---
model = Ollama(model="llama3")
manual_prompt = ChatPromptTemplate.from_template(manual_topic_template)
pdf_prompt = ChatPromptTemplate.from_template(pdf_topic_template)

# --- Pydantic Model for Form Input ---
class LevelerFormInput(BaseModel):
    input_type: str
    topic: str
    grade_level: str
    learning_speed: str
    message_id: Optional[str] = None

    @classmethod
    def as_form(
        cls,
        input_type: str = Form(...),
        topic: str = Form(""),
        grade_level: str = Form(...),
        learning_speed: str = Form(...),
        message_id: Optional[str] = Form(default=None)
    ):
        return cls(
            input_type=input_type,
            topic=topic,
            grade_level=grade_level,
            learning_speed=learning_speed,
            message_id=message_id
        )

# --- PDF Loader ---
def load_pdf_content(pdf_path: str) -> str:
    if not os.path.exists(pdf_path):
        raise FileNotFoundError("PDF file not found.")
    loader = PyPDFLoader(pdf_path)
    documents = loader.load()
    return "\n".join(doc.page_content for doc in documents)

# --- Output Cleaner ---
def clean_output(text: str) -> str:
    text = re.sub(r"\*\*(.*?)\*\*", r"\1", text)
    text = re.sub(r"\*(.*?)\*", r"\1", text)
    text = re.sub(r"^\s*[\*\-]\s*", "", text, flags=re.MULTILINE)
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
        prompt = pdf_prompt
    else:
        if not topic.strip():
            raise ValueError("Text input is required.")
        prompt = manual_prompt

    prompt_input = {
        "topic": topic,
        "grade_level": grade_level,
        "learning_speed": learning_speed
    }

    chain = prompt | model
    result = chain.invoke(prompt_input)
    return clean_output(result)

# --- FastAPI Setup ---
app = FastAPI()
app.include_router(chat_router)

@app.post("/leveler")
async def leveler_api(
    form_data: LevelerFormInput = Depends(LevelerFormInput.as_form),
    pdf_file: UploadFile = File(None)
):
    try:
        if form_data.input_type == "pdf" and not pdf_file:
            raise HTTPException(status_code=400, detail="PDF file required for PDF input_type")

        message_id = form_data.message_id or str(uuid4())

        output = await generate_output(
            input_type=form_data.input_type,
            topic=form_data.topic,
            pdf_file=pdf_file,
            grade_level=form_data.grade_level,
            learning_speed=form_data.learning_speed,
        )

        history = get_history_by_message_id(message_id)
        human_content = form_data.topic.strip() or f"Uploaded PDF: {pdf_file.filename}"
        history.add_messages([
            HumanMessage(content=human_content),
            AIMessage(content=output)
        ])

        return {"output": output, "message_id": message_id}
    except Exception as e:
        traceback_str = traceback.format_exc()
        print(traceback_str)
        return JSONResponse(status_code=500, content={"detail": str(e), "trace": traceback_str})

# --- Uvicorn entrypoint ---
if __name__ == "__main__":
    uvicorn.run("leveler_agent:app", host="127.0.0.1", port=5001, reload=True)
