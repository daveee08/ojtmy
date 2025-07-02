from fastapi import FastAPI, HTTPException, UploadFile, Form, File # type: ignore
from fastapi.responses import JSONResponse # type: ignore
from pydantic import BaseModel, ValidationError # type: ignore
from langchain_community.llms import Ollama # type: ignore
from langchain_core.prompts import ChatPromptTemplate # type: ignore
from langchain_community.document_loaders.pdf import PyPDFLoader # type: ignore
import shutil, os, re, tempfile, uvicorn, traceback # type: ignore

prompt = """
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

model = Ollama(model="llama3")
prompt = ChatPromptTemplate.from_template(prompt)

class InformationalInput(BaseModel):
    input_type: str
    topic: str = ""
    pdf_path: str = ""

def load_pdf_content(pdf_path: str) -> str:
    if not os.path.exists(pdf_path):
        raise FileNotFoundError("PDF file not found.")
    loader = PyPDFLoader(pdf_path)
    documents = loader.load()
    return "\n".join(doc.page_content for doc in documents)

# Function to clean the output from formatting artifacts
def clean_output(text: str) -> str:
    text = re.sub(r"\*\*(.*?)\*\*", r"\1", text)
    text = re.sub(r"\*(.*?)\*", r"\1", text)
    text = re.sub(r"^\s*[\*\-]\s*", "", text, flags=re.MULTILINE)
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
    
    chain = prompt | model
    result = chain.invoke(prompt_input)
    return clean_output(result)

app = FastAPI()

@app.post("/chatwithdocs")
async def chatwithdocs_api(
    input_type: str = Form(...),
    input_type_1: str = Form(""),
    topic: str = Form(""),
    topic_1: str = Form(""),
    pdf_file: UploadFile = File(None),
    pdf_file_1: UploadFile = File(None)
):
    
    try:
        if input_type == "pdf" and not pdf_file:
            raise HTTPException(status_code=400, detail="PDF file required for PDF input_type")

        output = await generate_output(
            input_type=input_type,
            input_type_1=input_type_1,
            topic=topic,
            topic_1=topic_1,
            pdf_file=pdf_file,
            pdf_file_1=pdf_file_1
        )

        return {"output": output}
    except Exception as e:
        traceback_str = traceback.format_exc()
        print(traceback_str)
        return JSONResponse(status_code=500, content={"detail": str(e), "trace": traceback_str})

if __name__ == "__main__":
    uvicorn.run("chatwithdocs_agent:app", host="127.0.0.1", port=5001, reload=True)