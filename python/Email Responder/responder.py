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
prompt_template = """
Your task is toGenerate a custom professional email in response to an email that you received based on the user's intent and the original message received.

Parameters:
- Author Name: {author}
- Email to Respond To: {email}
- Communication Intent: {intent}
- Tone: {tone}

Instructions:

Write an email from the user (the author) replying to the provided email.
- Reflect the user’s communication intent and follow the selected tone accurately.
- The output should begin with this format on the first line:
- Subject: Re: [insert appropriate subject from the email]
- Use appropriate greetings and sign-offs based on the specified tone:
    - Formal: “Dear [Name],” | “Sincerely,” or “With respect,”
    - Friendly: “Hi [Name],” or “Hello [Name],” | “Warm regards,” or “Take care,”
    - Concise: “Hello [Name],” | “Best,” or “Regards,”
    - Apologetic: “Dear [Name],” | “Apologies,” or “Thank you again,”
    - Assertive: “Dear [Name],” | “Respectfully,” or “Thank you for your attention,”
- Avoid generic placeholders (e.g., “[Name]”); use a specific name if present, or a courteous general greeting (e.g., “Dear Hiring Team,”).
- Use a specific name if mentioned; otherwise, use a polite general salutation (e.g., “Dear Hiring Team,”).
- Keep the tone consistent throughout the email.
- Format the email with clear paragraph breaks for readability.
- Apply any requested formatting or structure (e.g., word limit, bullet points) as needed.
- End with a proper sign-off and the author’s full name.

Response Only — do not include instructions, tags, or explanations.
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
model = Ollama(model="gemma3:4b")
prompt_template = ChatPromptTemplate.from_template(prompt_template)

# Clean output from formatting artifacts
def clean_output(text: str) -> str:
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

    chain = prompt_template | model
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

        filled_prompt = prompt_template.format(
            author=form_data.author.strip(),
            email=form_data.email.strip(),
            intent=form_data.intent.strip(),
            tone=form_data.tone.strip()
        )

        session_id = create_session_and_parameter_inputs(
            user_id=form_data.user_id,
            agent_id=11,
            scope_vars=scope_vars,
            human_topic=form_data.intent,
            ai_output=output,
            agent_prompt=filled_prompt
        )

        return {"output": output, "message_id": session_id}
    except Exception as e:
        traceback_str = traceback.format_exc()
        print(traceback_str)
        return JSONResponse(status_code=500, content={"detail": str(e), "trace": traceback_str})

if __name__ == "__main__":
    uvicorn.run("responder:app", host="127.0.0.1", port=5001, reload=True)