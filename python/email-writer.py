# from fastapi import FastAPI, Form
# from fastapi.middleware.cors import CORSMiddleware
# from langchain_ollama import OllamaLLM as Ollama
# from langchain_core.chat_history import BaseChatMessageHistory
# from langchain_core.messages import BaseMessage, HumanMessage, AIMessage
# from langchain_core.prompts import ChatPromptTemplate, MessagesPlaceholder
# from langchain_core.runnables import RunnableWithMessageHistory, ConfigurableFieldSpec
# from pydantic import BaseModel
# from typing import List

# app = FastAPI()

# # CORS setup for Laravel frontend
# app.add_middleware(
#     CORSMiddleware,
#     allow_origins=["*"],  # For development; restrict for prod
#     allow_methods=["*"],
#     allow_headers=["*"],
# )

# # --- In-Memory Chat Store ---
# class InMemoryHistory(BaseChatMessageHistory, BaseModel):
#     messages: List[BaseMessage] = []

#     def add_messages(self, messages: List[BaseMessage]) -> None:
#         self.messages.extend(messages)

#     def clear(self) -> None:
#         self.messages = []

# store = {}
# def get_by_session_id(session_id: str) -> BaseChatMessageHistory:
#     if session_id not in store:
#         store[session_id] = InMemoryHistory()
#     return store[session_id]

# # --- Prompt + Chain ---
# prompt = ChatPromptTemplate.from_messages([
#     ("system", "You are a helpful and professional email writing assistant. Respond politely, and make edits or generate emails."),
#     MessagesPlaceholder(variable_name="messages")
# ])

# llm = Ollama(model="gemma3:4b")

# chat_chain = RunnableWithMessageHistory(
#     prompt | llm,
#     get_by_session_id,
#     input_messages_key="messages",
#     history_messages_key="messages"
# ).with_configurable_fields(configurable_fields={
#     "session_id": ConfigurableFieldSpec(id="session_id", annotation=str, default="default-session")
# })

# # --- Email Writer (original) ---
# @app.post("/generate-email")
# async def generate_email(content: str = Form(...)):
#     prompt_text = f"""
# You are an expert at writing professional and polite emails.

# Your task is to generate a formal, respectful email using the user's input.

# Details:
# {content}

# Write an email that:
# - Has a clear subject
# - Starts with a greeting
# - Explains the situation clearly
# - Ends with a polite closing

# Return only the email text. No notes or explanations.
# """
#     result = Ollama(model="gemma3:4b").invoke(prompt_text.strip())
#     return {"email": result.strip()}

# # --- Chat Follow-up Endpoint ---
# @app.post("/chat")
# async def continue_chat(content: str = Form(...), session_id: str = Form(default="default-session")):
#     history = get_by_session_id(session_id)
#     user_msg = HumanMessage(content=content)
#     full_response = chat_chain.invoke(
#         {"messages": history.messages + [user_msg]},
#         config={"configurable": {"session_id": session_id}}
#     )
#     return {"response": full_response.content}


# from fastapi import FastAPI, Form
# from fastapi.middleware.cors import CORSMiddleware
# from langchain_ollama import OllamaLLM as Ollama
# from langchain.prompts import PromptTemplate

# app = FastAPI()

# # Enable CORS for frontend (like Laravel)
# app.add_middleware(
#     CORSMiddleware,
#     allow_origins=["*"],  # OK for local dev
#     allow_methods=["*"],
#     allow_headers=["*"],
# )

# @app.post("/generate-email")
# async def generate_email(content: str = Form(...)):
#     prompt_template = """
# You are an expert at writing professional and polite emails.

# Your task is to generate a formal, respectful email using the user's input.

# Details:
# {content}

# Write an email that:
# - Has a clear subject
# - Starts with a greeting
# - Explains the situation clearly
# - Ends with a polite closing

