from fastapi import FastAPI, Form
from fastapi.middleware.cors import CORSMiddleware
from langchain_ollama import OllamaLLM as Ollama
from langchain.prompts import PromptTemplate

app = FastAPI()

# Enable CORS for frontend (like Laravel)
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # OK for local dev
    allow_methods=["*"],
    allow_headers=["*"],
)

@app.post("/generate-email")
async def generate_email(content: str = Form(...)):
    prompt_template = """
You are an expert at writing professional and polite emails.

Your task is to generate a formal, respectful email using the user's input.

Details:
{content}

Write an email that:
- Has a clear subject
- Starts with a greeting
- Explains the situation clearly
- Ends with a polite closing

Return only the email text. No notes or explanations.
"""
    prompt = PromptTemplate.from_template(prompt_template)
    llm = Ollama(model="gemma3:4b")  # Match your model
    chain = prompt | llm
    result = chain.invoke({"content": content.strip()})
    return {"email": result.strip()}
