from fastapi import FastAPI
from pydantic import BaseModel, ConfigDict
from langchain_ollama import OllamaLLM
from langchain.prompts import PromptTemplate
from fpdf import FPDF
from io import BytesIO
from fastapi.responses import StreamingResponse, JSONResponse

app = FastAPI()

class QuoteRequest(BaseModel):
    model_config = ConfigDict(extra='ignore')
    topic: str
    grade_level: str

@app.get("/")
async def read_root():
    return {"message": "QOTD API is running!"}

@app.post("/generate-quote")
async def generate_quote_api(request: QuoteRequest):
    try:
        llm = OllamaLLM(model="gemma:2b")

        prompt_template = PromptTemplate(
            input_variables=["topic", "grade_level"],
            template="""
            You are an AI assistant that generates quotes.
            Generate a quote about {topic} suitable for a {grade_level} student.
            Provide only the quote text, without any introductory or concluding remarks, or attribution.
            """
        )

        quote = (prompt_template | llm).invoke({'topic': request.topic, 'grade_level': request.grade_level})
        quote = quote.strip()

        return {"quote": quote}
    except Exception as e:
        return JSONResponse(status_code=500, content={"error": str(e)})

class PdfRequest(BaseModel):
    content: str
    filename: str

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
        return JSONResponse(status_code=500, content={"error": str(e)})
