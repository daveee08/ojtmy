from fastapi import FastAPI, HTTPException, UploadFile, Form, File, Depends
from fastapi.responses import JSONResponse
from pydantic import BaseModel
from langchain_community.llms import Ollama
from langchain_core.prompts import ChatPromptTemplate
from langchain_community.document_loaders.pdf import PyPDFLoader
import shutil, os, re, tempfile, uvicorn, traceback, sys
from typing import Optional
from langchain_core.messages import HumanMessage, AIMessage
from fastapi.middleware.cors import CORSMiddleware

current_script_dir = os.path.dirname(os.path.abspath(__file__))
project_root = os.path.join(current_script_dir, '..', '..')
sys.path.insert(0, project_root)

from python.chat_router import chat_router
from python.db_utilss import create_session_and_parameter_inputs, insert_message

manual_concept_template = """
You are a knowledgeable, student-friendly virtual tutor.

Your task is to explain the given concept clearly, accurately, and appropriately for the student's grade level. Use flexible structure based on what the concept includes — not a fixed template.

<b>Concept:</b> {concept}<br>
<b>Grade Level:</b> {grade_level}<br><br>

<b>Guidelines:</b><br>
- Adjust your explanation to match the student's grade level<br>
- Expand on the concept using as many subtopics, key ideas, and real examples as needed<br>
- Do not force sections like “Understanding” or “Features” — instead, break down ideas naturally depending on what the concept is<br>
- Use bold subheadings for each important idea (e.g., <b>Routing</b>, <b>Photosynthesis Steps</b>, etc.)<br>
- Use analogies and comparisons to real-life situations when appropriate<br>
- Always write in clean, correct HTML<br><br>

<b>Grade Adaptation:</b><br>
- <b>Grades 1-3:</b> Use very simple words and relatable, everyday examples (no code or jargon)<br>
- <b>Grades 4-5:</b> Introduce basic structure, light technical ideas, and more familiar scenarios<br>
- <b>Grades 6-8:</b> Use logical flow, clear terms, and analogies tied to school, games, or life<br>
- <b>Grades 9-10:</b> Use more technical vocabulary and break things down step-by-step<br>
- <b>Grades 11-12:</b> Use accurate technical flow, real-world applications, and multiple examples<br><br>

<b>Formatting Requirements:</b><br>
- Use only valid HTML — no Markdown or plaintext<br>
- Bold subheadings using <b>...</b><br>
- Use <br> or <br><br> for line spacing (no empty lines)<br>
- Do not include structural labels like “Features,” “Understanding,” etc. — only add headings if the content requires it<br>
- Avoid fluff, filler phrases, or redundant summaries<br><br>

<b>Your Output:</b><br>
Return a clear, engaging, well-organized explanation in valid HTML. Focus on substance. Expand naturally based on the concept.
"""

pdf_concept_template = """
You are a knowledgeable, student-friendly virtual tutor.

Your task is to explain the given concept clearly, accurately, and appropriately for the student's grade level. Use flexible structure based on what the concept includes — not a fixed template.

<b>Concept:</b> {concept}<br>
<b>Grade Level:</b> {grade_level}<br><br>

<b>Guidelines:</b><br>
- Adjust your explanation to match the student's grade level<br>
- Expand on the concept using as many subtopics, key ideas, and real examples as needed<br>
- Do not force sections like “Understanding” or “Features” — instead, break down ideas naturally depending on what the concept is<br>
- Use bold subheadings for each important idea (e.g., <b>Routing</b>, <b>Photosynthesis Steps</b>, etc.)<br>
- Use analogies and comparisons to real-life situations when appropriate<br>
- Always write in clean, correct HTML<br><br>

<b>Grade Adaptation:</b><br>
- <b>Grades 1-3:</b> Use very simple words and relatable, everyday examples (no code or jargon)<br>
- <b>Grades 4-5:</b> Introduce basic structure, light technical ideas, and more familiar scenarios<br>
- <b>Grades 6-8:</b> Use logical flow, clear terms, and analogies tied to school, games, or life<br>
- <b>Grades 9-10:</b> Use more technical vocabulary and break things down step-by-step<br>
- <b>Grades 11-12:</b> Use accurate technical flow, real-world applications, and multiple examples<br><br>

<b>Formatting Requirements:</b><br>
- Use only valid HTML — no Markdown or plaintext<br>
- Bold subheadings using <b>...</b><br>
- Use <br> or <br><br> for line spacing (no empty lines)<br>
- Do not include structural labels like “Features,” “Understanding,” etc. — only add headings if the content requires it<br>
- Avoid fluff, filler phrases, or redundant summaries<br><br>

<b>Your Output:</b><br>
Return a clear, engaging, well-organized explanation in valid HTML. Focus on substance. Expand naturally based on the concept.
"""

app = FastAPI(debug=True)
app.include_router(chat_router)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # or Laravel origin like "http://localhost:8000"
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

class ExplanationsInput(BaseModel):
    user_id: int
    input_type: str
    concept: str
    grade_level: str
    message_id: Optional[str] = None

    @classmethod
    def as_form(
        cls,
        user_id: int = Form(...),
        input_type: str = Form(...),
        concept: str = Form(""),
        grade_level: str = Form(...),
        message_id: Optional[str] = Form(default=None)
    ):
        return cls(
            user_id=user_id,
            input_type=input_type,
            concept=concept,
            grade_level=grade_level,
            message_id=message_id
        )

