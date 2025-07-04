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

Your task is to turn the following prompt into a fully scaffolded, student-friendly assignment for **Grade {grade_level}** students.

**Prompt:**  
"{topic}"

Your output must follow the structure below. The goal is to turn the assignment into a clear, step-by-step activity that supports understanding and completion. Use simple, encouraging language and **text-only formatting**.

**Title:**  
Create a short, engaging title that captures the theme or task of the assignment.

**Step-by-Step Instructions:**  
Break the assignment down into **clear, manageable steps (Step 1, Step 2, etc.)** that guide the student from start to finish.  
For each step:
- Begin with a bold heading (e.g., **Step 1: Read the Task Carefully**)  
- Use bullet points or numbered lists for clarity  
- Include scaffolds like:
  - Sentence starters  
  - Guiding questions  
  - Tips or examples  
  - Clarification of terms or choices  

Each step should help students make progress toward completing the assignment. Do **not** refer to or include any pictures, diagrams, or visual materials.

**Important Vocabulary** *(if needed)*  
If the prompt uses technical, subject-specific, or academic words, include **3–5 key terms** and define them in student-friendly language. Use words only — no visual aids.

**Tips for Success:**  
Give **2–4 friendly tips or reminders** to support students as they work. Focus on effort, clarity, and checking their work. Keep the tone warm and motivating.

**Formatting Notes:**  
- Use **bold headers** to organize each section  
- The output should look like a clean, ready-to-use student handout  
- Do **not** include explanations or developer notes — return only the final formatted assignment  
- Output must be **fully text-based** with no images, diagrams, or visual references
"""

# Load PDF content
def load_pdf_content(pdf_path: str) -> str:
    if not os.path.exists(pdf_path):
        raise FileNotFoundError("PDF file not found.")
    loader = PyPDFLoader(pdf_path)
    documents = loader.load()
    return "\n".join(doc.page_content for doc in documents)

def clean_but_keep_format(text: str) -> str:
    # Remove leading asterisks/dashes
    text = re.sub(r"^\s*[\*\-]\s*", "", text, flags=re.MULTILINE)

    # Optional: Convert bullet sections into numbered steps (optional)
    def number_steps(match):
        steps = match.group(0).strip().split("\n")
        return "\n".join([f"{i+1}. {s.strip()}" for i, s in enumerate(steps)])

    text = re.sub(r"(?<=\*\*Step \d:.*?\*\*)\n(.*(?:\n(?!\*\*).*)*)", number_steps, text, flags=re.MULTILINE)

    # Clean up extra spacing
    text = re.sub(r"\n{3,}", "\n\n", text)
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
    return clean_but_keep_format(result)

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
