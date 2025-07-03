from fastapi import FastAPI
from pydantic import BaseModel
import requests

app = FastAPI()

OLLAMA_URL = "http://localhost:11434/api/generate"

class ChatRequest(BaseModel):
    prompt: str
    model: str = "gemma:2b"

@app.post("/chat")
def chat(req: ChatRequest):
    payload = {
        "model": req.model,
        "prompt": req.prompt,
        "stream": False  # Turn off streaming for simple response
    }

    try:
        response = requests.post(OLLAMA_URL, json=payload)
        response.raise_for_status()
        data = response.json()
        return {"response": data["response"]}
    except Exception as e:
        return {"error": str(e)}


#  curl -X POST http://192.168.50.108:8000/chat ^   -H "Content-Type: application/json" ^   -d "{\"prompt\": \"Explain quantum physics in simple terms.\"}"

#uvicorn ollama_run:app --host 192.168.50.108 --port 8000 --reload

