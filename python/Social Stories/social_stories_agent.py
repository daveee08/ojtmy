from fastapi import FastAPI, Form, Depends, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from langchain_ollama import OllamaLLM as Ollama
from langchain.prompts import PromptTemplate
from typing import Optional
from pydantic import BaseModel
# from http.client import HTTPException
import sys
import os

current_script_dir = os.path.dirname(os.path.abspath(__file__))
project_root = os.path.join(current_script_dir, '..', '..')
sys.path.insert(0, project_root)

print(f"Adding to sys.path: {project_root}") 

from python.chat_router_final import chat_router
from python.db_utils_final import create_session_and_parameter_inputs, insert_message

app = FastAPI()
app.include_router(chat_router)

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


prompt = PromptTemplate.from_template(prompt_template)
llm = Ollama(model="gemma3:1b")
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