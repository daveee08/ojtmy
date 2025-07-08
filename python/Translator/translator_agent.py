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
# from db_utils import insert_message, insert_dynamic_parameter_input, insert_session
from db_utils import insert_session_and_message, load_messages_by_agent_and_user, load_messages_by_session_id
from typing import Optional


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
    # parameter_inputs: int = 1  # default
    agent_id: int = 16         # translator agent_id (adjust as needed)
    # message_id: Optional[int]         # Laravel-generated session thread ID

    @classmethod
    def as_form(
        cls,
        text: str = Form(...),
        target_language: str = Form(...),
        mode: str = Form(...),
        user_id: int = Form(...),
        # parameter_inputs: int = Form(1),
        agent_id: int = Form(16),
        # message_id: int = Form(...)
    ):
        return cls(
            text=text,
            target_language=target_language,
            mode=mode,
            user_id=user_id,
            # parameter_inputs=parameter_inputs,
            agent_id=agent_id,
            # message_id=message_id
        )


class TranslationFollowupInput(BaseModel):
    text: str
    user_id: int
    message_id: int
    # target_language: str # Default to bisaya
    agent_id: int = 16  # translator agent_id (adjust as needed)


    @classmethod
    def as_form(
        cls,
        text: str = Form(...),
        user_id: int = Form(...),
        message_id: int = Form(...),
        # target_language: str = Form(...),  # Default to bisaya
    ):
        return cls(
            text=text,
            user_id=user_id,
            message_id=message_id,
            # target_language=target_language,
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
    
        output = translate_text(data.text, data.target_language)


        scope_vars = {
            "target_language": data.target_language
        }

        message_id =insert_session_and_message(
            user_id=data.user_id,
            agent_id=data.agent_id,
            sender="human",
            topic=data.text,
            scope_vars=scope_vars,
        )

        insert_session_and_message(
            user_id=data.user_id,
            agent_id=data.agent_id,
            sender="ai",
            topic=output,
            scope_vars=scope_vars,
            message_id=message_id,
        )
        
        return {"translation": output, "message_id": message_id}
    # except Exception as e:
    #     raise HTTPException(status_code=500, detail=str(e))
    
@app.post("/translate/followup")
async def translate_followup_endpoint(data: TranslationFollowupInput = Depends(TranslationFollowupInput.as_form)):
    try:
        async with httpx.AsyncClient(timeout=None) as client:
            form_data = {
                "topic": data.text,
                "user_id": str(data.user_id),
                "db_message_id": int(data.message_id),
            }
            chat_url = "http://192.168.50.10:8001/chat_with_history"
            try:
                print("[DEBUG] Sending chat request:", form_data, flush=True)
                resp = await client.post(chat_url, data=form_data)
                print("[DEBUG] Response status:", resp.status_code, flush=True)
                print("[DEBUG] Response body:", await resp.aread(), flush=True)
            except Exception as e:
                import traceback
                print("[ERROR] Failed to contact chat_url", flush=True)
                print(traceback.format_exc(), flush=True)
                raise
            resp.raise_for_status()
            result = resp.json()
            output = result.get("response", "No output")

        scope_vars = {
            "target_language": "follow up"
        }

        insert_session_and_message(
            user_id=data.user_id,
            agent_id=data.agent_id,
            sender="human",
            topic=data.text,
            scope_vars=scope_vars,
            message_id=data.message_id,
        )

        insert_session_and_message(
            user_id=data.user_id,
            agent_id=data.agent_id,
            sender="ai",
            topic=output,
            scope_vars=scope_vars,
            message_id=data.message_id,
        )
        

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


# @app.post("/translate")
# async def translate_endpoint(data: TranslationInput = Depends(TranslationInput.as_form)):
#     try:
#         output = translate_text(data.text, data.target_language)
#         return {"translation": output}
#     except Exception as e:
#         raise HTTPException(status_code=500, detail=str(e))
