from fastapi import FastAPI, Form, Depends, Request
from fastapi.responses import JSONResponse
from pydantic import BaseModel
from typing import Optional
from fastapi.middleware.cors import CORSMiddleware
import traceback, os, sys

# --- Path Setup ---
current_script_dir = os.path.dirname(os.path.abspath(__file__))
project_root = os.path.join(current_script_dir, '..', '..')
sys.path.insert(0, project_root)

# --- Optional DB Utilities ---
try:
    from python.chat_router_final import chat_router
    from python.db_utils_final import create_session_and_parameter_inputs, insert_message
except ImportError:
    chat_router = None
    create_session_and_parameter_inputs = None
    insert_message = None

# --- Prompt Template ---
tongue_twister_prompt = """
You are a master of creating tongue twisters.
Your job is to generate a short, fun, and challenging tongue twister that is difficult to say quickly and repeatedly.
It should use alliteration and wordplay, and be tricky to pronounce.
Do NOT just make a simple sentence—make it a real tongue twister!
Topic: {topic}
Grade Level: {grade_level}
Tongue Twister (no explanation, just the twister):
"""

# --- LangChain Setup ---
try:
    from langchain_ollama import OllamaLLM as Ollama
except ImportError:
    from langchain_community.llms import Ollama  # fallback for dev
from langchain_core.prompts import ChatPromptTemplate

model = Ollama(model="llama3")
prompt_template = ChatPromptTemplate.from_template(tongue_twister_prompt)

# --- FastAPI Setup ---
app = FastAPI(debug=True)

if chat_router:
    app.include_router(chat_router)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_methods=["*"],
    allow_headers=["*"],
)

# --- Input Model ---
class TongueTwisterFormInput(BaseModel):
    topic: str
    grade_level: str
    user_id: Optional[int] = 1
    session_id: Optional[str] = None

    @classmethod
    def as_form(
        cls,
        topic: str = Form(...),
        grade_level: str = Form(...),
        user_id: Optional[int] = Form(default=1),
        session_id: Optional[str] = Form(default=None)
    ):
        return cls(
            topic=topic,
            grade_level=grade_level,
            user_id=user_id,
            session_id=session_id
        )

# --- Generate Twister Core Logic ---
async def generate_twister(topic: str, grade_level: str) -> str:
    prompt_input = {"topic": topic, "grade_level": grade_level}
    chain = prompt_template | model
    result = chain.invoke(prompt_input)
    return result.strip()

# --- Main Endpoint ---
@app.post("/tonguetwister")
async def tonguetwister_api(form_data: TongueTwisterFormInput = Depends(TongueTwisterFormInput.as_form)):
    try:
        print("🎯 Received form:", form_data.dict())

        output = await generate_twister(
            topic=form_data.topic,
            grade_level=form_data.grade_level
        )

        if not output:
            raise Exception("⚠️ No output returned by model")

        print("✅ Generated Twister:", output)

        filled_prompt = tongue_twister_prompt.format(
            topic=form_data.topic,
            grade_level=form_data.grade_level
        )

        session_id = form_data.session_id
        if create_session_and_parameter_inputs:
            try:
                session_id = create_session_and_parameter_inputs(
                    user_id=form_data.user_id or 1,
                    agent_id=26,
                    scope_vars={
                        "topic": form_data.topic,
                        "grade_level": form_data.grade_level
                    },
                    human_topic=form_data.topic,
                    ai_output=output,
                    agent_prompt=filled_prompt
                )
            except Exception as db_error:
                print("❌ DB Error:", db_error)

        if insert_message and session_id:
            try:
                insert_message(
                    session_id=session_id,
                    role="assistant",
                    content=output
                )
            except Exception as msg_error:
                print("⚠️ Message insert failed:", msg_error)

        return {
            "output": output,
            "message_id": session_id,
            "session_id": session_id
        }

    except Exception as e:
        traceback_str = traceback.format_exc()
        print("❌ Main Error:", traceback_str)
        return JSONResponse(status_code=500, content={
            "detail": str(e),
            "trace": traceback_str
        })