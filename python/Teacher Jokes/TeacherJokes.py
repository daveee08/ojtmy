from fastapi import FastAPI, Form, Depends
from fastapi.responses import JSONResponse
from pydantic import BaseModel
from typing import Optional
import re, os, sys, traceback

# path setup
current_script_dir = os.path.dirname(os.path.abspath(__file__))
project_root = os.path.join(current_script_dir, '..', '..')
sys.path.insert(0, project_root)

# optional db utilities
try:
    from python.chat_router_final import chat_router
    from python.db_utils_final import create_session_and_parameter_inputs, insert_message
except ImportError:
    chat_router = None
    create_session_and_parameter_inputs = None
    insert_message = None

# fastapi
app = FastAPI(debug=True)
if chat_router:
    app.include_router(chat_router)

from langchain_ollama import OllamaLLM
from langchain_core.prompts import PromptTemplate

app.add_middleware(
    CORSMiddleware := __import__('fastapi.middleware.cors', fromlist=['CORSMiddleware']).CORSMiddleware,
    allow_origins=["*"], allow_methods=["*"], allow_headers=["*"]
)

# in-memory history demo
joke_sessions: dict[str, list[str]] = {}

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

# Prompt template
prompt_template = PromptTemplate(
    input_variables=["grade_level", "customization"],
    template="""You are a teacher-friendly joke generator.
Generate exactly *one* joke suitable for a {grade_level} class.
{customization}
Return ONLY the joke text, no intros or formatting."""
)

# input model
class JokeFormInput(BaseModel):
    grade_level: str
    additional_customization: Optional[str]
    user_id: Optional[int] = 1
    session_id: Optional[str] = None

    @classmethod
    def as_form(
        cls,
        grade_level: str = Form(...),
        additional_customization: Optional[str] = Form(default=""),
        user_id: Optional[int] = Form(default=1),
        session_id: Optional[str] = Form(default=None)
    ):
        return cls(
            grade_level=grade_level,
            additional_customization=additional_customization,
            user_id=user_id,
            session_id=session_id
        )

@app.post("/generate-joke")
async def generate_joke_api(form: JokeFormInput = Depends(JokeFormInput.as_form)):
    try:
        print("Received form:", form.dict())

        llm = OllamaLLM(model="gemma:2b")
        customization = f"Make it about: {form.additional_customization}." if form.additional_customization else ""
        raw = (prompt_template | llm).invoke({
            "grade_level": form.grade_level,
            "customization": customization
        })
        joke = _clean_ai_output(raw)
        print("Generated joke:", joke)

        # DB + session handling
        session_id = form.session_id
        if create_session_and_parameter_inputs:
            session_id = create_session_and_parameter_inputs(
                user_id=form.user_id or 1,
                agent_id=42,
                scope_vars={"grade_level": form.grade_level},
                human_topic=form.additional_customization or "",
                ai_output=joke,
                agent_prompt=prompt_template.template
            )
        if insert_message and session_id:
            insert_message(session_id=session_id, role="assistant", content=joke)

        # in‑memory history store (for demo)
        if session_id:
            history = joke_sessions.setdefault(session_id, [])
            history.append(joke)

        return {
            "joke": joke,
            "message_id": session_id,
            "session_id": session_id
        }

    except Exception as e:
        traceback_str = traceback.format_exc()
        print("ERROR:", traceback_str)
        return JSONResponse(status_code=500, content={"detail": str(e), "trace": traceback_str})

@app.post("/generate-joke/history")
async def joke_history(session_id: str = Form(...)):
    return {"jokes": joke_sessions.get(session_id, [])}
