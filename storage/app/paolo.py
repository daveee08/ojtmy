# main.py

from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
import uvicorn

from five_question_agent import FiveQuestionInput, generate_questions
from proofreader import ProofreaderInput as ProofreadInput, run_proofread

app = FastAPI()

# === CORS setup ===
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_methods=["POST"],
    allow_headers=["*"],
)

# === 5 Questions Endpoint ===
@app.post("/5questions")
async def five_questions_endpoint(data: FiveQuestionInput):
    try:
        questions = generate_questions(data.grade_level, data.prompt)
        return {"questions": questions}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

# === Proofreader Endpoint ===
@app.post("/proofread")
async def proofread_endpoint(data: ProofreadInput):
    try:
        # Support both text and pdf_path (if passed)
        result = run_proofread(data.profile, text=data.text or "", pdf_path=data.pdf_path or "")
        return result
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

# === Uvicorn launch ===
if __name__ == "__main__":
    uvicorn.run("main:app", host="127.0.0.1", port=5001, reload=True)
