# study_habits_agent.py

from pydantic import BaseModel
from langchain_ollama import OllamaLLM
from langchain_core.prompts import ChatPromptTemplate

class StudyPlanInput(BaseModel):
    grade_level: str
    goal: str

def generate_study_plan(grade_level: str, goal: str) -> str:
    prompt = f"""
You are a smart AI tutor.

Your task is to generate a practical, organized, and motivational study plan for a student in grade level "{grade_level}".
The student's preparation goal is:
"{goal}"

Instructions:
- Break the plan into weekly sections.
- Include daily study suggestions.
- Recommend specific strategies for review, practice, time management, and test prep.
- Conclude with encouraging advice.
- Avoid generic tips. Tailor the plan to the student's level and time frame.

Format clearly for easy reading.
"""

    model = OllamaLLM(model="gemma3")
    chain = ChatPromptTemplate.from_template(prompt) | model
    result = chain.invoke({})
    return result.strip()
