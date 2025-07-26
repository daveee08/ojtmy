from fastapi import FastAPI, UploadFile, Form, HTTPException, Request, Depends, File
from fastapi.responses import JSONResponse
from pydantic import BaseModel
import traceback, os, re, tempfile
import json
import httpx
from fastapi.middleware.cors import CORSMiddleware


from typing import Optional
from langchain_ollama import OllamaLLM
from langchain_core.prompts import ChatPromptTemplate
from langchain_community.document_loaders import PyPDFLoader
from langchain.prompts import PromptTemplate


import sys
import os

from sentence_transformers import SentenceTransformer
from transformers import AutoTokenizer
import fitz  # PyMuPDF
import faiss
import numpy as np
import mysql.connector
import requests
from contextlib import closing

# === Config ===
EMBED_MODEL = SentenceTransformer("all-MiniLM-L6-v2")
TOKENIZER = AutoTokenizer.from_pretrained("bert-base-uncased")
OLLAMA_URL = "http://localhost:11434/api/generate"
OLLAMA_MODEL = "gemma3:1b"
# OLLAMA_MODEL = "gemma3:latest"

HEADERS = {"Content-Type": "application/json"}

DB_CONFIG = {
    "host": "192.168.50.20",
    "user": "dev",
    "password": "dev123",
    "database": "ck_agent"
}

current_script_dir = os.path.dirname(os.path.abspath(__file__))
project_root = os.path.join(current_script_dir, '..', '..')
sys.path.insert(0, project_root)

print(f"Adding to sys.path: {project_root}") 

from chat_router_final import chat_router
from db_utils_final import create_session_and_parameter_inputs, insert_message

# ===================== App Initialization =====================

app = FastAPI()
app.include_router(chat_router)


app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # OK for local dev
    allow_methods=["*"],
    allow_headers=["*"],
)

llm = OllamaLLM(model="gemma3:1b")
model = OllamaLLM(model="gemma3:1b")



class TutorRequest(BaseModel):
    user_id: int
    grade_level: str
    topic: str = ""
    add_cont: str = ""
    message_id: Optional[int]

    @classmethod
    def as_form(
        cls,
        user_id: int = Form(...),
        grade_level: str = Form(...),
        topic: str = Form(""),
        add_cont: str = Form(""),
        message_id: int = Form(None)
    ):
        return cls(
            user_id=user_id,
            grade_level=grade_level,
            topic=topic,
            add_cont=add_cont,
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

manual_prompt = ChatPromptTemplate.from_template(manual_topic_template)
pdf_prompt = ChatPromptTemplate.from_template(pdf_topic_template)
chat_history_prompt = ChatPromptTemplate.from_template(chat_history_template)

# ===================== Helper Functions =====================

def extract_text_from_pdf(path: str) -> str:
    loader = PyPDFLoader(path)
    pages = loader.load()
    return " ".join([page.page_content for page in pages[:2]])

def clean_output(text: str) -> str:
    # text = re.sub(r"\*\*(.*?)\*\*", r"\1", text)
    # text = re.sub(r"\*(.*?)\*", r"\1", text)
    text = re.sub(r'\"', '', text)
    return text.strip()

async def generate_output_with_file(grade_level, topic="", add_cont=""):
    
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
):
    try:
        output = await generate_output_with_file(
            grade_level=data.grade_level,
            topic=data.topic,
            add_cont=data.add_cont,
        )
        scope_vars = {
                "grade_level": data.grade_level
            } 
        
        filled_prompt = manual_topic_template.format(grade_level=data.grade_level.strip(), topic=data.topic.strip(), add_cont=data.add_cont.strip()) #step 1

        session_id = create_session_and_parameter_inputs(
                user_id=data.user_id,
                agent_id=21,  # Default agent_id for step tutor
                scope_vars=scope_vars,
                human_topic=data.topic,
                ai_output=output.strip(),
                agent_prompt=filled_prompt
            )


        return {"output": output, "message_id": session_id}
    except Exception as e:
        traceback_str = traceback.format_exc()
        print("[DEBUG] Full Traceback:\n", traceback_str, flush=True)
        return JSONResponse(status_code=500, content={"detail": str(e), "trace": traceback_str})
    

# storage/app/python/step_tutor_agent.py

from http.client import HTTPException
import re
# Prompt Template for Step-by-Step Explanation (without add_cont)
step_prompt_template = """
You are a patient and supportive tutor that teaches students step-by-step based on their grade level. You must explain the given topic clearly, breaking it down into understandable parts for the student.

Instructions:
- Adjust your explanation to suit the student's grade level.
- Teach in a friendly and approachable tone.
- Explain each part or concept step-by-step.
- Keep steps numbered and easy to follow.
- Use analogies or examples appropriate for the grade level.

---
**Student Grade Level:** {grade_level}
**Topic:** {topic}

**Your Output (Step-by-Step):**
"""
follow_up_prompt_template = """You are a dedicated multilingual translator and translation assistant. 
Your primary function is to translate text clearly and naturally. Additionally, you can answer questions directly related to the provided history of translation, such as 'translate it back' or 'how do I pronounce this?' 
Do not engage in conversations, provide information, or answer questions outside the scope of translation or translation-related assistance. 
For direct translation requests, return only the translated text, without the original, explanations, or extra commentary."""

model = OllamaLLM(model="gemma3:1b")
step_prompt = ChatPromptTemplate.from_template(step_prompt_template)

class StepTutorInput(BaseModel):
    grade_level: str
    topic: str
    user_id: int
    message_id: Optional[int] = None
    # agent_id: int = 16  # Default agent_id for step tutor

    @classmethod
    def as_form(
        cls,
        grade_level: str = Form(...),
        topic: str = Form(...),
        user_id: int = Form(...),
        message_id: Optional[int] = Form(None)
    ):
        return cls(
            grade_level=grade_level,
            topic=topic,
            user_id=user_id,
            message_id=message_id
        )

async def explain_topic_step_by_step(grade_level: str, topic: str) -> str:
    chain = step_prompt | model
    result = chain.invoke({
        "grade_level": grade_level,
        "topic": topic
    })
    return clean_output(result)

def clean_output(text: str) -> str:
    # text = re.sub(r"\*\*(.*?)\*\*", r"\1", text)
    # text = re.sub(r"\*(.*?)\*", r"\1", text)
    # text = re.sub(r"^\s*[\*\-]\s*", "", text, flags=re.MULTILINE)
    return text.strip()


@app.post("/explain_step_by_step")
async def explain_step_by_step_endpoint(data: StepTutorInput = Depends(StepTutorInput.as_form)):
    if not data.grade_level or not data.topic:
        raise HTTPException(status_code=400, detail="Grade level and topic are required.")

    try:
        explanation = await explain_topic_step_by_step(data.grade_level, data.topic)

        scope_vars = {
            "grade_level": data.grade_level
        }
        filled_prompt = step_prompt_template.format(grade_level=data.grade_level.strip(),topic=data.topic.strip()) #step 1
        
        session_id = create_session_and_parameter_inputs(
            user_id=data.user_id,
            agent_id=2,  # Default agent_id for step tutor
            scope_vars=scope_vars,
            human_topic=data.topic,
            ai_output=explanation,
            agent_prompt=filled_prompt
        )
        return {"explanation": explanation, "message_id":  session_id}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))


# 🧠 Final strict prompt template with profanity guidance
social_prompt_template = """
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

class SocialStoryInput(BaseModel):
    grade_level: str
    situation: str
    user_id: int
    message_id: Optional[int] = None

    @classmethod
    def as_form(
        cls,
        grade_level: str = Form(...),
        situation: str = Form(...),
        user_id: int = Form(...),
        message_id: Optional[int] = Form(None)
    ):
        return cls(
            grade_level=grade_level,
            situation=situation,
            user_id=user_id,
            message_id=message_id
        )


social_prompt = PromptTemplate.from_template(social_prompt_template)

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
async def generate_social_story(data: SocialStoryInput = Depends(SocialStoryInput.as_form)
):
    try:
        cleaned_input = censor_input(data.situation.strip())

        chain = prompt | llm
        result = chain.invoke({
            "grade_level": data.grade_level,
            "situation": cleaned_input
        })

        scope_vars = {
                "grade_level": data.grade_level
            }
        
        filled_prompt= prompt_template.format(
            grade_level=data.grade_level.strip(), 
            situation=cleaned_input.strip()
        )
        
        session_id = create_session_and_parameter_inputs(
                user_id=data.user_id,
                agent_id=21,  # Default agent_id for step tutor
                scope_vars=scope_vars,
                human_topic=data.situation,
                ai_output=result.strip(),
                agent_prompt=filled_prompt
            )
        return {"story": result.strip(), "message_id": session_id}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))
    


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



# Safer templated prompt
translator_prompt_template = ChatPromptTemplate.from_template("""
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
chain = translator_prompt_template | model

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

        filled_prompt = translator_prompt_template.format(language=data.target_language.strip(), text=data.text.strip())

        session_id = create_session_and_parameter_inputs(
            user_id=data.user_id,
            agent_id=20,  # Default agent_id for step tutor
            scope_vars=scope_vars,
            human_topic=data.text,
            ai_output=output,
            agent_prompt=filled_prompt
        )

        
        return {"translation": output, "message_id": session_id}




# ------------------- Email Writer -------------------

