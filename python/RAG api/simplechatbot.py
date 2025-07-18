from fastapi import FastAPI
from pydantic import BaseModel
import requests

app = FastAPI()

OLLAMA_URL = "http://localhost:11434/api/generate"
OLLAMA_MODEL = "llama3"

# Temporary in-memory history (for 1 user/session only)
chat_history = []

class ChatInput(BaseModel):
    prompt: str

@app.post("/chat")
def chat(input: ChatInput):
    try:
        # Add user's message to history
        chat_history.append(f"User: {input.prompt}")

        # Combine history for context
        context_prompt = "\n".join(chat_history) + "\nAI:"

        payload = {
            "model": OLLAMA_MODEL,
            "prompt": context_prompt,
            "stream": False
        }

        response = requests.post(OLLAMA_URL, json=payload)
        response.raise_for_status()
        data = response.json()
        reply = data.get("response", "").strip()

        # Save AI response in history
        chat_history.append(f"AI: {reply}")

        return {"response": reply}

    except Exception as e:
        return {"error": str(e)}
