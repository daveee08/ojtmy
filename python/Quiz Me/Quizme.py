from fastapi import FastAPI, HTTPException, UploadFile, Form, File, Depends, Request
from fastapi.responses import JSONResponse, StreamingResponse
from pydantic import BaseModel
from langchain_community.llms import Ollama
from langchain_core.prompts import ChatPromptTemplate
import os, sys, traceback, uvicorn
from typing import Optional
from langchain_core.messages import HumanMessage, AIMessage
from fastapi.middleware.cors import CORSMiddleware
import re
from io import BytesIO
from reportlab.lib.pagesizes import letter
from reportlab.pdfgen import canvas
from reportlab.lib.utils import simpleSplit

current_script_dir = os.path.dirname(os.path.abspath(__file__))
project_root = os.path.join(current_script_dir, '..', '..')
sys.path.insert(0, project_root)

try:
    from python.chat_router_final import chat_router
    from python.db_utils_final import create_session_and_parameter_inputs, insert_message
except ImportError:
    chat_router = None
    create_session_and_parameter_inputs = None
    insert_message = None

# --- Prompt Template ---
quizme_prompt = """
You are a strict, professional quiz generator AI.
Generate exactly {num_questions} questions about "{topic}".
Question types: {question_types}.
Do not add any extra text or explanations.
"""

# --- FastAPI App Initialization ---
app = FastAPI(debug=True)
if chat_router:
    app.include_router(chat_router)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    #allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# --- Pydantic Model for Form Input ---
class QuizMeFormInput(BaseModel):
    user_id: int
    input_type: str
    topic: str
    grade_level: str
    num_questions: int
    question_types: str  # <-- add this
    message_id: Optional[str] = None

    @classmethod
    def as_form(
        cls,
        user_id: int = Form(...),
        input_type: str = Form(...),
        topic: str = Form(...),
        grade_level: str = Form(...),
        num_questions: int = Form(...),
        question_types: str = Form(...),  # <-- add this
        message_id: Optional[str] = Form(default=None)
    ):
        return cls(
            user_id=user_id,
            input_type=input_type,
            topic=topic,
            grade_level=grade_level,
            num_questions=num_questions,
            question_types=question_types,  # <-- add this
            message_id=message_id
        )

# --- LangChain Setup ---
model = Ollama(model="llama3")
prompt_template = ChatPromptTemplate.from_template(quizme_prompt)

# --- Output Cleaner ---
def clean_output(text: str) -> str:
    return text.strip()

def format_quiz_output(text: str) -> str:
    # Remove markdown bullets and pluses, normalize newlines
    text = re.sub(r'^\s*[\*\+]\s*', '', text, flags=re.MULTILINE)
    text = re.sub(r'[_\*]', '', text)
    text = re.sub(r'\n{2,}', '\n\n', text)
    return text.strip()

# --- Main Output Generation Function ---
async def generate_output(
    input_type: str,
    topic: str,
    grade_level: str,
    num_questions: int,
    question_types: str,  # <-- add this
):
    prompt_input = {
        "topic": topic,
        "grade_level": grade_level,
        "num_questions": num_questions,
        "question_types": question_types,  # <-- add this
    }
    chain = prompt_template | model
    result = chain.invoke(prompt_input)
    return clean_output(result)

@app.post("/quizme")
async def quizme_api(
    form_data: QuizMeFormInput = Depends(QuizMeFormInput.as_form)
):
    try:
        print("Received form data:", form_data)
        output = await generate_output(
            input_type=form_data.input_type,
            topic=form_data.topic,
            grade_level=form_data.grade_level,
            num_questions=form_data.num_questions,
            question_types=form_data.question_types,
        )
        print("Generated output:", output)
        output = format_quiz_output(output)

        scope_vars = {
            "grade_level": form_data.grade_level,
            "topic": form_data.topic,
            "num_questions": form_data.num_questions,
        }

        filled_prompt = quizme_prompt.format(
            topic=form_data.topic.strip(),
            grade_level=form_data.grade_level.strip(),
            num_questions=form_data.num_questions,
            question_types=form_data.question_types
        )

        session_id = None
        if create_session_and_parameter_inputs:
            session_id = create_session_and_parameter_inputs(
                user_id=form_data.user_id,
                agent_id=7,  # Use a unique agent_id for Quiz Me
                scope_vars=scope_vars,
                human_topic=form_data.topic,
                ai_output=output,
                agent_prompt=filled_prompt
            )

        return {"output": output, "message_id": session_id}
    except Exception as e:
        traceback_str = traceback.format_exc()
        print("ERROR:", traceback_str)
        return JSONResponse(status_code=500, content={"detail": str(e), "trace": traceback_str})

# In-memory quiz state for demo (replace with DB in production)
quiz_sessions = {}

@app.post("/quizme/start")
async def start_quiz(request: Request):
    data = await request.json()
    session_id = str(data["session_id"])
    questions = data["questions"]  # [{"question": "...", "options": [...], "answer": "A"}, ...]
    quiz_sessions[session_id] = {
        "questions": questions,
        "current": 0,
        "answers": [],
        "revealed": False
    }
    q = questions[0]
    return {"question": q["question"], "options": q["options"], "index": 0}

@app.post("/quizme/answer")
async def answer_question(request: Request):
    data = await request.json()
    session_id = str(data["session_id"])
    user_answer = data["answer"].strip().upper()
    state = quiz_sessions[session_id]
    q_idx = state["current"]
    question = state["questions"][q_idx]
    correct = (user_answer == question["answer"].strip().upper())
    state["answers"].append({"user": user_answer, "correct": correct, "right": question["answer"]})
    feedback = "✅ Correct!" if correct else f"❌ Incorrect. The correct answer is {question['answer']}."
    state["current"] += 1
    if state["current"] < len(state["questions"]):
        next_q = state["questions"][state["current"]]
        return {
            "feedback": feedback,
            "next_question": next_q["question"],
            "options": next_q["options"],
            "done": False,
            "index": state["current"]
        }
        else:
        return {
            "feedback": feedback,
            "done": True,
            "summary": state["answers"]
        }

@app.post("/quizme/reveal-answers")
async def reveal_answers(request: Request):
    data = await request.json()
    session_id = str(data["session_id"])
    state = quiz_sessions.get(session_id)
    if not state:
        return JSONResponse(status_code=404, content={"error": "Session not found"})
    state["revealed"] = True
    answers = [{"question": q["question"], "answer": q["answer"]} for q in state["questions"]]
    return {"answers": answers}
