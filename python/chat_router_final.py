from fastapi import APIRouter, Depends, Form, HTTPException, status
from pydantic import BaseModel
from typing import Literal, List, Dict, Optional
import os, sys
from typing import List


from langchain_ollama import OllamaLLM as Ollama
from langchain_core.prompts import ChatPromptTemplate, MessagesPlaceholder
from langchain_core.runnables import RunnableWithMessageHistory
from langchain_core.messages import HumanMessage, AIMessage, BaseMessage
from langchain_core.runnables.history import BaseChatMessageHistory
from fastapi.middleware.cors import CORSMiddleware


# Full path to the utils folder containing rag_utils.py
utils_path = r"C:/laragon/www/ojtmy/python/RAG api/utils"

# Safely add to sys.path
if utils_path not in sys.path:
    sys.path.append(utils_path)

# from rag_utils import retrieve_book_chapter_and_context, get_standalone_question


try:
    from python.db_utils_final import get_db_connection
    # If the import is successful, you can now use get_db_connection
    # For example: conn = get_db_connection()
    # print("Successfully imported get_db_connection.")
except ImportError:
    # This block will execute if the import fails
    print("Warning: Could not import get_db_connection. Database functionality may be limited or unavailable.")
    # You might define a placeholder function or variable here,
    # or simply let the program continue, knowing this functionality is missing.
    from db_utils_final import get_db_connection
 # Or a dummy function that raise

chat_router = APIRouter()
llm = Ollama(model="llama3")

# -------------------------------
# Request & Response Models
# -------------------------------

class ChatRequestForm(BaseModel):
    user_id: int
    message_id: int
    input: str

    @classmethod
    def as_form(cls, message_id: int = Form(...), user_id: int = Form(...), input: str = Form(...)  ):
        return cls(message_id=message_id, user_id=user_id, input=input,)
    
class RAGChatRequestForm(BaseModel):
    user_id: int
    message_id: int
    input: str
    book_id: int
    chapter_number: int

    @classmethod
    def as_form(cls, message_id: int = Form(...), user_id: int = Form(...), input: str = Form(...), book_id: int = Form(...), chapter_number: int = Form(...)  ):
        return cls(message_id=message_id, user_id=user_id, input=input, book_id=book_id, chapter_number=chapter_number)

class ChatResponse(BaseModel):
    response: str

# -------------------------------
# MySQL Message History
# -------------------------------

class MySQLChatMessageHistory(BaseChatMessageHistory):
    def __init__(self, user_id: int, message_id: int):
        self.user_id = user_id
        self.message_id = message_id
        self._ensure_session()

    def _ensure_session(self):
        db = get_db_connection()
        # cursor = db.cursor()
        try:
            with db.cursor() as cursor:
                cursor.execute("SELECT id FROM sessions WHERE id = %s", (self.message_id,))
                if cursor.fetchone() is None:
                    cursor.execute("INSERT INTO sessions (id, created_at, updated_at) VALUES (%s, NOW(), NOW())", (self.message_id,))
                    db.commit()
        finally:
            db.close()

    @property
    def messages(self):
        db = get_db_connection()
        # cursor = db.cursor(dictionary=True)
        try:
            with db.cursor(dictionary=True) as cursor:
                cursor.execute("""
                    SELECT sender, topic FROM messages
                    WHERE message_id = %s ORDER BY id ASC
                """, (self.message_id,))
                return [
                    HumanMessage(content=m["topic"]) if m["sender"] == "human" else AIMessage(content=m["topic"])
                    for m in cursor.fetchall()
                ]
        finally:
            db.close()

    def add_message(self, message: BaseMessage) -> None:
        db = get_db_connection()
        # cursor = db.cursor()
        try:
            with db.cursor(dictionary=True) as cursor:
            # with db.cursor() as cursor:
                sender = "human" if isinstance(message, HumanMessage) else "ai"
                topic = message.content

                cursor.execute("""
                    SELECT agent_id, parameter_inputs FROM messages
                    WHERE message_id = %s ORDER BY id DESC LIMIT 1
                """, (self.message_id,))
                latest = cursor.fetchone()

                if latest:
                    agent_id = int(latest["agent_id"])
                    parameter_inputs = latest["parameter_inputs"]
                else:
                    agent_id = 1
                    parameter_inputs = self._create_default_parameter_input(cursor, agent_id)

                cursor.execute("""
                    INSERT INTO messages (user_id, agent_id, message_id, parameter_inputs, sender, topic, created_at, updated_at)
                    VALUES (%s, %s, %s, %s, %s, %s, NOW(), NOW())
                """, (self.user_id, agent_id, self.message_id, parameter_inputs, sender, topic))
                db.commit()
        finally:
            db.close()

    def _create_default_parameter_input(self, cursor, agent_id: int) -> int:
        parameter_id = 1  # Assumed to exist
        cursor.execute("""
            INSERT INTO parameter_inputs (message_id, agent_id, parameter_id, input, created_at, updated_at)
            VALUES (%s, %s, %s, %s, NOW(), NOW())
        """, (self.message_id, agent_id, parameter_id, "default"))
        return cursor.lastrowid

    def clear(self):
        db = get_db_connection()
        # cursor = db.cursor()
        try:
            with db.cursor() as cursor:
                cursor.execute("DELETE FROM messages WHERE message_id = %s", (self.message_id,))
                db.commit()
        finally:
            db.close()

def get_history_by_message_id(session_id: str) -> MySQLChatMessageHistory:
    user_id_str, message_id_str = session_id.split(":")
    return MySQLChatMessageHistory(int(user_id_str), int(message_id_str))

