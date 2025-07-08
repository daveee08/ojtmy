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
from typing import Optional
# --------------------------------------------------------------------------- #
#  Path setup so "db_utils" (one directory up) can be imported without hacks. #
# --------------------------------------------------------------------------- #
sys.path.append(os.path.abspath(os.path.join(os.path.dirname(__file__), "..")))
from db_utils import insert_session_and_message, load_messages_by_agent_and_user # noqa: E402  (import after path fix)

# --------------------------------------------------------------------------- #
#  FastAPI app + CORS                                                         #
# --------------------------------------------------------------------------- #
app = FastAPI(debug=True)
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_methods=["POST", "OPTIONS"],
    allow_headers=["*"],
)

# --------------------------------------------------------------------------- #
#  Pydantic input / output models                                             #
# --------------------------------------------------------------------------- #
class SentenceStarterInput(BaseModel):
    text: str
    grade_level: str
    # topic: str
    mode: str  # "chat" or "single"
    user_id: int
    # parameter_inputs: int = 1
    agent_id: int = 14  # <-- adjust if you change the DB

    @classmethod
    def as_form(
        cls,
        text: str = Form(...),  # This is not used, but kept for consistency with other agents
        grade_level: str = Form(...),
        # topic: str = Form(...),
        mode: str = Form(...),
        user_id: int = Form(...),
        # parameter_inputs: int = Form(1),
        agent_id: int = Form(14),
    ) :
        return cls(
            text=text,  # This is not used in the sentence starters agent
            grade_level=grade_level,
            # topic=topic,
            mode=mode,
            user_id=user_id,
            # parameter_inputs=parameter_inputs,
            agent_id=agent_id,
        )


class SentenceStarterFollowupInput(BaseModel):
    text: str
    user_id: int
    message_id: int
    # target_language: str # Default to bisaya
    agent_id: int = 14  # translator agent_id (adjust as needed)


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


# --------------------------------------------------------------------------- #
#  Grade‑level profiles and prompt                                            #
# --------------------------------------------------------------------------- #
model = OllamaLLM(model="gemma3:1b")

GRADE_LEVEL_PROFILES = {
    "kindergarten": "Use very simple words and phrases. Encourage curiosity.",
    "elementary": "Use clear, easy words. Help the student describe or explain ideas.",
    "junior high": "Use age‑appropriate vocabulary. Encourage detail and opinions.",
    "senior high": "Use a semi‑formal tone. Invite analysis or comparison.",
    "college": "Use an academic tone. Encourage critical thinking and synthesis.",
}
prompt_template = ChatPromptTemplate.from_template("""
You are an AI writing coach for {grade_level} students.
Your task is to generate **exactly five** open‑ended sentence starters
to help them begin writing about the topic below.

Topic: "{topic}"

Guidelines:
- Return only the five starters, each on its own line (no numbering, no bullets).
- Do NOT complete the thought. Starters must remain open‑ended.
- Avoid phrases like "This topic is about...".
- Tone and vocabulary should match the grade level:
  {instructions}
""")

# Combine into a chain
chain = prompt_template | model

def sentence_starters(grade_level: str, topic: str) -> list[str]:
    if not topic.strip():
        return "No input text provided for sentence starters."

    instructions = GRADE_LEVEL_PROFILES.get(grade_level.lower())
    if instructions is None:
        raise ValueError(f"Unknown grade level: '{grade_level}'. Allowed: {', '.join(GRADE_LEVEL_PROFILES)}")

    raw_output = chain.invoke({
        "grade_level": grade_level,
        "topic": topic,
        "instructions": instructions,  # <-- Add this line
    })
    
    sentence = raw_output.strip().removeprefix("Generated").strip()
    return sentence

     



# # --------------------------------------------------------------------------- #
# #  LLM and chain                                                              #
# # --------------------------------------------------------------------------- #
# llm = OllamaLLM(model="gemma3:1b")
# chain = PROMPT | llm


