from pydantic import BaseModel
from langchain_ollama import OllamaLLM
from langchain_core.prompts import ChatPromptTemplate
from pydantic import BaseModel

class TranslationInput(BaseModel):
    text: str
    target_language: str
    pdf_path: str = ""

def translate_text(text: str, target_language: str) -> str:
    if not text.strip():
        return "No input text provided for translation."

    prompt = f"""
You are a multilingual translator. Translate the following text clearly and naturally into {target_language}.
You  should not include the original text in your response, only the translation.
You dont need to explain the translation, just provide the translated text.
You dont  need to include any additional information, just the translation.
Text:
{text}
"""

    model = OllamaLLM(model="mistral:7b-instruct")
    chain = ChatPromptTemplate.from_template(prompt) | model

    raw_output = chain.invoke({})

    # Cleanup: strip potential "Translation:" prefix and whitespace
    translated = raw_output.strip().replace("Translation:", "").strip()

    return translated
