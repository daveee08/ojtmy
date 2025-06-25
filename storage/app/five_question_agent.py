# five_question_agent.py

from pydantic import BaseModel
from typing import List
from langchain_ollama import OllamaLLM
from langchain_core.prompts import ChatPromptTemplate

class FiveQuestionInput(BaseModel):
    grade_level: str
    prompt: str

def generate_questions(grade_level: str, prompt: str) -> List[str]:
    instructions = f"""
You are a smart AI that helps users think critically.
You generate open-ended questions based on a given topic and grade level.
Make sure you separate each question with a newline.
Each question will start with a number for clarity.
Make sure you make 5 questions, no more, no less.

Based on the grade level "{grade_level}", generate 5 thoughtful, deep, open-ended questions 
that push the user's thinking about this topic:

"{prompt}"

Make the questions simple enough for the grade level, but still meaningful and thought-provoking.
Only return the 5 questions as a list.
"""

    model = OllamaLLM(model="gemma3")
    chain = ChatPromptTemplate.from_template(instructions) | model
    result = chain.invoke({})  # No input vars needed

    # Clean and return top 5 lines
    lines = [line.strip(" -•1234.").strip() for line in result.splitlines() if line.strip()]
    return lines[:5]
