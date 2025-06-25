from fastapi import FastAPI, HTTPException, UploadFile, Form, File # type: ignore
from fastapi.responses import JSONResponse # type: ignore
from pydantic import BaseModel, ValidationError # type: ignore
from langchain_community.llms import Ollama # type: ignore
from langchain_core.prompts import ChatPromptTemplate # type: ignore
from langchain_community.document_loaders.pdf import PyPDFLoader # type: ignore
import shutil, os, re, tempfile, uvicorn, traceback # type: ignore

manual_topic_template = """
You are an informative and student-friendly virtual tutor. Your job is to explain informational topics clearly and appropriately for a student's grade level.

Parameters:
- Grade Level: {grade_level}
- Detail Level: {text_length} (e.g., one_paragraph, one_page, etc.)
- Informational Text Type: {text_type}
- Topic: {topic}

Instructions:
- Adjust the explanation based on the length setting:
  - "one_paragraph" → concise summary of key ideas.
  - "one_page" → moderately detailed with 1–2 examples.
  - "two_page" → well-developed explanation with structure.
  - "three_page" → comprehensive, slow-paced, with examples, breakdowns, and clarity.
- Match the tone and format to the informational text type:
  - "literary" → tell real events or facts with narrative clarity.
  - "expository" → explain facts, concepts, and relationships clearly.
  - "argumentive" → show claims with supporting reasons and evidence.
  - "procedural" → guide the learner step-by-step through a process.
- Use age-appropriate language and examples.
- Organize the output with headings or bullet points if helpful.
- Avoid summarizing — explain the topic in full.

Respond ONLY with the explanation.
"""

pdf_topic_template = """
You are a knowledgeable virtual tutor who explains content extracted from documents in a way suitable for the student's learning level.

Parameters:
- Grade Level: {grade_level}
- Detail Level: {text_length} (e.g., one_paragraph, one_page, etc.)
- Informational Text Type: {text_type}
- Extracted Content: {topic}

Instructions:
- Analyze and explain the extracted content according to:
  - "one_paragraph" → short but meaningful explanation.
  - "one_page" → standard explanation with clarity.
  - "two_page" → expanded content with structure and depth.
  - "three_page" → highly detailed, in-depth explanation with full breakdowns.
- Adapt explanation style to the type of text:
  - "literary" → describe people/events with narrative clarity.
  - "expository" → inform and explain logically.
  - "argumentive" → outline claims, reasons, and evidence.
  - "procedural" → explain in a step-by-step instructional format.
- Avoid summarizing — provide a full, student-friendly explanation.
- Keep the tone clear, objective, and instructional.

Respond ONLY with the structured explanation.
"""

model = Ollama(model="llama3")
manual_prompt = ChatPromptTemplate.from_template(manual_topic_template)
pdf_prompt = ChatPromptTemplate.from_template(pdf_topic_template)

class InformationalInput(BaseModel):
    input_type: str
    topic: str = ""
    pdf_path: str = ""
    grade_level: str
    text_length: str
    text_type: str

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
    text_length: str,
    text_type: str,
    topic: str = "",
    pdf_file: UploadFile = None,
):
    if input_type == "pdf":
        # Save PDF temporarily
        with tempfile.NamedTemporaryFile(delete=False, suffix=".pdf") as tmp:
            content = await pdf_file.read()
            tmp.write(content)
            tmp_path = tmp.name

        topic = load_pdf_content(tmp_path)
        os.unlink(tmp_path)  # Delete file after use
        prompt = pdf_prompt
    else:
        if not topic.strip():
            raise ValueError("Text input is required")
        prompt = manual_prompt

    # Compose input dict for prompt
    prompt_input = {
    "topic": topic,
    "grade_level": grade_level,
    "text_length": text_length,
    "text_type": text_type
    }
    
    chain = prompt | model
    result = chain.invoke(prompt_input)
    return clean_output(result)

app = FastAPI()

@app.post("/informational")
async def informational_api(
    input_type: str = Form(...),
    topic: str = Form(""),
    pdf_file: UploadFile = File(None),
    grade_level: str = Form(...),
    text_length: str = Form(...),
    text_type: str = Form(...),
):
    
    try:
        if input_type == "pdf" and not pdf_file:
            raise HTTPException(status_code=400, detail="PDF file required for PDF input_type")

        output = await generate_output(
            input_type=input_type,
            topic=topic,
            pdf_file=pdf_file,
            grade_level=grade_level,
            text_length=text_length,
            text_type=text_type,
        )

        return {"output": output}
    except Exception as e:
        traceback_str = traceback.format_exc()
        print(traceback_str)
        return JSONResponse(status_code=500, content={"detail": str(e), "trace": traceback_str})

if __name__ == "__main__":
    uvicorn.run("informational_agent:app", host="127.0.0.1", port=5001, reload=True)