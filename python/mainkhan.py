from fastapi import FastAPI, UploadFile, File, Form
from fastapi.middleware.cors import CORSMiddleware
from langchain_ollama import OllamaLLM as Ollama
from langchain.prompts import PromptTemplate
from langchain_community.document_loaders import PyPDFLoader
import tempfile, os

app = FastAPI()

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_methods=["*"],
    allow_headers=["*"],
)

def summarize_text(text: str, conditions: str) -> str:
    clean_text = " ".join(text.strip().replace("\n", " ").replace("\r", "").split())[:3000]
    prompt_template = """
You are an intelligent and precise summarization assistant.

Your task is to summarize the following content based on the user's exact instructions.

------------------------
Content to Summarize:
{text}

User Instructions:
{conditions}
------------------------

Important:
- Follow the structure, tone, and formatting exactly as requested by the user.
- Do not add your own formatting or assumptions.
- If bullet points, markdown, or specific paragraph structure is requested, apply them precisely.
- Return only the summary — do not include extra explanations or labels.

Now generate the summary below:
"""
    prompt = PromptTemplate.from_template(prompt_template)
    llm = Ollama(model="gemma3:4b")
    chain = prompt | llm
    result = chain.invoke({"text": clean_text, "conditions": conditions})
    return result.strip()


@app.post("/summarize")
async def summarize(
    conditions: str = Form(...),
    text: str = Form(""),
    pdf: UploadFile = File(None)
):
    if pdf and pdf.filename and pdf.content_type == "application/pdf":
        contents = await pdf.read()
        if contents:
            with tempfile.NamedTemporaryFile(delete=False, suffix=".pdf") as tmp:
                tmp.write(contents)
                tmp_path = tmp.name

            loader = PyPDFLoader(tmp_path)
            pages = loader.load()
            os.remove(tmp_path)
            text = "\n".join([page.page_content for page in pages])

    if not text.strip():
        return {"summary": "No valid text provided."}

    summary = summarize_text(text, conditions)
    return {"summary": summary}
