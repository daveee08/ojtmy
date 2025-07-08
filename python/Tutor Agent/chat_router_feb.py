from fastapi import FastAPI, APIRouter, Form, HTTPException, Depends
from fastapi.responses import JSONResponse
from langchain_community.llms import Ollama
from langchain_core.prompts import ChatPromptTemplate, MessagesPlaceholder
from langchain_core.runnables import Runnable
from langchain_core.runnables.history import RunnableWithMessageHistory
from langchain_core.chat_history import BaseChatMessageHistory
from langchain_core.messages import HumanMessage, AIMessage, BaseMessage

import mysql.connector
import traceback

chat_router = FastAPI(debug=True)

# ====================== LangChain Setup ======================

# chat_prompt = ChatPromptTemplate.from_messages([
#     ("system", "You are a helpful assistant. Keep responses clear and concise."),
#     MessagesPlaceholder(variable_name="history"),
#     ("human", "{topic}")
# ])


from pydantic import BaseModel

# ====================== Pydantic Setup ======================

class ChatRequest(BaseModel):
    agent_system_prompt: str
    topic: str
    user_id: int
    db_message_id: int
    context: str 
    
    @classmethod
    def as_form(
        cls,
        agent_system_prompt: str = Form(...),
        topic: str = Form(...),
        user_id: int = Form(...),
        db_message_id: int = Form(...),
        context: str = Form(...)
    ):
        return cls(
            agent_system_prompt=agent_system_prompt,
            topic=topic,
            user_id=user_id,
            db_message_id=db_message_id,
            context=context
        )


chat_prompt = ChatPromptTemplate.from_messages([
    ("system", "{agent_system_prompt}"),
    MessagesPlaceholder(variable_name="history"),
    ("human", "{topic} context: {context}"),
])

model = Ollama(model="gemma3:latest")
chat_chain: Runnable = chat_prompt | model

# ====================== DB Message History ======================

class DBChatHistory(BaseChatMessageHistory):
    def __init__(self, user_id: int, message_id: int, agent: str = "tutor"):
        self.user_id = user_id
        self.agent = agent
        self.message_id = message_id
        self._load()

    def _connect(self):
        return mysql.connector.connect(
            host="127.0.0.1",
            user="root",
            port=3306,
            password="",
            database="ck_agent"
        )

    def _load(self):
        self._messages: list[BaseMessage] = []
        conn = self._connect()
        cursor = conn.cursor(dictionary=True)
        cursor.execute("""
            SELECT sender, topic FROM messages
            WHERE user_id = %s AND message_id = %s
            ORDER BY created_at
        """, (self.user_id, self.message_id))
        for row in cursor.fetchall():
            if row["sender"] == "human":
                self._messages.append(HumanMessage(content=row["topic"]))
            elif row["sender"] == "ai":
                self._messages.append(AIMessage(content=row["topic"]))
        cursor.close()
        conn.close()

    @property
    def messages(self) -> list[BaseMessage]:
        return self._messages

    def clear(self) -> None:
        conn = self._connect()
        cursor = conn.cursor()
        cursor.execute("""
            DELETE FROM messages
            WHERE user_id = %s AND message_id = %s
        """, (self.user_id, self.message_id))
        conn.commit()
        cursor.close()
        conn.close()
        self._messages = []

# ====================== Runnable With DB-Backed History ======================

def get_history(session_id: str) -> BaseChatMessageHistory:
    print("[DEBUG] raw session_id:", session_id)
    user_id, message_id = map(int, session_id.split(":"))
    return DBChatHistory(user_id=user_id, message_id=message_id, agent="tutor")
chat_with_memory = RunnableWithMessageHistory(
    chat_chain,
    get_session_history=get_history,
    input_messages_key="topic",
    history_messages_key="history"
)

# ====================== Endpoint ======================



@chat_router.post("/chat_with_history")
async def chat_with_history_api(
    data: ChatRequest = Depends(ChatRequest.as_form)
):
    try:
        print("[DEBUG] user_id:", data.user_id)
        print("[DEBUG] db_message_id:", data.db_message_id)
        print("[DEBUG] topic:", data.topic)

        # Use colon-encoded session_id
        session_key = f"{data.user_id}:{data.db_message_id}"

        result = await chat_with_memory.ainvoke(
            {
            "topic": data.topic,
            "agent_system_prompt": data.agent_system_prompt,
            "context": data.context,
            },
            config={"configurable": {
            "session_id": session_key
            }}
        )

        return JSONResponse(content={"response": result})

    except Exception as e:
        traceback_str = traceback.format_exc()
        print(f"[Chat Error] {e}\n{traceback_str}")
        raise HTTPException(status_code=500, detail="Chat processing failed.")
    



# def add_user_message(self, message: str) -> None:
    #     self._messages.append(HumanMessage(content=message))
    #     self._save_message(message, "human")

    # def add_ai_message(self, message: str) -> None:
    #     self._messages.append(AIMessage(content=message))
    #     self._save_message(message, "ai")

    # def _save_message(self, message: str, sender: str) -> None:
    #     conn = self._connect()
    #     cursor = conn.cursor()
    #     cursor.execute("""
    #         INSERT INTO messages (agent_id, user_id, parameter_inputs, sender, message_id, topic, created_at, updated_at)
    #         VALUES (%s, %s, %s, %s, %s, %s, NOW(), NOW())
    #     """, (
    #         1,  # agent_id placeholder
    #         self.user_id,
    #         1,  # parameter_inputs placeholder
    #         sender,
    #         1,  # message_id placeholder for threading
    #         message
    #     ))
    #     conn.commit()
    #     cursor.close()
    #     conn.close()