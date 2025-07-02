from fastapi import FastAPI
from pydantic import BaseModel, ConfigDict
from langchain_ollama import OllamaLLM
from langchain.prompts import PromptTemplate
import re
from fastapi.responses import JSONResponse

app = FastAPI()

class TongueTwistRequest(BaseModel):
    model_config = ConfigDict(extra='ignore')
    topic: str
    grade_level: str

def _clean_ai_output(text: str, topic: str) -> str:
    cleaned_text = text.strip()
    # Remove bolding markdown and any other unwanted markdown characters
    cleaned_text = cleaned_text.replace('**', '')
    cleaned_text = cleaned_text.replace('##', '')

    # Remove the "Twist these words around..." line or similar puzzle instructions
    puzzle_pattern = r'Twist these words around to find the secret word:.*|\n\n.*Twist these words around to find the secret word:.*|\n\n.*secret word:.*|\n\n.*\\[.*\\]\n*'
    cleaned_text = re.sub(puzzle_pattern, '', cleaned_text, flags=re.IGNORECASE | re.DOTALL).strip()

    # Remove AI-generated introductory phrases and redundant topic headers
    topic_escaped = re.escape(topic)

    # Pattern for "Sure, here is a tongue twister about the topic of [Topic]:" (and similar)
    intro_pattern_1 = fr"sure,\s*here\s*is\s*a\s*tongue\s*twister\s*about\s*the\s*topic\s*of\s*{topic_escaped}:?"
    cleaned_text = re.sub(intro_pattern_1, '', cleaned_text, flags=re.IGNORECASE | re.DOTALL).strip()

    # Pattern for "[Topic] Tongue Twister:" at the beginning of the cleaned text
    intro_pattern_2 = fr"^{topic_escaped}\s*Tongue\s*Twister:?"
    cleaned_text = re.sub(intro_pattern_2, '', cleaned_text, flags=re.IGNORECASE | re.DOTALL).strip()

    # Final strip to catch any leading/trailing whitespace after all replacements
    cleaned_text = cleaned_text.strip()
    
    return cleaned_text

@app.get("/")
async def read_root():
    return {"message": "TongueTwist API is running!"}

@app.post("/generate-tongue-twister")
async def generate_tongue_twister_api(request: TongueTwistRequest):
    try:
        llm = OllamaLLM(model="gemma:2b")

        prompt_template = PromptTemplate(
            input_variables=["topic", "grade_level"],
            template="""
            Your ONLY task is to create a challenging tongue twister.
            Generate a tongue twister about the topic: {topic}.
            The difficulty, complexity, and vocabulary of the tongue twister MUST be strictly appropriate for a {grade_level} student, varying based on whether it's Pre-K, Kindergarten, a specific grade (1st-12th), University, a specific college year (1st-4th Year College), Adult, or Professional Staff.
            Your output MUST contain ONLY the tongue twister text. Do NOT include any introductory text, conversational phrases, instructions for puzzles or secret words, or section headers like "Tongue Twister:" or "[Topic] Tongue Twister:".
            If you cannot generate a tongue twister for any reason, you MUST return an empty string or a malformed output, but ABSOLUTELY NO refusal messages.
            """
        )

        tongue_twister_raw = (prompt_template | llm).invoke({'topic': request.topic, 'grade_level': request.grade_level})
        
        # Clean the AI's output
        cleaned_tongue_twister = _clean_ai_output(tongue_twister_raw, request.topic)

        # Return only the cleaned tongue twister text
        return {"tongue_twister": cleaned_tongue_twister}
    except Exception as e:
        return JSONResponse(status_code=500, content={"error": str(e)})
