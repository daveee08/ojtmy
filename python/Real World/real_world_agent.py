# real_world_agent.py
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
    allow_origins=["*"],  # or Laravel origin like "http://localhost:8000"
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)
# Define grade-specific profiles
GRADE_PROFILES = {
    "kindergarten": {
        "tone": "Use very simple words and short sentences. Keep it playful and easy to understand.",
    },
    "elementary": {
        "tone": "Use basic vocabulary with simple explanations. Keep it friendly and relatable.",
    },
    "junior_high": {
        "tone": "Use slightly more detailed explanations with everyday language. Avoid jargon.",
    },
    "senior_high": {
        "tone": "Use clear, semi-formal tone. Introduce basic academic vocabulary where appropriate.",
    },
    "college": {
        "tone": "Use a formal, academic tone. Be concise but include enough detail to show deeper relevance.",
    }
}

base_instructions = """
You are an educational AI assistant helping students connect academic topics to the real world.

Topic: "{topic}"
Grade level: {grade_level}

Your task:
Generate 3 to 5 real-world examples or applications that make this topic meaningful for a {grade_level} student.

Writing style:
- Use clear, simple language appropriate for the grade level. 
- Adjust based on the provided grade profile.

Output format:
- Each example should start with a **bolded title**
- Follow with 2–3 sentences explaining the connection
- Do not include an introduction or conclusion
"""

model = OllamaLLM(model="gemma3:1b")
real_world_prompt = ChatPromptTemplate.from_template(base_instructions)

class RealWorldInput(BaseModel):
    topic: str
    grade_level: str
    user_id: int
    message_id: Optional[int] = None

    @classmethod
    def as_form(
        cls,
        topic: str = Form(...),
        grade_level: str = Form(...),
        user_id: int = Form(...),
        message_id: Optional[int] = Form(None)
    ):
        return cls(
            topic=topic,
            grade_level=grade_level,
            user_id=user_id,
            message_id=message_id
        )
async def real_world_agent(topic: str, grade_level: str) -> str:
    chain = real_world_prompt | model
    result = chain.invoke({
        "topic": topic,
        "grade_level": grade_level
    })
    return clean_output(result)

def clean_output(text: str) -> str:
    text = re.sub(r"\*\*(.*?)\*\*", r"\1", text)  # Remove bold formatting
    text = re.sub(r"\*(.*?)\*", r"\1", text)  # Remove italic formatting
    text = re.sub(r"^\s*[\*\-]\s*", "", text, flags=re.MULTILINE)  # Remove bullet points
    return text.strip()

@app.post("/real_world")
async def real_world_endpoint(data: RealWorldInput = Depends(RealWorldInput.as_form)):
    if not data.topic or not data.grade_level:
        raise HTTPException(status_code=400, detail="Topic and grade level are required.")
    try:
        explanation = await real_world_agent(data.topic, data.grade_level)

        scope_vars = {
            "topic": data.topic,
            "grade_level": data.grade_level
        }
        session_id = create_session_and_parameter_inputs(
            user_id=data.user_id,
            agent_id=13,
            scope_vars=scope_vars,
            human_topic=data.topic,
            ai_output=explanation
        )
        return {"explanation": explanation, "message_id": session_id}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))
    

#     instructions = f"""
# You're an educational AI expert helping students relate what they're learning to real-world applications.

# Generate 2–3 real-world examples or applications of this topic:
# "{topic}"
# for a student in {grade_level}. Focus on relevance, clarity, and practical context.

# Start each example with a short bolded title, followed by a short paragraph explaining the connection.
# Don't include any extra commentary.
# """

#     model = OllamaLLM(model="gemma3")
#     chain = ChatPromptTemplate.from_template(instructions) | model
#     response = chain.invoke({})  # no input vars needed

#     # Split by double newline or format manually
#     examples = [e.strip() for e in response.split("\n\n") if e.strip()]
#     return examples
