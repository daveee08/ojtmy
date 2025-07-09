# storage/app/python/step_tutor_agent.py

from http.client import HTTPException
import re
from pydantic import BaseModel
from langchain_ollama import OllamaLLM
from langchain_core.prompts import ChatPromptTemplate
from fastapi import Form, Depends, FastAPI, HTTPException
from typing import Optional



app = FastAPI(debug=True)





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

model = OllamaLLM(model="gemma:2b")
step_prompt = ChatPromptTemplate.from_template(step_prompt_template)

class StepTutorInput(BaseModel):
    grade_level: str
    topic: str

    @classmethod
    def as_form(
        cls,
        grade_level: str = Form(...),
        topic: str = Form(...)
    ):
        return cls(
            grade_level=grade_level,
            topic=topic
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
        return {"explanation": explanation}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

