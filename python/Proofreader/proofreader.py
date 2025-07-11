#!/usr/bin/env python3
from http.client import HTTPException
import re
from pydantic import BaseModel
from langchain_ollama import OllamaLLM
from langchain_core.prompts import ChatPromptTemplate
from fastapi import Form, Depends, FastAPI, HTTPException
from typing import Optional
import sys
import os
from fastapi.middleware.cors import CORSMiddleware
project_root = os.path.abspath(os.path.join(os.path.dirname(__file__), '..', '..'))
sys.path.insert(0,project_root)

from python.db_utils_final import create_session_and_parameter_inputs, insert_message
from python.chat_router_final import chat_router

app = FastAPI(debug=True)
app.include_router(chat_router)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # or Laravel origin like "http://localhost:8000"
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

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
prompt_template = """
You are a professional proofreader. Proofread the following text and:
1. Correct grammar errors
2. Fix spelling mistakes
3. Adjust punctuation
4. Improve clarity while preserving the meaning
5. Return the corrected version and a list of changes

Tone guide:
{profile}

Text:
{text}

Respond in this format:

Corrected text:
[Your corrected version]



Changes made:
[List of major changes]


"""

model = OllamaLLM(model="gemma3:latest")
proofreader_prompt = ChatPromptTemplate.from_template(prompt_template)


class ProofreadInput(BaseModel):
    user_id: int
    profile: str
    text: str
    message_id: Optional[int] = None

    @classmethod
    def as_form(
        cls,
        user_id: int = Form(...),
        profile: str = Form(...),
        text: str = Form(...),
        message_id: Optional[int] = Form(None)
    ):
        return cls(
            user_id=user_id,
            profile=profile,
            text=text,
            message_id=message_id
        )
async def proofread_agent(profile: str, text: str) -> str:
    # if profile not in PROFILES:
    #     raise ValueError(f"Unknown profile '{profile}'. Must be one of: {', '.join(PROFILES.keys())}")
    chain = proofreader_prompt | model
    result = chain.invoke({
        # "instructions": PROFILES[profile]["instructions"],
        "profile": profile,
        "text": text
    })

    return clean_output(result)  # Make sure clean_output exists and works

def clean_output(text: str) -> str:
    # text = re.sub(r"\*\*(.*?)\*\*", r"\1", text)  # Remove bold formatting
    # text = re.sub(r"\*(.*?)\*", r"\1", text)  # Remove italic formatting
    # text = re.sub(r"^\s*[\*\-]\s*", "", text, flags=re.MULTILINE)  # Remove bullet points
    return text.strip()

@app.post("/proofread")
async def proofread_endpoint(data: ProofreadInput = Depends(ProofreadInput.as_form)):
    if not data.profile or not data.text:
        raise HTTPException(status_code=400, detail="Profile and text are required.")
    try:
        explanation = await proofread_agent(data.profile, data.text)
        filled_prompt = prompt_template.format(profile=data.profile, text=data.text)

        scope_vars = {
            "profile": data.profile,
            "text": data.text
        }

        session_id = create_session_and_parameter_inputs(
            user_id=data.user_id,
            agent_id=12,  # Default agent_id for proofreader
            scope_vars=scope_vars,
            human_topic=data.text,
            ai_output=explanation,
            agent_prompt=filled_prompt
        )
        return {"corrected": explanation, "message_id": session_id}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))




# # Extract text from first 2 pages of a PDF
# def extract_text_from_pdf(file_path: str) -> str:
#     with open(file_path, "rb") as f:
#         reader = PyPDF2.PdfReader(f)
#         pages = reader.pages[:2]
#         return "\n".join([page.extract_text() or "" for page in pages])

# # Clean output formatting
# def clean_output(text: str) -> str:
#     text = re.sub(r"\*\*(.*?)\*\*", r"\1", text)
#     text = re.sub(r"\*(.*?)\*", r"\1", text)
#     text = re.sub(r"^\s*[\*\-]\s*", "", text, flags=re.MULTILINE)
#     return text.strip()

# # Main proofread function
# async def run_proofread(profile: str, text: str = "", pdf_file: UploadFile = None) -> dict:
#     if pdf_file:
#         try:
#             with tempfile.NamedTemporaryFile(delete=False, suffix=".pdf") as tmp:
#                 contents = await pdf_file.read()
#                 tmp.write(contents)
#                 tmp_path = tmp.name

#             text = extract_text_from_pdf(tmp_path) 
#             os.unlink(tmp_path)   
#         except Exception as e:
#             raise ValueError(f"Failed to read PDF: {e}")

#     if not text.strip():
#         raise ValueError("No text provided to proofread.")

#     profile_cfg = PROFILES.get(profile)
#     if not profile_cfg:
#         raise ValueError(f"Unknown profile: {profile}")

#     instructions = profile_cfg["instructions"]

#     if len(text) > 2000:
#         instructions += (
#             "\nNote: This text may come from a PDF, so ignore formatting issues and focus on clarity."
#         )

#     # Prompt Template
    

#     prompt = ChatPromptTemplate.from_template(prompt_template)
#     model = OllamaLLM(model=DEFAULT_MODEL)
#     chain = prompt | model

#     raw_output = chain.invoke({"input_text": text})

#     try:
#         corrected = raw_output.split("Corrected text:")[1].split("===END_CORRECTED===")[0].strip()
#         changes_block = raw_output.split("Changes made:")[1].split("===END_CHANGES===")[0].strip()
#         changes = [
#             line.lstrip("*-•1234. ").strip()
#             for line in changes_block.splitlines()
#             if line.strip()
#         ]
#     except Exception:
#         corrected = raw_output.strip()
#         changes = []

#     return {"corrected": clean_output(corrected), "changes": changes}
