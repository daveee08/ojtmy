from fastapi import FastAPI, Form, UploadFile, File
from fastapi.middleware.cors import CORSMiddleware
from langchain_ollama import OllamaLLM as Ollama
from langchain.prompts import PromptTemplate
from langchain_community.document_loaders import PyPDFLoader
import tempfile, os
import httpx

app = FastAPI()

# Enable CORS for frontend (like Laravel)
app.add_middleware(
    CORSMiddleware, 
    allow_origins=["*"],  # OK for local dev
    allow_methods=["*"],
    allow_headers=["*"],
)

# ------------------- Email Writer -------------------
@app.post("/generate-email")
async def generate_email(content: str = Form(...)):
    prompt_template = """
You are an expert in writing professional and polite emails.

Your task is to generate a formal and respectful email based on the user's input.

Context:
{content}

The email must:
- Include a clear and relevant subject line
- Begin with an appropriate greeting
- Clearly explain the purpose or situation
- End with a courteous closing and sign-off

Important:
- Use a professional and respectful tone throughout
- Do not include any additional notes, labels, or explanations
- Return only the final email text
"""

    prompt = PromptTemplate.from_template(prompt_template)
    llm = Ollama(model="gemma3:1b")
    chain = prompt | llm
    result = chain.invoke({"content": content.strip()})
    return {"email": result.strip()}

# ------------------- Summarizer -------------------
from fastapi import FastAPI, UploadFile, Form, HTTPException, Request, Depends, File
from fastapi.responses import JSONResponse
from pydantic import BaseModel
import tempfile, traceback, os, re
from typing import Optional
from langchain_core.prompts import PromptTemplate
from langchain_ollama import OllamaLLM as Ollama
from langchain_community.document_loaders import PyPDFLoader

app = FastAPI(debug=True)

# ==================== Pydantic Model ====================

class SummarizerRequest(BaseModel):
    summary_instructions: str
    text: Optional[str] = ""
    mode: Optional[str] = "manual"

    @classmethod
    def as_form(
        cls,
        summary_instructions: str = Form(...),
        text: str = Form(""),
        mode: str = Form("manual")
    ):
        return cls(summary_instructions=summary_instructions, text=text, mode=mode)

# ==================== Prompt + LLM Setup ====================

summary_template = """
You are an intelligent and precise summarization assistant.

Your task is to summarize the following content based on the user's exact instructions.

------------------------
Content to Summarize:
{text}

User Instructions:
{conditions}
------------------------

Important:
- Follow the structure, tone, and formatting exactly as requested by the user.
- Do not add your own formatting or assumptions.
- If bullet points, markdown, or specific paragraph structure is requested, apply them precisely.
- Return only the summary — do not include extra explanations or labels.

Now generate the summary below:
"""

prompt = PromptTemplate.from_template(summary_template)
model = Ollama(model="gemma3:4b")

# ==================== Helpers ====================

def clean_text(text: str) -> str:
    return " ".join(text.strip().replace("\n", " ").replace("\r", "").split())[:3000]

def clean_output(text: str) -> str:
    text = re.sub(r"\*\*(.*?)\*\*", r"\1", text)
    text = re.sub(r"\*(.*?)\*", r"\1", text)
    text = re.sub(r"^\s*[\*\-]\s*", "- ", text, flags=re.MULTILINE)
    return text.strip()

def extract_text_from_pdf(file: UploadFile) -> str:
    with tempfile.NamedTemporaryFile(delete=False, suffix=".pdf") as tmp:
        tmp.write(file.file.read())
        tmp_path = tmp.name
    loader = PyPDFLoader(tmp_path)
    pages = loader.load()
    os.remove(tmp_path)
    return "\n".join([page.page_content for page in pages])

# ==================== Route ====================

