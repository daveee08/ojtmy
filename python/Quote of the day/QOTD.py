from fastapi import FastAPI, Request, Form
from fastapi.responses import JSONResponse, StreamingResponse
from pydantic import BaseModel
import re, os, sys, traceback
from fpdf import FPDF
from io import BytesIO
from langchain_community.llms import Ollama
from langchain_core.prompts import ChatPromptTemplate
from fastapi.middleware.cors import CORSMiddleware


# path setup
current_script_dir = os.path.dirname(os.path.abspath(__file__))
project_root = os.path.join(current_script_dir, '..', '..') # Adjust as necessary
sys.path.insert(0, project_root)

from typing import Optional, Dict, Any, List
from python.chat_router_final import chat_router
from python.db_utils_final import create_session_and_parameter_inputs, insert_message

#Add the python/ directory to sys.path
sys.path.append(os.path.abspath(os.path.join(os.path.dirname(__file__), "..")))

# FastAPI app initialization
app = FastAPI(debug=True)
app.include_router(chat_router)  # Assuming chat_router is defined in your project

class QOTDRequest(BaseModel):
    session_id: str
    topic: str
    grade_level: str
    user_id: Optional[int] = None  # Optional for simplicity if not authenticating=
# Add CORS middleware
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_methods=["*"],
    allow_headers=["*"],
)

# --- Pydantic Models ---
class QOTDRequest(BaseModel):
    topic: str
    grade_level: str
    user_id: Optional[int] = None # Optional for simplicity if not authenticating

class QOTDResponse(BaseModel):
    QOTD: str
    message_id: Optional[int] = None # Optional as it might not be saved to DB always

class PdfRequest(BaseModel):
    content: str
    filename: str

class MessageDetail(BaseModel):
    id: int # Corresponds to message_id/session_id
    user_id: int
    session_id: int
    message_title: Optional[str] = None
    user_message: str
    ai_message: str
    timestamp: str

class GetMessageDetailsRequest(BaseModel):
    message_id: int

class UserSessionTitle(BaseModel):
    session_id: int
    latest_message_title: str
    latest_message_id: int

class GetAllUserSessionsRequest(BaseModel):
    user_id: int

# Helper function to clean AI output
def _clean_ai_output(text: str) -> str:
    cleaned = text.strip()
    patterns = [
        r"^Sure,\s*here\'?s\s*a\s*QOTD:?\s*", r"^Here\'?s\s*a\s*QOTD:?\s*",
        r"^QOTD:?\s*", r"^Alright,\s*here\'?s\s*a\s*QOTD:?\s*"
    ]
    for pat in patterns:
        cleaned = re.sub(pat, '', cleaned, flags=re.IGNORECASE)
    cleaned = cleaned.replace('**', '').replace('##', '')
    cleaned = re.sub(r'\n\n.*?\[.*\]\n*$', '', cleaned, flags=re.IGNORECASE|re.DOTALL)
    return cleaned.strip()

# --- QOTD Prompt Template ---
qotd_prompt = """You are a QOTD (Quote of the Day) generator.
Generate exactly *one* QOTD suitable for a {grade_level} class.
Make it inspirational, thought-provoking, and relevant to the topic.
Topic: {topic}
Grade Level: {grade_level}
Return Only QOTD text, no intros or formatting."""

model = Ollama(model="llama3")
prompt_template = ChatPromptTemplate.from_template(qotd_prompt)

# In-memory session state (for demo)
qotd_sessions = {}

class QOTDRequest(BaseModel):
    session_id: str
    topic: str = ""
    grade_level: str = "All Levels"

# Prompt template - updated to use 'topic'
@app.post("/qotd/start")
async def generate_qotd(req: QOTDRequest):
    prompt_input = {
        "topic": req.topic or "any",
        "grade_level": req.grade_level or "All Levels"
    }
    chain = prompt_template | model
    qotd = chain.invoke(prompt_input).strip()
    qotd_sessions[req.session_id] = {
        "qotds": [qotd],
        "topic": req.topic,
            "grade_level": req.grade_level
    }
    return {"qotd": qotd}

@app.post("/qotd/next")
async def next_qotd(req: QOTDRequest):
    prompt_input = {
        "topic": req.topic or qotd_sessions[req.session_id]["topic"] or "any",
        "grade_level": req.grade_level or qotd_sessions[req.session_id]["grade_level"] or "All Levels"
    }
    chain = qotd_prompt | model
    qotd = chain.invoke(prompt_input).strip()
    qotd_sessions[req.session_id] = {
    }
    return {"qotd": qotd}
    
@app.post("/qotd/history")
async def qotd_history(req: QOTDRequest):
    session = qotd_sessions.get(req.session_id, {})
    return {"qotds": session.get("qotds", [])}
    
@app.post("/qotd")
async def generate_qotd(
    topic: str = Form(...),
    grade_level: str = Form(...),
    user_id: int = Form(...),
): 
    try:
        prompt_input = {
            "topic": topic,
            "grade_level": grade_level
        }
        chain = prompt_template | model
        qotd = chain.invoke(prompt_input).strip()
    
        #save to db and get session_id
        scope_vars = {
            "topic": topic,
            "grade_level": grade_level
        }
        filled_prompt = qotd_prompt.format(topic=topic, grade_level=grade_level)
        session_id = None
        if create_session_and_parameter_inputs:
           try:
               if create_session_and_parameter_inputs:
                   user_id_to_use = user_id if user_id is not None else 1
                   scope_vars = {
                       "topic": topic.strip(),
                       "grade_level": grade_level.strip()
                   }
                   filled_prompt = qotd_prompt.format(
                       topic=topic.strip(), 
                       grade_level=grade_level.strip()
                   )
                   session_id = create_session_and_parameter_inputs(
                       user_id=user_id_to_use,
                       agent_id=26,  # or your correct agent_id
                       scope_vars=scope_vars,
                       human_topic=topic.strip(),
                       ai_output=qotd,
                       agent_prompt=filled_prompt
                   )
                   print("message_id :", session_id)
                   cleaned_QOTD = _clean_ai_output(qotd)
                   return JSONResponse(content={
                       "qotd": cleaned_QOTD,
                       "message_id": session_id
                   }, headers={"Content-Type": "application/json"})
           except Exception as e:
               print(f"DB error: {e}")
        session_id = None

        cleaned_QOTD = _clean_ai_output(qotd)
        return {"output": cleaned_QOTD, "message_id": session_id}
    except Exception as e:
        return JSONResponse(status_code=500, content={"detail": str(e), "trace": traceback.format_exc()})
    # The following code was outside any function and caused a syntax error, so it has been removed.

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_methods=["*"],
    allow_headers=["*"],
)