from pydantic import BaseModel
from langchain_ollama import OllamaLLM
from langchain_core.prompts import ChatPromptTemplate

class TranslationInput(BaseModel):
    text: str
    target_language: str
    pdf_path: str = ""

# Instantiate once
model = OllamaLLM(model="mistral:7b-instruct")

# Safer templated prompt
prompt_template = ChatPromptTemplate.from_template("""
You are a multilingual translator. Translate the following text clearly and naturally into {language}.
Only return the translated text. Do not include the original, explanations, or extra information.
Text:
{text}
""")

# Combine into a chain
chain = prompt_template | model

def translate_text(text: str, target_language: str) -> str:
    if not text.strip():
        return "No input text provided for translation."

    raw_output = chain.invoke({
        "language": target_language,
        "text": text
    })

    # Cleanup
    translated = raw_output.strip().removeprefix("Translation:").strip()
    return translated
