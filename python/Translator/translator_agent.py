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
from db_utils import insert_session_and_message


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
    parameter_inputs: int = 1  # default
    agent_id: int = 16         # translator agent_id (adjust as needed)
    # message_id: int         # Laravel-generated session thread ID

    @classmethod
    def as_form(
        cls,
        text: str = Form(...),
        target_language: str = Form(...),
        mode: str = Form(...),
        user_id: int = Form(...),
        parameter_inputs: int = Form(1),
        agent_id: int = Form(2),
        # message_id: int = Form(...)
    ):
        return cls(
            text=text,
            target_language=target_language,
            mode=mode,
            user_id=user_id,
            parameter_inputs=parameter_inputs,
            agent_id=agent_id,
            # message_id=message_id
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
        # insert_message(
        #     agent_id=data.agent_id,
        #     user_id=data.user_id,
        #     parameter_inputs=parameter_inputs_id,
        #     sender="human",
        #     topic=data.text,
        #     message_id=message_id  # Laravel-generated session thread ID
        # )

        if data.mode == "chat":
            async with httpx.AsyncClient(timeout=None) as client:
                form_data = {
                    "topic": data.topic,
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
        else:
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
        
        return {"translation": output}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

# @app.post("/translate")
# async def translate_endpoint(data: TranslationInput = Depends(TranslationInput.as_form)):
#     try:
#         output = translate_text(data.text, data.target_language)
#         return {"translation": output}
#     except Exception as e:
#         raise HTTPException(status_code=500, detail=str(e))
