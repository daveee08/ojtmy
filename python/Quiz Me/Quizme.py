from fastapi import FastAPI, HTTPException
from pydantic import BaseModel, Field
from typing import List, Optional
import uuid
import logging

app = FastAPI()

# Setup basic logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

# In-memory store for quiz sessions (for demonstration, use a database in production)
quiz_sessions = {}

class QuizRequest(BaseModel):
    session_id: str = Field(default_factory=lambda: str(uuid.uuid4()))
    topic: str
    grade_level: str
    num_questions: Optional[int] = Field(None, ge=1, le=50)
    quiz_types: List[str] # This correctly expects a list of strings!

# A simplified model for your generated questions
class Question(BaseModel):
    question: str
    type: str # e.g., 'multiple_choice', 'fill_in_the_blanks', 'identification'
    options: Optional[List[str]] = None # Only for multiple_choice
    answer: str

class QuizResponse(BaseModel):
    message: str
    session_id: str
    questions: List[Question]

@app.post("/quizme", response_model=QuizResponse)
async def generate_quiz(request: QuizRequest):
    logger.info(f"Received quiz generation request: {request.dict()}")

    # Convert the list of quiz types into a human-readable string for the AI prompt
    # THIS IS THE CRUCIAL PART TO AVOID "ARRAY TO STRING CONVERSION" IN PYTHON
    if request.quiz_types:
        if len(request.quiz_types) > 1:
            quiz_types_str = ", ".join(request.quiz_types[:-1]) + " and " + request.quiz_types[-1]
        else:
            quiz_types_str = request.quiz_types[0]
    else:
        quiz_types_str = "general knowledge" # Default if somehow empty (though Laravel's 'required' handles this)

    # Construct the prompt for your AI model
    prompt = f"Generate a quiz about '{request.topic}' for a '{request.grade_level}' student. "
    prompt += f"The quiz should include questions of the following types: {quiz_types_str}. "
    if request.num_questions:
        prompt += f"Generate exactly {request.num_questions} questions."
    else:
        prompt += "Generate a suitable number of questions."

    logger.info(f"Generated AI prompt: {prompt}")

    # --- Your existing logic to call the actual AI (e.g., OpenAI, Gemini) goes here ---
    # For demonstration, let's return dummy questions
    try:
        # Replace this with your actual AI call
        # ai_response = call_your_ai_model(prompt)
        # generated_questions_from_ai = parse_ai_response(ai_response)

        # Dummy data for testing:
        generated_questions_from_ai = [
            {"question": "What is the capital of France?", "type": "multiple_choice", "options": ["Berlin", "Madrid", "Paris", "Rome"], "answer": "Paris"},
            {"question": "The chemical symbol for water is __.", "type": "fill_in_the_blanks", "answer": "H2O"},
            {"question": "Name the largest planet in our solar system.", "type": "identification", "answer": "Jupiter"},
        ]
        if request.num_questions and len(generated_questions_from_ai) > request.num_questions:
            generated_questions_from_ai = generated_questions_from_ai[:request.num_questions]
        # End of dummy data

        # Store the generated questions in the session
        quiz_sessions[request.session_id] = [
            Question(**q) for q in generated_questions_from_ai
        ]
        logger.info(f"Quiz generated for session {request.session_id} with {len(quiz_sessions[request.session_id])} questions.")

        return QuizResponse(
            message="Quiz questions generated successfully.",
            session_id=request.session_id,
            questions=quiz_sessions[request.session_id]
        )
    except Exception as e:
        logger.error(f"Error calling AI model: {e}")
        raise HTTPException(status_code=500, detail=f"Failed to generate quiz questions from AI: {e}")


# Endpoint to check individual answers (as per your Laravel controller's logic)
class AnswerCheckRequest(BaseModel):
    session_id: str
    question_index: int
    user_answer: Optional[str] = None # Allow null for multiple choice where value might be empty string
    # You might also want to send the question type and correct answer from the frontend for double-check,
    # but for security and consistency, it's better to fetch from stored quiz_data.
    # For now, let's assume the question_index is enough to retrieve stored correct answer.

@app.post("/quizme/check-answer")
async def check_answer(request: AnswerCheckRequest):
    logger.info(f"Received answer check request: {request.dict()}")

    quiz_data = quiz_sessions.get(request.session_id)
    if not quiz_data or request.question_index >= len(quiz_data):
        logger.warning(f"Invalid session ID or question index for answer check: {request.session_id}, {request.question_index}")
        raise HTTPException(status_code=404, detail="Quiz session expired or invalid question.")

    current_question = quiz_data[request.question_index]

    normalized_user_answer = request.user_answer.strip().lower() if request.user_answer else ""
    normalized_correct_answer = current_question.answer.strip().lower()

    is_correct = normalized_user_answer == normalized_correct_answer
    feedback = "Correct!" if is_correct else f"Incorrect. The correct answer was: {current_question.answer}"

    # You could optionally update the stored quiz_data in the session with user's answer and correctness
    # For now, we just return the feedback.

    return {
        "feedback": feedback,
        "is_correct": is_correct,
        "correct_answer": current_question.answer # Sending correct answer back for display
    }