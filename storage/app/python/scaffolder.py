from fastapi import FastAPI, HTTPException, UploadFile, Form, File
from fastapi.responses import JSONResponse
from pydantic import BaseModel
from langchain_community.llms import Ollama
from langchain_core.prompts import ChatPromptTemplate
from langchain_community.document_loaders.pdf import PyPDFLoader
import shutil, os, re, tempfile, uvicorn, traceback

manual_topic_template = """
You are an educational assistant.

ONLY return literal comprehension questions and vocabulary definitions. Do NOT explain, summarize, or introduce the topic.

Parameters:
- Grade Level: {grade_level}
- Vocabulary Words: {vocab_limit}
- Literal Questions: {literal_questions}
- Topic: {topic}

Instructions:
- Generate exactly {literal_questions} literal comprehension questions. Use clear and simple language appropriate for {grade_level}.
- Then define up to {vocab_limit} vocabulary words appropriate for {grade_level}.
- Do NOT include paragraph explanations, summaries, or any extra commentary.
- Do NOT include "Answer:" lines or blank answer spaces.
- Do NOT use labels like "Word1:", "Definition:", etc.
- Start with the heading "Vocabulary:", followed by the word and its definition on a new line for each.
- Then use the heading "Questions:", and number and list the questions.
- Keep spacing clean and readable.
- Do NOT include any other text besides the literal questions and vocabulary definitions.
- List them in number order, e.g. "1. Question1? 2. Question2?" and e.g. "1. Word1: Definition of word1."

Example Output Format:
Vocabulary:
1. Word1: Definition of word1.
2. Word2: Definition of word2.
Questions:
1. First question?
2. Second question?
"""

pdf_topic_template = """
You are a knowledgeable and supportive virtual tutor.

You will receive content extracted from a textbook or document (such as a PDF). Your task is to explain this content in a way that is understandable to a student at the given grade level.

Parameters:
- Grade Level: {grade_level}
- Extracted Content: {topic}
- Number of Literal Questions: {literal_questions}
- Vocabulary Words to Define: {vocab_limit}

Instructions:
- No introductions, answers, or examples.t
- Include {literal_questions} literal comprehension questions.
- Define up to {vocab_limit} important vocabulary words.
- Use a style appropriate for the grade level: short sentences, simple language, and logical structure.
- Use bullet points or numbered lists when appropriate.

Respond ONLY with the scaffolded explanation.
"""

model = Ollama(model="llama3")
manual_prompt = ChatPromptTemplate.from_template(manual_topic_template)
pdf_prompt = ChatPromptTemplate.from_template(pdf_topic_template)

class ScaffolderInput(BaseModel):
    input_type: str
    topic: str = ""
    pdf_path: str = ""
    grade_level: str
    literal_questions: int
    vocab_limit: int

def load_pdf_content(pdf_path: str) -> str:
    if not os.path.exists(pdf_path):
        raise FileNotFoundError("PDF file not found.")
    loader = PyPDFLoader(pdf_path)
    documents = loader.load()
    return "\n".join(doc.page_content for doc in documents)

def clean_output(text: str) -> str:
    text = re.sub(r"\*\*(.*?)\*\*", r"\1", text)
    text = re.sub(r"\*(.*?)\*", r"\1", text)
    text = re.sub(r"^\s*[\*\-]\s*", "", text, flags=re.MULTILINE)
    return text.strip()

async def generate_output(
    input_type: str,
    grade_level: str,
    literal_questions: int,
    vocab_limit: int,
    topic: str = "",
    pdf_file: UploadFile = None,
):
    if input_type == "pdf":
        with tempfile.NamedTemporaryFile(delete=False, suffix=".pdf") as tmp:
            content = await pdf_file.read()
            tmp.write(content)
            tmp_path = tmp.name

        topic = load_pdf_content(tmp_path)
        os.unlink(tmp_path)
        prompt = pdf_prompt
    else:
        if not topic.strip():
            raise ValueError("Text input is required")
        prompt = manual_prompt

    prompt_input = {
        "grade_level": grade_level,
        "literal_questions": literal_questions,
        "vocab_limit": vocab_limit,
        "topic": topic
    }

    chain = prompt | model
    result = chain.invoke(prompt_input)
    return clean_output(result)

app = FastAPI()

@app.post("/scaffolder")
async def scaffolder_api(
    input_type: str = Form(...),
    topic: str = Form(""),
    pdf_file: UploadFile = File(None),
    grade_level: str = Form(...),
    literal_questions: int = Form(...),
    vocab_limit: int = Form(...)
):
    try:
        if input_type == "pdf" and not pdf_file:
            raise HTTPException(status_code=400, detail="PDF file required for PDF input_type")

        output = await generate_output(
            input_type=input_type,
            topic=topic,
            pdf_file=pdf_file,
            grade_level=grade_level,
            literal_questions=literal_questions,
            vocab_limit=vocab_limit
        )

        return {"output": output}
    except Exception as e:
        traceback_str = traceback.format_exc()
        print(traceback_str)
        return JSONResponse(status_code=500, content={"detail": str(e), "trace": traceback_str})

if __name__ == "__main__":
    uvicorn.run("scaffolder_agent:app", host="127.0.0.1", port=5001, reload=True)
