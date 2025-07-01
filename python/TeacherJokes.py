from fastapi import FastAPI
from pydantic import BaseModel, ConfigDict
from langchain_ollama import OllamaLLM
from langchain.prompts import PromptTemplate

app = FastAPI()

class JokeRequest(BaseModel):
    model_config = ConfigDict(extra='ignore')
    grade_level: str
    additional_customization: str | None = None

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
            You are an AI assistant that generates jokes suitable for a teacher to tell in class.
            Generate a joke for a {grade_level} class.
            {additional_customization}
            
            Provide only the joke text, without any introductory or concluding remarks.
            """
        )
        
        customization_text = f"Make it about: {request.additional_customization}." if request.additional_customization else ""

        joke = (prompt_template | llm).invoke({
            'grade_level': request.grade_level,
            'additional_customization': customization_text,
        })
        joke = joke.strip()

        return {"joke": joke}
    except Exception as e:
        return {"error": str(e)}