@app.post("/summarize")
async def summarize(
    data: SummarizerRequest = Depends(SummarizerRequest.as_form),
    pdf: Optional[UploadFile] = File(None),
    request: Request = None
):
    try:
        content = data.text.strip()

        if pdf and pdf.filename:
            content = extract_text_from_pdf(pdf)

        if not content:
            return {"summary": "No valid text provided."}

        user_input = {
            "text": clean_text(content),
            "conditions": data.summary_instructions
        }

        if data.mode =="chat":
            async with httpx.AsyncClient(timeout=None) as client:
                form_data = {
                    "topic": data.text,
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
            chain = prompt | model
            result = chain.invoke(user_input)

            return {"summary": clean_output(result)}

    except Exception as e:
        return JSONResponse(
            status_code=500,
            content={"detail": str(e), "trace": traceback.format_exc()}
        )

# ------------------- Thank You Note Generator -------------------
# 🔍 Tone Detection Helper
def detect_tone(reason: str) -> str:
    reason_lower = reason.lower()
    if any(w in reason_lower for w in ["crayon", "blocks", "snack", "story", "game", "coloring", "stickers", "play"]):
        return "child"
    elif any(w in reason_lower for w in ["project", "science", "essay", "math", "presentation", "exam", "class"]):
        return "student"
    elif any(w in reason_lower for w in ["client", "proposal", "review", "feedback", "deadline", "meeting", "support"]):
        return "professional"
    elif len(reason.split()) <= 6:
        return "casual"
    return "neutral"

# 🧽 Output Cleaner Helper
def clean_output(text: str, tone_hint: str) -> str:
    closings = ["Best regards", "Sincerely", "Kind regards", "Warm wishes", "With appreciation"]
    if tone_hint in ["child", "casual", "student"]:
        for phrase in closings:
            if phrase in text:
                text = text.replace(phrase, "").strip()
    return text

# 🚀 Main Route
@app.post("/generate-thankyou")
async def generate_thank_you(reason: str = Form(...)):
    tone_hint = detect_tone(reason)

    prompt_template = """
You are a thoughtful assistant who writes thank-you notes for users of all ages — from young children to working professionals.

Your task is to write a thank-you note that matches the user's age and tone as best as possible based only on what they wrote.

Reason for thanks:
{reason}

Tone hint (inferred): {tone_hint}

Write a thank-you note that:
- Uses tone, sentence structure, and vocabulary appropriate for the user's likely age or level, based only on the language of the reason
- Expresses genuine appreciation in a clear, kind, and natural way
- Mentions only what is directly included or implied in the reason
- Ends with a warm and suitable closing

Rules:
- Do NOT include made-up names, sender names, or signature lines
- Do NOT assume or invent extra context, events, relationships, or scenarios
- Do NOT refer to time, places, or actions unless they are clearly stated in the input
- Keep the message short and age-appropriate
- Return only the thank-you note text — no explanations, no formatting, no labels
- If the user sounds like a child, end the note with a cheerful, friendly phrase that a child might naturally say (like “Thanks again!” or “You’re awesome!”)
- If the user sounds like an adult, end the note with a more formal closing only if the tone is clearly professional
"""

    prompt = PromptTemplate.from_template(prompt_template)
    llm = Ollama(model="gemma3:4b")
    chain = prompt | llm

    result = chain.invoke({
        "reason": reason.strip(),
        "tone_hint": tone_hint
    })

    final_note = clean_output(result.strip(), tone_hint)
    return {"thank_you_note": final_note}

# ------------------- Idea Generator -------------------
@app.post("/generate-idea")
async def generate_idea(grade_level: str = Form(...), prompt: str = Form(...)):
    full_prompt = f"""
You are a helpful and practical assistant.

Based on the user's request below, generate a list of creative, realistic, and well-explained ideas suitable for a {grade_level} learner:

"{prompt}"

Instructions:
- If the user specifies a number (e.g., "give me 5 ideas"), provide exactly that number.
- If no number is specified, return only 3 of the best, most relevant ideas.
- Format each idea like this:

Idea 1: Title  
A clear, detailed, and actionable description (2–3 sentences). Focus on practical execution, setting, and expected outcome.

Do not include:
- Any introductions, summaries, or closing lines
- Any asterisks, bullet points, or markdown syntax
- Any labels like “Generated Ideas” or headings

Only return the list of ideas using the specified format.
"""

    llm = Ollama(model="gemma3:4b")
    template = PromptTemplate.from_template(full_prompt)
    chain = template | llm
    result = chain.invoke({})
    return {"idea": result.strip()}

# ------------------- Content Creator -------------------
@app.post("/contentcreator")
async def generate_contentcreator(
    grade_level: str = Form(...),
    length: str = Form(...),
    prompt: str = Form(...),
    extra: str = Form("")
):
    full_prompt = f"""
You are a helpful educational content writer.

Your task is to write general content for a user who is in {grade_level}.
Make sure the tone and language matches the cognitive understanding of that grade level.

Topic:
{prompt}

Additional Instructions:
{extra}

Content Length:
{length}

Write clearly and accurately.
"""

    try:
        llm = Ollama(model="gemma3:4b")
        prompt_template = PromptTemplate.from_template(full_prompt)
        chain = prompt_template | llm
        result = chain.invoke({})

        return {
            "content": result.strip()
        }
    except Exception as e:
        return {
            "error": f"Generation failed: {str(e)}"
        }