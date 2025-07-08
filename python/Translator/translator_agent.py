from pydantic import BaseModel
from langchain_ollama import OllamaLLM
from langchain_core.prompts import ChatPromptTemplate
from fastapi import FastAPI, HTTPException, UploadFile, Form
from fastapi.middleware.cors import CORSMiddleware
import uvicorn
from fastapi import Form, Depends
import httpx
import sys
import os
# Ensure parent directory (python/) is in the path
sys.path.append(os.path.abspath(os.path.join(os.path.dirname(__file__), '..')))
from db_utils import insert_message, insert_session_and_message, load_messages_by_agent_and_user, load_messages_by_session_id, get_db_connection, fetch_messages, add_message, clear_messages_by_session_id, get_all_session_ids, get_messages_by_session_id, create_session_and_parameter_inputs
from langchain_core.messages import HumanMessage, AIMessage, BaseMessage
from langchain_core.runnables.history import BaseChatMessageHistory
from chat_router import chat_router, get_history_by_message_id
from typing import Optional
from uuid import uuid4


app = FastAPI(debug=True)

# === CORS setup ===
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_methods=["POST"],
    allow_headers=["*"],
)

class TranslationInput(BaseModel):
    text: str
    target_language: str
    mode: str
    user_id: int
    agent_id: int = 16         # translator agent_id (adjust as needed)

    @classmethod
    def as_form(
        cls,
        text: str = Form(...),
        target_language: str = Form(...),
        mode: str = Form(...),
        user_id: int = Form(...),
        agent_id: int = Form(16),
    ):
        return cls(
            text=text,
            target_language=target_language,
            mode=mode,
            user_id=user_id,
            agent_id=agent_id,
        )


class TranslationFollowupInput(BaseModel):
    text: str
    user_id: int
    message_id: int
    agent_id: int = 16  # translator agent_id (adjust as needed)


    @classmethod
    def as_form(
        cls,
        text: str = Form(...),
        user_id: int = Form(...),
        message_id: int = Form(...),
    ):
        return cls(
            text=text,
            user_id=user_id,
            message_id=message_id,
        )

# Instantiate once
model = OllamaLLM(model="gemma3:1b")

# Safer templated prompt
prompt_template = ChatPromptTemplate.from_template("""
You are a multilingual translator. Translate the following text clearly and naturally into {language}.
Only return the translated text. Do not include the original, explanations, or extra information.
Text:
{text}
""")

# Combine into a chain
chain = prompt_template | model

def translate_text(text: str, target_language: str) -> str:
    if not text.strip():
        return "No input text provided for translation."

    raw_output = chain.invoke({
        "language": target_language,
        "text": text
    })

    # Cleanup
    translated = raw_output.strip().removeprefix("Translation:").strip()
    return translated


@app.post("/translate")
async def translate_endpoint(data: TranslationInput = Depends(TranslationInput.as_form)):
    try:
        output = translate_text(data.text, data.target_language)
        return {"translation": output}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@app.post("/translate/followup")
async def translate_followup(
    form_data: TranslationFollowupInput = Depends(TranslationFollowupInput.as_form),
):
    try:
        # Ensure we have a session_id for follow-up
        if not form_data.message_id:
            raise HTTPException(status_code=400, detail="message_id is required for follow-up.")

        user_id = 1
        agent_id = 16  # Translator agent ID
        session_id = int(form_data.message_id) # Ensure integer type
        
        # Add the human follow-up message to history
        add_message(session_id, HumanMessage(content=form_data.text), user_id, agent_id) # Using agent_id here

        history = get_history_by_message_id(str(session_id)) # History needs string ID
        
        prompt_input = {
            "topic": form_data.text,
            "language": form_data.target_language,
            "chat_history": history.messages
        }

        chain = prompt_template | model
        result = chain.invoke(prompt_input)
        output = result.strip().removeprefix("Translation:").strip()

        # Add AI response to history
        add_message(session_id, AIMessage(content=output), user_id, agent_id)

        return {"translation": output}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

class ChatMessage(BaseModel):
    user_id: int
    agent_id: int

    @classmethod
    def as_form(
        cls,
        user_id: int = Form(...),
        agent_id: int = Form(...),
    ):
        return cls(
            user_id=user_id,
            agent_id=agent_id
        )
   
@app.post("/chat/messages")
async def get_translator_messages(data: ChatMessage = Depends(ChatMessage.as_form), limit: Optional[int] = None, order: str = 'desc'):
    return {
        "messages": load_messages_by_agent_and_user(
            agent_id=data.agent_id,
            user_id=data.user_id,
            limit=limit,
            order=order
        )
    }

class SpecificMessageRequest(BaseModel):
    session_id: int
    limit: Optional[int] = None
    order: str = 'asc'

    @classmethod
    def as_form(
        cls,
        session_id: int = Form(...),
        limit: Optional[int] = Form(None),
        order: str = Form('asc')
    ):
        return cls(
            session_id=session_id,
            limit=limit,
            order=order
        )

@app.post("/chat/specific_messages")
async def get_specific_translator_messages(data: SpecificMessageRequest = Depends(SpecificMessageRequest.as_form)):
    return {
        "messages": load_messages_by_session_id(
            session_id=data.session_id,
            limit=data.limit,
            order=data.order
        )
    }
