from fastapi import FastAPI, UploadFile, Form, HTTPException
from fastapi.responses import JSONResponse
import traceback
from tutor_agent import generate_output_with_file
from fastapi import Body
from pydantic import BaseModel

class HistoryRequest(BaseModel):
    history: str


app = FastAPI()

@app.post("/tutor")
async def tutor_endpoint(
    grade_level: str = Form(...),
    input_type: str = Form(...),
    topic: str = Form(""),
    add_cont: str = Form(""),
    pdf_file: UploadFile = None
):
    try:
        if input_type == "pdf" and not pdf_file:
            raise HTTPException(status_code=400, detail="PDF file required for PDF input_type")

        output = await generate_output_with_file(
            grade_level=grade_level,
            input_type=input_type,
            topic=topic,
            add_cont=add_cont,
            pdf_file=pdf_file
        )

        return {"output": output}

    except Exception as e:
        traceback_str = traceback.format_exc()
        print(traceback_str)
        return JSONResponse(status_code=500, content={"detail": str(e), "trace": traceback_str})
    
from step_tutor_agent import StepTutorInput, explain_topic_step_by_step

@app.post("/step-tutor")
async def step_tutor_endpoint(data: StepTutorInput):
    try:
        output = await explain_topic_step_by_step(
            grade_level=data.grade_level,
            topic=data.topic
        )
        return {"response": output}
    except Exception as e:
        return {"error": str(e)}



@app.post("/summarize-history")
async def summarize_history_endpoint(data: HistoryRequest):
    from summarizer_agent import summarize_conversation
    print("Received history for summarization:", data.history[:100])
    return {"summary": await summarize_conversation(data.history)}



