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

manual_topic_template = """
You are a clear, engaging, and student-friendly virtual tutor. Your role is to deliver accurate, well-structured informational content suited to the student's grade level and comprehension ability.

Parameters:
- Grade Level: {grade_level}
- Detail Level: {text_length} (e.g., one_paragraph, one_page, two_page, three_page)
- Informational Text Type: {text_type}
- Topic: {topic}

Instructions:
1. Your explanation must strictly follow the specified detail level:
   - "one_paragraph" → 1 concise paragraph (4-6 sentences).
   - "one_page" → enough content to fill a single page (approx. 250-300 words).
   - "two_page" → structured explanation covering two pages (approx. 500-600 words).
   - "three_page" → detailed explanation spanning three pages (approx. 750-900 words).
   - Avoid commentary phrases such as:
     - “Here is …”
- Do **not** include any headings, introductions, summaries, or formatting explanations.
- Do not exceed or fall short of the required length.

2. Match the tone and structure to the selected **informational text type**:
   - **Literary** → Present real events or factual information using a clear and engaging storytelling approach. Use descriptive language while maintaining accuracy.
   - **Expository** → Explain facts, concepts, or systems logically, clearly, and in a structured format. Prioritize clarity and coherence.
   - **Argumentative** → Present a central claim supported by logical reasoning and evidence. Clearly distinguish between claims, evidence, and counterpoints.
   - **Procedural** → Provide step-by-step instructions that guide the learner through a process. Use precise, instructional language and logical sequencing.

3. Tailor all content to the specified grade level:
   - Use age-appropriate, clear, and academically polished language. The tone should be accessible and student-friendly, while reflecting high-quality informational writing.
   - Break down complex ideas into understandable components without oversimplifying the content.
   - Use relatable examples that match the student's developmental stage.

4. Use formatting tools (headings, bullet points, steps, or short paragraphs) to improve readability, especially for longer texts.

5. Focus on **full explanation** of the topic. Avoid mere summaries—develop the content thoroughly based on the required length and text type.


 Your response must contain only the final informational text. 
"""

pdf_topic_template = """
You are a knowledgeable virtual tutor who explains content extracted from documents in a way suitable for the student's learning level.

Parameters:
- Grade Level: {grade_level}
- Detail Level: {text_length} (e.g., one_paragraph, one_page, etc.)
- Informational Text Type: {text_type}
- Extracted Content: {topic}

Instructions:
- Analyze and explain the extracted content according to:
  - "one_paragraph" → short but meaningful explanation.
  - "one_page" → standard explanation with clarity.
  - "two_page" → expanded content with structure and depth.
  - "three_page" → highly detailed, in-depth explanation with full breakdowns.
- Adapt explanation style to the type of text:
  - "literary" → describe people/events with narrative clarity.
  - "expository" → inform and explain logically.
  - "argumentive" → outline claims, reasons, and evidence.
  - "procedural" → explain in a step-by-step instructional format.
- Avoid summarizing — provide a full, student-friendly explanation.
- Keep the tone clear, objective, and instructional.

Respond ONLY with the structured explanation.
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

class InformationalInput(BaseModel):
    user_id: int
    input_type: str
    topic: str
    grade_level: str
    text_length: str
    text_type: str
    message_id: Optional[str] = None

    @classmethod
    def as_form(
        cls,
        user_id: int = Form(...),
        input_type: str = Form(...),
        topic: str = Form(""),
        grade_level: str = Form(...),
        text_length: str = Form(...),
        text_type: str = Form(...),
        message_id: Optional[str] = Form(default=None)
    ):
            return cls(
                user_id=user_id,
                input_type=input_type,
                topic=topic,
                grade_level=grade_level,
                text_length=text_length,
                text_type=text_type,
                message_id=message_id
            )
    
model = Ollama(model="llama3")
manual_prompt = ChatPromptTemplate.from_template(manual_topic_template)
pdf_prompt = ChatPromptTemplate.from_template(pdf_topic_template)

# --- PDF Loader ---
def load_pdf_content(pdf_path: str) -> str:
    if not os.path.exists(pdf_path):
        raise FileNotFoundError("PDF file not found.")
    loader = PyPDFLoader(pdf_path)
    documents = loader.load()
    return "\n".join(doc.page_content for doc in documents)

# --- Output Cleaner ---
def clean_output(text: str) -> str:
    text = re.sub(r"\*\*(.*?)\*\*", r"\1", text)
    text = re.sub(r"\*(.*?)\*", r"\1", text)
    text = re.sub(r"^\s*[\*\-]\s*", "", text, flags=re.MULTILINE)
    return text.strip()

async def generate_output(
    input_type: str,
    grade_level: str,
    text_length: str,
    text_type: str,
    topic: str = "",
    pdf_file: UploadFile = None,
):
    if input_type == "pdf":
        # Save PDF temporarily
        with tempfile.NamedTemporaryFile(delete=False, suffix=".pdf") as tmp:
            content = await pdf_file.read()
            tmp.write(content)
            tmp_path = tmp.name

        topic = load_pdf_content(tmp_path)
        os.unlink(tmp_path)  # Delete file after use
        prompt = pdf_prompt
    else:
        if not topic.strip():
            raise ValueError("Text input is required")
        prompt = manual_prompt

    # Compose input dict for prompt
    prompt_input = {
    "topic": topic,
    "grade_level": grade_level,
    "text_length": text_length,
    "text_type": text_type
    }
    
    chain = prompt | model
    result = chain.invoke(prompt_input)
    return clean_output(result)

@app.post("/informational")
async def informational_api(
    form_data: InformationalInput = Depends(InformationalInput.as_form),
    pdf_file: UploadFile = File(None)
):
    
    try:
        if form_data.input_type == "pdf" and not pdf_file:
            raise HTTPException(status_code=400, detail="PDF file required for PDF input_type")

        output = await generate_output(
            input_type=form_data.input_type,
            topic=form_data.topic,
            grade_level=form_data.grade_level,
            text_length=form_data.text_length,
            text_type=form_data.text_type,
            pdf_file=pdf_file,
        )

        scope_vars = {
            "grade_level": form_data.grade_level,
            "text_length": form_data.text_length,
            "text_type": form_data.text_type,
        }

        human_topic = form_data.topic if form_data.input_type != "pdf" else "[PDF Input]"

        session_id = create_session_and_parameter_inputs(
            user_id=form_data.user_id,
            agent_id=6,
            scope_vars=scope_vars,
            human_topic=human_topic,
            ai_output=output
        )

        return {"output": output, "message_id": session_id}
    except Exception as e:
        traceback_str = traceback.format_exc()
        print(traceback_str)
        return JSONResponse(status_code=500, content={"detail": str(e), "trace": traceback_str})

if __name__ == "__main__":
    uvicorn.run("informational_agent:app", host="127.0.0.1", port=5001, reload=True)