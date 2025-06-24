# storage/app/python/main.py
from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel, validator, ValidationError
import uvicorn

from proofreader import run_proofread

app = FastAPI()

# CORS so your Laravel front‑end can call this
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_methods=["POST"],
    allow_headers=["*"],
)

class ProofreadInput(BaseModel):
    profile: str
    text: str = ""
    pdf_path: str | None = None  

    @validator("profile")
    def check_profile(cls, v):
        if v not in ("academic", "casual", "concise"):
            raise ValueError("profile must be one of: academic, casual, concise")
        return v

    @validator("pdf_path", always=True)
    def require_text_or_pdf(cls, v, values):
        if not v and not values.get("text"):
            raise ValueError("You must provide either text or a pdf_path")
        return v

@app.post("/proofread")
async def proofread_endpoint(payload: ProofreadInput):
    try:
        result = run_proofread(payload.profile, payload.text, payload.pdf_path)
        return result
    except ValidationError as ve:
        raise HTTPException(status_code=422, detail=ve.errors())
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

if __name__ == "__main__":
    uvicorn.run("main:app", host="127.0.0.1", port=5001, reload=True)
