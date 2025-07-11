#five_question_agent.py
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
    "kindergarten": "Keep the language extremely simple and use playful, imaginative words that a 5-year-old can understand. Focus on feelings, colors, and basic choices.",
    "elementary": "Use friendly and clear language suitable for children aged 6 to 10. Encourage curiosity and basic reasoning, like 'why' and 'how' questions.",
    "junior high": "Write with a bit more depth. Use relatable and engaging language for students aged 11 to 14. Encourage connections to real life or personal experience.",
    "senior high": "Use thoughtful and mature language suitable for students aged 15 to 18. Encourage analytical thinking, ethical reasoning, and creative problem-solving.",
    "college": "Use academic, mature, and precise language. Encourage synthesis, debate, and deep exploration of ideas."
}

step_prompt_template = """
You are a helpful AI that creates thoughtful, open-ended questions to promote critical thinking.

Your task is to generate **exactly five** unique, deep, and age-appropriate questions based on the topic: "{topic}".

Audience: Students at the grade level: "{grade_level}"


Guidelines:
- Questions should encourage reflection, analysis, or creative thinking.
- Use plain, student-friendly language tailored to the specified grade level and profile.
- Each question must be open-ended (not answerable with just "yes" or "no").
- Avoid factual quiz-style or multiple-choice questions.

Output Rules:
- Return only the five questions, numbered exactly as shown below.
- No bullet points, titles, summaries, explanations, or greetings.
- Do not include example answers.

Format:
1. 
2. 
3. 
4. 
5. 
"""
model = OllamaLLM(model="gemma3:1b")
step_prompt = ChatPromptTemplate.from_template(step_prompt_template)

class FiveQuestionInput(BaseModel):
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
async def five_question_agent(grade_level: str, topic: str) -> str:
    chain = step_prompt | model
    result = chain.invoke({
        "grade_level": grade_level,
        "topic": topic,
    })
    return clean_output(result)

def clean_output(text: str) -> str:
    # text = re.sub(r"\*\*(.*?)\*\*", r"\1", text)
    # text = re.sub(r"\*(.*?)\*", r"\1", text)
    # text = re.sub(r"^\s*[\*\-]\s*", "", text, flags=re.MULTILINE)
    return text.strip()

@app.post("/five_questions")
async def five_questions_endpoint(data: FiveQuestionInput = Depends(FiveQuestionInput.as_form)):
    if not data.grade_level or not data.topic:
        raise HTTPException(status_code=400, detail="Grade level and topic are required.")

    try:
        explanation = await five_question_agent(data.grade_level, data.topic)
        filled_prompt = step_prompt_template.format(grade_level=data.grade_level, topic=data.topic)

        scope_vars = {
            "grade_level": data.grade_level
        }
        
        session_id = create_session_and_parameter_inputs(
            user_id=data.user_id,
            agent_id=11,  # Default agent_id for step tutor
            scope_vars=scope_vars,
            human_topic=data.topic,
            ai_output=explanation,
            agent_prompt=filled_prompt
        )
        return {"explanation": explanation, "message_id":  session_id}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

