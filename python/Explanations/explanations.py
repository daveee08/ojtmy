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
You are a knowledgeable and student-friendly virtual tutor.

You will receive either:
- A brand new concept to explain from scratch, or
- A follow-up question based on a previously explained concept

Your task is to respond clearly, accurately, and appropriately for the student's grade level. Always write with the student's level of understanding in mind.

Input:
Concept or Question: {concept}
Grade Level: {grade_level}

Guidelines:
- If this is a follow-up question (e.g. "Is this related to climate change?" or "Can you explain trade more?"), respond only to that part. Avoid repeating the original explanation unless necessary.
- If this is a new concept, provide a full explanation in a natural and flexible structure — not a fixed template.
- Break down key ideas using clear subheadings (e.g., Causes, Examples, Steps, etc.) when helpful. Use real-world analogies and comparisons when appropriate.
- Avoid using rigid section labels unless they naturally fit the topic.
- Make your explanation engaging, structured, and focused. Do not repeat the concept in the first sentence — get straight into explaining it.

Grade Adaptation:
- Grades 1-3: Use very simple language and examples from everyday life.
- Grades 4-5: Introduce light structure and familiar scenarios with simple logic.
- Grades 6-8: Use clear explanations, school-related analogies, and logical flow.
- Grades 9-10: Use more advanced vocabulary, organized reasoning, and step-based explanations.
- Grades 11-12: Use technical vocabulary, relevant examples, and accurate explanations.

Output:
Respond with a well-organized, conversational, and clearly structured explanation. Focus on clarity, relevance, and student comprehension.
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

class ExplanationsInput(BaseModel):
    user_id: int
    input_type: str
    concept: str
    grade_level: str
    message_id: Optional[str] = None

    @classmethod
    def as_form(
        cls,
        user_id: int = Form(...),
        input_type: str = Form(...),
        concept: str = Form(""),
        grade_level: str = Form(...),
        message_id: Optional[str] = Form(default=None)
    ):
        return cls(
            user_id=user_id,
            input_type=input_type,
            concept=concept,
            grade_level=grade_level,
            message_id=message_id
        )

model = Ollama(model="gemma:2b")
prompt_template = ChatPromptTemplate.from_template(prompt_template)

def load_pdf_content(pdf_path: str) -> str:
    if not os.path.exists(pdf_path):
        raise FileNotFoundError("PDF file not found.")
    loader = PyPDFLoader(pdf_path)
    documents = loader.load()
    return "\n".join(doc.page_content for doc in documents)

def clean_output(text: str) -> str:
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
    else:
        if not concept.strip():
            raise ValueError("Text input is required")
    # Compose input dict for prompt
    prompt_input = {
        "concept": concept,
        "grade_level": grade_level
    }
    
    chain = prompt_template | model
    result = chain.invoke(prompt_input)
    return clean_output(result)

@app.post("/explanations")
async def explanations_api(
    form_data: ExplanationsInput = Depends(ExplanationsInput.as_form),
    pdf_file: UploadFile = File(None)
):
    
    try:
        if form_data.input_type == "pdf" and not pdf_file:
            raise HTTPException(status_code=400, detail="PDF file required for PDF input_type")

        output = await generate_output(
            input_type=form_data.input_type,
            concept=form_data.concept,
            grade_level=form_data.grade_level,
            pdf_file=pdf_file,
        )

        scope_vars = {
            "grade_level": form_data.grade_level 
        }

        filled_prompt = prompt_template.format(
            concept=form_data.concept.strip(),
            grade_level=form_data.grade_level.strip()
        )

        session_id = create_session_and_parameter_inputs(
            user_id=form_data.user_id,
            agent_id=10,
            scope_vars=scope_vars,
            human_topic=form_data.concept,
            ai_output=output,
            agent_prompt=filled_prompt
        )

        return {"output": output, "message_id": session_id}
    except Exception as e:
        traceback_str = traceback.format_exc()
        print(traceback_str)
        return JSONResponse(status_code=500, content={"detail": str(e), "trace": traceback_str})

if __name__ == "__main__":
    uvicorn.run("explanations:app", host="127.0.0.1", port=5001, reload=True)

# original