from fastapi import FastAPI, HTTPException, UploadFile, Form, File
from fastapi.responses import JSONResponse
from pydantic import BaseModel
from langchain_community.llms import Ollama
from langchain_core.prompts import ChatPromptTemplate
from langchain_community.document_loaders.pdf import PyPDFLoader
import tempfile, os, re, uvicorn, traceback

# Assignment Scaffolding Prompt Template
assignment_scaffolder_template = """
You are an educational assistant.

Your task is to turn the prompt below into a scaffolded, student-friendly assignment for **Grade {grade_level}**.

Adjust the content based on grade level:
- Kindergarten: Very simple words, playful tone, short guided steps.
- Grades 1-6: Simple language, clear tasks, with examples.
- Grades 7-10: Moderate complexity, independent ideas encouraged.
- Grades 11-12: Formal tone, critical thinking, self-directed learning.
- University: Academic tone, structured, analytical, and reflective.

**Prompt:**
"{topic}"

Your output should be a plain-text, step-by-step assignment handout that includes:
• A clear title
• A brief overview of the task
• Step-by-step instructions (numbered)
• Lists using the bullet character `•`
• Encouraging, student-friendly language

Formatting Rules:
• Use plain text only — no Markdown formatting.
• Match clean layout, consistent line breaks, and no extra explanation.
• Do not include any developer notes or images.
"""



# Load PDF content
def load_pdf_content(pdf_path: str) -> str:
    if not os.path.exists(pdf_path):
        raise FileNotFoundError("PDF file not found.")
    loader = PyPDFLoader(pdf_path)
    documents = loader.load()
    return "\n".join(doc.page_content for doc in documents)

import re

def clean_output(text: str) -> str:
    text = re.sub(r'<[^>]+>', '', text)                                # Remove HTML
    text = re.sub(r'\*\*(.*?)\*\*', r'\1', text)                       # Remove Markdown bold
    text = re.sub(r'^\*\s*|\s*\*$', '', text, flags=re.MULTILINE)     # Strip stray asterisks
    text = re.sub(r'^[ \t]*[+\*\-][ \t]*', '• ', text, flags=re.MULTILINE)  # Normalize bullets
    text = re.sub(r'_+', '', text)                                     # Remove underscores
    text = re.sub(r'[ \t]+$', '', text, flags=re.MULTILINE)            # Trim trailing spaces
    text = "\n".join(line.strip() for line in text.splitlines())       # Strip each line
    text = re.sub(r'\n\s*\n\s*\n+', '\n\n', text)                      # Limit blank lines
    return text.strip()

# LangChain setup
model = Ollama(model="llama3")
scaffolder_prompt = ChatPromptTemplate.from_template(assignment_scaffolder_template)

# Input schema
class AssignmentScaffolderInput(BaseModel):
    input_type: str  # 'manual' or 'pdf'
    topic: str = ""
    grade_level: str

# Generation logic
async def generate_output(
    input_type: str,
    grade_level: str,
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
    }

    chain = scaffolder_prompt | model
    result = chain.invoke(prompt_input)
    return clean_output(result)

# FastAPI app
app = FastAPI()

@app.post("/assignmentscaffolder")
async def assignmentscaffolder_api(
    input_type: str = Form(...),
    topic: str = Form(""),
    pdf_file: UploadFile = File(None),
    grade_level: str = Form(...)
):
    try:
        output = await generate_output(
            input_type=input_type,
            topic=topic,
            pdf_file=pdf_file,
            grade_level=grade_level
        )
        return {"output": output}
    except Exception as e:
        traceback_str = traceback.format_exc()
        print(traceback_str)
        return JSONResponse(status_code=500, content={"detail": str(e), "trace": traceback_str})

# Run with: uvicorn assignmentscaffolder:app --reload
if __name__ == "__main__":
    uvicorn.run("assignmentscaffolder:app", host="127.0.0.1", port=5001, reload=True)

#original 