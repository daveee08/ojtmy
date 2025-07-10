from fastapi import FastAPI, Form, Depends, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from langchain_ollama import OllamaLLM as Ollama
from langchain.prompts import PromptTemplate
from typing import Optional
from pydantic import BaseModel
# from http.client import HTTPException
import sys
import os

# current_script_dir = os.path.dirname(os.path.abspath(__file__))
# project_root = os.path.join(current_script_dir, '..', '..')
# sys.path.insert(0, project_root)

# print(f"Adding to sys.path: {project_root}") 

# from python.chat_router_final import chat_router
# from python.db_utils_final import create_session_and_parameter_inputs, insert_message

# app = FastAPI()


# app.add_middleware(
#     CORSMiddleware,
#     allow_origins=["*"],
#     allow_methods=["*"],
#     allow_headers=["*"],
# )

# prompt_template = PromptTemplate.from_template("""
# You are a helpful assistant. Based on the grade level "{grade_level}", come up with creative, practical, and thoughtful ideas for the following request:

# {prompt}

# Only return the list of ideas. Do not add extra explanation.
# """)

# class IdeaGeneratorInput(BaseModel):
#     grade_level: str
#     prompt: str
#     user_id: int
#     message_id: Optional[int] = None
#     # agent_id: int = 16  # Default agent_id for step tutor

#     @classmethod
#     def as_form(
#         cls,
#         grade_level: str = Form(...),
#         prompt: str = Form(...),
#         user_id: int = Form(...),
#         message_id: Optional[int] = Form(None)
#     ):
#         return cls(
#             grade_level=grade_level,
#             prompt=prompt,
#             user_id=user_id,
#             message_id=message_id
#         )


# llm = Ollama(model="gemma3:1b")
# chain = prompt_template | llm

# @app.post("/generate-idea")
# async def generate_idea(data: IdeaGeneratorInput = Depends(IdeaGeneratorInput.as_form) ):
#     print("GRADE LEVEL:", data.grade_level)
#     print("PROMPT:", data.prompt)
#     result = chain.invoke({
#         "grade_level": data.grade_level,
#         "prompt": data.prompt
#     })

#     print("RESULT:", result)
#     return {"idea": result.strip()}