class EmailWriterInput(BaseModel):
    content: str
    user_id: int
    message_id: Optional[int] = None
    # agent_id: int = 16  # Default agent_id for step tutor

    @classmethod
    def as_form(
        cls,
        content: str = Form(...),
        user_id: int = Form(...),
        message_id: Optional[int] = Form(None)
    ):
        return cls(
            content=content,
            user_id=user_id,
            message_id=message_id
        )
    

@app.post("/generate-email")
async def generate_email(data: EmailWriterInput = Depends(EmailWriterInput.as_form)):
    email_prompt_template = f"""
You are an expert in writing professional and polite emails.

Your task is to generate a formal and respectful email based on the user's input.

Context:
{data.content}

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

    prompt = PromptTemplate.from_template(email_prompt_template)
    chain = prompt | llm
    result = chain.invoke({})

    scope_vars = {
            "content": data.content
        }
    
    session_id = create_session_and_parameter_inputs(
            user_id=data.user_id,
            agent_id=4,  # Default agent_id for step tutor
            scope_vars=scope_vars,
            human_topic=data.content,
            ai_output=result,
            agent_prompt=email_prompt_template
        )
    return {"email": result.strip(), "message_id": session_id}


# ------------------- Summarizer -------------------

summarize_prompt_template = """
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
def summarize_text(text: str, conditions: str) -> str:
    # clean_text = " ".join(text.strip().replace("\n", " ").replace("\r", "").split())[:3000]
    clean_text = text.strip()
    prompt = PromptTemplate.from_template(summarize_prompt_template)
    chain = prompt | llm
    result = chain.invoke({"text": clean_text, "conditions": conditions})
    return result.strip()

class SummarizeInput(BaseModel):
    conditions: str
    text: str
    user_id: int
    message_id: Optional[int] = None
    # agent_id: int = 16  # Default agent_id for step tutor

    @classmethod
    def as_form(
        cls,
        conditions: str = Form(...),
        text: str = Form(...),
        user_id: int = Form(...),
        message_id: Optional[int] = Form(None)
    ):
        return cls(
            conditions=conditions,
            text=text,
            user_id=user_id,
            message_id=message_id
        )

@app.post("/summarize")
async def summarize(data: SummarizeInput = Depends(SummarizeInput.as_form),
                    pdf: UploadFile = File(None) ):
    if pdf and pdf.filename and pdf.content_type == "application/pdf":
        contents = await pdf.read()
        if contents:
            with tempfile.NamedTemporaryFile(delete=False, suffix=".pdf") as tmp:
                tmp.write(contents)
                tmp_path = tmp.name

            loader = PyPDFLoader(tmp_path)
            pages = loader.load()
            os.remove(tmp_path)
            data.text = "\n".join([page.page_content for page in pages])

    if not data.text.strip():
        return {"summary": "No valid text provided."}
    
    summary = summarize_text(data.text, data.conditions)

    scope_vars = {
            "conditions": data.conditions,
        }
    filled_prompt = summarize_prompt_template.format(text=data.text.strip(), conditions=data.conditions.strip())
    session_id = create_session_and_parameter_inputs(
            user_id=data.user_id,
            agent_id=4,
            scope_vars=scope_vars,
            human_topic=data.text,
            ai_output=summary,
            agent_prompt=filled_prompt
        )


    return {"summary": summary, "message_id": session_id}



# ------------------- Thank You Note Generator -------------------
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

class GenerateThankyou(BaseModel):
    reason: str
    user_id: int
    message_id: Optional[int] = None
    # agent_id: int = 16  # Default agent_id for step tutor

    @classmethod
    def as_form(
        cls,
        reason: str = Form(...),
        user_id: int = Form(...),
        message_id: Optional[int] = Form(None)
    ):
        return cls(
            reason=reason,
            user_id=user_id,
            message_id=message_id
        )

@app.post("/generate-thankyou")
async def generate_thank_you(data: GenerateThankyou = Depends(GenerateThankyou.as_form)):
    reason = data.reason
    if not reason.strip():
        return {"thank_you_note": "Please enter a valid message."}

    # Soft tone guidance (invisible to user)
    if is_childlike_input(reason):
        reason += " (This message seems to be written by a young child with playful or informal spelling)"

    # Prompt that tells the LLM to do age and tone inference
    thank_you_prompt_template = """
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
        prompt = PromptTemplate.from_template(thank_you_prompt_template)
        chain = prompt | llm
        result = chain.invoke({"reason": reason.strip()})
        filled_prompt = thank_you_prompt_template.format(reason=reason.strip()) #step 1


        final_note = clean_output(result.strip())

        scope_vars = {
        "reason": data.reason
        }
        session_id = create_session_and_parameter_inputs(
                user_id=data.user_id,
                agent_id=12,  # Default agent_id for thankyou
                scope_vars=scope_vars,
                human_topic=data.reason,
                ai_output=result,
                agent_prompt=filled_prompt
        )
    
        return {"thank_you_note": final_note ,"message_id": session_id}

    except Exception as e:
        return {"thank_you_note": f"Error generating note: {str(e)}"}
        # return {"thank_you_note": f"Error generating note: {type(filled_prompt)}"}


# ------------------- Idea Generator -------------------

class IdeaGeneratorInput(BaseModel):
    grade_level: str
    prompt: str
    user_id: int
    message_id: Optional[int] = None
    # agent_id: int = 16  # Default agent_id for step tutor

    @classmethod
    def as_form(
        cls,
        grade_level: str = Form(...),
        prompt: str = Form(...),
        user_id: int = Form(...),
        message_id: Optional[int] = Form(None)
    ):
        return cls(
            grade_level=grade_level,
            prompt=prompt,
            user_id=user_id,
            message_id=message_id
        )
@app.post("/generate-idea")
async def generate_idea(data:  IdeaGeneratorInput = Depends(IdeaGeneratorInput.as_form)):
    idea_full_prompt = f"""
You are a helpful and practical assistant.

Based on the user's request below, generate a list of creative, realistic, and well-explained ideas suitable for a {data.grade_level} learner:

"{data.prompt}"

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

    # llm = Ollama(model="gemma3:1b")
    template = PromptTemplate.from_template(idea_full_prompt)
    chain = template | llm
    result = chain.invoke({})

    scope_vars = {
        "grade_level": data.grade_level
    }
    session_id = create_session_and_parameter_inputs(
            user_id=data.user_id,
            agent_id=13,  # Default agent_id for step tutor
            scope_vars=scope_vars,
            human_topic=data.prompt,
            ai_output=result,
            agent_prompt=idea_full_prompt
        )
    return {"idea": result.strip(), "message_id": session_id}

@app.get("/test")
def test():
    return {"message": "Hello, FastAPI is working!"}

# ------------------- Content Creator -------------------

class ContentCreatorInput(BaseModel):
    grade_level: str
    prompt: str
    user_id: int
    message_id: Optional[int] = None
    length: str
    extra: str

    @classmethod
    def as_form(
        cls,
        grade_level: str = Form(...),
        prompt: str = Form(...),
        user_id: int = Form(...),
        message_id: Optional[int] = Form(None),
        extra: Optional[str] = Form(""),
        length: str = Form(...),
        

    ):
        return cls(
            grade_level=grade_level,
            prompt=prompt,
            user_id=user_id,
            message_id=message_id,
            length=length,
            extra=extra
        )
    
@app.post("/generate-contentcreator")
async def generate_contentcreator(
    data: ContentCreatorInput = Depends(ContentCreatorInput.as_form)
):
    content_full_prompt = f"""
You are a creative and helpful content assistant.

Generate educational or engaging content based on the user's request. The content should match this grade level: {data.grade_level}

Prompt:
{data.prompt}

Additional Instruction:
{data.extra}

Length requested: {data.length}

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
    prompt_template = PromptTemplate.from_template(content_full_prompt)
    chain = prompt_template | llm
    result = chain.invoke({})

    sections = result.strip().split("CAPTION:")
    content = sections[0].replace("CONTENT:", "").strip()
    caption = sections[1].strip() if len(sections) > 1 else ""

    result = content + caption

    scope_vars ={
        "grade_level": data.grade_level,
        "length": data.length
    }

    session_id = create_session_and_parameter_inputs(
            user_id=data.user_id,
            agent_id=14,  # Default agent_id for step tutor
            scope_vars=scope_vars,
            human_topic=data.prompt,
            ai_output=result,
            agent_prompt=content_full_prompt
        )


    return {
        "content": content,
        "caption": caption,
        "message_id": session_id
    }


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

class CharacterBotInput(BaseModel):
    grade_level: str
    character: str
    user_id: int
    message_id: Optional[int] = None
    # agent_id: int = 16  # Default agent_id for step tutor

    @classmethod
    def as_form(
        cls,
        grade_level: str = Form(...),
        character: str = Form(...),
        user_id: int = Form(...),
        message_id: Optional[int] = Form(None)
    ):
        return cls(
            grade_level=grade_level,
            character=character,
            user_id=user_id,
            message_id=message_id
        )
    
@app.post("/generate-characterchat")
async def generate_character_chat( data: CharacterBotInput = Depends(CharacterBotInput.as_form)
):
    try:
        prompt = character_prompt.format(
            grade_level=data.grade_level,
            character=data.character
        )
        result = llm.invoke(prompt)

        scope_vars = {
        "grade_level": data.grade_level,
        "character": data.character,
        }

        filled_prompt = character_prompt.format(character=data.character.strip(), grade_level=data.grade_level.strip()) #step 1


        session_id = create_session_and_parameter_inputs(
                user_id=data.user_id,
                agent_id=28,  # Default agent_id for thankyou
                scope_vars=scope_vars,
                human_topic=data.character,
                ai_output=result,
                agent_prompt=filled_prompt
        )
    

        return {"response": result.strip(), "message_id": session_id}
    except Exception as e:
        return {"error": "Character generation failed", "details": str(e)}

# Assignment Scaffolding Prompt Template

ass_scaff_prompt_template = """
You are an educational assistant.

