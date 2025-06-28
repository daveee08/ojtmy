from fastapi import FastAPI, HTTPException, UploadFile, Form, File 
from fastapi.responses import JSONResponse 
from pydantic import BaseModel, ValidationError 
from langchain_community.llms import Ollama 
from langchain_core.prompts import ChatPromptTemplate 
from langchain_community.document_loaders.pdf import PyPDFLoader 
import shutil, os, re, tempfile, uvicorn, traceback 

manual_topic_template = """
You are an experienced and friendly virtual tutor who helps students understand academic topics clearly and effectively.

Your goal is to explain the topic in a way that matches the student's needs.

Parameters:
- Custom Instruction: {custom_instruction}
- Topic: {topic}

Instructions:
- Focus on clarity, accuracy, and readability.
- Do not include introductions, summaries, explanations of the rewrite, or phrases like:
 - “Here’s a version that…”
 - “To understand this…”
 - “This means that…”
 - “In simpler terms…”
- Rewrite to improve understanding, using simpler words or clearer sentence structure.
- Maintain the original meaning and facts.
- Follow any custom preferences such as:
 - Tone (e.g., neutral, academic, conversational)
 - Formatting (bullets, numbered lists, short paragraphs)
- Ensure smooth logical flow from idea to idea.
- Avoid repetition, fluff, or jargon unless the input or user specifically calls for it.

Respond ONLY with the explanation text (no extra commentary).
"""

pdf_topic_template = """
You are a knowledgeable and supportive virtual tutor.

You will receive content extracted from a textbook or document (such as a PDF). Your task is to explain this content in a way that is understandable to a student at the given grade level.

Parameters:
- Custom Instruction: {custom_instruction}
- Extracted Content: {topic}

Instructions:
- Rewrite the content to build real understanding.
- Follow any custom preferences provided by the user (e.g., tone, vocabulary, examples, bullet forms, numbered lists).
- Use alternative words, simpler phrasing, or clearer sentence structure if requested.
- Ensure logical flow — each idea should connect naturally to the next.
- Avoid unnecessary repetition, fluff, or jargon unless its part of the instruction.

Respond ONLY with the explanation text (no extra commentary).
"""

model = Ollama(model="llama3")
manual_prompt = ChatPromptTemplate.from_template(manual_topic_template)
pdf_prompt = ChatPromptTemplate.from_template(pdf_topic_template)

class RewriterInput(BaseModel):
    input_type: str
    topic: str = ""
    pdf_path: str = ""
    custom_instruction: str = ""

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
    custom_instruction: str,
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
    else: # This 'else' block will catch anything not 'pdf'
        if not topic.strip():
            raise ValueError("Text input is required")
        prompt = manual_prompt

    # Compose input dict for prompt
    prompt_input = {
        "custom_instruction": custom_instruction,
        "topic": topic
    }

    chain = prompt | model
    result = chain.invoke(prompt_input)
    return clean_output(result)

app = FastAPI()

@app.post("/rewriter")
async def rewriter_api(
    input_type: str = Form(...),
    topic: str = Form(""),
    pdf_file: UploadFile = File(None),
    custom_instruction: str = Form(...),
):
    try:
        if input_type == "pdf" and not pdf_file:
            raise HTTPException(status_code=400, detail="PDF file required for PDF input_type")
        
        output = await generate_output(
            input_type=input_type,
            topic=topic,
            pdf_file=pdf_file,
            custom_instruction=custom_instruction
        )
        
        return {"output": output}
    except Exception as e:
        traceback_str = traceback.format_exc()
        print(traceback_str)
        return JSONResponse(status_code=500, content={"detail": str(e), "trace": traceback_str})

if __name__ == "__main__":
    uvicorn.run("rewriter_agent:app", host="127.0.0.1", port=5001, reload=True)