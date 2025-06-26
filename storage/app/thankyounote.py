from fastapi import FastAPI, Form
from fastapi.middleware.cors import CORSMiddleware
from langchain_ollama import OllamaLLM as Ollama
from langchain.prompts import PromptTemplate

app = FastAPI()

# Enable CORS for Laravel or any frontend
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_methods=["*"],
    allow_headers=["*"],
)

@app.post("/generate-thankyou")
async def generate_thank_you(reason: str = Form(...)):
    prompt_template = """
You are a thoughtful and kind assistant.

Your task is to write a sincere and warm thank-you note based on what the user is thankful for.

Reason for thanks:
{reason}

Write a thank-you note that:
- Expresses genuine appreciation
- Mentions specific contributions or actions
- Sounds human and heartfelt
- Ends with a warm closing

Return only the thank-you message. Do not include any explanations or formatting notes.
"""
    prompt = PromptTemplate.from_template(prompt_template)
    llm = Ollama(model="gemma3:4b")
    chain = prompt | llm
    result = chain.invoke({"reason": reason.strip()})
    return {"thank_you_note": result.strip()}
