from fastapi import APIRouter, Form, HTTPException
from fastapi.responses import JSONResponse
from langchain_community.llms import Ollama
from langchain_core.prompts import ChatPromptTemplate, MessagesPlaceholder
import json, traceback
from langchain_core.messages import HumanMessage, AIMessage

chat_router = APIRouter()

# --- Chat Prompt ---
chat_prompt = ChatPromptTemplate.from_messages([
    ("system", "You are a helpful assistant. Keep responses clear and concise."),
    MessagesPlaceholder(variable_name="history"),
    ("human", "{topic}")
])

model = Ollama(model="llama3")

@chat_router.post("/chat_with_history")
async def chat_with_history_api( 
    topic: str = Form(...),
    history: str = Form("[]"),
    user_id: int = Form(...)
):
    try:
        # Parse the history from Laravel
        parsed_history = json.loads(history)
        messages = []
        for msg in parsed_history:
            if msg["role"] == "user":
                messages.append(HumanMessage(content=msg["content"]))
            else:
                messages.append(AIMessage(content=msg["content"]))

        # Compose the input for the chain
        chain_input = {
            "topic": topic,
            "history": messages
        }

        # Run the chain (no RunnableWithMessageHistory needed)
        result = (chat_prompt | model).invoke(chain_input)
        return JSONResponse(content={"response": result})
    except Exception as e:
        traceback_str = traceback.format_exc()
        print(f"[Chat Error] {e}\n{traceback_str}")
        raise HTTPException(status_code=500, detail="Chat processing failed.")