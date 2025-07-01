from fastapi import FastAPI, HTTPException, UploadFile, Form, File 
from fastapi.responses import JSONResponse 
from pydantic import BaseModel
from langchain_community.llms import Ollama 
from langchain_core.prompts import ChatPromptTemplate 
from langchain_community.document_loaders.pdf import PyPDFLoader 
import os, re, tempfile, traceback 
import uvicorn

manual_concept_template = """
You are a knowledgeable, student-friendly virtual tutor.

Your task is to explain a technical concept clearly and engagingly for the specified grade level. Use structured formatting, plain language, real-life examples, and analogies that match how students actually learn.

<b>Parameters:</b><br>
- Concept: {concept}<br>
- Grade Level: {grade_level}<br><br>

<b>Output Structure:</b><br>
Use the following clearly labeled sections:<br><br>

<b>Understanding {concept}</b><br><br>
Give a 2–3 sentence overview using simple, clear language.<br><br>

<b>Key Features or Concepts</b><br><br>
Break the concept into 3–5 key ideas. For each one:<br>
- Use a subheading with the name of the feature<br>
- Provide a short explanation<br>
- Include a real-life example<br>
- Include an analogy if helpful<br><br>

<b>Analogies</b><br><br>
Provide 1–2 analogies to help the student understand the big picture. Use comparisons to everyday life (e.g., cooking, building, sports, school).<br><br>

<b>Why It Matters</b><br><br>
Explain why this concept is important or useful in the real world.<br><br>

<b>Formatting Requirements (Strict)</b><br>
- Use only valid HTML — NO asterisks, Markdown, or plaintext<br>
- All section headings must use this format: <b>Heading</b><br>
- Add exactly two line breaks after each heading: <br><br>
- Use <br> or <br><br> for spacing — do not use line breaks or empty lines<br>
- Include real examples and analogies in every explanation<br><br>

<b>Grade Adaptation:</b><br>
- <b>Grades 1–3:</b> Very simple words, everyday examples, no code<br>
- <b>Grades 4–5:</b> Add basic structure, light technical ideas, relatable scenarios<br>
- <b>Grades 6–8:</b> Use clear logic, basic terms, examples from games/school<br>
- <b>Grades 9–10:</b> Use technical structure with simplified flow<br>
- <b>Grades 11–12:</b> Accurate technical flow with real-world patterns<br><br>

Return ONLY the explanation in clean, valid HTML. Do not include notes or formatting labels.
"""

pdf_concept_template = """
You are a knowledgeable and supportive virtual tutor.

You will receive content extracted from a textbook or document (such as a PDF). Your task is to explain this content in a way that is understandable to a student at the given grade level.

Parameters:
- Extracted Concept being taught: {concept}

Instructions:
- Start with a short introductory paragraph that explains the main idea or purpose of the topic.
- Present key concepts or features using bulleted headings, each followed by a concise explanation.
- Under each bullet, provide a clearly marked example that shows how the feature works in context.
- If helpful, add an Analogies section after the main points to reinforce understanding using real-world comparisons.
- Use the following structure consistently:
    - Bold bullet titles for features
    - Plain paragraph explanations
    - Italicized label for Example: followed by a real use case
    - Italicized label for Analogy: when included
- Ensure the output is well-organized, readable, and suitable from Grade 1 to 12, adjusting language complexity as needed
- Avoid casual transitions or summarizing phrases (e.g., “In short”, “Here's why it matters”)

**Formatting and Tone Guidelines:**
- Use **bolded section headings** 
- Use markdown formatting: **bolded headings**, bullet points, and numbered steps.
- Avoid filler phrases like “Let me explain” or “Here's an example.”
- Do not label sections with explanation type (e.g., “Analogy explanation”).
- Maintain a clear, confident, and engaging tone—like a strong textbook or hands-on workshop guide.
- Build understanding progressively:
  - What the concept is
  - How it works
  - Where it's used
  - How it connects to things the student already knows

"""

model = Ollama(model="llama3")
manual_prompt = ChatPromptTemplate.from_template(manual_concept_template)
pdf_prompt = ChatPromptTemplate.from_template(pdf_concept_template)

class ExplanationsInput(BaseModel):
    input_type: str
    concept: str = ""
    pdf_path: str = ""
    grade_level: str

def load_pdf_content(pdf_path: str) -> str:
    if not os.path.exists(pdf_path):
        raise FileNotFoundError("PDF file not found.")
    loader = PyPDFLoader(pdf_path)
    documents = loader.load()
    return "\n".join(doc.page_content for doc in documents)

# Function to clean the output from formatting artifacts
import re

def clean_output(text: str) -> str:
    import re

    # Convert **bold** or *bold* to <b>
    text = re.sub(r"\*{1,2}([^\n*]+?)\*{1,2}", r"<b>\1</b>", text)
    text = re.sub(r"^\*+(.+?)\*+\s*$", r"<b>\1</b>", text, flags=re.MULTILINE)

    # Italic formatting for examples/analogies
    text = re.sub(r"_(Example|Analogy):", r"<i>\1:</i>", text)

    # Remove code blocks
    text = re.sub(r"```.*?```", "", text, flags=re.DOTALL)

    # Process lines
    lines = text.split("\n")
    html = []
    in_list = False

    for line in lines:
        stripped = line.strip()

        if re.match(r"^[•\*\-]\s*", stripped):
            content = re.sub(r"^[•\*\-]\s*", "", stripped)
            if not in_list:
                html.append("<ul>")
                in_list = True
            html.append(f"<li>{content}</li>")
        else:
            if in_list:
                html.append("</ul>")
                in_list = False
            html.append(stripped)

    if in_list:
        html.append("</ul>")

    # Join and handle spacing
    html_output = "<br>".join(html)

    # Ensure <b>Section</b> has <br><br> after it
    html_output = re.sub(r"</b>(?!<br><br>)", r"</b><br><br>", html_output)

    # Remove <br> directly inside <ul> or <li>
    html_output = re.sub(r"<ul><br>", "<ul>", html_output)
    html_output = re.sub(r"<br></ul>", "</ul>", html_output)
    html_output = re.sub(r"<li>(.*?)<br></li>", r"<li>\1</li>", html_output)

    # Collapse too many breaks
    html_output = re.sub(r"(<br>\s*){3,}", "<br><br>", html_output)

    return html_output.strip()


    # Join with minimal breaks (1 <br> between items, 2 after headings)
    text = "<br>".join(html_lines)

    # Add 2 breaks after bold headings if not already followed by them
    text = re.sub(r"(</b>)(?!<br><br>)", r"\1<br><br>", text)

    # Collapse extra <br> (3+ in a row)
    text = re.sub(r"(<br>\s*){3,}", "<br><br>", text)

    return text.strip()

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

app = FastAPI()

@app.post("/explanations")
async def explanations_api(
    input_type: str = Form(...),
    concept: str = Form(""),
    pdf_file: UploadFile = File(None),
    grade_level: str = Form(...),
):
    
    try:
        if input_type == "pdf" and not pdf_file:
            raise HTTPException(status_code=400, detail="PDF file required for PDF input_type")

        output = await generate_output(
            input_type=input_type,
            concept=concept,
            pdf_file=pdf_file,
            grade_level=grade_level,
        )

        return {"output": output}
    except Exception as e:
        traceback_str = traceback.format_exc()
        print(traceback_str)
        return JSONResponse(status_code=500, content={"detail": str(e), "trace": traceback_str})

if __name__ == "__main__":
    uvicorn.run("explanations:app", host="127.0.0.1", port=5001, reload=True)