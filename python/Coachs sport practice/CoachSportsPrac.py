from fastapi import FastAPI, HTTPException, UploadFile, Form, File, Depends
from fastapi.responses import JSONResponse, StreamingResponse
from pydantic import BaseModel
from langchain_community.llms import Ollama
from langchain_core.prompts import ChatPromptTemplate
import shutil, os, tempfile, uvicorn, traceback, sys
from typing import Optional
from langchain_core.messages import HumanMessage, AIMessage
from fastapi.middleware.cors import CORSMiddleware
import re
from io import BytesIO
from reportlab.lib.pagesizes import letter
from reportlab.pdfgen import canvas
from reportlab.lib.utils import simpleSplit

current_script_dir = os.path.dirname(os.path.abspath(__file__))
project_root = os.path.join(current_script_dir, '..', '..')
sys.path.insert(0, project_root)

# try:
#     from python.chat_router_final import chat_router
#     from python.db_utils_final import create_session_and_parameter_inputs, insert_message
# except ImportError:
#     chat_router = None
#     create_session_and_parameter_inputs = None
#     insert_message = None
from python.chat_router_final import chat_router
from python.db_utils_final import create_session_and_parameter_inputs, insert_message


# --- Prompt Templates ---
coach_prompt_template = """
You are an expert sports coach AI. Your job is to generate a detailed practice plan for students.

Parameters:
- Grade Level: {grade_level}
- Length of Practice: {length_of_practice}
- Sport: {sport}
- Additional Customization: {additional_customization}

Instructions:
- Provide a detailed practice plan, including warm-up, drills, and cool-down.
- Use bullet points for each item.
- Highlight important keywords and sports terms in bold and italics using markdown.
- Start directly with the practice plan, no intro or conclusion.
- Adapt the plan based on any additional customization provided.

Respond ONLY with the practice plan text (no extra commentary).
"""

# --- FastAPI App Initialization ---
app = FastAPI(debug=True)
if chat_router:
    app.include_router(chat_router)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# --- Pydantic Model for Form Input ---
class CoachSportsPracFormInput(BaseModel):
    user_id: int
    input_type: str
    sport: str
    grade_level: str
    length_of_practice: str
    additional_customization: str = ""
    message_id: Optional[str] = None

    @classmethod
    def as_form(
        cls,
        user_id: int = Form(...),
        input_type: str = Form(...),
        sport: str = Form(...),
        grade_level: str = Form(...),
        length_of_practice: str = Form(...),
        additional_customization: str = Form(""),
        message_id: Optional[str] = Form(default=None)
    ):
        return cls(
            user_id=user_id,
            input_type=input_type,
            sport=sport,
            grade_level=grade_level,
            length_of_practice=length_of_practice,
            additional_customization=additional_customization,
            message_id=message_id
        )

# --- LangChain Setup ---
model = Ollama(model="llama3")
prompt_template = ChatPromptTemplate.from_template(coach_prompt_template)

# --- Output Cleaner ---
def clean_output(text: str) -> str:
    return text.strip()

def format_practice_plan(text: str) -> str:
    # Remove markdown bullets and pluses
    text = re.sub(r'^\s*[\*\+]\s*', '', text, flags=re.MULTILINE)
    # Replace multiple newlines with a single newline
    text = re.sub(r'\n{2,}', '\n\n', text)
    # Optionally, you can further format headings, etc.
    return text.strip()

# --- Main Output Generation Function ---
async def generate_output(
    input_type: str,
    sport: str,
    grade_level: str,
    length_of_practice: str,
    additional_customization: str = "",
):
    if not sport.strip() or not grade_level.strip() or not length_of_practice.strip():
        raise ValueError("Sport, grade level, and length of practice are required.")

    prompt_input = {
        "sport": sport,
        "grade_level": grade_level,
        "length_of_practice": length_of_practice,
        "additional_customization": additional_customization
    }

    chain = prompt_template | model
    result = chain.invoke(prompt_input)
    return clean_output(result)

@app.post("/coach_sports_prac")
async def coach_sports_prac_api(
    form_data: CoachSportsPracFormInput = Depends(CoachSportsPracFormInput.as_form)
):
    print("Received request")
    try:
        output = await generate_output(
            input_type=form_data.input_type,
            sport=form_data.sport,
            grade_level=form_data.grade_level,
            length_of_practice=form_data.length_of_practice,
            additional_customization=form_data.additional_customization,
        )

        output = format_practice_plan(output)

        scope_vars = {
            "grade_level": form_data.grade_level,
            "length_of_practice": form_data.length_of_practice,
            "sport": form_data.sport,
            "additional_customization": form_data.additional_customization,
        }

        filled_prompt = coach_prompt_template.format(
            sport=form_data.sport.strip(),
            grade_level=form_data.grade_level.strip(),
            length_of_practice=form_data.length_of_practice.strip(),
            additional_customization=form_data.additional_customization.strip()
        )

        session_id = None
        if create_session_and_parameter_inputs:
            session_id = create_session_and_parameter_inputs(
                user_id=form_data.user_id,
                agent_id=5,  # Use a unique agent_id for Coach's Sport Practice
                scope_vars=scope_vars,
                human_topic=form_data.sport,
                ai_output=output,
                agent_prompt=filled_prompt
            )

        print("Returning response")
        return {"output": output, "message_id": session_id}
    except Exception as e:
        traceback_str = traceback.format_exc()
        print(traceback_str)
        return JSONResponse(status_code=500, content={"detail": str(e), "trace": traceback_str})

@app.get("/")
def root():
    return {"message": "Coach's Sport Practice API is running!"}

def clean_for_pdf(text):
    import re
    # Replace bullets, dashes, and non-breaking spaces
    text = text.replace('•', '-').replace('–', '-').replace('—', '-').replace('●', '-')
    text = text.replace('\u00A0', ' ')
    text = re.sub(r'[_*]', '', text)
    return text

@app.post("/generate-pdf")
async def generate_pdf(content: str = Form(...)):
    clean_content = clean_for_pdf(content)
    buffer = BytesIO()
    p = canvas.Canvas(buffer, pagesize=letter)
    width, height = letter
    left_margin = 50
    right_margin = 50
    top_margin = 60
    bottom_margin = 50
    max_width = width - left_margin - right_margin
    y = height - top_margin

    lines = clean_content.split('\n')
    textobject = p.beginText(left_margin, y)
    textobject.setFont("Helvetica", 12)

    for line in lines:
        # Wrap long lines
        wrapped_lines = simpleSplit(line, "Helvetica", 12, max_width)
        for wline in wrapped_lines:
            if y < bottom_margin:
                p.drawText(textobject)
                p.showPage()
                y = height - top_margin
                textobject = p.beginText(left_margin, y)
                textobject.setFont("Helvetica", 12)
            textobject.textLine(wline)
            y -= 16  # line spacing

        # Add extra space after empty lines (paragraphs)
        if line.strip() == "":
            y -= 8

    p.drawText(textobject)
    p.save()
    buffer.seek(0)
    return StreamingResponse(buffer, media_type="application/pdf")

if __name__ == "__main__":
    uvicorn.run("CoachSportsPrac:app", host="127.0.0.1", port=5003, reload=True)