Your task is to turn the following prompt into a fully scaffolded, student-friendly assignment for **Grade {grade_level}** students.

Before generating the output, consider the student-s level based on the grade provided. Adapt the language, tone, and level of thinking appropriately using the following categories:

- Kindergarten: Use very basic vocabulary, large font structure, simple tasks, and a playful tone. Focus on short, guided steps.
- Grades 1-6 (Elementary): Use simple instructions and vocabulary. Provide concrete examples and offer lots of guidance.
- Grades 7-10 (Junior High): Use age-appropriate academic tone, moderately complex instructions, and include examples that encourage independent thinking.
- Grades 11-12 (Senior High): Use a more formal tone. Encourage higher-order thinking and self-directed learning.
- University: Use clear academic language. Expect independent interpretation, structured output, and critical analysis or reflection.

**Prompt:**
"{topic}"

Your output MUST follow the structure and exact formatting of the example below. The goal is to turn the assignment into a clear, step-by-step activity that supports understanding and completion. Use simple, encouraging language.

--- EXAMPLE OUTPUT FORMAT TO FOLLOW EXACTLY ---

Writing Figures of Speech: A Step-by-Step Guide

Assignment Overview
In this assignment, you will write one sentence for each of the five figures of speech listed below. Each sentence should clearly show the figure of speech you are using. Follow the steps carefully to complete your work successfully!

Figures of Speech
• Simile
• Metaphor
• Personification
• Hyperbole
• Onomatopoeia

Step-by-Step Instructions

Step 1: Understand Each Figure of Speech
Before you start writing, let's learn what each figure of speech means. Here are some definitions:

Simile: A comparison between two things using "like" or "as."
Metaphor: A direct comparison between two things without using "like" or "as."
Personification: Giving human qualities to non-human things.
Hyperbole: An exaggerated statement that isn't meant to be taken literally.
Onomatopoeia: A word that imitates a sound.

Step 2: Brainstorm Ideas
Think of examples for each figure of speech. You can write down a few ideas for each one:

Simile: (e.g., "as brave as a lion")
Metaphor: (e.g., "time is a thief")
Personification: (e.g., "the wind whispered")
Hyperbole: (e.g., "I could sleep for a thousand years")
Onomatopoeia: (e.g., "buzz," "clang")

Step 3: Write Your Sentences
Now, use your brainstormed ideas to write a complete sentence for each figure of speech. Make sure to:

Simile: Write a sentence comparing two things using "like" or "as."
Metaphor: Write a sentence making a direct comparison without "like" or "as."
Personification: Write a sentence giving human traits to something non-human.
Hyperbole: Write a sentence that exaggerates for effect.
Onomatopoeia: Write a sentence that includes a sound word.

Step 4: Review Your Sentences
After you write your sentences, read them carefully. Check for:

Clarity: Does each sentence clearly show the figure of speech?
Grammar: Are your sentences complete and correct?
Creativity: Did you use unique and interesting examples?

Step 5: Submit Your Work
Once you are happy with your sentences, turn in your assignment. Make sure you have written one sentence for each of the five figures of speech!

Tips for Success
If you get stuck, think of examples from books, movies, or songs.
Don't hesitate to ask a classmate or teacher for help if you're unsure about a figure of speech.
Take your time and have fun with your writing!

--- END OF EXAMPLE OUTPUT ---

Your output should directly replace the "--- EXAMPLE OUTPUT FORMAT TO FOLLOW EXACTLY ---" section with the content specific to the provided prompt and grade level, while maintaining the exact structural elements and plain text formatting shown.

**Formatting Notes for your output:**
* **Do NOT use Markdown bolding characters (`**`) or any other Markdown formatting like italics, links, etc.** The output should be plain text.
* For lists, use the specific bullet character `•` (U+2022) as shown in the example, followed by a single space.
* Ensure consistent line breaks and spacing as in the example.
* The output should be a clean, ready-to-use student handout, entirely text-based.
* Do NOT include any explanations, developer notes, or "thought process" in your final output. Return ONLY the formatted assignment.
* Do NOT include any images, diagrams, or visual references.
"""



class AssignmentScaffolderInput(BaseModel):
    user_id: int
    input_type: str 
    topic: str
    grade_level: str

    @classmethod
    def as_form(
        cls,
        user_id: int = Form(...),
        input_type: str = Form(...),
        topic: str = Form(""),
        grade_level: str = Form(...),
        message_id: Optional[str] = Form(default=None)
    ):
        return cls(
            user_id=user_id,
            input_type=input_type,
            topic=topic,
            grade_level=grade_level,
            message_id=message_id
        )

scaffolder_prompt = ChatPromptTemplate.from_template(ass_scaff_prompt_template)

# Load PDF content
def load_pdf_content(pdf_path: str) -> str:
    if not os.path.exists(pdf_path):
        raise FileNotFoundError("PDF file not found.")
    loader = PyPDFLoader(pdf_path)
    documents = loader.load()
    return "\n".join(doc.page_content for doc in documents)

def clean_output(text: str) -> str:
    return text.strip()

# Generation logic
async def generate_output(
    input_type: str,
    grade_level: str,
    topic: str = "",
    pdf_file: UploadFile = None,
):
    if input_type == "pdf":
        # Save and load PDF
        with tempfile.NamedTemporaryFile(delete=False, suffix=".pdf") as tmp:
            content = await pdf_file.read()
            tmp.write(content)
            tmp_path = tmp.name

        topic = load_pdf_content(tmp_path)
        os.unlink(tmp_path)
    else:
        if not topic.strip():
            raise ValueError("Text input is required.")

    prompt_input = {
        "topic": topic,
        "grade_level": grade_level,
    }

    chain = ass_scaff_prompt_template | model
    result = chain.invoke(prompt_input)
    return clean_output(result)

@app.post("/assignmentscaffolder")
async def informational_api(
    form_data: AssignmentScaffolderInput = Depends(AssignmentScaffolderInput.as_form),
    pdf_file: UploadFile = File(None)
):
    
    try:
        if form_data.input_type == "pdf" and not pdf_file:
            raise HTTPException(status_code=400, detail="PDF file required for PDF input_type")

        output = await generate_output(
            input_type=form_data.input_type,
            topic=form_data.topic,
            grade_level=form_data.grade_level,
            pdf_file=pdf_file,
        )

        scope_vars = {
            "grade_level": form_data.grade_level
        }

        filled_prompt = ass_scaff_prompt_template.format(
            topic=form_data.topic.strip(),
            grade_level=form_data.grade_level.strip()
        )

        session_id = create_session_and_parameter_inputs(
            user_id=form_data.user_id,
            agent_id=6,
            scope_vars=scope_vars,
            human_topic=form_data.topic,
            ai_output=output,
            agent_prompt=filled_prompt
        )

        return {"output": output, "message_id": session_id}
    except Exception as e:
        traceback_str = traceback.format_exc()
        print(traceback_str)
        return JSONResponse(status_code=500, content={"detail": str(e), "trace": traceback_str})


chat_with_docs_prompt_template = """
You are a helpful and student-friendly tutor. Your job is to explain the following topics clearly and simply, as if you're teaching a student.

Topics:
- Topic A: {topic}
- Topic B: {topic_1}

Instructions:
- Use simple language appropriate for the student's level.
- Break down complex ideas into understandable parts.
- Be concise, but ensure the explanation is complete.

