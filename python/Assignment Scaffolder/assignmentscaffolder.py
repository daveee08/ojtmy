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

# Assignment Scaffolding Prompt Template
prompt_template = """
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

app = FastAPI(debug=True)
app.include_router(chat_router)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # or Laravel origin like "http://localhost:8000"
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

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

# LangChain setup
model = Ollama(model="gemma3:4b")
scaffolder_prompt = ChatPromptTemplate.from_template(prompt_template)

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

    chain = prompt_template | model
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

        filled_prompt = prompt_template.format(
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

# Run with: uvicorn assignmentscaffolder:app --reload
if __name__ == "__main__":
    uvicorn.run("assignmentscaffolder:app", host="127.0.0.1", port=5001, reload=True)