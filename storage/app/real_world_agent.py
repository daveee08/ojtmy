# real_world_agent.py

from pydantic import BaseModel
from typing import List
from langchain_ollama import OllamaLLM
from langchain_core.prompts import ChatPromptTemplate

class RealWorldInput(BaseModel):
    grade_level: str
    topic: str

def generate_real_world_examples(grade_level: str, topic: str) -> List[str]:
    instructions = f"""
You're an educational AI expert helping students relate what they're learning to real-world applications.

Generate 2–3 real-world examples or applications of this topic:
"{topic}"
for a student in {grade_level}. Focus on relevance, clarity, and practical context.

Start each example with a short bolded title, followed by a short paragraph explaining the connection.
Don't include any extra commentary.
"""

    model = OllamaLLM(model="gemma3")
    chain = ChatPromptTemplate.from_template(instructions) | model
    response = chain.invoke({})  # no input vars needed

    # Split by double newline or format manually
    examples = [e.strip() for e in response.split("\n\n") if e.strip()]
    return examples
