from fastapi import FastAPI, HTTPException, UploadFile, Form, File 
from fastapi.responses import JSONResponse 
from pydantic import BaseModel 
from langchain_community.llms import Ollama 
from langchain_core.prompts import ChatPromptTemplate 
from langchain_community.document_loaders.pdf import PyPDFLoader 
import os, re, tempfile, uvicorn

manual_topic_template = """
You are a precise and reliable rewriting tool. Your job is to rewrite the original input exactly according to the user's instructions — without adding, explaining, simplifying, or removing any key content.

Parameters:
- Topic: {topic}
- Custom Instruction: {custom_instruction}

Instructions:

1. **Rewriting Only**:
   - Rephrase the original input into the format specified by the user.
   - Do not summarize, explain, expand, or reduce the meaning.
   - Avoid commentary phrases such as:
     - "In other words…"
     - "To clarify…"
     - "This means that…"

2. **Follow Custom Instructions Exactly**:
   - Use the tone and structure requested (e.g., **formal**, **concise**, **friendly**).
   - Match format precisely (e.g., **2 paragraphs**, **bullet points**, **100 words**, etc.).
   - If a specific word count is given, meet it **exactly**.
   - If specific examples, vocabulary, or content types are required, integrate them as-is and **do not exclude any unless explicitly told to**.

3. **Preserve Every Important Detail**:
   - Rephrase all content, but **do not omit or simplify** technical terms, examples, explanations, relationships, or cause-effect descriptions from the original.
   - Every meaningful sentence, term, and claim in the original **must be present** in the rewritten version — even if reworded.
   - Do not skip anything that introduces new information, such as:
     - Definitions
     - Limitations
     - Historical context
     - Contributions to other fields
     - Technological impacts

4. **Clarity, Flow, and Redundancy**:
   - Ensure the rewrite reads smoothly and logically.
   - Avoid awkward repetition and overly complex structures (unless a formal tone is required).
   - Use natural transitions and sentence variety to improve readability.

**Final Output Rule**:
Return the rewritten version only. Do not include any labels, notes, headings, or commentary — just the clean, rewritten text.
"""



pdf_topic_template = """
You are a precise and reliable rewriting tool. Your job is to rewrite the original input exactly according to the user's instructions — without adding, explaining, simplifying, or removing any key content.

Parameters:
- Topic: {topic}
- Custom Instruction: {custom_instruction}

Instructions:

1. **Rewriting Only**:
   - Rephrase the original input into the format specified by the user.
   - Do not summarize, explain, expand, or reduce the meaning.
   - Avoid commentary phrases such as:
     - "In other words…"
     - "To clarify…"
     - "This means that…"

2. **Follow Custom Instructions Exactly**:
   - Use the tone and structure requested (e.g., **formal**, **concise**, **friendly**).
   - Match format precisely (e.g., **2 paragraphs**, **bullet points**, **100 words**, etc.).
   - If a specific word count is given, meet it **exactly**.
   - If specific examples, vocabulary, or content types are required, integrate them as-is and **do not exclude any unless explicitly told to**.

3. **Preserve Every Important Detail**:
   - Rephrase all content, but **do not omit or simplify** technical terms, examples, explanations, relationships, or cause-effect descriptions from the original.
   - Every meaningful sentence, term, and claim in the original **must be present** in the rewritten version — even if reworded.
   - Do not skip anything that introduces new information, such as:
     - Definitions
     - Limitations
     - Historical context
     - Contributions to other fields
     - Technological impacts

4. **Clarity, Flow, and Redundancy**:
   - Ensure the rewrite reads smoothly and logically.
   - Avoid awkward repetition and overly complex structures (unless a formal tone is required).
   - Use natural transitions and sentence variety to improve readability.

**Final Output Rule**:
Return the rewritten version only. Do not include any labels, notes, headings, or commentary — just the clean, rewritten text.
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
        return JSONResponse(status_code=500, content={"detail": str(e)})

if __name__ == "__main__":
    uvicorn.run("rewriter_agent:app", host="127.0.0.1", port=5001, reload=True)