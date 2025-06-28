#!/usr/bin/env python3
import os
import PyPDF2
from langchain_ollama import OllamaLLM
from langchain_core.prompts import ChatPromptTemplate
from pydantic import BaseModel

class ProofreaderInput(BaseModel):
    profile: str
    text: str = ""
    pdf_path: str = ""

# Hard-coded style profiles
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

def run_proofread(profile: str, text: str = "", pdf_path: str = "") -> dict:
    """
    Select the style profile, optionally extract text from a PDF,
    run the LLM, and return the corrected version and list of changes.
    """

    # If a PDF is provided, extract its text
    if pdf_path:
        if not os.path.exists(pdf_path):
            raise ValueError(f"PDF file not found at: {pdf_path}")
        try:
            with open(pdf_path, "rb") as f:
                reader = PyPDF2.PdfReader(f)
                text = "\n".join([page.extract_text() or "" for page in reader.pages])
        except Exception as e:
            raise ValueError(f"Failed to read PDF: {e}")

    if not text.strip():
        raise ValueError("No text provided to proofread.")

    profile_cfg = PROFILES.get(profile)
    if not profile_cfg:
        raise ValueError(f"Unknown profile: {profile}")

    instructions = profile_cfg["instructions"]

    # Add note for long text
    if len(text) > 2000:
        instructions += (
            "\nNote: This text may come from a PDF, so ignore formatting issues and focus on clarity."
        )

    # Final prompt
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
    model  = OllamaLLM(model=DEFAULT_MODEL)
    chain  = prompt | model

    raw_output = chain.invoke({"input_text": text})

    try:
        corrected_section = raw_output.split("Corrected text:")[1].split("===END_CORRECTED===")[0].strip()
        changes_section = raw_output.split("Changes made:")[1].split("===END_CHANGES===")[0].strip()
        corrected = corrected_section
        changes = [
            line.lstrip("*-•1234. ").strip()
            for line in changes_section.splitlines()
            if line.strip()
        ]
    except Exception:
        corrected = raw_output.strip()
        changes = []

    return {"corrected": corrected, "changes": changes}