Only provide the explanation. Do not include introductions, conclusions, or phrases like "Sure, here's the explanation".
"""

class InformationalInput(BaseModel):
    user_id: int
    input_type: str
    input_type_1: Optional[str] = None
    topic: str
    topic_1: Optional[str] = None
    message_id: Optional[str] = None

    @classmethod
    def as_form(
        cls,
        user_id: int = Form(...),
        input_type: str = Form(...),
        input_type_1: Optional[str] = Form(None),
        topic: str = Form(""),
        topic_1: Optional[str] = Form(None),
        message_id: Optional[str] = Form(None),
    ):
        return cls(
            user_id=user_id,
            input_type=input_type,
            input_type_1=input_type_1,
            topic=topic,
            topic_1=topic_1,
            message_id=message_id
        )
chatdocs_prompt_template = ChatPromptTemplate.from_template(chat_with_docs_prompt_template)

def load_pdf_content(pdf_path: str) -> str:
    if not os.path.exists(pdf_path):
        raise FileNotFoundError("PDF file not found.")
    loader = PyPDFLoader(pdf_path)
    documents = loader.load()
    return "\n".join(doc.page_content for doc in documents)

# Function to clean the output from formatting artifacts
def clean_output(text: str) -> str:
    return text.strip()

async def generate_output(
    input_type: str,
    input_type_1: str,
    topic: str = "",
    topic_1: str = "",
    pdf_file: UploadFile = None,
    pdf_file_1: UploadFile = None,
):
    if input_type == "pdf":
        with tempfile.NamedTemporaryFile(delete=False, suffix=".pdf") as tmp:
            content = await pdf_file.read()
            tmp.write(content)
            tmp_path = tmp.name
        topic = load_pdf_content(tmp_path)
        os.unlink(tmp_path)
    elif not topic.strip():
        raise ValueError("Text input is required")

    if input_type_1 == "pdf_1":
        if not pdf_file_1:
            raise ValueError("PDF file required for second input")
        with tempfile.NamedTemporaryFile(delete=False, suffix=".pdf") as tmp:
            content = await pdf_file_1.read()
            tmp.write(content)
            tmp_path = tmp.name
        topic_1 = load_pdf_content(tmp_path)
        os.unlink(tmp_path)
    elif input_type_1 == "topic_1":
        if not topic_1.strip():
            raise ValueError("Text input is required for second input")
    else:
        topic_1 = ""

    # Compose input dict for prompt
    prompt_input = {
    "topic": topic,
    "topic_1": topic_1,
    }
    
    chain = chatdocs_prompt_template | model
    result = chain.invoke(prompt_input)
    return clean_output(result)

@app.post("/chatwithdocs")
async def chatwithdocs_api(
    form_data: InformationalInput = Depends(InformationalInput.as_form),
    pdf_file: UploadFile = File(None),
    pdf_file_1: UploadFile = File(None)
):
    
    try:
        if form_data.input_type == "pdf" and not pdf_file:
            raise HTTPException(status_code=400, detail="PDF file required for PDF input_type")

        output = await generate_output(
            input_type=form_data.input_type,
            input_type_1=form_data.input_type_1,
            topic=form_data.topic,
            topic_1=form_data.topic_1,
            pdf_file=pdf_file,
            pdf_file_1=pdf_file_1
        )

        scope_vars = {}
        
        filled_prompt = chat_with_docs_prompt_template.format(
            topic=form_data.topic.strip(),
            topic_1=form_data.topic_1.strip(),
        )

        session_id = create_session_and_parameter_inputs(
            user_id=form_data.user_id,
            agent_id=7,
            scope_vars=scope_vars,
            human_topic=form_data.topic,
            ai_output=output,
            agent_prompt=filled_prompt
        )

        return {"output": output, 'message_id': session_id}
    except Exception as e:
        traceback_str = traceback.format_exc()
        print(traceback_str)
        return JSONResponse(status_code=500, content={"detail": str(e), "trace": traceback_str})

# Instruction template
inst_prompt_template = """
Your task is toGenerate a custom professional email in response to an email that you received based on the user's intent and the original message received.

Parameters:
- Author Name: {author}
- Email to Respond To: {email}
- Communication Intent: {intent}
- Tone: {tone}

Instructions:

Write an email from the user (the author) replying to the provided email.
- Reflect the user’s communication intent and follow the selected tone accurately.
- The output should begin with this format on the first line:
- Subject: Re: [insert appropriate subject from the email]
- Use appropriate greetings and sign-offs based on the specified tone:
    - Formal: “Dear [Name],” | “Sincerely,” or “With respect,”
    - Friendly: “Hi [Name],” or “Hello [Name],” | “Warm regards,” or “Take care,”
    - Concise: “Hello [Name],” | “Best,” or “Regards,”
    - Apologetic: “Dear [Name],” | “Apologies,” or “Thank you again,”
    - Assertive: “Dear [Name],” | “Respectfully,” or “Thank you for your attention,”
- Avoid generic placeholders (e.g., “[Name]”); use a specific name if present, or a courteous general greeting (e.g., “Dear Hiring Team,”).
- Use a specific name if mentioned; otherwise, use a polite general salutation (e.g., “Dear Hiring Team,”).
- Keep the tone consistent throughout the email.
- Format the email with clear paragraph breaks for readability.
- Apply any requested formatting or structure (e.g., word limit, bullet points) as needed.
- End with a proper sign-off and the author’s full name.

Response Only — do not include instructions, tags, or explanations.
"""

class ResponderInput(BaseModel):
    user_id: int
    author: str
    email: str
    intent: str
    tone: str
    message_id: Optional[str] = None

    @classmethod
    def as_form(
        cls,
        user_id: int = Form(...),
        author: str = Form(""),
        email: str = Form(""),
        intent: str = Form(""),
        tone: str = Form(...),
        message_id: Optional[str] = Form(default=None)
    ):
        return cls(
            user_id=user_id,
            author=author,
            email=email,
            intent=intent,
            tone=tone,
            message_id=message_id
        )




# Clean output from formatting artifacts
def clean_output(text: str) -> str:
    return text.strip()

# Email generation logic
async def generate_output(
    tone: str,
    author: str = "",
    email: str = "",
    intent: str = "",
):
    prompt_input = {
        "author": author,
        "email": email,
        "intent": intent,
        "tone": tone,
    }

    reso_prompt_template = ChatPromptTemplate.from_template(inst_prompt_template)

    chain = reso_prompt_template | model
    result = chain.invoke(prompt_input)
    return clean_output(result)

@app.post("/responder")
async def responder_api(
    form_data: ResponderInput = Depends(ResponderInput.as_form),
    pdf_file: UploadFile = File(None)
):
    try:
        output = await generate_output(
            author=form_data.author,
            email=form_data.email,
            intent=form_data.intent,
            tone=form_data.tone,
        )

        scope_vars = {
            "author": form_data.author,
            "email": form_data.email,
            "intent": form_data.intent,
            "tone": form_data.tone
        }

        filled_prompt = inst_prompt_template.format(
            author=form_data.author.strip(),
            email=form_data.email.strip(),
            intent=form_data.intent.strip(),
            tone=form_data.tone.strip()
        )

        session_id = create_session_and_parameter_inputs(
            user_id=form_data.user_id,
            agent_id=11,
            scope_vars=scope_vars,
            human_topic=form_data.intent,
            ai_output=output,
            agent_prompt=filled_prompt
        )

        return {"output": output, "message_id": session_id}
    except Exception as e:
        traceback_str = traceback.format_exc()
        print(traceback_str)
        return JSONResponse(status_code=500, content={"detail": str(e), "trace": traceback_str})

exp_prompt_template = """
You are a knowledgeable and student-friendly virtual tutor.

You will receive either:
- A brand new concept to explain from scratch, or
- A follow-up question based on a previously explained concept

Your task is to respond clearly, accurately, and appropriately for the student's grade level. Always write with the student's level of understanding in mind.

Input:
Concept or Question: {concept}
Grade Level: {grade_level}

Guidelines:
- If this is a follow-up question (e.g. "Is this related to climate change?" or "Can you explain trade more?"), respond only to that part. Avoid repeating the original explanation unless necessary.
- If this is a new concept, provide a full explanation in a natural and flexible structure — not a fixed template.
- Break down key ideas using clear subheadings (e.g., Causes, Examples, Steps, etc.) when helpful. Use real-world analogies and comparisons when appropriate.
- Avoid using rigid section labels unless they naturally fit the topic.
- Make your explanation engaging, structured, and focused. Do not repeat the concept in the first sentence — get straight into explaining it.

Grade Adaptation:
- Grades 1-3: Use very simple language and examples from everyday life.
- Grades 4-5: Introduce light structure and familiar scenarios with simple logic.
- Grades 6-8: Use clear explanations, school-related analogies, and logical flow.
- Grades 9-10: Use more advanced vocabulary, organized reasoning, and step-based explanations.
- Grades 11-12: Use technical vocabulary, relevant examples, and accurate explanations.

Output:
Respond with a well-organized, conversational, and clearly structured explanation. Focus on clarity, relevance, and student comprehension.
"""

class ExplanationsInput(BaseModel):
    user_id: int
    input_type: str
    concept: str
    grade_level: str
    message_id: Optional[str] = None

    @classmethod
    def as_form(
        cls,
        user_id: int = Form(...),
        input_type: str = Form(...),
        concept: str = Form(""),
        grade_level: str = Form(...),
        message_id: Optional[str] = Form(default=None)
    ):
        return cls(
            user_id=user_id,
            input_type=input_type,
            concept=concept,
            grade_level=grade_level,
            message_id=message_id
        )

explainations_prompt_template = ChatPromptTemplate.from_template(exp_prompt_template)

def load_pdf_content(pdf_path: str) -> str:
    if not os.path.exists(pdf_path):
        raise FileNotFoundError("PDF file not found.")
    loader = PyPDFLoader(pdf_path)
    documents = loader.load()
    return "\n".join(doc.page_content for doc in documents)

def clean_output(text: str) -> str:
    return text.strip()

async def generate_output(
    input_type: str,
    grade_level: str,
    concept: str = "",
    pdf_file: UploadFile = None,
):
    if input_type == "pdf":
        # Save PDF temporarily
        with tempfile.NamedTemporaryFile(delete=False, suffix=".pdf") as tmp:
            content = await pdf_file.read()
            tmp.write(content)
            tmp_path = tmp.name

        concept = load_pdf_content(tmp_path)
        os.unlink(tmp_path)  # Delete file after use
    else:
        if not concept.strip():
            raise ValueError("Text input is required")
    # Compose input dict for prompt
    prompt_input = {
        "concept": concept,
        "grade_level": grade_level
    }
    
    chain = explainations_prompt_template | model
    result = chain.invoke(prompt_input)
    return clean_output(result)

@app.post("/explanations")
async def explanations_api(
    form_data: ExplanationsInput = Depends(ExplanationsInput.as_form),
    pdf_file: UploadFile = File(None)
):
    
    try:
        if form_data.input_type == "pdf" and not pdf_file:
            raise HTTPException(status_code=400, detail="PDF file required for PDF input_type")

        output = await generate_output(
            input_type=form_data.input_type,
            concept=form_data.concept,
            grade_level=form_data.grade_level,
            pdf_file=pdf_file,
        )

        scope_vars = {
            "grade_level": form_data.grade_level 
        }

        filled_prompt = exp_prompt_template.format(
            concept=form_data.concept.strip(),
            grade_level=form_data.grade_level.strip()
        )

        session_id = create_session_and_parameter_inputs(
            user_id=form_data.user_id,
            agent_id=10,
            scope_vars=scope_vars,
            human_topic=form_data.concept,
            ai_output=output,
            agent_prompt=filled_prompt
        )

        return {"output": output, "message_id": session_id}
    except Exception as e:
        traceback_str = traceback.format_exc()
        print(traceback_str)
        return JSONResponse(status_code=500, content={"detail": str(e), "trace": traceback_str})

# Define grade-specific profiles
GRADE_PROFILES = {
    "kindergarten": "Keep the language extremely simple and use playful, imaginative words that a 5-year-old can understand. Focus on feelings, colors, and basic choices.",
    "elementary": "Use friendly and clear language suitable for children aged 6 to 10. Encourage curiosity and basic reasoning, like 'why' and 'how' questions.",
    "junior high": "Write with a bit more depth. Use relatable and engaging language for students aged 11 to 14. Encourage connections to real life or personal experience.",
    "senior high": "Use thoughtful and mature language suitable for students aged 15 to 18. Encourage analytical thinking, ethical reasoning, and creative problem-solving.",
    "college": "Use academic, mature, and precise language. Encourage synthesis, debate, and deep exploration of ideas."
}

five_prompt_template = """
You are a helpful AI that creates thoughtful, open-ended questions to promote critical thinking.

