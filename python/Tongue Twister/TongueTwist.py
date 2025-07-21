from fastapi import FastAPI, Request
from fastapi.responses import JSONResponse
from pydantic import BaseModel
from langchain_community.llms import Ollama
from langchain_core.prompts import ChatPromptTemplate
import os, sys, traceback
from fastapi.middleware.cors import CORSMiddleware

app = FastAPI()

# --- Prompt Template ---
twister_prompt = """
You are a creative AI that generates fun, challenging tongue twisters.
Topic: {topic}
Difficulty: {difficulty}
Grade Level: {grade_level}
Generate a unique tongue twister. Do not add explanations.
"""

model = Ollama(model="llama3")
prompt_template = ChatPromptTemplate.from_template(twister_prompt)

# In-memory session state (for demo)
twister_sessions = {}

class TwisterRequest(BaseModel):
    session_id: str
    topic: str = ""
    difficulty: str = "medium"
    grade_level: str = "All Levels"

@app.post("/tonguetwister/start")
async def start_twister(req: TwisterRequest):
    prompt_input = {
        "topic": req.topic or "any",
        "difficulty": req.difficulty or "medium",
        "grade_level": req.grade_level or "All Levels"
    }
    chain = prompt_template | model
    twister = chain.invoke(prompt_input).strip()
    twister_sessions[req.session_id] = {
        "twisters": [twister],
        "topic": req.topic,
        "difficulty": req.difficulty,
        "grade_level": req.grade_level
    }
    return {"twister": twister}

@app.post("/tonguetwister/next")
async def next_twister(req: TwisterRequest):
    prompt_input = {
        "topic": req.topic or twister_sessions[req.session_id]["topic"] or "any",
        "difficulty": req.difficulty or twister_sessions[req.session_id]["difficulty"] or "medium",
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

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_methods=["*"],
    allow_headers=["*"],
)
