from fastapi import FastAPI, HTTPException, UploadFile, Form, File, Depends
from fastapi.responses import JSONResponse
from pydantic import BaseModel
from langchain_community.llms import Ollama
from langchain_core.prompts import ChatPromptTemplate
from langchain_community.document_loaders.pdf import PyPDFLoader
import shutil, os, re, tempfile, uvicorn, traceback, sys
from typing import Optional
from langchain_core.messages import HumanMessage, AIMessage
from fastapi.middleware.cors import CORSMiddleware

current_script_dir = os.path.dirname(os.path.abspath(__file__))
project_root = os.path.join(current_script_dir, '..', '..')
sys.path.insert(0, project_root)

from python.chat_router import chat_router
from python.db_utilss import create_session_and_parameter_inputs, insert_message

# Instruction template
template = """
Your task is to draft a professional email response based on the user's intent and the original message received.

Parameters:
- Author Name: {author}
- Email to Respond To: {email}
- Communication Intent: {intent}
- Tone: {tone}

Instructions:
- Compose an email reply that reflects the user's intent and the tone.
- Do NOT reverse the roles: this is a response **from the user**, not from the original sender.
- Always begin with a line like:
    - Subject: Re: [your subject or topic] — with no leading spaces or indentation.
- Adjust the greeting and closing to reflect the selected tone:
    - Formal: "Dear [Name]," | "Sincerely," or "With respect,"
    - Friendly: "Hi [Name]," or "Hello [Name]," | "Warm regards," or "Take care,"
    - Concise: "Hello [Name]," | "Best," or "Regards,"
    - Apologetic: "Dear [Name]," | "Apologies," or "Thank you again,"
    - Assertive: "Dear [Name]," | "Respectfully," or "Thank you for your attention,"
- Follow any formatting preferences (e.g., word limit, paragraph count, bullets, numbered list) exactly as given. Match the requested structure and layout.
- Do not use placeholder phrases like “[Name]”; use an inferred or polite general salutation if needed.
- Keep formatting readable with paragraph breaks.
- End with a closing line and the sender's name.

Respond with the email only — no labels, commentary, or explanation.
"""

app = FastAPI(debug=True)
app.include_router(chat_router)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # or Laravel origin like "http://localhost:8000"
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

class ResponderInput(BaseModel):
    user_id: int
    author: str
    email: str
    intent: str
    tone: str
    message_id: Optional[str] = None

    @classmethod
    def as_form(
        cls,
        user_id: int = Form(...),
        author: str = Form(""),
        email: str = Form(""),
        intent: str = Form(""),
        tone: str = Form(...),
        message_id: Optional[str] = Form(default=None)
    ):
        return cls(
            user_id=user_id,
            author=author,
            email=email,
            intent=intent,
            tone=tone,
            message_id=message_id
        )

# LangChain setup
model = Ollama(model="llama3")
concept_template = ChatPromptTemplate.from_template(template)

# Clean output from formatting artifacts
def clean_output(text: str) -> str:
    import re

    text = text.strip()

    # Normalize Subject line: extract and rewrap
    match = re.match(r"^Subject:\s*(.+)", text)
    if match:
        subject = match.group(0)
        remaining = text[len(subject):].lstrip()
        text = f"<p>{subject}</p><br>{remaining}"
    else:
        # If no clear Subject line, keep the original
        text = text

    # Remove markdown artifacts
    text = re.sub(r"\*\*(.*?)\*\*", r"\1", text)  # bold markdown
    text = re.sub(r"\*(.*?)\*", r"\1", text)      # italic markdown

    return text.strip()

# Email generation logic
async def generate_output(
    tone: str,
    author: str = "",
    email: str = "",
    intent: str = "",
):
    prompt_input = {
        "author": author,
        "email": email,
        "intent": intent,
        "tone": tone,
    }

    chain = concept_template | model
    result = chain.invoke(prompt_input)
    return clean_output(result)

@app.post("/responder")
async def responder_api(
    form_data: ResponderInput = Depends(ResponderInput.as_form),
    pdf_file: UploadFile = File(None)
):
    try:
        output = await generate_output(
            author=form_data.author,
            email=form_data.email,
            intent=form_data.intent,
            tone=form_data.tone,
        )

        scope_vars = {
            "author": form_data.author,
            "email": form_data.email,
            "intent": form_data.intent,
            "tone": form_data.tone
        }

        session_id = create_session_and_parameter_inputs(
            user_id=form_data.user_id,
            agent_id=11,
            scope_vars=scope_vars,
            human_topic=form_data.intent,
            ai_output=output
        )

        return {"output": output, "message_id": session_id}
    except Exception as e:
        traceback_str = traceback.format_exc()
        print(traceback_str)
        return JSONResponse(status_code=500, content={"detail": str(e), "trace": traceback_str})

if __name__ == "__main__":
    uvicorn.run("responder:app", host="127.0.0.1", port=5001, reload=True)