Your task is to generate **exactly five** unique, deep, and age-appropriate questions based on the topic: "{topic}".

Audience: Students at the grade level: "{grade_level}"


Guidelines:
- Questions should encourage reflection, analysis, or creative thinking.
- Use plain, student-friendly language tailored to the specified grade level and profile.
- Each question must be open-ended (not answerable with just "yes" or "no").
- Avoid factual quiz-style or multiple-choice questions.

Output Rules:
- Return only the five questions, numbered exactly as shown below.
- No bullet points, titles, summaries, explanations, or greetings.
- Do not include example answers.

Format:
1. 
2. 
3. 
4. 
5. 
"""

five_prompt = ChatPromptTemplate.from_template(five_prompt_template)

class FiveQuestionInput(BaseModel):
    grade_level: str
    topic: str
    user_id: int
    message_id: Optional[int] = None
    # agent_id: int = 16  # Default agent_id for step tutor

    @classmethod
    def as_form(
        cls,
        grade_level: str = Form(...),
        topic: str = Form(...),
        user_id: int = Form(...),
        message_id: Optional[int] = Form(None)
    ):
        return cls(
            grade_level=grade_level,
            topic=topic,
            user_id=user_id,
            message_id=message_id
        )
async def five_question_agent(grade_level: str, topic: str) -> str:
    chain = five_prompt | model
    result = chain.invoke({
        "grade_level": grade_level,
        "topic": topic,
    })
    return clean_output(result)

def clean_output(text: str) -> str:
    # text = re.sub(r"\*\*(.*?)\*\*", r"\1", text)
    # text = re.sub(r"\*(.*?)\*", r"\1", text)
    # text = re.sub(r"^\s*[\*\-]\s*", "", text, flags=re.MULTILINE)
    return text.strip()

@app.post("/five_questions")
async def five_questions_endpoint(data: FiveQuestionInput = Depends(FiveQuestionInput.as_form)):
    if not data.grade_level or not data.topic:
        raise HTTPException(status_code=400, detail="Grade level and topic are required.")

    try:
        explanation = await five_question_agent(data.grade_level, data.topic)
        filled_prompt = five_prompt_template.format(grade_level=data.grade_level, topic=data.topic)

        scope_vars = {
            "grade_level": data.grade_level
        }
        
        session_id = create_session_and_parameter_inputs(
            user_id=data.user_id,
            agent_id=11,  # Default agent_id for step tutor
            scope_vars=scope_vars,
            human_topic=data.topic,
            ai_output=explanation,
            agent_prompt=filled_prompt
        )
        return {"explanation": explanation, "message_id":  session_id}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

infor_prompt_template = """
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

infor_pdf_topic_template = """
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



class InfoInformationalInput(BaseModel):
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
    

info_prompt_template = ChatPromptTemplate.from_template(infor_prompt_template)
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
    else:
        if not topic.strip():
            raise ValueError("Text input is required")
        
    # Compose input dict for prompt
    prompt_input = {
    "topic": topic,
    "grade_level": grade_level,
    "text_length": text_length,
    "text_type": text_type
    }
    
    chain = info_prompt_template | model
    result = chain.invoke(prompt_input)
    return clean_output(result)

@app.post("/informational")
async def informational_api(
    form_data: InfoInformationalInput = Depends(InfoInformationalInput.as_form),
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

        filled_prompt = infor_prompt_template.format(
            topic=form_data.topic.strip(), 
            grade_level=form_data.grade_level.strip(), 
            text_length=form_data.text_length.strip(),
            text_type=form_data.text_type.strip(),
        )

        session_id = create_session_and_parameter_inputs(
            user_id=form_data.user_id,
            agent_id=6,
            scope_vars=scope_vars,
            human_topic=form_data.topic,
            ai_output=output,
            agent_prompt=filled_prompt
        )

        return {"output": output, "message_id": session_id}
    except Exception as e:
        traceback_str = traceback.format_exc()
        print(traceback_str)
        return JSONResponse(status_code=500, content={"detail": str(e), "trace": traceback_str})

makeitrel_prompt_template = """
You are an educational assistant, skilled at helping students connect academic concepts to their real-world interests. Your goal is to create meaningful, tailored content.

Your task is to connect what you're learning to your interests and the world.

**Parameters:**
- Grade Level: {grade_level}
- What They're Learning: {learning_topic}
- Student Interests: {interests}

**Output Structure & Content**
- Begin with a title that links the learning topic and the student's interest.
- Provide 2-3 clear numbered explanations.
    - Add a line of space between each numbered item to improve readability.
- Each numbered item must:
  • Introduce a real-world example tied to the student's interest.
  • Explain the academic concpet involved simply and clearly.
  • Explicitly connect it back to the learning topic.
- Use simple, direct language appropriate for the specified grade level.
- Ensure proper grammar and punctuation.
- End with a closing sentence that summarizes the connection and encourages further exploration.
    - Do not include "Closing Sentence"

**Tone and Style Guidelines (by Grade Level):**

*Grades 1-2*
- Use playful examples from daily life (e.g., toys, food, pets).
- Keep language very simple and concrete.
- Keep each explanation to 1-2 short sentences.

*Grades 3-4*
- Use relatable hobbies (e.g., games, sports, crafts).
- Give simple cause-effect explanations.
- Use a friendly tone, while beginning to introduce academic terms.
- Keep each explanation to 1-2 short sentences.

*Grades 5-6*
- Show how the concept works in real-life activities.
- Use more detail and structured thinking.
- Maintain a curious and engaging tone.
- Keep each explanation to 1-3 short sentences.

*Grades 7-8*
- Make examples relevant to identity, creativity, or media.
- Use specific scenarios and deeper explanations.
- Encourage exploration of how the topic matters in real life.
- Keep each explanation to 1-3 short sentences.

*Grades 9-12*
- Connect concepts to real-world challenges, goals, or hobbies.
- Use thoughtful language and clear logic.
- Highlight how the concept applies practically.
- Keep each explanation to 1-4 short sentences.

*College/University*
- Use analytical, concise language.
- Tie academic content to industry, research, or real-world systems.
- Emphasize how understanding the concept enhances skill, insight, or innovation.
- Keep each explanation to 1-4 short sentences.

**Your output must be informative, engaging, and specifically tailored—never generic.**
"""


# --- Pydantic Form Input ---
class MakeRelevantFormInput(BaseModel):
    user_id: int
    grade_level: str
    learning_topic: str
    interests: str

    @classmethod
    def as_form(
        cls,
        user_id: int = Form(...),
        grade_level: str = Form(...),
        learning_topic: str = Form(...),
        interests: str = Form(...)
    ):
        return cls(
            user_id=user_id,
            grade_level=grade_level,
            learning_topic=learning_topic,
            interests=interests
        )


marprompt_template = ChatPromptTemplate.from_template(makeitrel_prompt_template)

# --- Output Cleaner ---
def clean_output(text: str) -> str:
    return text.strip()

# --- Core Logic ---
async def generate_relevant_connection(
    grade_level: str, 
    learning_topic: str = "", 
    interests: str = "",
):
    prompt_input = {
        "grade_level": grade_level,
        "learning_topic": learning_topic,
        "interests": interests
    }
    chain = marprompt_template | model
    result = chain.invoke(prompt_input)
    return clean_output(result)

# --- API Endpoint ---
@app.post("/makeitrelevant")
async def make_relevant_api(form_data: MakeRelevantFormInput = Depends(MakeRelevantFormInput.as_form)):
    try:
        output = await generate_relevant_connection(
            grade_level=form_data.grade_level,
            learning_topic=form_data.learning_topic,
            interests=form_data.interests
        )
        
        scope_vars = {}

        filled_prompt = makeitrel_prompt_template.format(
            learning_topic=form_data.learning_topic.strip(), 
            grade_level=form_data.grade_level.strip(), 
            interests=form_data.interests.strip()
        )

        session_id = create_session_and_parameter_inputs(
            user_id=form_data.user_id,
            agent_id=23,
            scope_vars=scope_vars,
            human_topic=form_data.learning_topic,
            ai_output=output,
            agent_prompt=filled_prompt
        )

        return {"output": output, 'message_id': session_id}
    except Exception as e:
        traceback_str = traceback.format_exc()
        print(traceback_str)
        return JSONResponse(status_code=500, content={"detail": str(e), "trace": traceback_str})

# --- Prompt Template ---
math_prompt_template = """
You are an expert math educator and problem designer. Your goal is to create engaging, clear, and precisely leveled math review problems.

