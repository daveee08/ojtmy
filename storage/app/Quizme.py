from fastapi import FastAPI
from pydantic import BaseModel, ConfigDict
from langchain_ollama import OllamaLLM
from langchain.prompts import PromptTemplate
# from langchain.chains import LLMChain # Commenting out for RunnableSequence
from fpdf import FPDF
from io import BytesIO
from fastapi.responses import StreamingResponse
from langchain_community.tools import DuckDuckGoSearchRun
from langchain.agents import initialize_agent, AgentType
import logging
import json
import re # Add this import for regular expressions

# Configure logging
logging.basicConfig(level=logging.INFO, format='%(asctime)s - %(levelname)s - %(message)s')


class Question(BaseModel):
    question_text: str
    options: dict[str, str]
    correct_answer: str

class QuizResponse(BaseModel):
    quiz: list[Question]
    resources: str


class QuizRequest(BaseModel):
    model_config = ConfigDict(extra='ignore')
    topic: str
    grade_level: str
    num_questions: int = 5

class PdfRequest(BaseModel):
    content: str
    filename: str

class AnswerEvaluationRequest(BaseModel):
    user_answer: str
    question_text: str
    options: dict[str, str]
    correct_answer: str
    topic: str
    grade_level: str

class ChatRequest(BaseModel):
    user_query: str
    topic: str # The original quiz topic for context
    grade_level: str

app = FastAPI()

# Initialize the search tool
search = DuckDuckGoSearchRun()

@app.get("/")
async def read_root():
    return {"message": "Quizme API is running!"}

