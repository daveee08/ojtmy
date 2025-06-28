from fastapi import FastAPI
from pydantic import BaseModel, Field, ConfigDict
from langchain_ollama import OllamaLLM
from langchain.prompts import PromptTemplate
from fpdf import FPDF
from io import BytesIO
from fastapi.responses import StreamingResponse

app = FastAPI()

class PracticePlanRequest(BaseModel):
    model_config = ConfigDict(extra='ignore')
    grade_level: str
    length_of_practice: str
    sport: str
    additional_customization: str | None = None

class PdfRequest(BaseModel):
    content: str
    filename: str

@app.get("/")
async def read_root():
    return {"message": "CoachSportsPrac API is running!"}

@app.post("/generate-practice-plan")
async def generate_practice_plan_api(request: PracticePlanRequest):
    try:
        llm = OllamaLLM(model="gemma:2b")

        prompt_template = PromptTemplate(
            input_variables=["grade_level", "length_of_practice", "sport", "additional_customization"],
            template="""
            You are an AI assistant that generates sports practice plans.
            Create a practice plan for a {grade_level} level for {sport}.
            The practice should be {length_of_practice} long.
            {additional_customization}
            
            Provide a detailed practice plan, including warm-up, drills, and cool-down.
            
            **Important Formatting Instructions:**
            - Use a single asterisk (*) for bullet points at the beginning of each item.
            - Make important keywords and specific sports terms **bold** and *italic* using markdown (e.g., `**warm-up**` and `*sprint drills*`).
            - Start directly with the practice plan, without any introductory or concluding remarks.
            """
        )
        
        customization_text = f"Also, include the following: {request.additional_customization}." if request.additional_customization else ""

        practice_plan = (prompt_template | llm).invoke({
            'grade_level': request.grade_level,
            'length_of_practice': request.length_of_practice,
            'sport': request.sport,
            'additional_customization': customization_text,
        })
        practice_plan = practice_plan.strip()

        return {"practice_plan": practice_plan}
    except Exception as e:
        return {"error": str(e)}

@app.post("/generate-pdf")
async def generate_pdf(request: PdfRequest):
    try:
        pdf = FPDF()
        pdf.add_page()
        pdf.set_font("Arial", size=12)
        pdf.multi_cell(0, 10, request.content.encode('latin-1', 'replace').decode('latin-1'))

        buffer = BytesIO()
        pdf.output(buffer, 'S')
        buffer.seek(0)

        return StreamingResponse(buffer, media_type="application/pdf", headers={
            "Content-Disposition": f"attachment; filename={request.filename}.pdf"
        })
    except Exception as e:
        return {"error": str(e)}
