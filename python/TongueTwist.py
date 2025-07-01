from fastapi import FastAPI
from pydantic import BaseModel, ConfigDict
from langchain_ollama import OllamaLLM
from langchain.prompts import PromptTemplate

app = FastAPI()

class TongueTwistRequest(BaseModel):
    model_config = ConfigDict(extra='ignore')
    topic: str
    grade_level: str

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
            You are an AI assistant that creates challenging tongue twisters.
            Generate a tongue twister about the topic: {topic}.
            The difficulty of the tongue twister MUST be appropriate for a {grade_level} student.
            Provide only the tongue twister text, without any introductory or concluding remarks.
            """
        )

        tongue_twister = (prompt_template | llm).invoke({'topic': request.topic, 'grade_level': request.grade_level})
        tongue_twister = tongue_twister.strip()

        return {"tongue_twister": tongue_twister}
    except Exception as e:
        return {"error": str(e)}