# Return only the email text. No notes or explanations.
# """
#     prompt = PromptTemplate.from_template(prompt_template)
#     llm = Ollama(model="gemma3:4b")  # Match your model
#     chain = prompt | llm
#     result = chain.invoke({"content": content.strip()})
#     return {"email": result.strip()}

from fastapi import FastAPI, Form, UploadFile, File
from fastapi.middleware.cors import CORSMiddleware
from langchain_ollama import OllamaLLM as Ollama
from langchain.prompts import PromptTemplate
from langchain_community.document_loaders import PyPDFLoader
import tempfile, os

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
    llm = Ollama(model="gemma3:4b")
    chain = prompt | llm
    result = chain.invoke({"content": content.strip()})
    return {"email": result.strip()}

# ------------------- Summarizer -------------------
def summarize_text(text: str, conditions: str) -> str:
    clean_text = " ".join(text.strip().replace("\n", " ").replace("\r", "").split())[:3000]
    prompt_template = """
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
    prompt = PromptTemplate.from_template(prompt_template)
    llm = Ollama(model="gemma3:4b")
    chain = prompt | llm
    result = chain.invoke({"text": clean_text, "conditions": conditions})
    return result.strip()

@app.post("/summarize")
async def summarize(
    conditions: str = Form(...),
    text: str = Form(""),
    pdf: UploadFile = File(None)
):
    if pdf and pdf.filename and pdf.content_type == "application/pdf":
        contents = await pdf.read()
        if contents:
            with tempfile.NamedTemporaryFile(delete=False, suffix=".pdf") as tmp:
                tmp.write(contents)
                tmp_path = tmp.name

            loader = PyPDFLoader(tmp_path)
            pages = loader.load()
            os.remove(tmp_path)
            text = "\n".join([page.page_content for page in pages])

    if not text.strip():
        return {"summary": "No valid text provided."}

    summary = summarize_text(text, conditions)
    return {"summary": summary}

# ------------------- Thank You Note Generator -------------------
@app.post("/generate-thankyou")
async def generate_thank_you(reason: str = Form(...)):
    prompt_template = """
You are a thoughtful and kind assistant.

Your task is to write a sincere and warm thank-you note based on what the user is thankful for.

Reason for thanks:
{reason}

Write a thank-you note that:
- Expresses genuine appreciation
- Mentions specific contributions or actions
- Sounds human and heartfelt
- Ends with a warm closing

Important:
- Use a warm, natural tone (not robotic)
- Do not include any explanations or labels
- Return only the thank-you message
"""
    prompt = PromptTemplate.from_template(prompt_template)
    llm = Ollama(model="gemma3:4b")
    chain = prompt | llm
    result = chain.invoke({"reason": reason.strip()})
    return {"thank_you_note": result.strip()}

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
@app.post("/generate-contentcreator")
async def generate_contentcreator(
    grade_level: str = Form(...),
    length: str = Form(...),
    prompt: str = Form(...),
    extra: str = Form("")
):
    full_prompt = f"""
You are a creative and helpful content assistant.

Generate educational or engaging content based on the user's request. The content should match this grade level: {grade_level}

Prompt:
{prompt}

Additional Instruction:
{extra}

Length requested: {length}

Guidelines:
- Keep the tone clear, human, and helpful.
- Match the length closely (e.g., 1 paragraph, 2 paragraphs, 1 page, etc.)
- At the end, also write a catchy social media caption based on the generated content.

Output format:
CONTENT:
[full content here]

CAPTION:
[social media caption here]
"""
    llm = Ollama(model="gemma3:4b")
    prompt_template = PromptTemplate.from_template(full_prompt)
    chain = prompt_template | llm
    result = chain.invoke({})

    sections = result.strip().split("CAPTION:")
    content = sections[0].replace("CONTENT:", "").strip()
    caption = sections[1].strip() if len(sections) > 1 else ""

    return {
        "content": content,
        "caption": caption
    }
