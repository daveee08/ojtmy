from fastapi import FastAPI, Request, Form
from fastapi.responses import JSONResponse
from pydantic import BaseModel
from langchain_community.llms import Ollama
from langchain_core.prompts import ChatPromptTemplate
import os, sys, traceback
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

app = FastAPI()

# --- Prompt Template ---
twister_prompt = """
You are a master of creating tongue twisters.
Your job is to generate a short, fun, and challenging tongue twister that is difficult to say quickly and repeatedly.
It should use alliteration and wordplay, and be tricky to pronounce.
Do NOT just make a simple sentence—make it a real tongue twister!
Topic: {topic}
Grade Level: {grade_level}
Tongue Twister (no explanation, just the twister):
"""

model = Ollama(model="llama3")
prompt_template = ChatPromptTemplate.from_template(twister_prompt)

# In-memory session state (for demo)
twister_sessions = {}

class TwisterRequest(BaseModel):
    session_id: str
    topic: str = ""
    grade_level: str = "All Levels"

@app.post("/tonguetwister/start")
async def start_twister(req: TwisterRequest):
    prompt_input = {
        "topic": req.topic or "any",
        "grade_level": req.grade_level or "All Levels"
    }
    chain = prompt_template | model
    twister = chain.invoke(prompt_input).strip()
    twister_sessions[req.session_id] = {
        "twisters": [twister],
        "topic": req.topic,
        "grade_level": req.grade_level
    }
    return {"twister": twister}

@app.post("/tonguetwister/next")
async def next_twister(req: TwisterRequest):
    prompt_input = {
        "topic": req.topic or twister_sessions[req.session_id]["topic"] or "any",
        "grade_level": req.grade_level or twister_sessions[req.session_id]["grade_level"] or "All Levels"
    }
    chain = prompt_template | model
    twister = chain.invoke(prompt_input).strip()
    twister_sessions[req.session_id]["twisters"].append(twister)
    return {"twister": twister}

@app.post("/tonguetwister/history")
async def twister_history(req: TwisterRequest):
    session = twister_sessions.get(req.session_id, {})
    return {"twisters": session.get("twisters", [])}

@app.post("/tonguetwister")
async def generate_tongue_twister(
    topic: str = Form(...),
    grade_level: str = Form(...),
    user_id: int = Form(None)
):
    try:
        prompt_input = {
            "topic": topic,
            "grade_level": grade_level
        }
        chain = prompt_template | model
        twister = chain.invoke(prompt_input).strip()

        # Save to DB and get session_id
        scope_vars = {
            "topic": topic,
            "grade_level": grade_level
        }
        filled_prompt = twister_prompt.format(topic=topic, grade_level=grade_level)
        session_id = None
        if create_session_and_parameter_inputs:
            try:
                if create_session_and_parameter_inputs:
                    session_id = create_session_and_parameter_inputs(
                        user_id=user_id or 1,
                        agent_id=26,  # or your correct agent_id
                        scope_vars=scope_vars,
                        human_topic=topic,
                        ai_output=twister,
                        agent_prompt=filled_prompt
                    )
            except Exception as e:
                print(f"DB error: {e}")
                session_id = None

        return {"output": twister, "message_id": session_id}
    except Exception as e:
        return JSONResponse(status_code=500, content={"detail": str(e)})

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_methods=["*"],
    allow_headers=["*"],
)

if chat_router:
    app.include_router(chat_router)
