#!/usr/bin/env python3
import os
import PyPDF2
from langchain_ollama import OllamaLLM
from langchain_core.prompts import ChatPromptTemplate

# Hard-coded profiles and instructions
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

def run_proofread(profile: str, text: str = "", pdf_path: str = None) -> dict:
    """
    Select the profile, optionally extract text from a PDF,
    and return a dict with 'corrected' and 'changes'.
    """
    # If PDF is provided, extract text
    if pdf_path:
        if not os.path.exists(pdf_path):
            raise ValueError(f"PDF not found at: {pdf_path}")
        with open(pdf_path, "rb") as f:
            reader = PyPDF2.PdfReader(f)
            text = "\n".join([page.extract_text() or "" for page in reader.pages])

    if not text.strip():
        raise ValueError("No text provided to proofread.")

    # Validate profile
    profile_cfg = PROFILES.get(profile)
    if not profile_cfg:
        raise ValueError(f"Unknown profile: {profile}")

    instructions = profile_cfg["instructions"]

    # Adjust prompt for long input (optional heuristic)
    if len(text) > 2000:
        instructions += (
            "\nThe input may come from a PDF and could contain formatting issues. "
            "Ignore formatting artifacts and focus on correcting the content."
        )

    # Prompt template
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

    # Parse output
    try:
        corrected_section = raw_output.split("Corrected text:")[1].split("===END_CORRECTED===")[0].strip()
        changes_section = raw_output.split("Changes made:")[1].split("===END_CHANGES===")[0].strip()
        corrected = corrected_section
        changes = [
            line.lstrip("*-• ").strip()
            for line in changes_section.splitlines()
            if line.strip()
        ]
    except Exception:
        corrected = raw_output.strip()
        changes = []

    return {"corrected": corrected, "changes": changes}
