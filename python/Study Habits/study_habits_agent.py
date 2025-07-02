# study_habits_agent.py
from pydantic import BaseModel
from langchain_ollama import OllamaLLM
from langchain_core.prompts import ChatPromptTemplate

class StudyPlanInput(BaseModel):
    grade_level: str
    goal: str

# Optional grade profiles for age-based tone/style
GRADE_PROFILES = {
    "kindergarten": "Use playful, simple words. Focus on routines, fun learning moments, and positive reinforcement. Keep sessions short and colorful.",
    "elementary": "Use friendly and fun language. Break tasks into small steps. Focus on creating habits, using visual aids, and celebrating effort.",
    "junior high": "Use engaging and relatable tone. Encourage organization, self-motivation, and balancing school and personal life.",
    "senior high": "Use mature, focused advice. Address time management, review techniques, and subject-specific strategies.",
    "college": "Use academic tone. Focus on deep learning, exam strategy, scheduling, and productivity systems like Pomodoro or spaced repetition."
}

def generate_study_plan(grade_level: str, goal: str) -> str:
    profile = GRADE_PROFILES.get(grade_level.lower(), "Use clear, student-friendly language appropriate for the grade level.")

    prompt = f"""
You are a smart AI tutor.

Your task is to generate a practical, organized, and motivational study plan for a student in grade level "{grade_level}".
The student's preparation goal is:
"{goal}"

Student Profile: {profile}

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

    model = OllamaLLM(model="gemma3:1b")
    chain = ChatPromptTemplate.from_template(prompt) | model
    result = chain.invoke({})
    return result.strip()
