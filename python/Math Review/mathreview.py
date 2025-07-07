from fastapi import FastAPI, HTTPException, Form, Depends
from fastapi.responses import JSONResponse
from pydantic import BaseModel
from langchain_community.llms import Ollama
from langchain_core.prompts import ChatPromptTemplate
from langchain_core.messages import HumanMessage, AIMessage
from typing import Optional
import re, traceback, uvicorn

# --- Prompt Template ---
math_review_template = """
You are a helpful and knowledgeable math tutor.

Your task is to generate a set of clear, well-structured math review problems in numbered word-problem format.

Parameters:
- Grade Level: {grade_level}
- Number of Problems: {number_of_problems}
- Math Content: {math_content}
- Additional Criteria: {additional_criteria}

Grade-Level Guidelines:
(Adjust language, complexity, and structure based on the student's grade level.)

- Kindergarten:
    - Focus on counting, comparison, and shape recognition.
    - Use playful tone, basic vocabulary, and single-step visual problems.
- Grade 1:
    - Use addition and subtraction within 20.
    - Situate problems in familiar contexts (toys, snacks, pets).
    - Keep language simple and problems one-step.
- Grade 2:
    - Include addition/subtraction within 100, early multiplication, and equal sharing.
    - Use relatable, everyday contexts.
- Grade 3:
    - Use multiplication and division within 100, area, perimeter, and simple fractions.
    - Apply to real-world settings (classroom, store, garden).
- Grade 4:
    - Introduce multi-step problems, long division, and basic fraction concepts.
    - Avoid decimals unless specified. Emphasize reasoning and clarity.
- Grade 5:
    - Include operations with fractions and decimals, volume, and multi-step reasoning.
    - Use contexts like recipes, sports, or time management.
- Grade 6:
    - Incorporate ratios, decimals, statistics, and introductory algebra.
    - Emphasize practical reasoning in everyday contexts.
- Grade 7:
    - Use percent, proportions, integers, and 2D geometry.
    - Apply math to real-world tasks (budgets, maps, graphs).
- Grade 8:
    - Focus on linear equations, slope, functions, and the Pythagorean Theorem.
    - Encourage multi-step, real-life, and abstract reasoning.
- High School:
    - Include advanced topics (algebra, functions, geometry proofs, probability, statistics).
    - Apply to both abstract problems and real-world scenarios.
- College/University:
    - Include higher-level math (calculus, statistics, linear algebra, discrete math).
    - Use technical vocabulary and academic or industry-based applications.

Math Content Adaptation:
(Adjust formatting and context based on the specified math topic.)

- Division:
    - Use fair-sharing or equal-grouping contexts (bags, boxes, teams).
    - Avoid multi-step or remainder-based problems unless suitable for the grade level.
- Area/Geometry:
    - Use real-world objects with dimensions (gardens, rooms, tiles).
    - Always specify units and what is being measured or covered.
- Fractions:
    - Use relatable examples (pizza, cake, clocks).
    - Emphasize part-whole reasoning and equal sharing.
- Algebra/Equations:
    - Use real-world variables, unknowns, and simple expressions.
    - Provide context that allows students to form and solve equations.
- Data/Statistics:
    - Use number sets or visual data (charts, tables).
    - Ask for interpretation (average, mode, comparisons, trends).

General Instructions:
- Incorporate all provided Additional Criteria into the design of the problems.
- Vary problem types, numerical values, and contexts to build conceptual flexibility.
- When using objects or physical units (e.g., tiles, planks), clearly state size, quantity, and coverage.
- Use consistent and grade-appropriate units (metric or imperial) based on context.
- Include conversions or approximations (e.g., 12 inches = 1 foot, π ≈ 3.14) where necessary.
- Use multi-step reasoning only when developmentally appropriate.
- Keep language clear, concise, and suitable for the grade level.
- Do not include any labels, titles, headers, solutions, hints, or explanations—only the numbered problems.
"""


# --- LangChain & FastAPI Setup ---
app = FastAPI(debug=True)
model = Ollama(model="llama3")
math_prompt = ChatPromptTemplate.from_template(math_review_template)

# --- Output Cleaner ---
def clean_output(text: str) -> str:
    text = re.sub(r"\*\*(.*?)\*\*", r"\1", text)
    text = re.sub(r"\*(.*?)\*", r"\1", text)
    text = re.sub(r"^\s*[\*\-]\s*", "", text, flags=re.MULTILINE)
    return text.strip()

# --- Pydantic Model ---
class MathReviewFormInput(BaseModel):
    grade_level: str
    number_of_problems: int
    math_content: str
    additional_criteria: Optional[str] = ""

    @classmethod
    def as_form(
        cls,
        grade_level: str = Form(...),
        number_of_problems: int = Form(...),
        math_content: str = Form(...),
        additional_criteria: Optional[str] = Form(default="")
    ):
        return cls(
            grade_level=grade_level,
            number_of_problems=number_of_problems,
            math_content=math_content,
            additional_criteria=additional_criteria
        )

# --- Core Function ---
async def generate_math_review(
    grade_level: str,
    number_of_problems: int,
    math_content: str,
    additional_criteria: str = "",
):
    prompt_input = {
        "grade_level": grade_level,
        "number_of_problems": number_of_problems,
        "math_content": math_content,
        "additional_criteria": additional_criteria
    }
    chain = math_prompt | model
    result = chain.invoke(prompt_input)
    return clean_output(result)

# --- API Endpoint ---
@app.post("/mathreview")
async def math_review_api(form_data: MathReviewFormInput = Depends(MathReviewFormInput.as_form)):
    try:
        output = await generate_math_review(
            grade_level=form_data.grade_level,
            number_of_problems=form_data.number_of_problems,
            math_content=form_data.math_content,
            additional_criteria=form_data.additional_criteria
        )
        return {"output": output}
    except Exception as e:
        traceback_str = traceback.format_exc()
        print(traceback_str)
        return JSONResponse(status_code=500, content={"detail": str(e), "trace": traceback_str})

# --- Local Entry Point ---
if __name__ == "__main__":
    uvicorn.run("mathreview:app", host="127.0.0.1", port=5001, reload=True)