@app.post("/generate-quiz")
async def generate_quiz(request: QuizRequest):
    logging.info(f"Generating quiz and resources for topic: {request.topic}, grade: {request.grade_level}, questions: {request.num_questions}")
    try:
        llm = OllamaLLM(model="gemma:2b")

        # Perform web search for the topic
        search_query = f"{request.topic} facts"
        logging.info(f"Performing web search for: {search_query}")
        search_results = search.run(search_query)
        logging.info(f"Search results: Length: {len(search_results)} characters.")

        # Determine the context to pass to the LLM
        if not search_results or search_results == "No good DuckDuckGo Search Result was found":
            content_context = f"No specific search results were provided. Please use your extensive general knowledge about {request.topic} to generate the content."
        else:
            content_context = search_results

        # Prompt for Quiz Generation
        quiz_prompt_template = PromptTemplate(
            input_variables=["topic", "grade_level", "num_questions", "content_context"],
            template="""
            You are an Expert helpful AI assistant that creates quizzes.
            Generate **exactly** {num_questions} multiple-choice questions about **{topic}** for a {grade_level} student.
            DO NOT include any introductory sentences or conversational text before the quiz questions. Start directly with "Question 1:".
            The questions MUST be appropriate for the {grade_level}, varying in complexity based on whether it's Pre-K, Kindergarten, a specific grade (1st-12th), University, a specific college year (1st-4th Year College), Adult, or Professional Staff.

            Here is the information to use for quiz generation: {content_context}

            Each question should have 4 options (A, B, C, D) and clearly indicate the correct answer.
            Format the output as follows (ensure you provide **exactly** {num_questions} questions and their options, no more, no less, and no additional conversational text):

            Question 1: [Question text]
            Options:
            A) [Option A]
            B) [Option B]
            C) [Option C]
            D) [Option D]

            Question 2: [Question text]
            Options:
            A) [Option A]
            B) [Option B]
            C) [Option C]
            D) [Option D]
            ...
            """
        )
        # quiz_chain = LLMChain(llm=llm, prompt=quiz_prompt_template) # Deprecated
        # quiz_output = quiz_chain.run(topic=request.topic, grade_level=request.grade_level, num_questions=request.num_questions, search_results=search_results) # Deprecated
        quiz_output_raw = (quiz_prompt_template | llm).invoke({'topic': request.topic, 'grade_level': request.grade_level, 'num_questions': request.num_questions, 'content_context': content_context})
        
        try:
            # Parse the raw text output from the LLM into structured Question objects
            quiz_validated = parse_quiz_text(quiz_output_raw)
            if not quiz_validated:
                logging.error(f"Parsing resulted in an empty or invalid quiz. Raw output: {quiz_output_raw}")
                raise ValueError("Parsing resulted in an empty or invalid quiz.")
        except Exception as e:
            logging.error(f"Failed to parse quiz text: {e}. Raw output: {quiz_output_raw}")
            raise ValueError("AI did not return a parsable quiz structure.") from e

        # Generate correct answers for each question separately
        correct_answer_prompt_template = PromptTemplate(
            input_variables=["question_text", "options", "topic", "grade_level"],
            template="""
            You are an Expert helpful AI assistant. Given the following question and options, identify the correct answer.
            Question: {question_text}
            Options: {options}
            Topic: {topic}
            Grade Level: {grade_level}
            
            IMPORTANT: Provide ONLY the single correct option letter (A, B, C, or D). Do NOT provide any other text, explanations, or punctuation. Do NOT include any prefix like "Correct Answer: " or "The answer is". Your response MUST be a single character, one of 'A', 'B', 'C', or 'D'.
            For example, if the correct answer is option B, your response should be exactly: B
            """
        )

        for question in quiz_validated:
            try:
                correct_answer_raw = (correct_answer_prompt_template | llm).invoke({
                    'question_text': question.question_text,
                    'options': question.options,
                    'topic': request.topic,
                    'grade_level': request.grade_level
                }).strip()

                logging.info(f"Raw LLM output for correct answer: '{correct_answer_raw}'")

                if not correct_answer_raw:
                    logging.warning(f"Raw LLM output for correct answer was empty for question: {question.question_text}. Setting to default ''.")
                    question.correct_answer = "" # Set to empty if raw output is empty
                else:
                    # More lenient extraction: search for the first occurrence of A-D anywhere
                    correct_answer_match = re.search(r'([A-D])', correct_answer_raw, re.IGNORECASE)
                    if correct_answer_match:
                        question.correct_answer = correct_answer_match.group(1).upper() # Ensure it's uppercase
                        logging.info(f"Successfully extracted correct answer: {question.correct_answer} for question: {question.question_text}")
                    else:
                        logging.warning(f"AI returned unparsable correct answer: '{correct_answer_raw}' for question: {question.question_text}. Setting to default ''.")
                        question.correct_answer = "" # Set to empty if unparsable

            except Exception as e:
                logging.error(f"Error generating correct answer for question '{question.question_text}': {e}")
                question.correct_answer = "" # Set to empty on error

        # Prompt for Resource Generation
        resource_prompt_template = PromptTemplate(
            input_variables=["topic", "grade_level", "content_context"],
            template="""
            You are an Expert helpful AI assistant that provides study resources.
            Provide a concise summary or key points (200-300 words) about **{topic}** for a {grade_level} student.
            DO NOT include any introductory sentences or conversational text before the summary. Start directly with the summary content.
            The summary MUST be appropriate for the {grade_level}, varying in complexity and depth based on whether it's Pre-K, Kindergarten, a specific grade (1st-12th), University, a specific college year (1st-4th Year College), Adult, or Professional Staff.

            Here is the information to use for study resources: {content_context}
            """
        )
        # resource_chain = LLMChain(llm=llm, prompt=resource_prompt_template) # Deprecated
        # resource_output = resource_chain.run(topic=request.topic, grade_level=request.grade_level, search_results=search_results) # Deprecated
        resource_output = (resource_prompt_template | llm).invoke({'topic': request.topic, 'grade_level': request.grade_level, 'content_context': content_context})

        logging.info("Quiz and resources generated successfully.")
        return {"quiz": quiz_validated, "resources": resource_output}
    except Exception as e:
        logging.error(f"Error generating quiz: {e}")
        return {"error": str(e)}

