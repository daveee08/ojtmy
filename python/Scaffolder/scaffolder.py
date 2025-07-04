from fastapi import FastAPI, HTTPException, UploadFile, Form, File
from fastapi.responses import JSONResponse
from pydantic import BaseModel
from langchain_community.llms import Ollama
from langchain_core.prompts import ChatPromptTemplate
from langchain_community.document_loaders.pdf import PyPDFLoader
import os, re, tempfile, uvicorn
import traceback

manual_topic_template = """
You are an educational assistant.

Your task is to generate only **literal comprehension questions** and **vocabulary definitions** based on a given academic text. Your goal is to support accurate, age-appropriate understanding of the topic for {grade_level} students.

Parameters:
- Grade Level: {grade_level}
- Topic: {topic}
- Number of Vocabulary Words: {vocab_limit}
- Number of Literal Questions: {literal_questions}

Output Instructions:

1. Start with the heading **Vocabulary:** and list up to {vocab_limit} important words found in the text.
   - Each word must be followed by a short, clear definition suitable for {grade_level}.
   - Use simple, age-appropriate language. Avoid:
     - Technical jargon
     - Abstract phrasing
     - Circular or overly complex definitions

2. Then write the heading **Questions:** and list exactly {literal_questions} literal comprehension questions.
   - Questions must be based strictly on facts **explicitly stated** in the original text.
   - Avoid interpretation, inference, summarizing, or "Why/How" questions unless clearly supported by the text.
   - Phrase questions in a direct, concrete way that matches the reading level.

3. Use this exact format (and nothing else):

Vocabulary:
1. Word: Definition.
2. Word: Definition.

Questions:
1. Literal question?
2. Literal question?

Formatting Rules:
- Do **not** include any of the following:
  - Explanations, summaries, or extra commentary
  - Labels such as "Definition:", "Term:", or "Answer:"
  - Paragraphs or introductory text
  - Any text outside the required Vocabulary and Questions sections

Return only the formatted output. Do not add headings, titles, or instructional notes.
"""

pdf_topic_template = """
You are an educational assistant.

Your task is to generate only **literal comprehension questions** and **vocabulary definitions** based on a given academic text. Your goal is to support accurate, age-appropriate understanding of the topic for {grade_level} students.

Parameters:
- Grade Level: {grade_level}
- Topic: {topic}
- Number of Vocabulary Words: {vocab_limit}
- Number of Literal Questions: {literal_questions}

Output Instructions:

1. Start with the heading **Vocabulary:** and list up to {vocab_limit} important words found in the text.
   - Each word must be followed by a short, clear definition suitable for {grade_level}.
   - Use simple, age-appropriate language. Avoid:
     - Technical jargon
     - Abstract phrasing
     - Circular or overly complex definitions

2. Then write the heading **Questions:** and list exactly {literal_questions} literal comprehension questions.
   - Questions must be based strictly on facts **explicitly stated** in the original text.
   - Avoid interpretation, inference, summarizing, or "Why/How" questions unless clearly supported by the text.
   - Phrase questions in a direct, concrete way that matches the reading level.

3. Use this exact format (and nothing else):

Vocabulary:
1. Word: Definition.
2. Word: Definition.

Questions:
1. Literal question?
2. Literal question?

Formatting Rules:
- Do **not** include any of the following:
  - Explanations, summaries, or extra commentary
  - Labels such as "Definition:", "Term:", or "Answer:"
  - Paragraphs or introductory text
  - Any text outside the required Vocabulary and Questions sections
  - Answer choices, explanations, or blank answer spaces.

Return only the formatted output. Do not add headings, titles, or instructional notes.
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
        print("[DEBUG] Full Traceback:\n", traceback_str, flush=True)
        return JSONResponse(status_code=500, content={"detail": str(e), "trace": traceback_str})

if __name__ == "__main__":
    uvicorn.run("scaffolder_agent:app", host="127.0.0.1", port=5001, reload=True)
