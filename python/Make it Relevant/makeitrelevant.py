from fastapi import FastAPI, HTTPException, Form, Depends, UploadFile, File
from fastapi.responses import JSONResponse
from pydantic import BaseModel
from langchain_community.llms import Ollama
from langchain_core.prompts import ChatPromptTemplate
from typing import Optional
import uvicorn, traceback, re

# --- Prompt Template ---
make_relevant_template = """
You are an educational assistant helping students connect academic concepts to real-world interests in a structured, meaningful way.

Your task is to connect what you're learning to your interests and the world.

**Parameters:**
- Grade Level: {grade_level}
- What They're Learning: {learning_topic}
- Student Interests: {interests}

**Output Format:**
- Begin with a creative, engaging title that links the learning topic and the student's interest.
- Provide 2-3 structured numbered explanations.
    - Add a line of space between each numbered item to improve readability.
- Each numbered item must:
  • Introduce a real-world example tied to the student's interest.
  • Explain the scientific or academic concept involved.
  • Clearly connect it back to the learning topic.
- Use simple, clear language appropriate for the specified grade level.
- Proper grammar and punctuation are essential.
- Add a Closing Sentence that summarizes the connection and encourages further exploration.

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
- Keep each explanation to 1-5 short sentences.

**Your output must be informative, engaging, and tailored—never generic.**
"""



# --- LangChain Setup ---
app = FastAPI(debug=True)
model = Ollama(model="llama3")
relevant_prompt = ChatPromptTemplate.from_template(make_relevant_template)

# --- Output Cleaner ---
def clean_output(text: str) -> str:
    text = re.sub(r"\*\*(.*?)\*\*", r"\1", text)
    text = re.sub(r"\*(.*?)\*", r"\1", text)
    text = re.sub(r"^\s*[\*\-]\s*", "", text, flags=re.MULTILINE)
    return text.strip()

# --- Pydantic Form Input ---
class MakeRelevantFormInput(BaseModel):
    grade_level: str
    learning_topic: str
    interests: str

    @classmethod
    def as_form(
        cls,
        grade_level: str = Form(...),
        learning_topic: str = Form(...),
        interests: str = Form(...)
    ):
        return cls(
            grade_level=grade_level,
            learning_topic=learning_topic,
            interests=interests
        )

# --- Core Logic ---
async def generate_relevant_connection(grade_level: str, learning_topic: str, interests: str):
    prompt_input = {
        "grade_level": grade_level,
        "learning_topic": learning_topic,
        "interests": interests
    }
    chain = relevant_prompt | model
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
        return {"output": output}
    except Exception as e:
        traceback_str = traceback.format_exc()
        print(traceback_str)
        return JSONResponse(status_code=500, content={"detail": str(e), "trace": traceback_str})

# --- Local Dev Entry ---
if __name__ == "__main__":
    uvicorn.run("makeitrelevant:app", host="127.0.0.1", port=5001, reload=True)