def parse_quiz_text(quiz_text: str) -> list[Question]:
    questions = []
    logging.info(f"Attempting to parse raw quiz text (length {len(quiz_text)}):\n{quiz_text[:500]}...")
    
    # This regex is designed to capture a question block with question text and options A-D.
    # It's more lenient with `.*?` (non-greedy) and `\s*` to match across lines and varying whitespace.
    # It no longer expects the 'Correct Answer:' line in this initial parsing.
    question_pattern = re.compile(
        r'Question \d+:\s*(.*?)\s*\n'  # Question number and text
        r'(?:Options:\s*\n)?' # Optional "Options:" header, non-capturing group
        r'A\)\s*(.*?)\s*\n'    # Option A
        r'B\)\s*(.*?)\s*\n'    # Option B
        r'C\)\s*(.*?)\s*\n'    # Option C
        r'D\)\s*(.*?)(?:\s*\n|$)',    # Option D, ends at line or string end, or just whitespace
        re.DOTALL | re.MULTILINE
    )

    for match in question_pattern.finditer(quiz_text):
        try:
            logging.info(f"Found potential question match. Groups: {match.groups()}")
            # Extracting captured groups
            question_text = match.group(1).strip()
            option_a = match.group(2).strip()
            option_b = match.group(3).strip()
            option_c = match.group(4).strip()
            option_d = match.group(5).strip()

            options = {
                "A": option_a,
                "B": option_b,
                "C": option_c,
                "D": option_d
            }

            # Validate that extracted parts are not empty
            if not (question_text and all(options.values())): # Check if all option values are non-empty
                logging.warning(f"Skipping malformed question block due to empty components: {match.group(0)}")
                continue

            # Temporarily set correct_answer to an empty string, will be populated later
            questions.append(Question(question_text=question_text, options=options, correct_answer=""))
        except IndexError as e:
            logging.warning(f"Malformed question block found (IndexError), skipping: {match.group(0)}. Error: {e}")
            continue # Skip this block if it's malformed
        except Exception as e:
            logging.error(f"An unexpected error occurred during parsing a question block: {match.group(0)}. Error: {e}")
            continue

    logging.info(f"Finished parsing. Parsed {len(questions)} questions.")
    return questions

@app.post("/evaluate-answer")
async def evaluate_answer(request: AnswerEvaluationRequest):
    logging.info(f"Evaluating answer for question: {request.question_text}")
    logging.info(f"Received for evaluation: User Answer='{request.user_answer}', Correct Answer='{request.correct_answer}', Topic='{request.topic}', Grade Level='{request.grade_level}'")
    logging.info(f"Correct Answer received by evaluate_answer: '{request.correct_answer}'")
    logging.info(f"Options: {request.options}")
    try:
        # Ensure correct_answer is not empty
        if not request.correct_answer:
            logging.error(f"Correct answer is empty for question: {request.question_text}")
            return {"error": "Correct answer not available for evaluation.", "details": "AI failed to provide a valid correct answer during quiz generation."}

        llm = OllamaLLM(model="gemma:2b") # Using gemma:2b for evaluation as well

        # Determine the actual text of the correct answer option
        correct_answer_option_text = request.options.get(request.correct_answer.upper(), "").strip()
        if not correct_answer_option_text:
            logging.error(f"Correct answer option text not found for {request.correct_answer} in options: {request.options}")
            return {"error": "Correct answer option text not found.", "details": "The option for the correct answer letter was empty or missing."}

        user_answer_cleaned = request.user_answer.strip().upper()
        correct_answer_cleaned = request.correct_answer.strip().upper()

        if user_answer_cleaned == correct_answer_cleaned:
            feedback_template = PromptTemplate(
                input_variables=[],
                template="Your answer is correct! Excellent work. NO OTHER TEXT OR EXPLANATION. JUST THIS SENTENCE."
            )
            evaluation_output = (feedback_template | llm).invoke({})
        else:
            feedback_template = PromptTemplate(
                input_variables=["correct_answer_option_text", "topic", "grade_level"],
                template="""
                Your answer is incorrect. The correct answer is '{correct_answer_option_text}'. 
                Explain concisely (2-3 sentences) why '{correct_answer_option_text}' is the correct answer for the given topic ' {topic} ' and grade level ' {grade_level} '. Focus ONLY on explaining the correct answer. Do NOT mention the user's submitted answer or other options. Do NOT use markdown like **bold** in your explanation. Do NOT include any introductory or concluding conversational phrases, such as 'Thank you' or 'Is there anything else I can do?'. Just provide the explanation directly.
                """
            )
            evaluation_output = (feedback_template | llm).invoke({
                'correct_answer_option_text': correct_answer_option_text,
                'topic': request.topic,
                'grade_level': request.grade_level
            })

        logging.info("Answer evaluation completed.")
        return {"feedback": evaluation_output}
    except Exception as e:
        logging.error(f"Error evaluating answer: {e}")
        return {"error": str(e)}

