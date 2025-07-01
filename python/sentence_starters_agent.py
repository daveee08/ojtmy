# sentence_starters_agent.py

from pydantic import BaseModel
from typing import List
from langchain_ollama import OllamaLLM
from langchain_core.prompts import ChatPromptTemplate

class SentenceStarterInput(BaseModel):
    grade_level: str
    topic: str

GRADE_LEVEL_PROFILES = {
    "kindergarten": {
        "instructions": "Use very simple words and phrases. Focus on curiosity and helping the child describe basic things.",
    },
    "elementary": {
        "instructions": "Use clear and easy words. Help the student begin sentences that describe, explain, or share ideas clearly.",
    },
    "junior high": {
        "instructions": "Use age-appropriate vocabulary. Help the student write more detailed ideas and opinions.",
    },
    "senior high": {
        "instructions": "Use more formal phrasing. Encourage thoughtful analysis, opinions, or comparisons.",
    },
    "college": {
        "instructions": "Use academic tone. Encourage critical thinking, argument development, or synthesis of ideas.",
    }
}

DEFAULT_MODEL = "gemma3"

def generate_sentence_starters(grade_level: str, topic: str) -> List[str]:
    profile = GRADE_LEVEL_PROFILES.get(grade_level.lower())
    if not profile:
        raise ValueError(f"Unknown grade level: {grade_level}")

    instructions = profile["instructions"]

    prompt_template = f"""
You are an AI writing coach for students in {grade_level}. Your task is to help them begin writing about the topic below by giving them high-quality sentence starters.

Topic: "{topic}"

Guidelines:
- Generate exactly 5 sentence starters.
- Each one should begin naturally, like how a student would start a paragraph.
- Do NOT write full sentences or finish the thought.
- Starters must be **open-ended** and **not** complete ideas.
- Avoid repeating phrases like "This topic is about..."
- Adapt the tone and vocabulary for the grade level: simpler for younger students, more academic for older ones.
- Do not label or number them.
- Just return each one as a new line.
- Do not include any bullets, commentary, or explanation.


"""

    prompt = ChatPromptTemplate.from_template(prompt_template)
    model = OllamaLLM(model=DEFAULT_MODEL)
    chain = prompt | model

    result = chain.invoke({})

    # Clean and return top 5 lines only
    lines = [line.strip() for line in result.splitlines() if line.strip()]
    return lines[:5]