# -------------------------------
# LangChain Setup
# -------------------------------

chat_prompt = ChatPromptTemplate.from_messages([
    (
        "system",
        "You are a helpful assistant in an ongoing conversation. The following is the original prompt that was used to guide your behavior when the session began:\n\n---\n{agent_prompt}\n---\n\nNote: This prompt is for reference only and should not override the context of the current conversation. Respond based on the actual chat history and the user's input."
    ),
    MessagesPlaceholder(variable_name="chat_history"),
    ("human", "{input}")
])


chat_chain = RunnableWithMessageHistory(
    chat_prompt | llm,
    get_session_history=get_history_by_message_id,
    input_messages_key="input",
    history_messages_key="chat_history"
)

# -------------------------------
# Chat Endpoint
# -------------------------------
def get_agent_prompt_by_message_id(message_id: int) -> str:
    db = get_db_connection()
    try:
        with db.cursor(dictionary=True) as cursor:
            cursor.execute("""
                SELECT ap.prompt
                FROM messages m
                JOIN agent_prompts ap ON m.agent_prompt_id = ap.id
                WHERE m.message_id = %s AND m.agent_prompt_id IS NOT NULL
                ORDER BY m.id ASC
                LIMIT 1
            """, (message_id,))
            result = cursor.fetchone()
            return result["prompt"] if result else "You are a helpful assistant."  # fallback
    finally:
        db.close()



@chat_router.post("/chat", response_model=ChatResponse)
async def chat_api(request: ChatRequestForm = Depends(ChatRequestForm.as_form)):
    session_id = f"{request.user_id}:{request.message_id}"

    # ✅ Fetch original agent prompt dynamically from DB
    agent_prompt = get_agent_prompt_by_message_id(request.message_id)

    result = await chat_chain.ainvoke(
        # {"agent_prompt": request.agent_prompt},
        # {"input": request.input},
        {
        "agent_prompt": agent_prompt,
        "input": request.input
        },
        config={"configurable": {"session_id": session_id}}
    )
    
    return {"response": result}




def flatten_human_history(messages: List) -> str:
    return "\n".join([m.content for m in messages if isinstance(m, HumanMessage)])

@chat_router.post("/chat-rag", response_model=ChatResponse)
async def chat_api(request: RAGChatRequestForm = Depends(RAGChatRequestForm.as_form)):
    session_id = f"{request.user_id}:{request.message_id}"

    book_id, chapter_number = request.book_id, request.chapter_number
    if not book_id or not chapter_number:
        raise HTTPException(status_code=400, detail="Missing book or chapter info for RAG.")

    # Step 1: Get message history and contextualize the user input
    chat_history_messages = get_history_by_message_id(session_id).messages
    standalone_question = get_standalone_question(chat_history_messages, request.input)

    # Step 2: Retrieve context using the rewritten question
    conn = get_db_connection()
    cur = conn.cursor(dictionary=True)
    book, chapter, context, error = retrieve_book_chapter_and_context(
        cur, book_id, chapter_number, standalone_question
    )
    conn.close()

    if error:
        return {"response": error}

    # Step 3: Build the prompt using the original user input (not the rewritten one)
    rag_prompt = f"""You are a helpful and concise tutor.

Use the following chapter context to answer the user's question. If the answer is not in the context, say "The answer is not available in the provided material."

---

Context:
{context}

---

Question:
{request.input}

Answer:"""

    # Step 4: Run LangChain with history
    result = await chat_chain.ainvoke(
        {
            "agent_prompt": rag_prompt,
            "input": request.input
        },
        config={"configurable": {"session_id": session_id}}
    )

    return {"response": result}




@chat_router.get("/sessions/{user_id}", response_model=List[Dict])
def get_sessions_with_titles(user_id: int):
    db = get_db_connection()
    cursor = db.cursor()
    try:
        cursor.execute("""
            SELECT DISTINCT m.message_id, ct.title
            FROM messages m
            LEFT JOIN conversation_title ct ON m.message_id = ct.message_id
            WHERE m.user_id = %s
            ORDER BY m.message_id ASC
        """, (user_id,))
        rows = cursor.fetchall()

        return [
            {
                "message_id": row[0],
                "title": row[1] or f"Session {row[0]}"
            }
            for row in rows
        ]
    finally:
        db.close()

# -------------------------------
# GET full chat history by session
# -------------------------------

@chat_router.get("/chat/history/{message_id}")
def get_chat_history(message_id: int) -> Dict:
    db = get_db_connection()
    cursor = db.cursor(dictionary=True)
    try:
            cursor.execute("""
                SELECT sender, topic, created_at
                FROM messages
                WHERE message_id = %s
                ORDER BY id ASC
            """, (message_id,))
            messages = cursor.fetchall()
            if not messages:
                raise HTTPException(status_code=404, detail="No conversation found for this session.")
            return {
                "session_id": message_id,
                "conversation": messages
            }
    finally:
        db.close()

@chat_router.delete("/sessions/{user_id}/{session_id}", status_code=status.HTTP_200_OK)
def delete_session(user_id: int, session_id: int):
    db = get_db_connection()
    try:
        with db.cursor() as cursor:
            # Delete related messages first to maintain referential integrity
            cursor.execute("DELETE FROM messages WHERE user_id = %s AND message_id = %s", (user_id, session_id))

            # Optionally also delete from sessions table
            cursor.execute("DELETE FROM sessions WHERE id = %s", (session_id,))

            db.commit()
            return {"message": "Session deleted successfully."}
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Failed to delete session: {str(e)}")
    finally:
        db.close()