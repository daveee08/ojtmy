from fastapi import FastAPI, Form, Depends, HTTPException, Request
from fastapi.responses import JSONResponse, StreamingResponse
from pydantic import BaseModel, ConfigDict # ConfigDict is for Pydantic v2+, BaseModel is enough for v1
from typing import Optional, Dict, Any, List # Import List for type hinting
import re, os, sys, traceback
from fpdf import FPDF
from io import BytesIO
from datetime import datetime # Import datetime for timestamps

# Import CORS middleware for handling cross-origin requests
from fastapi.middleware.cors import CORSMiddleware

# path setup
current_script_dir = os.path.dirname(os.path.abspath(__file__))
project_root = os.path.join(current_script_dir, '..', '..') # Adjust as necessary
sys.path.insert(0, project_root)

# optional db utilities
# try:
#     # Assuming chat_router_final contains endpoints like /chat/history and db_utils_final handles actual DB ops
#     from python.chat_router_final import chat_router
#     from python.db_utils_final import create_session_and_parameter_inputs, get_session_details, insert_message, get_agent_sessions # Assuming get_agent_sessions is the new function
# except ImportError:
#     chat_router = None
#     create_session_and_parameter_inputs = None
#     get_session_details = None
#     insert_message = None
#     get_agent_sessions = None # Set this to None too
#     print("Warning: chat_router_final or db_utils_final not found. Session management and chat history features will be disabled.")
# Assuming chat_router_final contains endpoints like /chat/history and db_utils_final handles actual DB ops
from python.chat_router_final import chat_router
from python.db_utils_final import create_session_and_parameter_inputs,  insert_message # Assuming get_agent_sessions is the new function
# fastapi
app = FastAPI(debug=True)
if chat_router:
    app.include_router(chat_router)

from langchain_ollama import OllamaLLM
from langchain_core.prompts import PromptTemplate # Use langchain_core.prompts for PromptTemplate

# Add CORS middleware to allow requests from any origin
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # Allows all origins
    allow_methods=["*"],  # Allows all methods (GET, POST, PUT, DELETE, etc.)
    allow_headers=["*"],  # Allows all headers
)

# --- Pydantic Models (Refined to match Laravel input and QOTD logic) ---
class JokeRequest(BaseModel):
    # Renamed from 'additional_customization' to 'topic' to match Laravel's form input name
    topic: str # Matches 'topic' from Laravel's form
    grade_level: str
    user_id: Optional[int] = None # Allow None, will default to 1 in endpoint
    session_id: Optional[int] = None # Using int for session_id as it's an ID

class JokeResponse(BaseModel):
    joke: str
    message_id: int # The session_id returned by create_session_and_parameter_inputs

class PdfRequest(BaseModel):
    content: str
    filename: str

# Models for Chat History Integration
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

# Helper to clean AI output
def _clean_ai_output(text: str) -> str:
    cleaned = text.strip()
    patterns = [
        r"^Sure,\s*here\'?s\s*a\s*joke:?\s*", r"^Here\'?s\s*a\s*joke:?\s*",
        r"^Joke:?\s*", r"^Alright,\s*here\'?s\s*a\s*joke:?\s*"
    ]
    for pat in patterns:
        cleaned = re.sub(pat, '', cleaned, flags=re.IGNORECASE)
    cleaned = cleaned.replace('**', '').replace('##', '')
    cleaned = re.sub(r'\n\n.*?\[.*\]\n*$', '', cleaned, flags=re.IGNORECASE|re.DOTALL)
    return cleaned.strip()

# Prompt template - updated to use 'topic'
prompt_template = PromptTemplate(
    input_variables=["grade_level", "topic"], # Changed 'customization' to 'topic'
    template="""You are a teacher-friendly joke generator.
Generate exactly *one* joke suitable for a {grade_level} class.
Make it about: {topic}.
Return ONLY the joke text, no intros or formatting."""
)

# --- Joke Generation Endpoint ---
@app.post("/generate-joke", response_model=JokeResponse) # Updated endpoint name to match Laravel
async def generate_joke_api(request: JokeRequest):
    try:
        print("Received generate-joke request:", request.dict())

        # Validate input
        if not request.grade_level:
            raise HTTPException(status_code=422, detail="Grade level is required.")
        if not request.topic: # Topic is now required based on new model
            raise HTTPException(status_code=422, detail="Topic is required.")

        user_id_to_use = request.user_id if request.user_id is not None else 1

        llm = OllamaLLM(model="llama3")

        # Generate the joke using the LLM
        raw_joke = (prompt_template | llm).invoke({
            "grade_level": request.grade_level,
            "topic": request.topic # Pass 'topic' to the prompt
        })
        joke = _clean_ai_output(raw_joke)
        print("Generated joke:", joke)

        session_id = None
        if create_session_and_parameter_inputs:
            scope_vars: Dict[str, Any] = {
                "grade_level": request.grade_level,
                "topic": request.topic # Store topic in scope_vars
            }

            # Use the topic as the message_title for the conversation
            message_title = f"{request.topic} Joke" 

            session_id = create_session_and_parameter_inputs(
                user_id=user_id_to_use,
                agent_id=25,  # Agent ID for Teacher Jokes (ensure this is consistent)
                scope_vars=scope_vars,
                human_topic=message_title, # This often becomes the message_title
                ai_output=joke,
                agent_prompt=prompt_template.template.format(grade_level=request.grade_level, topic=request.topic)
            )
            
        print("message_id :", session_id)
        return JSONResponse(content={
            "joke": joke,
            "message_id": session_id
        }, headers={"Content-Type": "application/json"})

    except HTTPException as http_exc:
        raise http_exc
    except Exception as e:
        traceback_str = traceback.format_exc()
        print("ERROR in generate_joke_api:", traceback_str)
        return JSONResponse(status_code=500, content={"error": str(e), "trace": traceback_str})

# --- PDF Generation Endpoint ---
@app.post("/generate-pdf")
async def generate_pdf(request: PdfRequest):
    try:
        pdf = FPDF()
        pdf.add_page()
        pdf.set_font("Arial", size=12)
        # It's better to use a font that supports UTF-8 for broader character support
        # Example: pdf.set_font("DejaVu", size=12) # if you install and register a font like DejaVuSans
        pdf.multi_cell(0, 10, request.content.encode('latin-1', 'replace').decode('latin-1'))

        buffer = BytesIO()
        pdf.output(buffer, 'S')
        buffer.seek(0)

        return StreamingResponse(buffer, media_type="application/pdf", headers={
            "Content-Disposition": f"attachment; filename={request.filename}.pdf"
        })
    except Exception as e:
        traceback_str = traceback.format_exc()
        print("ERROR in generate_pdf:", traceback_str)
        return JSONResponse(status_code=500, content={"error": str(e), "trace": traceback_str})

