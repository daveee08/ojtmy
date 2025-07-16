from http.client import HTTPException
import re
from pydantic import BaseModel
from langchain_ollama import OllamaLLM
from langchain_core.prompts import ChatPromptTemplate
from fastapi import Form, Depends, FastAPI, HTTPException
from typing import Optional
import sys
import os
from fastapi.middleware.cors import CORSMiddleware
project_root = os.path.abspath(os.path.join(os.path.dirname(__file__), '..', '..'))
sys.path.insert(0,project_root)

from python.db_utils_final import create_session_and_parameter_inputs, insert_message
from python.chat_router_final import chat_router



app = FastAPI(debug=True)
app.include_router(chat_router)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# --------------------------------------------------------------------------- #
GRADE_LEVEL_PROFILES = {
    "kindergarten": "Use very simple words and phrases. Encourage curiosity.",
    "elementary": "Use clear, easy words. Help the student describe or explain ideas.",
    "junior high": "Use age‑appropriate vocabulary. Encourage detail and opinions.",
    "senior high": "Use a semi‑formal tone. Invite analysis or comparison.",
    "college": "Use an academic tone. Encourage critical thinking and synthesis.",
}
prompt_template = """
You are an AI writing coach for {grade_level} students.
Your task is to generate **exactly five** open‑ended sentence starters
to help them begin writing about the topic below.

Topic: "{topic}"
grade_level: {grade_level}

Guidelines:
- Return only the five starters, each on its own line, numbered 1 to 5.
- Do NOT complete the thought. Starters must remain open‑ended.
- Avoid questions, especially yes/no questions.
- Avoid phrases like "This topic is about..." or "I think that..."
- Tone and vocabulary should match the grade level.
- Each starter should naturally lead into a full sentence, not a title or headline.

"""

model = OllamaLLM(model="gemma3:latest")
prompt_template = ChatPromptTemplate.from_template(prompt_template)

# --------------------------------------------------------------------------- #
#  Pydantic input / output models                                             #
# --------------------------------------------------------------------------- #
class SentenceStarterInput(BaseModel):
    topic: str
    grade_level: str
    user_id: int
    message_id: Optional[int] = None  # Optional for initial requests
    
    @classmethod
    def as_form(
        cls,
        topic: str = Form(...),  # This is not used, but kept for consistency with other agents
        grade_level: str = Form(...),
        user_id: int = Form(...),
        message_id: Optional[int] = Form(None),  # Optional for initial requests
    ) :
        return cls(
            grade_level=grade_level,
            topic=topic,
            user_id=user_id,
            message_id=message_id,  # Optional for initial requests
        )

async def sentence_starters(grade_level: str, topic: str) -> list[str]:
    chain = prompt_template | model
    result = chain.invoke({
        "grade_level": grade_level,
        "topic": topic,
    })
    return clean_output(result)

def clean_output(text: str) -> list[str]:
    text = re.sub(r"\*\*(.*?)\*\*", r"\1", text)
    text = re.sub(r"\*(.*?)\*", r"\1", text)
    text = re.sub(r"^\s*[\*\-]\s*", "", text, flags=re.MULTILINE)
    return text.strip()

@app.post("/sentence-starters")
async def sentence_starters_endpoint(data: SentenceStarterInput = Depends(SentenceStarterInput.as_form)):
    if not data.grade_level or not data.topic:
        raise HTTPException(status_code=400, detail="Grade level and topic are required.")
    try:
        explanation = await sentence_starters(data.grade_level, data.topic)
        filled_prompt = prompt_template.format(grade_level=data.grade_level, topic=data.topic)

        scope_vars = {
            "grade_level": data.grade_level,
            "topic": data.topic
        }

        session_id = create_session_and_parameter_inputs(
            user_id=data.user_id,
            agent_id=14,  # Default agent_id for sentence starters
            scope_vars=scope_vars,
            human_topic=data.topic,
            ai_output=explanation,
            agent_prompt=filled_prompt
        )
        return {"sentence_starters": explanation, "message_id": session_id}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))
    

