# main.py

from fastapi import FastAPI, HTTPException, UploadFile, Form
from fastapi.middleware.cors import CORSMiddleware
import uvicorn

from five_questions.five_question_agent import FiveQuestionInput, generate_questions
from proofreader.proofreader import ProofreaderInput as ProofreadInput, run_proofread
from real_world_agent import RealWorldInput, generate_real_world_examples
from sentence_starters.sentence_starters_agent import SentenceStarterInput, generate_sentence_starters
from translator.translator_agent import TranslationInput, translate_text
from study_habits.study_habits_agent import StudyPlanInput, generate_study_plan

# from sentence_starters_agent import SentenceStarterInput, generate_sentence_starters
# from translator_agent import TranslationInput, translate_text
# from study_habits_agent import StudyPlanInput, generate_study_plan
# from five_question_agent import FiveQuestionInput, generate_questions

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
async def proofread_endpoint(
    profile: str = Form(...),
    text: str = Form(""),
    pdf_file: UploadFile = None
):
    try:
        result = await run_proofread(profile, text=text, pdf_file=pdf_file)
        return result
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

# === Real World Examples Endpoint ===
@app.post("/realworld")
async def real_world_endpoint(data: RealWorldInput):
    try:
        examples = generate_real_world_examples(data.grade_level, data.topic)
        return {"examples": examples}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))
    
    # === Sentence Starters Endpoint ===
@app.post("/sentencestarters")
async def sentence_starters_endpoint(data: SentenceStarterInput):
    try:
        starters = generate_sentence_starters(data.grade_level, data.topic)
        return {"starters": starters}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

 # === Translation Endpoint ===   
@app.post("/translate")
async def translate_endpoint(data: TranslationInput):
    try:
        output = translate_text(data.text, data.target_language)
        return {"translation": output}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

# === Study Habits Endpoint ===
@app.post("/studyhabits")
async def study_plan_endpoint(data: StudyPlanInput):
    try:
        plan = generate_study_plan(data.grade_level, data.goal)
        return {"plan": plan}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))
    

# # === Uvicorn launch ===
# if __name__ == "__main__":
#     uvicorn.run("paolo:app", host="127.0.0.1", port=5001, reload=True)
