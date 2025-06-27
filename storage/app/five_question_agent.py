# five_question_agent.py

from pydantic import BaseModel
from typing import List
from langchain_ollama import OllamaLLM
from langchain_core.prompts import ChatPromptTemplate
import re

class FiveQuestionInput(BaseModel):
    grade_level: str
    prompt: str

def generate_questions(grade_level: str, prompt: str) -> List[str]:
    instructions = f"""
You are a smart AI that helps users think critically.

Based on the grade level "{grade_level}", generate exactly **five** open-ended, deep, and thoughtful questions
that challenge the user's thinking about the topic: "{prompt}".

Each question should be on its own line and begin with a number (e.g., "1. ...", "2. ...", ..., "5. ...").
Do not include an introduction or summary—just output the five questions as a numbered list.
No bullet points. No extra formatting.

Example format:
1. Question one?
2. Question two?
3. Question three?
4. Question four?
5. Question five?
"""

    model = OllamaLLM(model="gemma3")
    chain = ChatPromptTemplate.from_template(instructions) | model
    result = chain.invoke({})  # No input vars needed

    # Use regex to extract numbered questions
    pattern = r"\d\.\s+(.*?)(?=\n\d\.|\Z)"
    matches = re.findall(pattern, result, re.DOTALL)

    return [q.strip() for q in matches][:5]
