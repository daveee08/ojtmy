from fastapi import FastAPI, Form
from fastapi.middleware.cors import CORSMiddleware
from langchain_ollama import OllamaLLM as Ollama
from langchain.prompts import PromptTemplate

app = FastAPI()

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_methods=["*"],
    allow_headers=["*"],
)

@app.post("/generate-idea")
async def generate_idea(grade_level: str = Form(...), prompt: str = Form(...)):
    print("GRADE LEVEL:", grade_level)
    print("PROMPT:", prompt)

    prompt_template = PromptTemplate.from_template("""
You are a helpful assistant. Based on the grade level "{grade_level}", come up with creative, practical, and thoughtful ideas for the following request:

{prompt}

Only return the list of ideas. Do not add extra explanation.
""")

    llm = Ollama(model="gemma3:4b")
    chain = prompt_template | llm

    result = chain.invoke({
        "grade_level": grade_level,
        "prompt": prompt
    })

    print("RESULT:", result)
    return {"idea": result.strip()}

