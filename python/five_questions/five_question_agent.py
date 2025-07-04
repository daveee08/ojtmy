#five_question_agent.py
from pydantic import BaseModel
from typing import List
from langchain_ollama import OllamaLLM
from langchain_core.prompts import ChatPromptTemplate
import re

class FiveQuestionInput(BaseModel):
    grade_level: str
    prompt: str

# Define grade-specific profiles
GRADE_PROFILES = {
    "kindergarten": "Keep the language extremely simple and use playful, imaginative words that a 5-year-old can understand. Focus on feelings, colors, and basic choices.",
    "elementary": "Use friendly and clear language suitable for children aged 6 to 10. Encourage curiosity and basic reasoning, like 'why' and 'how' questions.",
    "junior high": "Write with a bit more depth. Use relatable and engaging language for students aged 11 to 14. Encourage connections to real life or personal experience.",
    "senior high": "Use thoughtful and mature language suitable for students aged 15 to 18. Encourage analytical thinking, ethical reasoning, and creative problem-solving.",
    "college": "Use academic, mature, and precise language. Encourage synthesis, debate, and deep exploration of ideas."
}

def generate_questions(grade_level: str, prompt: str) -> List[str]:
    # Get the profile for the given grade level
    profile = GRADE_PROFILES.get(grade_level.lower(), "Use plain, student-friendly language.")

    instructions = f"""
You are a helpful AI that creates thoughtful, open-ended questions to promote critical thinking.

Your task is to generate **exactly five** unique, deep, and age-appropriate questions based on the topic: "{prompt}".

Audience: Students at the grade level: "{grade_level}"

Grade Profile: {profile}

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

    model = OllamaLLM(model="gemma3")
    chain = ChatPromptTemplate.from_template(instructions) | model
    result = chain.invoke({})  # No input vars needed

    # Use regex to extract numbered questions
    pattern = r"\d\.\s+(.*?)(?=\n\d\.|\Z)"
    matches = re.findall(pattern, result, re.DOTALL)

    return [q.strip() for q in matches][:5]
