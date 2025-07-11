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

from python.chat_router import chat_router
from python.db_utilss import create_session_and_parameter_inputs, insert_message

manual_topic_template = """
You are a precise and reliable rewriting tool. Your job is to Take any text and rewrite it with custom criteria.
Parameters:
- Topic: {topic}
- Custom Instruction: {custom_instruction}

Instructions:

1. **Rewriting Only**:
   - Rephrase the original input into the format specified by the user.
   - Do not summarize, explain, expand, or reduce the meaning.
   - Avoid commentary phrases such as:
     - “In other words…”
     - “To clarify…”
     - “This means that…”

2. **Follow Custom Instructions Exactly**:
   - **Follow the tone, structure, and format exactly** as specified by the user.
   - Match format precisely (e.g., **2 paragraphs**, **bullet points**, **100 words**, etc.).
   - Examples:
     - If told to write in formal tone, use formal diction and structure throughout.
     - If limited to 100 words or 2 paragraphs, your response must meet those **exactly** — not approximately.
     - If asked to use bullet points, headings, or a specific outline, replicate it perfectly.

3. **No Content Loss**:
   - Do not exclude or compress any idea, definition, example, claim, or explanation from the original input.
   - Every meaningful component — especially technical terms, timelines, processes, or conclusions — must remain intact, though reworded.

4. **Reword Thoughtfully**:
   - Use varied sentence structure, proper transitions, and correct grammar for clarity and flow.
   - Avoid redundancy, wordy phrasing, or awkward constructions — unless specifically instructed to mimic a style.

5. **Final Output Must Match the Format Exactly**:
   - Output should **match the structure and tone** exactly as the user specifies.
   - Do **not** include labels like "Rewritten version:", "Here is your output:", or any explanation.
   - Output only the final rewritten text — clean, complete, and ready to use.


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
     - “In other words…”
     - “To clarify…”
     - “This means that…”

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

app = FastAPI(debug=True)
app.include_router(chat_router)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # or Laravel origin like "http://localhost:8000"
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

class RewriterInput(BaseModel):
    user_id: int
    input_type: str
    topic: str
    custom_instruction: str
    message_id: Optional[str] = None

    @classmethod
    def as_form(
        cls,
        user_id: int = Form(...),
        input_type: str = Form(...),
        topic: str = Form(""),
        custom_instruction: str = Form(""),
        message_id: Optional[str] = Form(default=None)
    ):
        return cls(
            user_id=user_id,
            input_type=input_type,
            topic=topic,
            custom_instruction=custom_instruction,
            message_id=message_id
        )

model = Ollama(model="gemma:2b")
manual_prompt = ChatPromptTemplate.from_template(manual_topic_template)
pdf_prompt = ChatPromptTemplate.from_template(pdf_topic_template)

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
        "topic": topic,
        "custom_instruction": custom_instruction
    }

    chain = prompt | model
    result = chain.invoke(prompt_input)
    return clean_output(result)

@app.post("/rewriter")
async def rewriter_api(
    form_data: RewriterInput = Depends(RewriterInput.as_form),
    pdf_file: UploadFile = File(None)
):
    try:
        if form_data.input_type == "pdf" and not pdf_file:
            raise HTTPException(status_code=400, detail="PDF file required for PDF input_type")
        
        output = await generate_output(
            input_type=form_data.input_type,
            topic=form_data.topic,
            custom_instruction=form_data.custom_instruction,
            pdf_file=pdf_file
        )

        scope_vars = {
            "custom_instruction": form_data.custom_instruction
        }

        human_topic = form_data.topic if form_data.input_type != "pdf" else "[PDF Input]"

        session_id = create_session_and_parameter_inputs(
            user_id=form_data.user_id,
            agent_id=8,
            scope_vars=scope_vars,
            human_topic=human_topic,
            ai_output=output
        )
        
        return {"output": output, "message_id": session_id}
    except Exception as e:
        traceback_str = traceback.format_exc()
        print(traceback_str)
        return JSONResponse(status_code=500, content={"detail": str(e), "trace": traceback_str})

if __name__ == "__main__":
    uvicorn.run("rewriter_agent:app", host="127.0.0.1", port=5001, reload=True)