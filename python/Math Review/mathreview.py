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

# --- Prompt Template ---
math_review_template = """
You are an expert math educator and problem designer. Your goal is to create engaging, clear, and precisely leveled math review problems.

Your output must consist *only* of a numbered list of word problems, with no introductory text, titles, explanations, or solutions.

Parameters:
- Grade Level: {grade_level}
- Number of Problems: {number_of_problems}
- Math Content: {math_content}
- Additional Criteria: {additional_criteria}

General Instructions:
- DO NOT include any labels, titles, headers, solutions, hints, or explanations.
- Incorporate all provided Additional Criteria into the design of the problems.
- Vary problem types, numerical values, and contexts to build conceptual flexibility.
- When using objects or physical units (e.g., tiles, planks), clearly state size, quantity, and coverage.
- Use consistent and grade-appropriate units (metric or imperial) based on context.
- Include conversions or approximations (e.g., 12 inches = 1 foot, π ≈ 3.14) where necessary.
- Use superscript characters like ² or ³ for powers (e.g., area in cm², volume in m³).
- Use multi-step reasoning only when developmentally appropriate.
- Keep language clear, concise, and suitable for the grade level.
- Do not include any labels, titles, headers, solutions, hints, or explanations—only the numbered problems.

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

- Arithmetic (Addition, Subtraction, Multiplication, Division):
    - Frame problems around combining, separating, scaling, or sharing.
    - Specify if remainders are expected or if answers should be whole numbers/decimals.
- Fractions/Decimals/Percentages:
    - Use relatable quantities (food, money, measurements).
    - Clearly indicate if answers should be simplified, converted, or rounded.
    - For percentages, specify if it's a part of a whole, increase/decrease, or finding the original amount.
- Algebra/Equations/Expressions:
    - Introduce clear unknowns or variables in context.
    - For expressions, ask for simplification or evaluation.
    - For equations, provide a scenario that naturally leads to forming an equation to solve for an unknown.
    - For systems, ensure two distinct unknowns requiring multiple equations.
- Geometry/Measurement (Area, Perimeter, Volume, Angles):
    - Clearly define the shape(s) and relevant dimensions.
    - Specify units of measure and the unit expected for the answer.
    - Provide any necessary constants (e.g., $\pi \approx 3.14$ or use $\pi$).
    - For composite shapes, explicitly state how they are combined.
- Data/Statistics/Probability:
    - Provide raw data, a scenario for data collection, or a visual representation (describe it if you can't draw it).
    - Clearly define the statistical measure requested (mean, median, mode, range, probability, etc.).
    - For probability, clearly define the sample space and events.
- Powers and Exponents:
    - Integrate naturally into problems involving growth, repeated multiplication (e.g., volume of cubes, exponential growth/decay, scientific notation, binary systems).
    - Ensure the context makes the use of exponents logical.

"""

# --- FastAPI App Initialization ---
app = FastAPI(debug=True)
app.include_router(chat_router)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # or Laravel origin like "http://localhost:8000"
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# --- Pydantic Model ---
class MathReviewFormInput(BaseModel):
    user_id: int
    grade_level: str
    number_of_problems: int
    math_content: str
    additional_criteria: Optional[str] = ""

    @classmethod
    def as_form(
        cls,
        user_id: int = Form(...),
        grade_level: str = Form(...),
        number_of_problems: int = Form(...),
        math_content: str = Form(...),
        additional_criteria: Optional[str] = Form(default="")
    ):
        return cls(
            user_id=user_id,
            grade_level=grade_level,
            number_of_problems=number_of_problems,
            math_content=math_content,
            additional_criteria=additional_criteria
        )

# --- LangChain & FastAPI Setup ---
model = Ollama(model="llama3")
math_prompt = ChatPromptTemplate.from_template(math_review_template)

# --- Output Cleaner ---
def clean_output(text: str) -> str:
    return text.strip()

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
        scope_vars = {
            "grade_level": form_data.grade_level,
            "number_of_problems": form_data.number_of_problems,
            "math_content": form_data.math_content,
            "additional_criteria": form_data.additional_criteria
        }

        topic = form_data.math_content

        session_id = create_session_and_parameter_inputs(
            user_id=form_data.user_id,
            agent_id=22,
            scope_vars=scope_vars,
            human_topic=form_data.math_content,
            ai_output=output
        )
        return {"output": output, "message_id": session_id}
    except Exception as e:
        traceback_str = traceback.format_exc()
        print(traceback_str)
        return JSONResponse(status_code=500, content={"detail": str(e), "trace": traceback_str})

# --- Local Entry Point ---
if __name__ == "__main__":
    uvicorn.run("mathreview:app", host="127.0.0.1", port=5001, reload=True)