model = Ollama(model="gemma:2b")
manual_prompt = ChatPromptTemplate.from_template(manual_concept_template)
pdf_prompt = ChatPromptTemplate.from_template(pdf_concept_template)

def load_pdf_content(pdf_path: str) -> str:
    if not os.path.exists(pdf_path):
        raise FileNotFoundError("PDF file not found.")
    loader = PyPDFLoader(pdf_path)
    documents = loader.load()
    return "\n".join(doc.page_content for doc in documents)

def clean_output(text: str) -> str:
    # """
    # Cleans and formats the LLM output into HTML.
    # This function specifically targets markdown bolding, italicizing specific labels,
    # removing code blocks, and converting bullet points to HTML lists.

    # Args:
    #     text (str): The raw text output from the LLM.

    # Returns:
    #     str: The cleaned and HTML-formatted string.
    # """
    # if not isinstance(text, str):
    #     logger.warning(f"Expected string for clean_output, got {type(text)}")
    #     return "" # Or raise TypeError

    # # 1. Convert Markdown bold (**text**, *text*) to <b>text</b>
    # # This regex is more robust to variations in asterisk count
    # text = re.sub(r"\*{1,2}([^\n*]+?)\*{1,2}", r"<b>\1</b>", text)
    # # Handle bold headings on their own line (e.g., `**My Heading**`)
    # text = re.sub(r"^\*\*([^\n]+?)\*\*$", r"<b>\1</b>", text, flags=re.MULTILINE)
    # text = re.sub(r"^\*([^\n]+?)\*$", r"<b>\1</b>", text, flags=re.MULTILINE)


    # # 2. Italicize specific labels like _Example: or _Analogy:
    # text = re.sub(r"_(Example|Analogy):", r"<i>\1:</i>", text)

    # # 3. Remove markdown code blocks (``` blocks)
    # text = re.sub(r"```.*?```", "", text, flags=re.DOTALL)

    # # 4. Process lines to build HTML structure
    # lines = text.splitlines()
    # html_lines = []
    # in_list = False

    # for line in lines:
    #     stripped_line = line.strip()

    #     # Handle bullet points
    #     if re.match(r"^[•\*\-]\s+", stripped_line):
    #         content = re.sub(r"^[•\*\-]\s+", "", stripped_line)
    #         if not in_list:
    #             html_lines.append("<ul>")
    #             in_list = True
    #         html_lines.append(f"<li>{content}</li>")
    #     else:
    #         # If we were in a list and the current line is not a list item, close the list
    #         if in_list:
    #             html_lines.append("</ul>")
    #             in_list = False

    #         # Add paragraph or heading, if content exists
    #         if stripped_line:
    #             # Check if it's already a bold HTML heading from previous steps
    #             if stripped_line.startswith('<b>') and stripped_line.endswith('</b>'):
    #                 html_lines.append(f"{stripped_line}<br><br>")
    #             else:
    #                 html_lines.append(f"{stripped_line}<br>")

    # # Ensure list is closed if the text ends with a list
    # if in_list:
    #     html_lines.append("</ul>")

    # html_output = "".join(html_lines)

    # # 5. Cleanup excessive breaks (e.g., more than 2 in a row)
    # html_output = re.sub(r"(<br>\s*){3,}", "<br><br>", html_output)
    # # Remove leading/trailing <br> tags if any
    # html_output = re.sub(r"^(<br>\s*)+", "", html_output)
    # html_output = re.sub(r"(<br>\s*)+$", "", html_output)

    # return html_output.strip()

    return text.strip()  # Simplified for now, can be expanded later

async def generate_output(
    input_type: str,
    grade_level: str,
    concept: str = "",
    pdf_file: UploadFile = None,
):
    if input_type == "pdf":
        # Save PDF temporarily
        with tempfile.NamedTemporaryFile(delete=False, suffix=".pdf") as tmp:
            content = await pdf_file.read()
            tmp.write(content)
            tmp_path = tmp.name

        concept = load_pdf_content(tmp_path)
        os.unlink(tmp_path)  # Delete file after use
        prompt = pdf_prompt
    else:
        if not concept.strip():
            raise ValueError("Text input is required")
        prompt = manual_prompt

    # Compose input dict for prompt
    prompt_input = {
        "concept": concept,
        "grade_level": grade_level
    }
    
    chain = prompt | model
    result = chain.invoke(prompt_input)
    return clean_output(result)

@app.post("/explanations")
async def explanations_api(
    form_data: ExplanationsInput = Depends(ExplanationsInput.as_form),
    pdf_file: UploadFile = File(None)
):
    
    try:
        if form_data.input_type == "pdf" and not pdf_file:
            raise HTTPException(status_code=400, detail="PDF file required for PDF input_type")

        output = await generate_output(
            input_type=form_data.input_type,
            concept=form_data.concept,
            grade_level=form_data.grade_level,
            pdf_file=pdf_file,
        )

        scope_vars = {
            "grade_level": form_data.grade_level 
        }

        human_topic = form_data.concept if form_data.input_type != "pdf" else "[PDF Input]"

        session_id = create_session_and_parameter_inputs(
            user_id=form_data.user_id,
            agent_id=10,
            scope_vars=scope_vars,
            human_topic=human_topic,
            ai_output=output
        )

        return {"output": output, "message_id": session_id}
    except Exception as e:
        traceback_str = traceback.format_exc()
        print(traceback_str)
        return JSONResponse(status_code=500, content={"detail": str(e), "trace": traceback_str})

if __name__ == "__main__":
    uvicorn.run("explanations:app", host="127.0.0.1", port=5001, reload=True)

# original