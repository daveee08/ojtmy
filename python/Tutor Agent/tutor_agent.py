from fastapi import FastAPI, UploadFile, Form, HTTPException, Request, Depends
from fastapi.responses import JSONResponse
from pydantic import BaseModel
import os, re, tempfile
import json
import httpx

from typing import Optional
from langchain_ollama import OllamaLLM
from langchain_core.prompts import ChatPromptTemplate
from langchain_community.document_loaders import PyPDFLoader
from langchain_core.messages import HumanMessage, AIMessage
from chat_router_feb import chat_router

# ===================== App Initialization =====================

app = FastAPI(debug=True)
# app.include_router(chat_router)

# ===================== Pydantic Model =====================

from pydantic import BaseModel
from fastapi import Form, Depends

class TutorRequest(BaseModel):
    user_id: int
    grade_level: str
    input_type: str
    topic: str = ""
    add_cont: str = ""
    mode: str = "manual"
    history: str = "[]"
    message_id: int

    @classmethod
    def as_form(
        cls,
        user_id: int = Form(...),
        grade_level: str = Form(...),
        input_type: str = Form(...),
        topic: str = Form(""),
        add_cont: str = Form(""),
        mode: str = Form("manual"),
        history: str = Form("[]"),
        message_id: int = Form(...)
    ):
        return cls(
            user_id=user_id,
            grade_level=grade_level,
            input_type=input_type,
            topic=topic,
            add_cont=add_cont,
            mode=mode,
            history=history, 
            message_id=message_id
        )

# ===================== Prompt Templates =====================

manual_topic_template = """
You are an experienced and friendly virtual tutor, specializing in guiding students towards deep conceptual understanding. Your goal is to explain the given topic clearly and comprehensively, ensuring the student grasps the core ideas, their significance, and how they relate to broader concepts.

Please structure your explanation as follows:

1. Core Concept Explanation:
- Provide a concise and easy-to-understand definition.
- Break down the concept into its fundamental components or principles.
- Use analogies or simple real-world examples to illustrate the concept.

2. Why it Matters - It's Significance/Application (from the text):
- Explain the importance or relevance of this concept.
- Describe practical applications or scenarios where this concept is used.

3. Related Concepts - Connections (from the text):
- Briefly mention how this concept connects to other related ideas or topics.
- Highlight any prerequisite knowledge that would enhance understanding.

4. Potential Misconceptions & Clarifications:
- Identify common misunderstandings students might have about this topic.
- Provide clear explanations to correct these misconceptions.

Important: From now on, please respond speaking in the first person.
---
Student Details:
- Grade Level: {grade_level}
- Topic: {topic}
- Additional Context or Learning Needs: {add_cont}

**Your Output (following the structure above):**
"""

pdf_topic_template = """
You are a knowledgeable and supportive virtual tutor, specializing in guiding students towards deep conceptual understanding based on provided text. Your goal is to explain the core concepts from the extracted content clearly and comprehensively, ensuring the student grasps the main ideas, their significance, and how they relate to broader concepts discussed in the text.

Please structure your explanation as follows:

1. Core Concept Explanation:
- Provide a concise and easy-to-understand definition.
- Break down the concept into its fundamental components or principles.
- Use analogies or simple real-world examples to illustrate the concept.

2. Why it Matters - It's Significance/Application (from the text):
- Explain the importance or relevance of this concept.
- Describe practical applications or scenarios where this concept is used.

3. Related Concepts - Connections (from the text):
- Briefly mention how this concept connects to other related ideas or topics.
- Highlight any prerequisite knowledge that would enhance understanding.

4. Potential Misconceptions & Clarifications:
- Identify common misunderstandings students might have about this topic.
- Provide clear explanations to correct these misconceptions.

Important: From now on, please respond speaking in the first person.

---
**Student Details:**
- Grade Level: {grade_level}
- Extracted Content (first 2 pages): {topic}
- Additional Notes: {add_cont}

**Your Output (following the structure above):**
"""

chat_history_template = """
You are a skilled and supportive virtual tutor assisting a student in an ongoing conversation. Your task is to continue the tutoring session based on the summarized history of prior interactions and the latest student question.

Structure your response clearly and helpfully, as if you're replying to the student's most recent input with awareness of the prior discussion.

---  
**Student Details:**  
- Grade Level: {grade_level}  
- Prior Conversation Summary: {conversation_summary}  
- Current Message: {topic}  
- Additional Notes: {add_cont}  

From now on, please respond speaking in the first person.

**Your Output (explanation only):**
"""

# ===================== LangChain Setup =====================

model = OllamaLLM(model="gemma3:1b")
manual_prompt = ChatPromptTemplate.from_template(manual_topic_template)
pdf_prompt = ChatPromptTemplate.from_template(pdf_topic_template)
chat_history_prompt = ChatPromptTemplate.from_template(chat_history_template)

# ===================== Helper Functions =====================

def extract_text_from_pdf(path: str) -> str:
    loader = PyPDFLoader(path)
    pages = loader.load()
    return " ".join([page.page_content for page in pages[:2]])

def clean_output(text: str) -> str:
    text = re.sub(r"\*\*(.*?)\*\*", r"\1", text)
    text = re.sub(r"\*(.*?)\*", r"\1", text)
    text = re.sub(r"^\s*[\*\-]\s*", "", text, flags=re.MULTILINE)
    return text.strip()

async def generate_output_with_file(grade_level, input_type, topic="", add_cont="", pdf_file: UploadFile = None, mode="manual"):
    if input_type == "pdf":
        if not pdf_file:
            raise ValueError("PDF file is required but not provided.")
        with tempfile.NamedTemporaryFile(delete=False, suffix=".pdf") as tmp:
            contents = await pdf_file.read()
            tmp.write(contents)
            tmp_path = tmp.name
        topic = extract_text_from_pdf(tmp_path)
        user_input = {
            "grade_level": grade_level,
            "topic": topic,
            "add_cont": add_cont
        }
        prompt = pdf_prompt
    elif mode == "chat":
        prompt = chat_history_prompt
        user_input = {
            "grade_level": grade_level,
            "conversation_summary": topic,
            "topic": "",
            "add_cont": add_cont
        }
    else:
        prompt = manual_prompt
        user_input = {
            "grade_level": grade_level,
            "topic": topic,
            "add_cont": add_cont
        }

    chain = prompt | model
    result = chain.invoke(user_input)
    return clean_output(result)

# ===================== Route =====================

@app.post("/tutor")
async def tutor_endpoint(
    data: TutorRequest = Depends(TutorRequest.as_form),
    pdf_file: Optional[UploadFile] = None,
    request: Request = None
):
    try:
        if data.mode == "chat":
            async with httpx.AsyncClient(timeout=None) as client:
                form_data = {
                    "topic": data.topic,
                    # "history": data.history,
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
            output = await generate_output_with_file(
                grade_level=data.grade_level,
                input_type=data.input_type,
                topic=data.topic,
                add_cont=data.add_cont,
                pdf_file=pdf_file,
                mode=data.mode
            )

        return {"output": output}
    except Exception as e:
        traceback_str = traceback.format_exc()
        print("[DEBUG] Full Traceback:\n", traceback_str, flush=True)
        return JSONResponse(status_code=500, content={"detail": str(e), "trace": traceback_str})
