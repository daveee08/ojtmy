# sentence_starters_agent.py
from __future__ import annotations #Tells the interpreter use a newer behavior for type hints.

import os
import sys
from typing import List

import httpx
from fastapi import Depends, FastAPI, Form, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from langchain_core.prompts import ChatPromptTemplate
from langchain_ollama import OllamaLLM
from pydantic import BaseModel

# --------------------------------------------------------------------------- #
#  Path setup so "db_utils" (one directory up) can be imported without hacks. #
# --------------------------------------------------------------------------- #
sys.path.append(os.path.abspath(os.path.join(os.path.dirname(__file__), "..")))
from db_utils import insert_session_and_message  # noqa: E402  (import after path fix)

# --------------------------------------------------------------------------- #
#  FastAPI app + CORS                                                         #
# --------------------------------------------------------------------------- #
app = FastAPI(debug=True)
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_methods=["POST", "OPTIONS"],
    allow_headers=["*"],
)

# --------------------------------------------------------------------------- #
#  Pydantic input / output models                                             #
# --------------------------------------------------------------------------- #
class SentenceStarterInput(BaseModel):
    grade_level: str
    topic: str
    mode: str  # "chat" or "single"
    user_id: int
    parameter_inputs: int = 1
    agent_id: int = 14  # <-- adjust if you change the DB

    @classmethod
    def as_form(
        cls,
        grade_level: str = Form(...),
        topic: str = Form(...),
        mode: str = Form(...),
        user_id: int = Form(...),
        parameter_inputs: int = Form(1),
        agent_id: int = Form(14),
    ) -> "SentenceStarterInput":
        return cls(
            grade_level=grade_level,
            topic=topic,
            mode=mode,
            user_id=user_id,
            parameter_inputs=parameter_inputs,
            agent_id=agent_id,
        )


# class SentenceStarterOutput(BaseModel):
#     sentence_starters: List[str]


# --------------------------------------------------------------------------- #
#  Grade‑level profiles and prompt                                            #
# --------------------------------------------------------------------------- #
GRADE_LEVEL_PROFILES = {
    "kindergarten": "Use very simple words and phrases. Encourage curiosity.",
    "elementary": "Use clear, easy words. Help the student describe or explain ideas.",
    "junior high": "Use age‑appropriate vocabulary. Encourage detail and opinions.",
    "senior high": "Use a semi‑formal tone. Invite analysis or comparison.",
    "college": "Use an academic tone. Encourage critical thinking and synthesis.",
}

PROMPT = ChatPromptTemplate.from_template(
    """
You are an AI writing coach for {grade_level} students.
Your task is to generate **exactly five** open‑ended sentence starters
to help them begin writing about the topic below.

Topic: "{topic}"

Guidelines:
- Return only the five starters, each on its own line (no numbering, no bullets).
- Do NOT complete the thought. Starters must remain open‑ended.
- Avoid phrases like "This topic is about...".
- Tone and vocabulary should match the grade level:
  {instructions}
"""
)

# --------------------------------------------------------------------------- #
#  LLM and chain                                                              #
# --------------------------------------------------------------------------- #
llm = OllamaLLM(model="gemma3")
chain = PROMPT | llm


def generate_sentence_starters(grade_level: str, topic: str) -> List[str]:
    instructions = GRADE_LEVEL_PROFILES.get(grade_level.lower())
    if instructions is None:
        raise ValueError(f"Unknown grade level: '{grade_level}'. Allowed: {', '.join(GRADE_LEVEL_PROFILES)}")

    raw = chain.invoke({"grade_level": grade_level, "topic": topic, "instructions": instructions})
    starters = [line.strip() for line in raw.splitlines() if line.strip()]
    # Enforce exactly 5 lines; if the model returned extras, keep the first five.
    return starters[:5]


# --------------------------------------------------------------------------- #
#  Endpoint                                                                   #
# --------------------------------------------------------------------------- #
@app.post("/sentence-starters") 
async def sentence_starters_endpoint(data: SentenceStarterInput):
    try:
        # --------------------------- chat pass‑through ---------------------- #
        if data.mode.lower() == "chat":
            async with httpx.AsyncClient(timeout=None) as client:
                payload = {
                    "topic": data.topic,
                    "user_id": str(data.user_id),
                    "db_message_id": 0,  # <- supply your own thread ID if needed
                }
                r = await client.post("http://192.168.50.10:8001/chat_with_history", data=payload)
                r.raise_for_status()
                starters = [r.json().get("response", "")]  # Expecting the chat server to format
        # ----------------------- single‑shot generation --------------------- #
        else:
            starters = generate_sentence_starters(data.grade_level, data.topic)

        # ------------------ save both human prompt + AI reply --------------- #
        scope_vars = {"grade_level": data.grade_level, "topic": data.topic}
        msg_id = insert_session_and_message(
            user_id=data.user_id,
            agent_id=data.agent_id,
            sender="human",
            topic=data.topic,
            scope_vars=scope_vars,
        )
        insert_session_and_message(
            user_id=data.user_id,
            agent_id=data.agent_id,
            sender="ai",
            topic="\n".join(starters),
            scope_vars=scope_vars,
            message_id=msg_id,
        )

        return SentenceStarterOutput(sentence_starters=starters)

    # --------------------------- error handling ---------------------------- #
    except Exception as exc:  # broad, but useful for an API boundary
        raise HTTPException(status_code=500, detail=str(exc)) from exc


# --------------------------------------------------------------------------- #
#  Uvicorn entry‑point (optional)                                             #
# --------------------------------------------------------------------------- #
# if __name__ == "__main__":
#     import uvicorn  # local import so `uvicorn` isn’t required when run by Gunicorn
#     uvicorn.run("sentence_starters_agent:app", host="0.0.0.0", port=8000, reload=True)
