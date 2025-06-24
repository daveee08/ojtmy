# storage/app/python/tutor_agent.py

import os
import re
from pydantic import BaseModel, ValidationError
from langchain_ollama import OllamaLLM
from langchain_core.prompts import ChatPromptTemplate
from langchain_community.document_loaders import PyPDFLoader
from fastapi import UploadFile
import tempfile, os, re

# Define your prompt templates
manual_topic_template = """
You are an experienced and friendly virtual tutor, specializing in guiding students towards deep conceptual understanding. Your goal is to explain the given topic clearly and comprehensively, ensuring the student grasps the core ideas, their significance, and how they relate to broader concepts.

Please structure your explanation as follows:

1. Core Concept Explanation:
- Provide a concise and easy-to-understand definition.
- Break down the concept into its fundamental components or principles.
- Use analogies or simple real-world examples to illustrate the concept.

2. Why it Matters - It's Significance/Application (from the text):
- Explain the importance or relevance of this concept.
- Describe practical applications or scenarios where this concept is used.

3. Related Concepts - Connections (from the text):
- Briefly mention how this concept connects to other related ideas or topics.
- Highlight any prerequisite knowledge that would enhance understanding.

4. Potential Misconceptions & Clarifications:
- Identify common misunderstandings students might have about this topic.
- Provide clear explanations to correct these misconceptions.


Important: Don't ask for a follow-up question.
---
Student Details:
- Grade Level: {grade_level}
- Topic: {topic}
- Additional Context or Learning Needs: {add_cont}

**Your Output (following the structure above):**
"""

pdf_topic_template = """
You are a knowledgeable and supportive virtual tutor, specializing in guiding students towards deep conceptual understanding based on provided text. Your goal is to explain the core concepts from the extracted content clearly and comprehensively, ensuring the student grasps the main ideas, their significance, and how they relate to broader concepts discussed in the text.

Please structure your explanation as follows:

1. Core Concept Explanation:
- Provide a concise and easy-to-understand definition.
- Break down the concept into its fundamental components or principles.
- Use analogies or simple real-world examples to illustrate the concept.

2. Why it Matters - It's Significance/Application (from the text):
- Explain the importance or relevance of this concept.
- Describe practical applications or scenarios where this concept is used.

3. Related Concepts - Connections (from the text):
- Briefly mention how this concept connects to other related ideas or topics.
- Highlight any prerequisite knowledge that would enhance understanding.

4. Potential Misconceptions & Clarifications:
- Identify common misunderstandings students might have about this topic.
- Provide clear explanations to correct these misconceptions.


Important: Don't ask for a follow-up question.

---
**Student Details:**
- Grade Level: {grade_level}
- Extracted Content (first 2 pages): {topic}
- Additional Notes: {add_cont}

**Your Output (following the structure above):**
"""

chat_history_template = """
You are a skilled and supportive virtual tutor assisting a student in an ongoing conversation. Your task is to continue the tutoring session based on the summarized history of prior interactions and the latest student question.

Structure your response clearly and helpfully, as if you're replying to the student's most recent input with awareness of the prior discussion.

---  
**Student Details:**  
- Grade Level: {grade_level}  
- Prior Conversation Summary: {conversation_summary}  
- Current Message: {topic}  
- Additional Notes: {add_cont}  

**Your Output (explanation only):**
"""

chat_history_prompt = ChatPromptTemplate.from_template(chat_history_template)

# Initialize your language model and prompt templates
model = OllamaLLM(model="gemma3")
manual_prompt = ChatPromptTemplate.from_template(manual_topic_template)
pdf_prompt = ChatPromptTemplate.from_template(pdf_topic_template)

# Pydantic model used for input validation
class TutorInput(BaseModel):
    grade_level: str
    input_type: str
    topic: str = ""
    pdf_path: str = ""
    add_cont: str = ""

# Function to extract text from PDF (using only the first 2 pages)
def extract_text_from_pdf(path: str) -> str:
    if not os.path.exists(path):
        raise FileNotFoundError("PDF file not found.")
    loader = PyPDFLoader(path)
    pages = loader.load()
    return " ".join([page.page_content for page in pages[:2]])

# Function to clean the output from formatting artifacts
def clean_output(text: str) -> str:
    text = re.sub(r"\*\*(.*?)\*\*", r"\1", text)
    text = re.sub(r"\*(.*?)\*", r"\1", text)
    text = re.sub(r"^\s*[\*\-]\s*", "", text, flags=re.MULTILINE)
    return text.strip()

# Main function containing the tutor logic
async def generate_output_with_file(grade_level, input_type, topic="", add_cont="", pdf_file: UploadFile = None):
    if input_type == "pdf":
        # Save PDF temporarily
        with tempfile.NamedTemporaryFile(delete=False, suffix=".pdf") as tmp:
            content = await pdf_file.read()
            tmp.write(content)
            tmp_path = tmp.name

        topic = extract_text_from_pdf(tmp_path)
        os.unlink(tmp_path)  # Delete file after use
        prompt = pdf_prompt
    else:
        # Heuristic: If topic contains line breaks or looks like a conversation summary, use chat history prompt
        if topic.count("\n") > 3 and "Prior Conversation" in topic:
            # Topic is likely context + latest message
            prompt = chat_history_prompt
            # Extract the two parts manually (if Laravel prepends them separately in the future, adapt this)
            user_input = {
                "grade_level": grade_level,
                "conversation_summary": topic,  # already includes latest msg at end
                "topic": "",  # optional if topic already includes everything
                "add_cont": add_cont
            }
        else:
            prompt = manual_prompt
            user_input = {
                "grade_level": grade_level,
                "input_type": input_type,
                "topic": topic,
                "pdf_path": "",
                "add_cont": add_cont
            }
            
    chain = prompt | model
    result = chain.invoke(user_input)
    return clean_output(result)
