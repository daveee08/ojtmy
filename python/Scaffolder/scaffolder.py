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

prompt_template = """
You are an educational assistant.

Your task is to generate only **literal comprehension questions** and **vocabulary definitions** based on a given academic text. Your goal is to support accurate, age-appropriate understanding of the topic for {grade_level} students.

Parameters:
- Grade Level: {grade_level}
- Topic: {topic}
- Number of Vocabulary Words: {vocab_limit}
- Number of Literal Questions: {literal_questions}

Output Instructions:

1. Start with the heading **Vocabulary:** and list up to {vocab_limit} important words found in the text.
   - Each word must be followed by a short, clear definition suitable for {grade_level}.
   - Use simple, age-appropriate language. Avoid:
     - Technical jargon
     - Abstract phrasing
     - Circular or overly complex definitions

2. Then write the heading **Questions:** and list exactly {literal_questions} literal comprehension questions.
   - Questions must be based strictly on facts **explicitly stated** in the original text.
   - Avoid interpretation, inference, summarizing, or “Why/How” questions unless clearly supported by the text.
   - Phrase questions in a direct, concrete way that matches the reading level.

3. Use this exact format (and nothing else):

Vocabulary:
1. Word: Definition.
2. Word: Definition.

Questions:
1. Literal question?
2. Literal question?

Formatting Rules:
- Do **not** include any of the following:
  - Explanations, summaries, or extra commentary
  - Labels such as “Definition:”, “Term:”, or “Answer:”
  - Paragraphs or introductory text
  - Any text outside the required Vocabulary and Questions sections

Return only the formatted output. Do not add headings, titles, or instructional notes.
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

class ScaffolderInput(BaseModel):
    user_id: int
    input_type: str
    topic: str
    grade_level: str
    literal_questions: int
    vocab_limit: int
    message_id: Optional[str] = None

    @classmethod
    def as_form(
        cls,
        user_id: int = Form(...),
        input_type: str = Form(...),
        topic: str = Form(""),
        grade_level: str = Form(...),
        literal_questions: int = Form(...),
        vocab_limit: int = Form(...),
        message_id: Optional[str] = Form(default=None)
    ):
        return cls(
            user_id=user_id,
            input_type=input_type,
            topic=topic,
            grade_level=grade_level,
            literal_questions=literal_questions,
            vocab_limit=vocab_limit,
            message_id=message_id
        )

model = Ollama(model="gemma3:4b")
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
    literal_questions: int,
    vocab_limit: int,
    topic: str = "",
    pdf_file: UploadFile = None,
):
    if input_type == "pdf":
        with tempfile.NamedTemporaryFile(delete=False, suffix=".pdf") as tmp:
            content = await pdf_file.read()
            tmp.write(content)
            tmp_path = tmp.name

        topic = load_pdf_content(tmp_path)
        os.unlink(tmp_path)
    else:
        if not topic.strip():
            raise ValueError("Text input is required")

    prompt_input = {
        "topic": topic,
        "grade_level": grade_level,
        "literal_questions": literal_questions,
        "vocab_limit": vocab_limit
    }

    chain = prompt_template | model
    result = chain.invoke(prompt_input)
    return clean_output(result)

@app.post("/scaffolder")
async def scaffolder_api(
    form_data: ScaffolderInput = Depends(ScaffolderInput.as_form),
    pdf_file: UploadFile = File(None)
):
    try:
        if form_data.input_type == "pdf" and not pdf_file:
            raise HTTPException(status_code=400, detail="PDF file required for PDF input_type")

        output = await generate_output(
            input_type=form_data.input_type,
            topic=form_data.topic,
            grade_level=form_data.grade_level,
            literal_questions=form_data.literal_questions,
            vocab_limit=form_data.vocab_limit,
            pdf_file=pdf_file
        )

        scope_vars = {
            "grade_level": form_data.grade_level,
            "literal_questions": form_data.literal_questions,
            'vocab_limit': form_data.vocab_limit
        }

        filled_prompt = prompt_template.format(
            topic=form_data.topic.strip(),
            grade_level=form_data.grade_level.strip(),
            vocab_limit=form_data.vocab_limit.strip(),
            literal_questions=form_data.literal_questions.strip()
        )

        session_id = create_session_and_parameter_inputs(
            user_id=form_data.user_id,
            agent_id=9,
            scope_vars=scope_vars,
            human_topic=form_data.topic,
            ai_output=output,
            agent_prompt=filled_prompt
        )

        return {"output": output, "message_id": session_id}
    except Exception as e:
        traceback_str = traceback.format_exc()
        print(traceback_str)
        return JSONResponse(status_code=500, content={"detail": str(e), "trace": traceback_str})

if __name__ == "__main__":
    uvicorn.run("scaffolder_agent:app", host="127.0.0.1", port=5001, reload=True)
