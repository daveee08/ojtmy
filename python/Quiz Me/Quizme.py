from fastapi import FastAPI, HTTPException, UploadFile, Form, File, Depends
from fastapi.responses import JSONResponse
from pydantic import BaseModel
from langchain_community.llms import Ollama
from langchain_core.prompts import ChatPromptTemplate
import os, sys, traceback, uvicorn
from typing import Optional
from langchain_core.messages import HumanMessage, AIMessage
from fastapi.middleware.cors import CORSMiddleware

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
            You are an Expert helpful AI assistant that creates quizzes.
            Your strict instruction is to generate **exactly** {num_questions} multiple-choice questions.
            All questions MUST be solely about the topic: **{topic}**.
            The difficulty of the questions MUST be appropriate for a {grade_level} student.
            DO NOT include any introductory sentences, conversational text, or extraneous information before the quiz questions or after the last question. Start directly with "Question 1:".
            **Crucially, ensure there is only ONE set of options (A, B, C, D) for each question, appearing immediately after the question text, and DO NOT include a separate "Options:" header before them.**

            Each question must have 4 options (A, B, C, D).
            Format the output precisely as follows (ensure you provide **exactly** {num_questions} questions, no more, no less):

            Question 1: [Question text about {topic}]
            A) [Option A]
            B) [Option B]
            C) [Option C]
            D) [Option D]

            Question 2: [Question text about {topic}]
            A) [Option A]
            B) [Option B]
            C) [Option C]
            D) [Option D]
            ... (continue for {num_questions} questions)
            """

# --- FastAPI App Initialization ---
app = FastAPI(debug=True)
if chat_router:
    app.include_router(chat_router)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
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
    message_id: Optional[str] = None

    @classmethod
    def as_form(
        cls,
        user_id: int = Form(...),
        input_type: str = Form(...),
        topic: str = Form(...),
        grade_level: str = Form(...),
        num_questions: int = Form(...),
        message_id: Optional[str] = Form(default=None)
    ):
        return cls(
            user_id=user_id,
            input_type=input_type,
            topic=topic,
            grade_level=grade_level,
            num_questions=num_questions,
            message_id=message_id
        )

# --- LangChain Setup ---
model = Ollama(model="llama3")
prompt_template = ChatPromptTemplate.from_template(quizme_prompt)

# --- Output Cleaner ---
def clean_output(text: str) -> str:
    return text.strip()

# --- Main Output Generation Function ---
async def generate_output(
    input_type: str,
    topic: str,
    grade_level: str,
    num_questions: int,
):
    if not topic.strip() or not grade_level.strip() or not num_questions:
        raise ValueError("Topic, grade level, and number of questions are required.")

    prompt_input = {
        "topic": topic,
        "grade_level": grade_level,
        "num_questions": num_questions
    }

    chain = prompt_template | model
    result = chain.invoke(prompt_input)
    return clean_output(result)

@app.post("/quizme")
async def quizme_api(
    form_data: QuizMeFormInput = Depends(QuizMeFormInput.as_form)
):
    try:
        output = await generate_output(
            input_type=form_data.input_type,
            topic=form_data.topic,
            grade_level=form_data.grade_level,
            num_questions=form_data.num_questions,
        )

        scope_vars = {
            "grade_level": form_data.grade_level,
            "topic": form_data.topic,
            "num_questions": form_data.num_questions,
        }

        filled_prompt = quizme_prompt.format(
            topic=form_data.topic.strip(),
            grade_level=form_data.grade_level.strip(),
            num_questions=form_data.num_questions
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
        print(traceback_str)
        return JSONResponse(status_code=500, content={"detail": str(e), "trace": traceback_str})

if __name__ == "__main__":
    uvicorn.run("Quizme:app", host="127.0.0.1", port=5004, reload=True)
