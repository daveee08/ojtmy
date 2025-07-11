import os, sys, re
from typing import Optional
from fastapi import Form, Depends, FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
from langchain_ollama import OllamaLLM
from langchain_core.prompts import ChatPromptTemplate

# Add project root for relative imports
project_root = os.path.abspath(os.path.join(os.path.dirname(__file__), '..', '..'))
sys.path.insert(0, project_root)

from python.db_utils_final import create_session_and_parameter_inputs
from python.chat_router_final import chat_router

# Initialize FastAPI app
app = FastAPI(debug=True)
app.include_router(chat_router)

# Enable CORS (adjust origin as needed)
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Grade tone profiles
GRADE_PROFILES = {
    "kindergarten": "Use playful, simple words. Focus on routines, fun learning moments, and positive reinforcement. Keep sessions short and colorful.",
    "elementary": "Use friendly and fun language. Break tasks into small steps. Focus on creating habits, using visual aids, and celebrating effort.",
    "junior high": "Use engaging and relatable tone. Encourage organization, self-motivation, and balancing school and personal life.",
    "senior high": "Use mature, focused advice. Address time management, review techniques, and subject-specific strategies.",
    "college": "Use academic tone. Focus on deep learning, exam strategy, scheduling, and productivity systems like Pomodoro or spaced repetition."
}

# Prompt template (tone inserted dynamically)
base_prompt = """
You are a smart AI tutor.

Your task is to generate a practical, organized, and motivational study plan for a student in grade level "{grade_level}".
The student's preparation goal is:
"{goal}"

Student Profile: {tone}

Instructions:
- First, look at the input for any time-related phrases like:
  "exam in 3 days", "test next week", "quiz tomorrow", etc.
- If a specific time frame is mentioned (e.g., 3 days, 5 days, 1 week), make the plan match that time frame exactly. Each day should be planned accordingly.
- If no time constraint is mentioned, create a standard 2–3 week plan depending on the topic complexity.

Format guidelines:
- Use plain formatting (no asterisks or markdown bold) to keep it clean and readable.
- If the plan is for multiple weeks, use this format:
  Week 1: Title
    Day 1 - Topic (duration)
      Activity: ...
      Review: ...
      Tool: ...
- If it's a short plan (e.g., 3-day or 5-day), just use:
  Day 1 - Topic (duration)
    Activity: ...
    Review: ...
    Tool: ...

- Always include 1 rest day if the plan is 5 days or longer.
- End with an encouraging message, but don’t use headings like “Final Note.”
- Avoid using symbols like *** or ###. Keep it visually simple but structured.
"""

# LLM setup
model = OllamaLLM(model="gemma3:latest")

# Build prompt with grade-level tone
def build_prompt(goal: str, grade_level: str) -> str:
    tone = GRADE_PROFILES.get(grade_level.lower(), "Use a general, supportive tone suitable for all ages.")
    return base_prompt.format(grade_level=grade_level, goal=goal, tone=tone)

# Pydantic model for request input
class StudyHabitsInput(BaseModel):
    grade_level: str
    goal: str
    user_id: int
    message_id: Optional[int] = None

    @classmethod
    def as_form(
        cls,
        grade_level: str = Form(...),
        goal: str = Form(...),
        user_id: int = Form(...),
        message_id: Optional[int] = Form(None)
    ):
        return cls(
            grade_level=grade_level,
            goal=goal,
            user_id=user_id,
            message_id=message_id
        )

# Core agent logic
async def study_habits_agent(grade_level: str, goal: str) -> str:
    prompt_text = build_prompt(goal, grade_level)
    chain = ChatPromptTemplate.from_template(prompt_text) | model
    result = chain.invoke({})
    return clean_output(result)

# Clean markdown-like formatting
def clean_output(text: str) -> str:
    # text = re.sub(r"[*_`]", "", text)  # removes *, _, ` characters
    return text.strip()

# FastAPI endpoint
@app.post("/study_habits")
async def study_habits_endpoint(data: StudyHabitsInput = Depends(StudyHabitsInput.as_form)):
    if not data.grade_level or not data.goal:
        raise HTTPException(status_code=400, detail="Grade level and goal are required.")
    try:
        explanation = await study_habits_agent(data.grade_level, data.goal)

        session_id = create_session_and_parameter_inputs(
            user_id=data.user_id,
            agent_id=15,
            scope_vars={"grade_level": data.grade_level, "goal": data.goal},
            human_topic=data.goal,
            ai_output=explanation,
            agent_prompt=build_prompt(data.goal, data.grade_level)
        )
        return {"explanation": explanation, "message_id": session_id}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))
