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
from db_utils_final import create_session_and_parameter_inputs
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
    user_id: int
    # parameter_inputs: int = 1  # default
    # agent_id: int = 16         # translator agent_id (adjust as needed)
    # message_id: Optional[int]         # Laravel-generated session thread ID

    @classmethod
    def as_form(
        cls,
        text: str = Form(...),
        target_language: str = Form(...),
        user_id: int = Form(...),
        # parameter_inputs: int = Form(1),
        # agent_id: int = Form(16),
        # message_id: int = Form(...)
    ):
        return cls(
            text=text,
            target_language=target_language,
            user_id=user_id,
            # parameter_inputs=parameter_inputs,
            # agent_id=agent_id,
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

follow_up_prompt_template = """You are a dedicated multilingual translator and translation assistant. 
Your primary function is to translate text clearly and naturally. Additionally, you can answer questions directly related to the provided history of translation, such as 'translate it back' or 'how do I pronounce this?' 
Do not engage in conversations, provide information, or answer questions outside the scope of translation or translation-related assistance. 
For direct translation requests, return only the translated text, without the original, explanations, or extra commentary."""



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

        filled_prompt = prompt_template.format(language=data.target_language.strip(), text=data.text.strip())

        session_id = create_session_and_parameter_inputs(
            user_id=data.user_id,
            agent_id=20,  # Default agent_id for step tutor
            scope_vars=scope_vars,
            human_topic=data.text,
            ai_output=output,
            agent_prompt=filled_prompt
        )

        
        return {"translation": output, "message_id": session_id}
    # except Exception as e:
    #     raise HTTPException(status_code=500, detail=str(e))
    