@app.post("/chat")
async def chat_with_ai(request: ChatRequest):
    logging.info(f"Received chat request for user_query: {request.user_query}, topic: {request.topic}, grade: {request.grade_level}")
    try:
        llm = OllamaLLM(model="gemma:2b") # Using gemma:2b for chat

        # Use DuckDuckGo search for more informed answers based directly on user_query
        search_query = request.user_query

        logging.info(f"Performing web search for chat query: {search_query}")
        search_results = search.run(search_query)
        logging.info(f"Chat search results length: {len(search_results)} characters.")

        if not search_results or search_results == "No good DuckDuckGo Search Result was found":
            content_context = f"No specific search results were provided. Use your general knowledge to answer the question about {request.user_query}."
        else:
            content_context = f"Here is some relevant information: {search_results}"

        chat_prompt_template = PromptTemplate(
            input_variables=["user_query", "topic", "grade_level", "content_context"],
            template="""
            You are an Expert helpful AI assistant for a quiz application. You are currently in a conversational mode.
            You asked: {user_query}
            The current quiz topic is: {topic}
            The target grade level for the quiz is: {grade_level}

            Here is some additional context or search results: {content_context}

            Please provide a concise and helpful answer to your question. Keep the response suitable for the specified grade level and relevant to the topic. If you can use the provided context, please do so.
            Do NOT provide any introductory phrases or conversational filler, just the direct answer. If you are unsure, politely state that you cannot answer.
            """
        )

        chat_output = (chat_prompt_template | llm).invoke({
            'user_query': request.user_query,
            'topic': request.topic,
            'grade_level': request.grade_level,
            'content_context': content_context
        })

        logging.info("Chat response generated successfully.")
        return {"response": chat_output}
    except Exception as e:
        logging.error(f"Error during chat: {e}")
        return {"error": str(e)}

@app.post("/generate-pdf")
async def generate_pdf(request: PdfRequest):
    logging.info(f"Generating PDF for file: {request.filename}.pdf")
    try:
        pdf = FPDF()
        pdf.add_page()
        pdf.set_font("Arial", size=12)
        pdf.multi_cell(0, 10, request.content.encode('latin-1', 'replace').decode('latin-1')) # Encode/decode to handle various characters

        buffer = BytesIO()
        pdf.output(buffer, 'S') # 'S' for string output
        buffer.seek(0)

        logging.info(f"PDF generated successfully: {request.filename}.pdf")
        return StreamingResponse(buffer, media_type="application/pdf", headers={
            "Content-Disposition": f"attachment; filename={request.filename}.pdf"
        })
    except Exception as e:
        logging.error(f"Error generating PDF: {e}")
        return {"error": str(e)}
