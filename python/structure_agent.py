from pydantic import BaseModel
from langchain_ollama import OllamaLLM
from langchain_core.prompts import ChatPromptTemplate
from fastapi import FastAPI, HTTPException, UploadFile, Form
from fastapi.middleware.cors import CORSMiddleware
import uvicorn
from fastapi import Form, Depends




app = FastAPI(debug=True)


# === CORS setup ===
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_methods=["POST"],
    allow_headers=["*"],
)

class StructureInput(BaseModel):
    text: str

    @classmethod
    def as_form(
        cls,
        text: str = Form(...),
    ):
        return cls(
            text=text,

        )



# Instantiate once
model = OllamaLLM(model="gemma3:1b")

# Safer templated prompt
prompt_template = ChatPromptTemplate.from_template("""
You are an expert title generator. Your sole purpose is to create a concise, relevant, and engaging title based on the provided conversation.
                                                   
The text is:
{text}
                                                
Output:
Please provide only the generated title. Do not include any additional text, explanations, or formatting. The title should be:

Concise: Ideally 3-7 words.

Relevant: Accurately reflect the main topic or purpose of the conversation.

Engaging: Spark interest and clearly indicate the content.

Unique: Avoid generic phrases like "Chat with user" or "Conversation summary."                                                                                              

""")

# Combine into a chain
# chain = prompt_template | model

# def translate_text(text: str, target_language: str) -> str:
#     if not text.strip():
#         return "No input text provided for translation."

#     raw_output = chain.invoke({
#         "language": target_language,
#         "text": text
#     })

#     # Cleanup
#     translated = raw_output.strip().removeprefix("Translation:").strip()
#     return translated


@app.post("/structure")
async def translate_endpoint(data: StructureInput = Depends(StructureInput.as_form)):
        
    chain = prompt_template | model

    output = chain.invoke({
        "text": data.text
    })
        
    return {"structured": output}
    # except Exception as e:
    #     raise HTTPException(status_code=500, detail=str(e))
    
