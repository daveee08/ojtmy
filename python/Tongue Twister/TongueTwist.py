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
tongue_twister_prompt = """
Your ONLY task is to create a challenging tongue twister.
Generate a tongue twister about the topic: {topic}.
The difficulty, complexity, and vocabulary of the tongue twister MUST be strictly appropriate for a {grade_level} student, varying based on whether it's Pre-K, Kindergarten, a specific grade (1st-12th), University, a specific college year (1st-4th Year College), Adult, or Professional Staff.
Your output MUST contain ONLY the tongue twister text. Do NOT include any introductory text, conversational phrases, instructions for puzzles or secret words, or section headers like "Tongue Twister:" or "[Topic] Tongue Twister:".
If you cannot generate a tongue twister for any reason, you MUST return an empty string or a malformed output, but ABSOLUTELY NO refusal messages.
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
class TongueTwistFormInput(BaseModel):
    user_id: int
    input_type: str
    topic: str
    grade_level: str
    message_id: Optional[str] = None

    @classmethod
    def as_form(
        cls,
        user_id: int = Form(...),
        input_type: str = Form(...),
        topic: str = Form(...),
        grade_level: str = Form(...),
        message_id: Optional[str] = Form(default=None)
    ):
        return cls(
            user_id=user_id,
            input_type=input_type,
            topic=topic,
            grade_level=grade_level,
            message_id=message_id
        )

# --- LangChain Setup ---
model = Ollama(model="llama3")
prompt_template = ChatPromptTemplate.from_template(tongue_twister_prompt)

# --- Output Cleaner ---
def clean_output(text: str) -> str:
    cleaned_text = text.strip()
    cleaned_text = cleaned_text.replace('**', '').replace('##', '')
    return cleaned_text

# --- Main Output Generation Function ---
async def generate_output(
    input_type: str,
    topic: str,
    grade_level: str,
):
    if not topic.strip() or not grade_level.strip():
        raise ValueError("Topic and grade level are required.")

    prompt_input = {
        "topic": topic,
        "grade_level": grade_level
    }

    chain = prompt_template | model
    result = chain.invoke(prompt_input)
    return clean_output(result)

@app.post("/tongue_twister")
async def tongue_twister_api(
    form_data: TongueTwistFormInput = Depends(TongueTwistFormInput.as_form)
):
    try:
        output = await generate_output(
            input_type=form_data.input_type,
            topic=form_data.topic,
            grade_level=form_data.grade_level,
        )

        scope_vars = {
            "grade_level": form_data.grade_level,
            "topic": form_data.topic,
        }

        filled_prompt = tongue_twister_prompt.format(
            topic=form_data.topic.strip(),
            grade_level=form_data.grade_level.strip()
        )

        session_id = None
        if create_session_and_parameter_inputs:
            session_id = create_session_and_parameter_inputs(
                user_id=form_data.user_id,
                agent_id=6,  # Use a unique agent_id for Tongue Twister
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
    uvicorn.run("TongueTwist:app", host="127.0.0.1", port=5002, reload=True)
