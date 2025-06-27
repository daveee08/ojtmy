# contentcreator.py

from fastapi import APIRouter, Form
from langchain.prompts import PromptTemplate
from langchain_core.output_parsers import StrOutputParser
from langchain_community.llms import Ollama

router = APIRouter()

@router.post("/generate-content")
async def generate_content(
    grade_level: str = Form(...),
    text_length: str = Form(...),
    prompt: str = Form(...),
    additional_criteria: str = Form("")
):
    base_template = """
You are a helpful and engaging content writer. Your task is to create informative content based on the details below.

Grade level: {grade_level}  
Text length: {text_length}  
Content request: {prompt}  
Additional criteria: {additional_criteria}

Write a well-structured and age-appropriate piece of content that matches the grade level and desired length. Make it clear, engaging, and tailored to the context.

Then at the end, generate a short and catchy **social media caption** summarizing the topic and inviting discussion. Use a friendly and informal tone with relevant hashtags.

Return only the content and caption, with no extra labels or explanations.
"""

    prompt_template = PromptTemplate.from_template(base_template)

    chain = prompt_template | Ollama(model="gemma3:4b") | StrOutputParser()

    result = chain.invoke({
        "grade_level": grade_level.strip(),
        "text_length": text_length.strip(),
        "prompt": prompt.strip(),
        "additional_criteria": additional_criteria.strip(),
    })

    return {"generated_content": result.strip()}
