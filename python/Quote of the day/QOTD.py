from fastapi import FastAPI, HTTPException, Request
from fastapi.responses import JSONResponse, StreamingResponse
from pydantic import BaseModel, ConfigDict
from langchain_ollama import OllamaLLM
from langchain.prompts import PromptTemplate
from fpdf import FPDF
from io import BytesIO
from typing import Optional, Dict, Any
import os
import sys
import traceback

# Import CORS middleware for handling cross-origin requests
from fastapi.middleware.cors import CORSMiddleware

# Conditional imports for database utilities, mirroring the reference code's approach.
# This allows the application to run even if these modules are not present.
current_script_dir = os.path.dirname(os.path.abspath(__file__))
project_root = os.path.join(current_script_dir, '..', '..')
sys.path.insert(0, project_root)

try:
    # Assuming these are shared utility functions for session and message management
    # get_session_details is assumed to be a function that retrieves session data by message_id
    from python.chat_router_final import chat_router
    from python.db_utils_final import create_session_and_parameter_inputs, get_session_details 
except ImportError:
    chat_router = None
    create_session_and_parameter_inputs = None
    get_session_details = None 
    print("Warning: chat_router_final or db_utils_final not found. Session management features will be disabled.")


# --- FastAPI App Initialization ---
app = FastAPI(debug=True)

# Include chat router if available (from reference)
if chat_router:
    app.include_router(chat_router)

# Add CORS middleware to allow requests from any origin (similar to reference)
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # Allows all origins
    allow_methods=["*"],  # Allows all methods (GET, POST, PUT, DELETE, etc.)
    allow_headers=["*"],  # Allows all headers
)

# --- Pydantic Models ---
class QuoteRequest(BaseModel):
    topic: str
    grade_level: str
    user_id: Optional[int] = None # Added user_id to match Laravel controller's input

class PdfRequest(BaseModel):
    content: str
    filename: str

# --- Root Endpoint ---
@app.get("/")
async def read_root():
    return {"message": "QOTD API is running!"}

# --- Quote Generation Endpoint ---
@app.post("/generate-quote")
async def generate_quote_api(request: QuoteRequest):
    try:
        llm = OllamaLLM(model="gemma:2b")

        prompt_template = PromptTemplate(
            input_variables=["topic", "grade_level"],
            template="""
            You are an AI assistant that generates quotes.
            Generate a quote about {topic} suitable for a {grade_level} student.
            Provide only the quote text, without any introductory or concluding remarks, or attribution.
            """
        )

        # Generate the quote using the LLM
        quote = (prompt_template | llm).invoke({'topic': request.topic, 'grade_level': request.grade_level})
        quote = quote.strip()

        session_id = None
        # If database utilities are available, create a session and store parameters
        if create_session_and_parameter_inputs:
            # Define scope variables for the session, similar to the quizme tool
            scope_vars: Dict[str, Any] = {
                "topic": request.topic,
                "grade_level": request.grade_level,
            }

            # Fill the prompt template for logging
            filled_prompt = prompt_template.template.format(
                topic=request.topic,
                grade_level=request.grade_level
            )

            # Create a session and get the message_id (session_id)
            # This is where the data (quote, topic, grade, user_id) gets saved to the database.
            session_id = create_session_and_parameter_inputs(
                user_id=request.user_id, # Use the user_id from the request
                agent_id=8,  # Assign a unique agent_id for the QOTD tool (e.g., 8)
                scope_vars=scope_vars,
                human_topic=request.topic, # The user's input topic
                ai_output=quote, # The generated quote
                agent_prompt=filled_prompt # The prompt used to generate the quote
            )
            print(f"Session created with ID: {session_id}")

        # Return the quote and the message_id (session_id)
        return {"quote": quote, "message_id": session_id}
    except Exception as e:
        traceback_str = traceback.format_exc()
        print("ERROR:", traceback_str)
        return JSONResponse(status_code=500, content={"error": str(e), "trace": traceback_str})

# --- PDF Generation Endpoint ---
@app.post("/generate-pdf")
async def generate_pdf(request: PdfRequest):
    try:
        pdf = FPDF()
        pdf.add_page()
        pdf.set_font("Arial", size=12)
        # Ensure content is encoded correctly for FPDF
        pdf.multi_cell(0, 10, request.content.encode('latin-1', 'replace').decode('latin-1'))

        buffer = BytesIO()
        pdf.output(buffer, 'S')
        buffer.seek(0)

        return StreamingResponse(buffer, media_type="application/pdf", headers={
            "Content-Disposition": f"attachment; filename={request.filename}.pdf"
        })
    except Exception as e:
        traceback_str = traceback.format_exc()
        print("ERROR:", traceback_str)
        return JSONResponse(status_code=500, content={"error": str(e), "trace": traceback_str})

# --- Fetch User Sessions Endpoint (for Laravel controller) ---
@app.get("/qotd-sessions/{user_id}")
async def fetch_qotd_sessions(user_id: int):
    """
    Fetches user-specific sessions for the Quote of the Day tool from the database.
    """
    if get_session_details: # Assuming get_session_details can fetch multiple sessions for a user or there's another function
        try:
            # This part would need a specific function in db_utils_final
            # to retrieve sessions based on user_id and agent_id (e.g., agent_id=8 for QOTD).
            # For now, it will return a placeholder or raise an error if not implemented in db_utils.
            
            # Example if db_utils_final had a function like get_sessions_by_user_and_agent:
            # sessions = get_sessions_by_user_and_agent(user_id, agent_id=8)
            # formatted_sessions = []
            # for session in sessions:
            #     formatted_sessions.append({
            #         "message_id": session.session_id,
            #         "topic": session.human_topic,
            #         "grade_level": session.scope_vars.get("grade_level"),
            #         "timestamp": session.created_at.isoformat() # Assuming a created_at field
            #     })
            # return JSONResponse(content=formatted_sessions)

            # Placeholder for actual DB fetching if get_session_details is only by ID:
            raise HTTPException(status_code=501, detail="`fetch_qotd_sessions` requires specific DB utility for user-based session retrieval.")

        except Exception as e:
            traceback_str = traceback.format_exc()
            print("ERROR fetching sessions:", traceback_str)
            raise HTTPException(status_code=500, detail=f"Error fetching sessions: {str(e)}")
    else:
        raise HTTPException(status_code=501, detail="Database utilities not available for session fetching.")

# --- Endpoint to retrieve a specific quote history by message_id ---
@app.get("/qotd-history/{message_id}")
async def get_qotd_history(message_id: str):
    """
    Retrieves the details of a specific QOTD session from the database using its message_id.
    """
    if get_session_details: # Assuming db_utils_final provides a function to get session details by ID
        try:
            session_details = get_session_details(message_id) # Call the assumed function
            if session_details:
                # Assuming session_details is an object or dict with these attributes
                return JSONResponse(content={
                    "quote": session_details.ai_output,
                    "topic": session_details.human_topic,
                    "grade_level": session_details.scope_vars.get("grade_level")
                })
            else:
                raise HTTPException(status_code=404, detail="Quote history not found")
        except Exception as e:
            traceback_str = traceback.format_exc()
            print("ERROR retrieving QOTD history:", traceback_str)
            raise HTTPException(status_code=500, detail=f"Error retrieving quote history: {str(e)}")
    else:
        raise HTTPException(status_code=501, detail="Database utilities not available for history retrieval.")

# To run the app (for local testing, typically run via `uvicorn main:app --reload`)
if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=5006)

