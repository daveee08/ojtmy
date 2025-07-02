from fastapi import FastAPI
from pydantic import BaseModel, ConfigDict
from langchain_ollama import OllamaLLM
from langchain.prompts import PromptTemplate
import re

app = FastAPI()

class JokeRequest(BaseModel):
    model_config = ConfigDict(extra='ignore')
    grade_level: str
    additional_customization: str | None = None

def _clean_ai_output(text: str) -> str:
    cleaned_text = text.strip()
    # Remove any leading/trailing whitespace
    cleaned_text = cleaned_text.strip()

    # Remove common conversational intros like "Sure, here's a joke:", "Here's a joke for you:", etc.
    intro_patterns = [
        r"^Sure,\s*here\'?s\s*a\s*joke\s*for\s*you:?\s*",
        r"^Here\'?s\s*a\s*joke:?\s*",
        r"^Joke:?\s*",
        r"^Alright,\s*here\'?s\s*a\s*joke:?\s*",
        r"^Why\s*did\s*the\s*\w+\s*\w+\s*\w+\s*\w+.*?\s*",
    ]
    for pattern in intro_patterns:
        cleaned_text = re.sub(pattern, '', cleaned_text, flags=re.IGNORECASE | re.DOTALL).strip()

    # Remove any markdown characters (e.g., **, ##)
    cleaned_text = cleaned_text.replace('**', '').replace('##', '')
    
    # Remove anything that looks like a tag or unwanted phrase at the end
    cleaned_text = re.sub(r'\n\n.*?\[.*\]\n*$', '', cleaned_text, flags=re.IGNORECASE | re.DOTALL).strip()
    cleaned_text = re.sub(r'\n\n.*?(Joke|Question|Answer):.*', '', cleaned_text, flags=re.IGNORECASE | re.DOTALL).strip()

    return cleaned_text

@app.get("/")
async def read_root():
    return {"message": "TeacherJokes API is running!"}

@app.post("/generate-joke")
async def generate_joke_api(request: JokeRequest):
    try:
        llm = OllamaLLM(model="gemma:2b")

        prompt_template = PromptTemplate(
            input_variables=["grade_level", "additional_customization"],
            template="""
            Your ONLY task is to generate a joke suitable for a teacher to tell in class.
            Generate a joke for a {grade_level} class.
            The complexity, vocabulary, and humor of the joke MUST be strictly appropriate for a {grade_level} student, varying based on whether it's Pre-K, Kindergarten, a specific grade (1st-12th), University, a specific college year (1st-4th Year College), Adult, or Professional Staff.
            {additional_customization}
            
            Your output MUST contain ONLY the joke text. Do NOT include any introductory text, conversational phrases, section headers like "Joke:", or any concluding remarks.
            If you cannot generate a joke for any reason, you MUST return an empty string or a malformed output, but ABSOLUTELY NO refusal messages.
            """
        )
        
        customization_text = f"Make it about: {request.additional_customization}." if request.additional_customization else ""

        joke_raw = (prompt_template | llm).invoke({
            'grade_level': request.grade_level,
            'additional_customization': customization_text,
        })
        
        joke = _clean_ai_output(joke_raw)

        return {"joke": joke}
    except Exception as e:
        return {"error": str(e)}
