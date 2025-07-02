#!/usr/bin/env python3
import os
import re
import tempfile
from fastapi import UploadFile
from langchain_ollama import OllamaLLM
from langchain_core.prompts import ChatPromptTemplate
from pydantic import BaseModel
import PyPDF2

class ProofreaderInput(BaseModel):
    profile: str
    text: str = ""

# Style Profiles
PROFILES = {
    "academic": {
        "description": "Formal tone, avoid contractions, focus on technical accuracy.",
        "instructions": "Use a formal, academic tone. Avoid contractions and colloquialisms."
    },
    "casual": {
        "description": "Friendly tone, allow contractions, simpler phrasing.",
        "instructions": "Use a casual, conversational tone. Feel free to use contractions and natural speech."
    },
    "concise": {
        "description": "Very brief, to-the-point corrections.",
        "instructions": "Keep the corrected text as concise as possible, trimming unnecessary words."
    }
}

DEFAULT_MODEL = "gemma3"

# Extract text from first 2 pages of a PDF
def extract_text_from_pdf(file_path: str) -> str:
    with open(file_path, "rb") as f:
        reader = PyPDF2.PdfReader(f)
        pages = reader.pages[:2]
        return "\n".join([page.extract_text() or "" for page in pages])

# Clean output formatting
def clean_output(text: str) -> str:
    text = re.sub(r"\*\*(.*?)\*\*", r"\1", text)
    text = re.sub(r"\*(.*?)\*", r"\1", text)
    text = re.sub(r"^\s*[\*\-]\s*", "", text, flags=re.MULTILINE)
    return text.strip()

# Main proofread function
async def run_proofread(profile: str, text: str = "", pdf_file: UploadFile = None) -> dict:
    if pdf_file:
        try:
            with tempfile.NamedTemporaryFile(delete=False, suffix=".pdf") as tmp:
                contents = await pdf_file.read()
                tmp.write(contents)
                tmp_path = tmp.name

            text = extract_text_from_pdf(tmp_path) 
            os.unlink(tmp_path)   
        except Exception as e:
            raise ValueError(f"Failed to read PDF: {e}")

    if not text.strip():
        raise ValueError("No text provided to proofread.")

    profile_cfg = PROFILES.get(profile)
    if not profile_cfg:
        raise ValueError(f"Unknown profile: {profile}")

    instructions = profile_cfg["instructions"]

    if len(text) > 2000:
        instructions += (
            "\nNote: This text may come from a PDF, so ignore formatting issues and focus on clarity."
        )

    # Prompt Template
    prompt_template = f"""
{instructions}

You are a professional proofreader. Proofread the following text and:
1. Correct grammar errors
2. Fix spelling mistakes
3. Adjust punctuation
4. Improve clarity while preserving the meaning
5. Return the corrected version and a list of changes

Text:
{text}

Respond in this format:
Corrected text:
[Your corrected version]

===END_CORRECTED===

Changes made:
[List of major changes]

===END_CHANGES===
"""

    prompt = ChatPromptTemplate.from_template(prompt_template)
    model = OllamaLLM(model=DEFAULT_MODEL)
    chain = prompt | model

    raw_output = chain.invoke({"input_text": text})

    try:
        corrected = raw_output.split("Corrected text:")[1].split("===END_CORRECTED===")[0].strip()
        changes_block = raw_output.split("Changes made:")[1].split("===END_CHANGES===")[0].strip()
        changes = [
            line.lstrip("*-•1234. ").strip()
            for line in changes_block.splitlines()
            if line.strip()
        ]
    except Exception:
        corrected = raw_output.strip()
        changes = []

    return {"corrected": clean_output(corrected), "changes": changes}