Your output must consist *only* of a numbered list of word problems, with no introductory text, titles, explanations, or solutions.

Parameters:
- Grade Level: {grade_level}
- Number of Problems: {number_of_problems}
- Math Content: {math_content}
- Additional Criteria: {additional_criteria}

General Instructions:
- DO NOT include any labels, titles, headers, solutions, hints, or explanations.
- Incorporate all provided Additional Criteria into the design of the problems.
- Vary problem types, numerical values, and contexts to build conceptual flexibility.
- When using objects or physical units (e.g., tiles, planks), clearly state size, quantity, and coverage.
- Use consistent and grade-appropriate units (metric or imperial) based on context.
- Include conversions or approximations (e.g., 12 inches = 1 foot, π ≈ 3.14) where necessary.
- Use superscript characters like ² or ³ for powers (e.g., area in cm², volume in m³).
- Use multi-step reasoning only when developmentally appropriate.
- Keep language clear, concise, and suitable for the grade level.
- Do not include any labels, titles, headers, solutions, hints, or explanations—only the numbered problems.

Grade-Level Guidelines:
(Adjust language, complexity, and structure based on the student's grade level.)

- Kindergarten:
    - Focus on counting, comparison, and shape recognition.
    - Use playful tone, basic vocabulary, and single-step visual problems.
- Grade 1:
    - Use addition and subtraction within 20.
    - Situate problems in familiar contexts (toys, snacks, pets).
    - Keep language simple and problems one-step.
- Grade 2:
    - Include addition/subtraction within 100, early multiplication, and equal sharing.
    - Use relatable, everyday contexts.
- Grade 3:
    - Use multiplication and division within 100, area, perimeter, and simple fractions.
    - Apply to real-world settings (classroom, store, garden).
- Grade 4:
    - Introduce multi-step problems, long division, and basic fraction concepts.
    - Avoid decimals unless specified. Emphasize reasoning and clarity.
- Grade 5:
    - Include operations with fractions and decimals, volume, and multi-step reasoning.
    - Use contexts like recipes, sports, or time management.
- Grade 6:
    - Incorporate ratios, decimals, statistics, and introductory algebra.
    - Emphasize practical reasoning in everyday contexts.
- Grade 7:
    - Use percent, proportions, integers, and 2D geometry.
    - Apply math to real-world tasks (budgets, maps, graphs).
- Grade 8:
    - Focus on linear equations, slope, functions, and the Pythagorean Theorem.
    - Encourage multi-step, real-life, and abstract reasoning.
- High School:
    - Include advanced topics (algebra, functions, geometry proofs, probability, statistics).
    - Apply to both abstract problems and real-world scenarios.
- College/University:
    - Include higher-level math (calculus, statistics, linear algebra, discrete math).
    - Use technical vocabulary and academic or industry-based applications.

Math Content Adaptation:

- Arithmetic (Addition, Subtraction, Multiplication, Division):
    - Frame problems around combining, separating, scaling, or sharing.
    - Specify if remainders are expected or if answers should be whole numbers/decimals.
- Fractions/Decimals/Percentages:
    - Use relatable quantities (food, money, measurements).
    - Clearly indicate if answers should be simplified, converted, or rounded.
    - For percentages, specify if it's a part of a whole, increase/decrease, or finding the original amount.
- Algebra/Equations/Expressions:
    - Introduce clear unknowns or variables in context.
    - For expressions, ask for simplification or evaluation.
    - For equations, provide a scenario that naturally leads to forming an equation to solve for an unknown.
    - For systems, ensure two distinct unknowns requiring multiple equations.
- Geometry/Measurement (Area, Perimeter, Volume, Angles):
    - Clearly define the shape(s) and relevant dimensions.
    - Specify units of measure and the unit expected for the answer.
    - Provide any necessary constants (e.g., $\pi \approx 3.14$ or use $\pi$).
    - For composite shapes, explicitly state how they are combined.
- Data/Statistics/Probability:
    - Provide raw data, a scenario for data collection, or a visual representation (describe it if you can't draw it).
    - Clearly define the statistical measure requested (mean, median, mode, range, probability, etc.).
    - For probability, clearly define the sample space and events.
- Powers and Exponents:
    - Integrate naturally into problems involving growth, repeated multiplication (e.g., volume of cubes, exponential growth/decay, scientific notation, binary systems).
    - Ensure the context makes the use of exponents logical.

"""

# --- Pydantic Model ---
class MathReviewFormInput(BaseModel):
    user_id: int
    grade_level: str
    number_of_problems: int
    math_content: str
    additional_criteria: Optional[str] = ""

    @classmethod
    def as_form(
        cls,
        user_id: int = Form(...),
        grade_level: str = Form(...),
        number_of_problems: int = Form(...),
        math_content: str = Form(...),
        additional_criteria: Optional[str] = Form(default="")
    ):
        return cls(
            user_id=user_id,
            grade_level=grade_level,
            number_of_problems=number_of_problems,
            math_content=math_content,
            additional_criteria=additional_criteria
        )


math_rev_prompt_template = ChatPromptTemplate.from_template(math_prompt_template)

# --- Output Cleaner ---
def clean_output(text: str) -> str:
    return text.strip()

# --- Core Function ---
async def generate_math_review(
    grade_level: str,
    number_of_problems: int,
    math_content: str,
    additional_criteria: str = "",
):
    prompt_input = {
        "grade_level": grade_level,
        "number_of_problems": number_of_problems,
        "math_content": math_content,
        "additional_criteria": additional_criteria
    }
    chain = math_rev_prompt_template | model
    result = chain.invoke(prompt_input)
    return clean_output(result)

# --- API Endpoint ---
@app.post("/mathreview")
async def math_review_api(form_data: MathReviewFormInput = Depends(MathReviewFormInput.as_form)):
    try:
        output = await generate_math_review(
            grade_level=form_data.grade_level,
            number_of_problems=form_data.number_of_problems,
            math_content=form_data.math_content,
            additional_criteria=form_data.additional_criteria
        )
        scope_vars = {
            "grade_level": form_data.grade_level.strip(),
            "number_of_problems": str(form_data.number_of_problems).strip(),
            "math_content": form_data.math_content.strip(),
            "additional_criteria": form_data.additional_criteria.strip()
        }

        filled_prompt = math_prompt_template.format(
            math_content=form_data.math_content.strip(), 
            grade_level=form_data.grade_level.strip(), 
            number_of_problems=str(form_data.number_of_problems),  # ✅ added this
            additional_criteria=form_data.additional_criteria.strip()
        )

        session_id = create_session_and_parameter_inputs(
            user_id=form_data.user_id,
            agent_id=22,
            scope_vars=scope_vars,
            human_topic=form_data.math_content,
            ai_output=output,
            agent_prompt=filled_prompt
        )
        return {"output": output, "message_id": session_id}
    except Exception as e:
        traceback_str = traceback.format_exc()
        print(traceback_str)
        return JSONResponse(status_code=500, content={"detail": str(e), "trace": traceback_str})


# Style Profiles
PROFILES = {
    "academic": {
        "description": "Formal tone, avoid contractions, focus on technical accuracy.",
        "instructions": "Use a formal, academic tone. Avoid contractions and colloquialisms."
    },
    "casual": {
        "description": "Friendly tone, allow contractions, simpler phrasing.",
        "instructions": "Use a casual, conversational tone. Feel free to use contractions and natural speech."
    },
    "concise": {
        "description": "Very brief, to-the-point corrections.",
        "instructions": "Keep the corrected text as concise as possible, trimming unnecessary words."
    }
}
proof_prompt_template = """
You are a professional proofreader. Proofread the following text and:
1. Correct grammar errors
2. Fix spelling mistakes
3. Adjust punctuation
4. Improve clarity while preserving the meaning
5. Return the corrected version and a list of changes

Tone guide:
{profile}

Text:
{text}

Respond in this format:

Corrected text:
[Your corrected version]



Changes made:
[List of major changes]


"""

proofreader_prompt = ChatPromptTemplate.from_template(proof_prompt_template)


class ProofreadInput(BaseModel):
    user_id: int
    profile: str
    text: str
    message_id: Optional[int] = None

    @classmethod
    def as_form(
        cls,
        user_id: int = Form(...),
        profile: str = Form(...),
        text: str = Form(...),
        message_id: Optional[int] = Form(None)
    ):
        return cls(
            user_id=user_id,
            profile=profile,
            text=text,
            message_id=message_id
        )
async def proofread_agent(profile: str, text: str) -> str:
    # if profile not in PROFILES:
    #     raise ValueError(f"Unknown profile '{profile}'. Must be one of: {', '.join(PROFILES.keys())}")
    chain = proofreader_prompt | model
    result = chain.invoke({
        # "instructions": PROFILES[profile]["instructions"],
        "profile": profile,
        "text": text
    })

    return clean_output(result)  # Make sure clean_output exists and works

def clean_output(text: str) -> str:
    # text = re.sub(r"\*\*(.*?)\*\*", r"\1", text)  # Remove bold formatting
    # text = re.sub(r"\*(.*?)\*", r"\1", text)  # Remove italic formatting
    # text = re.sub(r"^\s*[\*\-]\s*", "", text, flags=re.MULTILINE)  # Remove bullet points
    return text.strip()

@app.post("/proofread")
async def proofread_endpoint(data: ProofreadInput = Depends(ProofreadInput.as_form)):
    if not data.profile or not data.text:
        raise HTTPException(status_code=400, detail="Profile and text are required.")
    try:
        explanation = await proofread_agent(data.profile, data.text)
        filled_prompt = proof_prompt_template.format(profile=data.profile, text=data.text)

        scope_vars = {
            "profile": data.profile,
            "text": data.text
        }

        session_id = create_session_and_parameter_inputs(
            user_id=data.user_id,
            agent_id=12,  # Default agent_id for proofreader
            scope_vars=scope_vars,
            human_topic=data.text,
            ai_output=explanation,
            agent_prompt=filled_prompt
        )
        return {"corrected": explanation, "message_id": session_id}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))





# === Token-based chunking ===
def chunk_text_token_based(text: str, max_tokens: int = 512) -> list:
    tokens = TOKENIZER.encode(text, add_special_tokens=False)
    chunks = []
    for i in range(0, len(tokens), max_tokens):
        chunk = tokens[i:i + max_tokens]
        chunk_text = TOKENIZER.decode(chunk, skip_special_tokens=True)
        chunks.append(chunk_text.strip())
    return chunks

# === Step 2: Rewrite user query using LLM (Ollama) ===
def get_standalone_question(history: list, user_prompt: str, first: bool) -> str:
    
    if first:
        return user_prompt

    conversation = ""
    for turn in history[-5:]:  # Limit to last 5 messages
        role = "User" if turn["role"] == "user" else "AI"
        conversation += f"{role}: {turn['message']}\n"

    prompt = f"""You are a helpful assistant. Your task is to rewrite the user's latest message into a standalone question or query that is context-independent and complete on its own.

This is critical for retrieval systems that use semantic similarity. Avoid using vague pronouns like "it", "this", "that" — be specific. Include relevant context from the conversation, but only what’s necessary to make the question clear and self-contained.

Conversation history:
{conversation.strip()}
User: {user_prompt.strip()}

Standalone version:"""

    payload = {
        "model": OLLAMA_MODEL,
        "prompt": prompt,
        "stream": False
    }

    try:
        response = requests.post(OLLAMA_URL, json=payload, headers=HEADERS)
        response.raise_for_status()
        return response.json().get("response", "").strip()
    except Exception as e:
        print(f"[Query Rewrite Failed] {e}")
        return user_prompt  # fallback
    
# === Upload + Embed Endpoint ===
@app.post("/upload-and-embed")
async def upload_pdf(
    book_id: int = Form(...),
    unit_id: int = Form(...),
    chapter_id: int = Form(...),
    lesson_id: int = Form(...),
    file: UploadFile = File(...)
):
    try:
        # Save PDF temporarily
        with tempfile.NamedTemporaryFile(delete=False, suffix=".pdf") as tmp:
            tmp.write(await file.read())
            pdf_path = tmp.name

        # Extract text from PDF
        doc = fitz.open(pdf_path)
        full_text = "".join([page.get_text() for page in doc])

        if not full_text.strip():
            return JSONResponse(status_code=400, content={"error": "PDF contains no extractable text."})

        chunks = chunk_text_token_based(full_text, max_tokens=100)
        embeddings = EMBED_MODEL.encode(chunks)

        # === FAISS index logic ===
        index_path = f"{book_id}_chapter_{chapter_id}.faiss"
        if os.path.exists(index_path):
            index = faiss.read_index(index_path)
        else:
            index = faiss.IndexFlatL2(EMBED_MODEL.get_sentence_embedding_dimension())

        vector_np = np.array(embeddings).astype("float32")
        start_id = index.ntotal
        index.add(vector_np)
        faiss.write_index(index, index_path)

        # === Insert chunks into DB in batches ===
        conn = mysql.connector.connect(**DB_CONFIG)
        conn.autocommit = False  # Manual commit mode

        try:
            cursor = conn.cursor()
            batch_size = 20
            for i, chunk in enumerate(chunks):
                faiss_id = start_id + i
                cursor.execute("""
                    INSERT INTO chunks (book_id, chapter_id, unit_id, lesson_id, global_faiss_id, text)
                    VALUES (%s, %s, %s, %s, %s, %s)
                """, (book_id, chapter_id, unit_id, lesson_id, faiss_id, chunk))

                if (i + 1) % batch_size == 0:
                    conn.commit()

            conn.commit()


        except Exception as db_err:
            conn.rollback()
            raise db_err
        finally:
            cursor.close()
            conn.close()

        return {
            "status": "success",
            "chunks_added": len(chunks),
            "index_path": index_path
        }

    except Exception as e:
        return JSONResponse(status_code=500, content={"error": str(e)})


# === Chat History DB Functions ===
def save_chat_to_db(session_id: int, role: str, message: str):
    conn = mysql.connector.connect(**DB_CONFIG)
    try:
        cursor = conn.cursor()

        # Step 1: Get latest turn
        cursor.execute("""
            SELECT IFNULL(MAX(turn), 0) + 1 AS next_turn
            FROM chat_rag_history
            WHERE session_id = %s
        """, (session_id,))
        next_turn = cursor.fetchone()[0]

        # Step 2: Insert new message with next turn
        cursor.execute("""
            INSERT INTO chat_rag_history (session_id, turn, role, message)
            VALUES (%s, %s, %s, %s)
        """, (session_id, next_turn, role, message))

        conn.commit()
    finally:
        cursor.close()
        conn.close()


def get_recent_chat_context(session_id: str, limit: int = 10):
    conn = mysql.connector.connect(**DB_CONFIG)
    try:
        cursor = conn.cursor(dictionary=True)
        cursor.execute("""
            SELECT role, message FROM chat_rag_history
            WHERE session_id = %s
            ORDER BY turn DESC
            LIMIT %s
        """, (session_id, limit))
        messages = cursor.fetchall()
        return list(reversed(messages))
    finally:
        cursor.close()
        conn.close()



# === Chat Endpoint ===
class ChatInput(BaseModel):
    session_id: int
    prompt: str
    book_id: int
    chapter_id: int
    unit_id: int
    lesson_id: int

@app.post("/ragchat")
def chat(input: ChatInput):
    try:
        session_id = input.session_id
        user_prompt = input.prompt.strip()
        book_id = input.book_id
        chapter_id = input.chapter_id
        unit_id = input.unit_id
        lesson_id = input.lesson_id

        # Save user message to DB first
        save_chat_to_db(session_id, "user", user_prompt)

        # === Step 1: Retrieve chat history ===
        history = get_recent_chat_context(session_id)


        # Step 2: Determine if it's the first message (i.e., only 1 in history = user message just saved)
        is_first_message = len(history) <= 1

        print("Is it first message?", is_first_message)

        # Step 3: Rewrite prompt conditionally
        rewritten_prompt = get_standalone_question(history, user_prompt, is_first_message)

        print(f"[Rewritten Prompt] {rewritten_prompt}")

        # === Step 3: Retrieve FAISS context using rewritten prompt ===
        current_script_dir = os.path.dirname(os.path.abspath(__file__))
        index_dir = os.path.join(current_script_dir, "RAG api")
        index_path = os.path.join(index_dir, f"{book_id}_chapter_{chapter_id}.faiss")

        if not os.path.exists(index_path):
            return JSONResponse(status_code=404, content={"error": "FAISS index not found for this chapter."})

        index = faiss.read_index(index_path)
        embedding = EMBED_MODEL.encode([rewritten_prompt]).astype("float32")
        D, I = index.search(embedding, k=10)
        raw_matches = [(int(idx), float(dist)) for idx, dist in zip(I[0], D[0]) if idx != -1]

        if not raw_matches:
            rag_context = "No relevant content found for this chapter."
        else:
            # Fetch matched chunks
            conn = mysql.connector.connect(**DB_CONFIG)
            try:
                cursor = conn.cursor(dictionary=True)
                ids = [idx for idx, _ in raw_matches]
                placeholder = ','.join(['%s'] * len(ids))
                cursor.execute(f"""
                    SELECT global_faiss_id, text FROM chunks
                    WHERE book_id = %s AND chapter_id = %s AND unit_id = %s AND lesson_id = %s
                    AND global_faiss_id IN ({placeholder})
                """, (book_id, chapter_id, unit_id, lesson_id, *ids))
                chunks = cursor.fetchall()
            finally:
                cursor.close()
                conn.close()

            # Hybrid reranking: keyword overlap
            def keyword_score(chunk_text):
                chunk_tokens = set(chunk_text.lower().split())
                prompt_tokens = set(rewritten_prompt.lower().split())
                return len(chunk_tokens & prompt_tokens)

            reranked_chunks = sorted(
                chunks,
                key=lambda c: keyword_score(c["text"]),
                reverse=True
            )

            top_k = min(10, len(reranked_chunks))
            rag_context = "\n".join([c["text"] for c in reranked_chunks[:top_k]])

        # === Step 4: Prepare chat history for final prompt ===
        chat_context = ""
        for turn in history:
            role_label = "User" if turn['role'] == 'user' else "AI"
            chat_context += f"{role_label}: {turn['message']}\n"

        # === Step 5: Final prompt construction ===
        final_prompt = (
        "You are a helpful educational assistant. You are only allowed to answer questions "
        "based on the context provided from the current chapter. If the user's question is not answerable "
        "using the context below, you must respond by saying that the question is outside the scope of this chapter.\n\n"
        "Only answer based on the context. Do not guess or add outside information.\n\n"

        "Context:\n"
        f"{rag_context}\n\n"
        "Conversation so far:\n"
        f"{chat_context}"
        "AI:"
    )


        # === Step 6: Send to Ollama ===
        payload = {
            "model": OLLAMA_MODEL,
            "prompt": final_prompt,
            "stream": False
        }

        response = requests.post(OLLAMA_URL, json=payload, headers=HEADERS)
        response.raise_for_status()
        ai_reply = response.json().get("response", "").strip()

        # Save AI response to DB
        save_chat_to_db(session_id, "ai", ai_reply)

        return {"response": ai_reply}

    except Exception as e:
        return {"error": str(e)}
    
class QuizInput(BaseModel):
    book_id: int
    chapter_number: int
    unit_id: int
    quiz_type: str
    number_of_questions: int
    difficulty_level: str
    grade_level: str
    answer_key: bool

# === Make Quiz Endpoint ===
@app.post("/make-quiz")
def make_quiz(input: QuizInput):
    try:
        index = validate_faiss_index(input.book_id, input.chapter_number)
        chunks = fetch_chunks(input.book_id, input.chapter_number, input.unit_id)

        if not chunks:
            return JSONResponse(status_code=404, content={"error": "No content found."})

        context = "\n".join([c["text"] for c in chunks])[:8000]
        raw_questions = generate_questions_with_ollama(context, input)
        questions = re.findall(r"\d+\.\s+(.*)", raw_questions, re.DOTALL)

        if not questions:
            return JSONResponse(status_code=500, content={"error": "Failed to parse questions."})

        answers = search_faiss_for_answers(index, questions, input)

        if not input.answer_key:
            answers = [{"question": qa["question"]} for qa in answers]

        # ✅ Format output as Markdown
        formatted = "\n\n".join([
            f"**Q{i+1}.** {qa['question'].strip()}\n\n**Answer:** {qa.get('answer', 'N/A').strip()}"
            for i, qa in enumerate(answers)
        ])

        # ✅ Save raw JSON for record, send Markdown to frontend
        markdown_quiz = format_quiz_to_markdown(answers)
        save_generated_quiz_to_db(input.book_id, input.chapter_number, markdown_quiz)

        return {"quiz": markdown_quiz}
    except Exception as e:
        import traceback
        return JSONResponse(
            status_code=500,
            content={"error": str(e), "details": traceback.format_exc()}
        )
class QuizExist(BaseModel):
    book_id: int
    chapter_number: int

@app.post("/quiz-check")
def quiz_check(input: QuizExist):
    try:
        with closing(mysql.connector.connect(**DB_CONFIG)) as conn:
            with conn.cursor(dictionary=True, buffered=True) as cursor:
                cursor.execute("""
                    SELECT * FROM generated_quiz
                    WHERE book_id = %s AND chapter_id = %s
                """, (input.book_id, input.chapter_number))
                quiz = cursor.fetchone()

                if quiz:
                    return JSONResponse(content={
                        "quiz": {
                            "message": quiz["message"],
                        }
                    })
                else:
                    return JSONResponse(content={
                        "status": "success",
                        "exists": False,
                        "quiz": None
                    })
    except mysql.connector.Error as err:
        raise HTTPException(status_code=500, detail=f"MySQL Error: {err}")
    
@app.post("/delete-quiz")
def quiz_delete(input: QuizExist):
    try:
        with closing(mysql.connector.connect(**DB_CONFIG)) as conn:
            with conn.cursor(buffered=True) as cursor:
                cursor.execute("""
                    DELETE FROM generated_quiz
                    WHERE book_id = %s AND chapter_id = %s
                """, (input.book_id, input.chapter_number))
                conn.commit()

                if cursor.rowcount == 0:
                    return JSONResponse(content={
                        "status": "fail",
                        "message": "No quiz found to delete for the given book_id and chapter_number."
                    }, status_code=404)

                return JSONResponse(content={
                    "status": "success",
                    "message": "Quiz deleted successfully."
                })

    except mysql.connector.Error as err:
        raise HTTPException(status_code=500, detail=f"MySQL Error: {err}")

def validate_faiss_index(book_id, chapter_number):
    index_path = f"{book_id}_chapter_{chapter_number}.faiss"
    if not os.path.exists(index_path):
        raise FileNotFoundError("Missing FAISS index.")
    return faiss.read_index(index_path)

def fetch_chunks(book_id, chapter, unit):
    with closing(mysql.connector.connect(**DB_CONFIG)) as conn:
        with conn.cursor(dictionary=True) as cursor:
            cursor.execute("""
                SELECT text FROM chunks
                WHERE book_id = %s AND chapter_id = %s AND unit_id = %s
            """, (book_id, chapter, unit))
            return cursor.fetchall()

def generate_questions_with_ollama(context, input):
    prompt = f"""
You are a strict quiz generator.

🎯 TASK:
Based ONLY on the context provided below, generate exactly **{input.number_of_questions} {input.quiz_type.lower()}** questions.
- Grade Level: {input.grade_level}
- Difficulty: {input.difficulty_level.capitalize()}

📌 RULES:
- All questions must strictly derive from the given context.
- Each question must be numbered starting from 1.
- Each question must be followed by exactly **4 options**, labeled **A) to D)**.
- Each option must be on its own line.
- No explanations, hints, or reasoning after any question.
- Separate each full question block (Q + 4 options) with a **single blank line**.
- Do NOT repeat or paraphrase the context.
- Do NOT include the correct answers directly after the questions.

