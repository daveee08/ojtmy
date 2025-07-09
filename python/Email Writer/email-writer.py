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
    llm = Ollama(model="gemma3:1b")
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
    llm = Ollama(model="gemma3:1b") 
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
import re
def is_childlike_input(reason: str) -> bool:
    reason = reason.strip()
    words = reason.split()

    # Case 1: Very short message + known playful misspellings
    if len(words) <= 6:
        if re.search(r"\b(tank|yu|yur|beri|gud|sooo|plai|luv|hap|fun+|wanna)\b", reason.lower()):
            return True

    # Case 2: Repeated letters (e.g., "sooo", "yayyyy", "funnnn")
    if re.search(r"(.)\1{2,}", reason.lower()):
        return True

    # Case 3: Multiple emojis or exclamations
    if reason.count("!") >= 2 or any(e in reason for e in [":)", "❤️", "💖", "😁"]):
        return True

    # Case 4: All lowercase + no punctuation + short = likely informal
    if reason.islower() and len(words) <= 8 and not re.search(r"[.?!]", reason):
        return True

    return False

# -------------------------------------------------------
# 🧽 Clean up formal closings if they feel inappropriate
# -------------------------------------------------------
def clean_output(text: str) -> str:
    closings = ["Best regards", "Sincerely", "Kind regards", "Warm wishes", "With appreciation"]
    for phrase in closings:
        if phrase in text:
            text = text.replace(phrase, "").strip()
    return text

# ----------------------------------------------------------------
# 🚀 Main Route: LLM writes thank-you note with inferred tone/age
# ----------------------------------------------------------------
@app.post("/generate-thankyou")
async def generate_thank_you(reason: str = Form(...)):
    if not reason.strip():
        return {"thank_you_note": "Please enter a valid message."}

    # Soft tone guidance (invisible to user)
    if is_childlike_input(reason):
        reason += " (This message seems to be written by a young child with playful or informal spelling)"

    # Prompt that tells the LLM to do age and tone inference
    prompt_template = """
You are a thoughtful assistant who writes thank-you notes for users of all ages — from young children to working professionals.

Your task is to read the user's message and infer their likely age group (such as child, student, or professional) based solely on the way they wrote their message — their vocabulary, spelling, punctuation, tone, sentence structure, and emotional expression.

Then, write a thank-you note that:
- Matches the user's likely age and tone
- Uses simple, cheerful language if they sound like a child
- Uses clear and polite language for students or casual users
- Uses polished and respectful tone for professionals
- Mentions only what is clearly stated or implied
- Ends with a natural and emotionally fitting closing
- Only mention what is explicitly stated. Do not assume how items were used unless clearly described.
- Read and interpret the user's message as a human would — including spelling variations, playful grammar, or childlike phrasing — and infer their age group based on how it’s written.


Rules:
- DO NOT include made-up names, sender names, or signature lines
- DO NOT assume or invent extra context, relationships, or events
- DO NOT reference places, times, or actions not present in the input
- DO NOT include overly formal closings like “Best regards” unless truly appropriate
- Return ONLY the thank-you message — no labels, commentary, or metadata
- DO NOT assume the purpose or use of anything (e.g., what the paper was for) unless explicitly stated
- The user’s message may contain phonetic or childlike spelling 
- You are expected to interpret these as natural human expressions and rewrite them clearly and correctly in the thank-you note.
- Preserve names or references even if they appear noisy or misspelled.
- Focus on understanding what the user meant, not just what they typed.



Reason for thanks:
{reason}
"""

    try:
        # Generate the thank-you note
        prompt = PromptTemplate.from_template(prompt_template)
        llm = Ollama(model="gemma3:4b")
        chain = prompt | llm
        result = chain.invoke({"reason": reason.strip()})

        final_note = clean_output(result.strip())
        return {"thank_you_note": final_note}

    except Exception as e:
        return {"thank_you_note": f"Error generating note: {str(e)}"}

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

# ------------------- Social Stories -------------------
from fastapi import FastAPI, Form
from fastapi.middleware.cors import CORSMiddleware
from langchain_ollama import OllamaLLM as Ollama
from langchain.prompts import PromptTemplate

app = FastAPI()

