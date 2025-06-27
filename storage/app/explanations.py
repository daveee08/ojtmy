from fastapi import FastAPI, HTTPException, UploadFile, Form, File 
from fastapi.responses import JSONResponse 
from pydantic import BaseModel, ValidationError 
from langchain_community.llms import Ollama 
from langchain_core.prompts import ChatPromptTemplate 
from langchain_community.document_loaders.pdf import PyPDFLoader 
import shutil, os, re, tempfile, traceback 
import uvicorn

manual_concept_template = """
You are a knowledgeable and friendly virtual tutor.

The following is an educational explanation about a specific programming concept written for general understanding. Your task is to rewrite it clearly and effectively for a student at the specified grade level.

Parameters:
- Concept content: {concept}
- Grade level: {grade_level}

Instructions:
- Retain all technical details and examples unless the grade level suggests otherwise.
- Break down complex ideas into simpler language if the grade level is below college level.
- Use analogies only when helpful for clarity.
- Maintain structure: keep key concepts, examples, and analogies.
- Output should be well-organized and readable.
- Do not include phrases like “Here's a version that…” or “To put it simply…” or “In summary…”

Respond ONLY with the rewritten explanation, formatted clearly for the intended grade level.
"""

pdf_concept_template = """
You are a knowledgeable and supportive virtual tutor.

You will receive content extracted from a textbook or document (such as a PDF). Your task is to explain this content in a way that is understandable to a student at the given grade level.

Parameters:
- Extracted Concept being taught: {concept}

Instructions:
- Retain all technical details and examples unless the grade level suggests otherwise.
- Break down complex ideas into simpler language if the grade level is below college level.
- Use analogies only when helpful for clarity.
- Maintain structure: keep key concepts, examples, and analogies.
- Output should be well-organized and readable.
- Do not include phrases like “Here's a version that…” or “To put it simply…” or “In summary…”

Respond ONLY with the rewritten explanation, formatted clearly for the intended grade level.
"""

model = Ollama(model="llama3")
manual_prompt = ChatPromptTemplate.from_template(manual_concept_template)
pdf_prompt = ChatPromptTemplate.from_template(pdf_concept_template)

class ExplanationsInput(BaseModel):
    input_type: str
    concept: str = ""
    pdf_path: str = ""
    grade_level: str

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
    grade_level: str,
    concept: str = "",
    pdf_file: UploadFile = None,
):
    if input_type == "pdf":
        # Save PDF temporarily
        with tempfile.NamedTemporaryFile(delete=False, suffix=".pdf") as tmp:
            content = await pdf_file.read()
            tmp.write(content)
            tmp_path = tmp.name

        concept = load_pdf_content(tmp_path)
        os.unlink(tmp_path)  # Delete file after use
        prompt = pdf_prompt
    else:
        if not concept.strip():
            raise ValueError("Text input is required")
        prompt = manual_prompt

    # Compose input dict for prompt
    prompt_input = {
    "concept": concept,
    "grade_level": grade_level
    }
    
    chain = prompt | model
    result = chain.invoke(prompt_input)
    return clean_output(result)

app = FastAPI()

@app.post("/explanations")
async def explanations_api(
    input_type: str = Form(...),
    concept: str = Form(""),
    pdf_file: UploadFile = File(None),
    grade_level: str = Form(...),
):
    
    try:
        if input_type == "pdf" and not pdf_file:
            raise HTTPException(status_code=400, detail="PDF file required for PDF input_type")

        output = await generate_output(
            input_type=input_type,
            concept=concept,
            pdf_file=pdf_file,
            grade_level=grade_level,
        )

        return {"output": output}
    except Exception as e:
        traceback_str = traceback.format_exc()
        print(traceback_str)
        return JSONResponse(status_code=500, content={"detail": str(e), "trace": traceback_str})

if __name__ == "__main__":
    uvicorn.run("explanations:app", host="127.0.0.1", port=5001, reload=True)