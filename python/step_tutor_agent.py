# storage/app/python/step_tutor_agent.py

from http.client import HTTPException
import re
from pydantic import BaseModel
from langchain_ollama import OllamaLLM
from langchain_core.prompts import ChatPromptTemplate
from fastapi import Form, Depends, FastAPI, HTTPException
from typing import Optional
import sys

from db_utils_final import create_session_and_parameter_inputs, insert_message
from fastapi.middleware.cors import CORSMiddleware
from chat_router_final import chat_router


app = FastAPI(debug=True)
app.include_router(chat_router)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # or Laravel origin like "http://localhost:8000"
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)




# Prompt Template for Step-by-Step Explanation (without add_cont)
step_prompt_template = """
You are a patient and supportive tutor that teaches students step-by-step based on their grade level. You must explain the given topic clearly, breaking it down into understandable parts for the student.

Instructions:
- Adjust your explanation to suit the student's grade level.
- Teach in a friendly and approachable tone.
- Explain each part or concept step-by-step.
- Keep steps numbered and easy to follow.
- Use analogies or examples appropriate for the grade level.

---
**Student Grade Level:** {grade_level}
**Topic:** {topic}

**Your Output (Step-by-Step):**
"""
follow_up_prompt_template = """You are a dedicated multilingual translator and translation assistant. 
Your primary function is to translate text clearly and naturally. Additionally, you can answer questions directly related to the provided history of translation, such as 'translate it back' or 'how do I pronounce this?' 
Do not engage in conversations, provide information, or answer questions outside the scope of translation or translation-related assistance. 
For direct translation requests, return only the translated text, without the original, explanations, or extra commentary."""

model = OllamaLLM(model="gemma:2b")
step_prompt = ChatPromptTemplate.from_template(step_prompt_template)

class StepTutorInput(BaseModel):
    grade_level: str
    topic: str
    user_id: int
    message_id: Optional[int] = None
    # agent_id: int = 16  # Default agent_id for step tutor

    @classmethod
    def as_form(
        cls,
        grade_level: str = Form(...),
        topic: str = Form(...),
        user_id: int = Form(...),
        message_id: Optional[int] = Form(None)
    ):
        return cls(
            grade_level=grade_level,
            topic=topic,
            user_id=user_id,
            message_id=message_id
        )

async def explain_topic_step_by_step(grade_level: str, topic: str) -> str:
    chain = step_prompt | model
    result = chain.invoke({
        "grade_level": grade_level,
        "topic": topic
    })
    return clean_output(result)

def clean_output(text: str) -> str:
    text = re.sub(r"\*\*(.*?)\*\*", r"\1", text)
    text = re.sub(r"\*(.*?)\*", r"\1", text)
    text = re.sub(r"^\s*[\*\-]\s*", "", text, flags=re.MULTILINE)
    return text.strip()


@app.post("/explain_step_by_step")
async def explain_step_by_step_endpoint(data: StepTutorInput = Depends(StepTutorInput.as_form)):
    if not data.grade_level or not data.topic:
        raise HTTPException(status_code=400, detail="Grade level and topic are required.")

    try:
        explanation = await explain_topic_step_by_step(data.grade_level, data.topic)

        scope_vars = {
            "grade_level": data.grade_level
        }
        
        session_id = create_session_and_parameter_inputs(
            user_id=data.user_id,
            agent_id=16,  # Default agent_id for step tutor
            scope_vars=scope_vars,
            human_topic=data.topic,
            ai_output=explanation
        )
        return {"explanation": explanation, "message_id":  session_id}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