# Enable CORS for Laravel frontend
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_methods=["*"],
    allow_headers=["*"],
)

# 🧠 Final strict prompt template with profanity guidance
prompt_template = """
You are a professional assistant who writes realistic, age-appropriate social stories for students and professionals at different levels.

Your task is to write a supportive, clear, and emotionally safe social story for someone at this level: {grade_level}

Context:
{situation}

Rules:
- DO NOT invent names of schools, universities, people, or places unless they are explicitly mentioned in the input.
- DO NOT invent scenes, routines, or activities (e.g., waking up, recess, lunch, playing with toys) unless clearly mentioned in the input.
- DO NOT make up examples of meals, cultural elements, or facilities (e.g., swings, adobo, uniforms).
- DO NOT assume classroom setup, routines, or location-specific details unless provided.
- DO NOT use markdown formatting (**, ##, headings, or bullets).
- DO NOT use storybook structure (Page 1, illustration suggestions, headings).
- DO NOT exaggerate, dramatize, or add fictional events.
- DO NOT include suggestions like “maybe,” “you might,” or “perhaps,” unless uncertainty is clearly in the user input.
- DO NOT include profanity in the story even if the user uses strong language.
- If the user is emotionally overwhelmed or frustrated, respond with empathy and grounded advice, not judgment.
- Do not include an introduction like "Here's your story" or "Let me tell you a story."

Tone & Voice:
- Speak gently, like a supportive teacher or counselor.
- If grade level is Pre-K to Grade 3, use short, simple sentences.
- If grade level is Grade 4 and above, maintain clarity with a calm, encouraging tone.
- Mention the person’s name only if it was included in the input.
- Focus only on what was shared by the user — avoid generalizations.
- Model realistic coping, kindness, and self-regulation strategies.

Output:
- Return only the story as plain text in paragraph form.
- Do not include a title, labels, or instructions.
- Ensure the response is grounded in the user’s actual context.

Now write the story.
"""

# 🧼 Profanity replacement
def censor_input(text: str) -> str:
    profanity_map = {
        "fuck": "mess up",
        "fucked": "messed up",
        "shit": "mistake",
        "damn": "problem",
        "bitch": "person",
        "asshole": "person",
        "crap": "mistake",
    }
    for bad, clean in profanity_map.items():
        text = text.replace(bad, clean).replace(bad.upper(), clean).replace(bad.capitalize(), clean)
    return text

@app.post("/generate-socialstory")
async def generate_social_story(
    grade_level: str = Form(...),
    situation: str = Form(...)
):
    cleaned_input = censor_input(situation.strip())

    prompt = PromptTemplate.from_template(prompt_template)
    llm = Ollama(model="llama3:instruct")
    chain = prompt | llm
    result = chain.invoke({
        "grade_level": grade_level,
        "situation": cleaned_input
    })
    return {"story": result.strip()}

# ------------------- Character Chatbot -------------------
from fastapi import FastAPI, Form
from fastapi.middleware.cors import CORSMiddleware
from langchain_ollama import OllamaLLM as Ollama
from langchain.prompts import PromptTemplate

app = FastAPI()

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_methods=["*"],
    allow_headers=["*"],
)

character_prompt = """
You are now roleplaying as: {character}

Your task is to have a conversation with a user who is at the following grade level: {grade_level}

Guidelines:
- Speak in the voice, style, and knowledge of the selected character, author, or historical figure.
- Your tone, vocabulary, and sentence structure must match the user's grade level.
- If the character is fictional, keep your responses within the story’s world and personality.
- If the character is real (like a historical figure or author), speak from their perspective using known facts and ideas from their life or works.
- DO NOT break character or reference being an AI or language model.
- DO NOT summarize their biography — speak as if you *are* the character.
- DO NOT ask the user to confirm who you are. Just reply as the character naturally would.

Start the first message as if the user greeted or asked you something.
"""

@app.post("/generate-characterchat")
async def generate_character_chat(
    grade_level: str = Form(...),
    character: str = Form(...)
):
    prompt = PromptTemplate.from_template(character_prompt)
    llm = Ollama(model="llama3:instruct")
    chain = prompt | llm
    result = chain.invoke({
        "grade_level": grade_level,
        "character": character
    })
    return {"response": result.strip()}
