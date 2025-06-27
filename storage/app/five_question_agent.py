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
You are a helpful AI that creates thoughtful, open-ended questions to promote critical thinking.

Your task is to generate **exactly five** unique, deep, and age-appropriate questions based on the topic: "{prompt}".

Audience: Students at the grade level: "{grade_level}"

Guidelines:
- Questions should encourage reflection, analysis, or creative thinking.
- Use plain, student-friendly language based on the specified grade level.
- Each question must be open-ended (not answerable with just "yes" or "no").
- Avoid factual quiz-style or multiple-choice questions.

Output Rules:
- Return only the five questions, numbered exactly as shown below.
- No bullet points, titles, summaries, explanations, or greetings.
- Do not include example answers.

Format:
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
