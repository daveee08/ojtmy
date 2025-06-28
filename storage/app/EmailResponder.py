from fastapi import FastAPI
from pydantic import BaseModel, ConfigDict
from langchain_ollama import OllamaLLM
from langchain.prompts import PromptTemplate
import logging

# Configure logging
logging.basicConfig(level=logging.INFO, format='%(asctime)s - %(levelname)s - %(message)s')

class EmailRequest(BaseModel):
    model_config = ConfigDict(extra='ignore')
    author_name: str
    email_responding_to: str
    communication_intent: str

app = FastAPI()

@app.get("/")
async def read_root():
    return {"message": "Email Responder API is running!"}

@app.post("/generate-email-response")
async def generate_email_response(request: EmailRequest):
    logging.info(f"Generating email response for author: {request.author_name}")
    try:
        llm = OllamaLLM(model="gemma:2b")

        email_prompt_template = PromptTemplate(
            input_variables=["author_name", "email_responding_to", "communication_intent"],
            template="""
            You are an expert AI assistant specialized in crafting professional email responses.
            Your task is to generate a formal and polite email response based on the following information:

            Author Name: {author_name}
            Email you're responding to: {email_responding_to}
            What you want to communicate in response: {communication_intent}

            **Exemplar:**
            Input:
            Author Name: Jane Smith
            Email you're responding to: I'm a PhD student at the University of Central Florida and I was wondering if we could partner. I would like to do a feasibility study using MagicSchool AI for my dissertation. Would you be interested in this partner?
            What you want to communicate in response: That sounds great lets set up time

            Output:
            Subject: Re: Partnership Opportunity - MagicSchool AI Dissertation Study

            Dear [Sender's Name, e.g., PhD Student's Name if available, otherwise just "Sender"],

            Thank you for reaching out regarding the potential partnership for your dissertation study on MagicSchool AI. I am very interested in discussing this further.

            Please let me know your availability for a brief call next week to explore this opportunity in more detail.

            Best regards,

            Jane Smith
            ---

            Please generate only the email content. Do not include any conversational text or explanations before or after the email. Ensure the email is professional and directly addresses the communication intent.
            """
        )

        generated_email = (email_prompt_template | llm).invoke({
            'author_name': request.author_name,
            'email_responding_to': request.email_responding_to,
            'communication_intent': request.communication_intent
        })

        logging.info("Email response generated successfully.")
        return {"generated_email": generated_email}
    except Exception as e:
        logging.error(f"Error generating email response: {e}")
        return {"error": str(e)}