# def sentence_starters(grade_level: str, topic: str) -> List[str]:
#     instructions = GRADE_LEVEL_PROFILES.get(grade_level.lower())
#     if instructions is None:
#         raise ValueError(f"Unknown grade level: '{grade_level}'. Allowed: {', '.join(GRADE_LEVEL_PROFILES)}")

#     raw = chain.invoke({"grade_level": grade_level, "topic": topic, "instructions": instructions})
#     starters = [line.strip() for line in raw.splitlines() if line.strip()]
#     # Enforce exactly 5 lines; if the model returned extras, keep the first five.
#     return starters[:5]


# --------------------------------------------------------------------------- #
#  Endpoint                                                                   #
# --------------------------------------------------------------------------- #
@app.post("/sentence-starters")
async def sentence_starters_endpoint(data: SentenceStarterInput):
    
        output = sentence_starters(data.grade_level, data.text)


        scope_vars = {
            "grade_level": data.grade_level,
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
        
        return {"sentence_starters": output, "message_id": message_id}

@app.post("/sentence_starter/followup")
async def sentence_starters_followup_endpoint(data:SentenceStarterFollowupInput = Depends(SentenceStarterFollowupInput.as_form)):
    try:
        async with httpx.AsyncClient(timeout=None) as client:
            form_data = {
                "topic": data.text,
                "user_id": str(data.user_id),
                "db_message_id": int(data.message_id),
                # "agent_system_prompt": "You are a helpful assistant.",  # <-- required
                # "context": "",
            }
            chat_url = "http://192.168.50.40:8001/chat_with_history"
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
            "grade_level": "follow up"   
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
        

        return {"sentence_starters": output} 
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
async def get_sentence_starters_messages(data: ChatMessage = Depends(ChatMessage.as_form), limit: Optional[int] = None, order: str = 'asc'):
    return {
        "messages": load_messages_by_agent_and_user(
            agent_id=data.agent_id,
            user_id=data.user_id,
            limit=limit,
            order=order
        )
    }


#  @app.post("/sentence-starters") 
# async def sentence_starters_endpoint(data: SentenceStarterInput = Depends(SentenceStarterInput.as_form)):
#     try:
#         # --------------------------- chat pass‑through ---------------------- #
#         if data.mode.lower() == "chat":
#             async with httpx.AsyncClient(timeout=None) as client:
#                 payload = {
#                     "topic": data.topic,
#                     "user_id": str(data.user_id),
#                     "db_message_id": 0,  # <- supply your own thread ID if needed
#                 }
#                 r = await client.post("http://192.168.50.10:8001/chat_with_history", data=payload)
#                 r.raise_for_status()
#                 starters = [r.json().get("response", "")]  # Expecting the chat server to format
#         # ----------------------- single‑shot generation --------------------- #
#         else:
#             starters = generate_sentence_starters(data.grade_level, data.topic)

#         # ------------------ save both human prompt + AI reply --------------- #
#         scope_vars = {"grade_level": data.grade_level, "topic": data.topic}
#         msg_id = insert_session_and_message(
#             user_id=data.user_id,
#             agent_id=data.agent_id,
#             sender="human",
#             topic=data.topic,
#             scope_vars=scope_vars,
#         )
#         insert_session_and_message(
#             user_id=data.user_id,
#             agent_id=data.agent_id,
#             sender="ai",
#             topic="\n".join(starters),
#             scope_vars=scope_vars,
#             message_id=msg_id,
#         )

#         return {"translation": starters}

#     # --------------------------- eraror handling ---------------------------- #
#     except Exception as exc:  # broad, but useful for an API boundary
#         raise HTTPException(status_code=500, detail=str(exc)) from exc


# --------------------------------------------------------------------------- #
#  Uvicorn entry‑point (optional)                                             #
# --------------------------------------------------------------------------- #
# if __name__ == "__main__":
#     import uvicorn  # local import so `uvicorn` isn’t required when run by Gunicorn
#     uvicorn.run("sentence_starters_agent:app", host="0.0.0.0", port=8000, reload=True