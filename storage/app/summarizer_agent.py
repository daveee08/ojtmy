from langchain_ollama import OllamaLLM
from langchain_core.prompts import PromptTemplate

summary_template = """
You are a summarization assistant. Your task is to summarize the following conversation clearly and concisely while preserving the original intent.

Instructions:
- Keep the summary under 300 words.
- Focus only on key points that you've provided or questions raised by the user.
- Do not explain or tutor — just summarize.

Conversation History:
----------------------
{history}

Summary:
"""

model = OllamaLLM(model="gemma3:1b")  # Or change to another model later
summarize_prompt = PromptTemplate.from_template(summary_template)

async def summarize_conversation(history: str) -> str:
    chain = summarize_prompt | model
    result = chain.invoke({"history": history})
    return result.strip()