📌 FINAL SECTION:
After all questions, include a section labeled exactly:
**Answer Key:**  
- List the answers on separate lines, in the format:  
  `1. A`  
  `2. C`  
  `3. D`  
  (Use only the number and the correct option letter. No extra text.)

✅ OUTPUT FORMAT EXAMPLE:

1. What is the capital of France?  
A) Berlin  
B) Madrid  
C) Paris  
D) Rome  

2. What color do you get by mixing red and blue?  
A) Yellow  
B) Purple  
C) Green  
D) Orange  

Answer Key:  
1. C  
2. B  

❌ DO NOT:
- Do not include explanations.
- Do not generate content outside the context.
- Do not change formatting or answer key structure.

--- BEGIN CONTEXT ---
{context}
--- END CONTEXT ---
"""
    return send_ollama_prompt(prompt)

def search_faiss_for_answers(index, questions, input):
    answers = []
    for q in questions:
        embedding = EMBED_MODEL.encode([q]).astype("float32")
        D, I = index.search(embedding, k=1)
        top_id = int(I[0][0])

        if top_id == -1:
            answers.append({"question": q, "answer": "No relevant content found."})
            continue

        chunk_text = fetch_chunk_by_faiss_id(input, top_id)
        answer_prompt = f"""
Given the following question and context from a PDF, provide a concise answer (1-2 sentences).
Question: {q}
Context: {chunk_text}
"""
        answer = send_ollama_prompt(answer_prompt)
        answers.append({"question": q, "answer": answer.strip()})
    return answers

def fetch_chunk_by_faiss_id(input, faiss_id):
    with closing(mysql.connector.connect(**DB_CONFIG)) as conn:
        with conn.cursor(dictionary=True) as cursor:
            cursor.execute("""
                SELECT text FROM chunks
                WHERE book_id = %s AND chapter_id = %s AND unit_id = %s AND global_faiss_id = %s
            """, (input.book_id, input.chapter_number, input.unit_id, faiss_id))
            result = cursor.fetchone()
            return result["text"] if result else "Answer not found."

def send_ollama_prompt(prompt):
    payload = {
        "model": OLLAMA_MODEL,
        "prompt": prompt,
        "stream": False
    }
    response = requests.post(OLLAMA_URL, json=payload, headers=HEADERS)
    response.raise_for_status()
    return response.json().get("response", "")

def save_generated_quiz_to_db(book_id, chapter_id, message):
    with closing(mysql.connector.connect(**DB_CONFIG)) as conn:
        with conn.cursor() as cursor:
            cursor.execute("""
                INSERT INTO generated_quiz (book_id, chapter_id, message, created_at, updated_at)
                VALUES (%s, %s, %s, NOW(), NOW())
            """, (book_id, chapter_id, message))
        conn.commit()

def format_quiz_to_markdown(qa_list):
    lines = [f"{i+1}. {q['question'].strip()}" for i, q in enumerate(qa_list)]
    lines.append("\nAnswer Key:")
    lines.extend(f"{i+1}. {q['answer'].strip()}" for i, q in enumerate(qa_list) if q.get("answer", "").strip())
    return "\n".join(lines)