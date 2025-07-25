from fastapi import FastAPI, HTTPException, Form
from fastapi.responses import JSONResponse # Re-added JSONResponse
import re, os, sys, traceback
from fastapi.middleware.cors import CORSMiddleware
# import json # Still not needed for parsing LLM output, but JSONResponse is used
from typing import Optional, Dict, Any, List

# --- LangChain & Ollama Imports ---
from langchain_ollama import OllamaLLM
from langchain_core.prompts import ChatPromptTemplate

# --- PATH SETUP ---
current_script_dir = os.path.dirname(os.path.abspath(__file__))
project_root = os.path.join(current_script_dir, '..', '..') # Adjust as necessary if your project structure differs
sys.path.insert(0, project_root)

# Add the python/ directory to sys.path
sys.path.append(os.path.abspath(os.path.join(os.path.dirname(__file__), "..")))

# Import your database utilities and chat router
try:
    from python.chat_router_final import chat_router
    from python.db_utils_final import create_session_and_parameter_inputs, insert_message
except ImportError as e:
    print(f"Error importing local modules: {e}")
    print("Please ensure 'python/chat_router_final.py' and 'python/db_utils_final.py' are in the correct path relative to Quizme.py.")
    sys.exit(1) # Exit if essential modules can't be imported


# --- FastAPI App Initialization ---
app = FastAPI(debug=True)
app.include_router(chat_router)

# --- CORS MIDDLEWARE ---
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_methods=["*"],
    allow_headers=["*"],
)

# --- Pydantic Models for Quiz Generation ---
# We still need a request model for incoming form data
from pydantic import BaseModel
class QuizRequestForm(BaseModel):
    topic: str
    grade_level: str
    num_questions: int = 10
    user_id: int

# NEW Pydantic Model for the response
class QuizTextResponse(BaseModel):
    session_id: Optional[int] = None
    quiz_content: str # This will hold the plain text quiz from the LLM

# --- Quiz Prompt (remains the same as per your last request) ---
quiz_prompt = """You are an expert quiz creator. Generate a quiz with the following parameters:
- Topic: {topic}
- Grade Level: {grade_level}
- Number of Questions: {num_questions}
Generate exactly {num_questions} questions. For multiple choice questions, provide options labeled A, B, C, etc. For fill-in-the-blanks and identification questions, just provide the question. After each question, on a new line, state the correct answer clearly.

Here is the quiz:

Question 1: [Question text here]
A) [Option 1]
B) [Option 2]
C) [Option 3]
Answer: [Correct answer here]

Question 2: [Question text here]
Answer: [Correct answer here]

(Continue for all {num_questions} questions in this format.)
"""

# --- Initialize OllamaLLM ---
model = OllamaLLM(model="llama3")
prompt_template = ChatPromptTemplate.from_template(quiz_prompt)

@app.post("/quizme", response_model=QuizTextResponse) # Changed response_class to response_model
async def generate_quiz(
    topic: str = Form(...),
    grade_level: str = Form(...),
    num_questions: int = Form(10),
    user_id: int = Form(...)
):
    session_id = None # Initialize session_id to None
    try:
        # Prepare prompt input for LLM
        prompt_input = {
            "topic": topic.strip(),
            "grade_level": grade_level.strip(),
            "num_questions": num_questions,
        }
        
        # Invoke LLM to get raw quiz output
        chain = prompt_template | model
        raw_ai_output = chain.invoke(prompt_input).strip()
        
        # --- REMOVED JSON EXTRACTION AND PARSING LOGIC from LLM output ---
        # All the 'json_match', 'fallback_match', 'json.loads', 'validated_questions'
        # logic has been removed as the LLM is now expected to return plain text.
        # The 'raw_ai_output' is the final content to be returned in the 'quiz_content' field.

        # --- Database Saving Logic ---
        # Ensure create_session_and_parameter_inputs is called correctly
        if create_session_and_parameter_inputs:
            try:
                user_id_to_use = user_id if user_id is not None else 1 # Fallback for user_id
                
                # These are the parameters that describe the quiz request itself
                scope_vars = {
                    "topic": topic.strip(),
                    "grade_level": grade_level.strip(),
                    "num_questions": num_questions,
                }
                
                # The actual prompt sent to the LLM
                agent_prompt_content = quiz_prompt.format(
                    topic=topic.strip(),
                    grade_level=grade_level.strip(),
                    num_questions=num_questions,
                )

                session_id = create_session_and_parameter_inputs(
                    user_id=user_id_to_use,
                    agent_id=27, # Your specified agent ID
                    scope_vars=scope_vars, # Dict of input parameters
                    human_topic=topic.strip(), # What the user asked for
                    ai_output=raw_ai_output, # The full raw output from LLM
                    agent_prompt=agent_prompt_content # The formatted prompt sent to LLM
                )
                print(f"DEBUG: Database session created with ID: {session_id}")
            except Exception as db_e:
                print(f"DEBUG: DB error during session creation: {db_e}")
                traceback.print_exc() # Print full traceback for DB errors
                # Do not raise HTTPException here, allow quiz to be returned even if DB fails
                # session_id will remain None if creation fails
        else:
            print("DEBUG: create_session_and_parameter_inputs not available, skipping DB save.")

        # --- Return the raw LLM output as plain text wrapped in a JSON object with session_id ---
        return QuizTextResponse(session_id=session_id, quiz_content=raw_ai_output)

    except HTTPException as e:
        # Re-raise HTTPException directly, these are controlled errors
        raise e
    except Exception as e:
        # Catch any other unexpected errors, log them, and return a 500
        print(f"DEBUG: An unexpected error occurred in generate_quiz: {e}")
        traceback.print_exc() # Print full traceback for unexpected errors
        raise HTTPException(status_code=500, detail=f"An internal server error occurred: {e}")
# --- End of Quiz Generation Endpoint ---