import os
import uvicorn
from fastapi import FastAPI, HTTPException, Form, File, UploadFile
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel, ValidationError
from langchain_ollama import ChatOllama
from langchain_core.prompts import ChatPromptTemplate
from langchain_core.messages import SystemMessage, HumanMessage
from langchain_core.output_parsers import StrOutputParser
import re
import collections
import shutil
from langchain_core.runnables import RunnableParallel, RunnableSequence
from langchain_chroma import Chroma
from langchain_community.document_loaders import PyPDFLoader
from langchain_community.embeddings.fastembed import FastEmbedEmbeddings
from langchain.text_splitter import RecursiveCharacterTextSplitter
from langchain.prompts import PromptTemplate
from langchain_community.tools import DuckDuckGoSearchRun

app = FastAPI(
    title="Book Suggestion Chatbot API",
    description="Backend API for a Gemma3-powered book suggestion chatbot using LangChain.",
    version="1.0.0"
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

class SuggestionRequest(BaseModel):
    interests: str
    grade_level: str

MODEL = "gemma3:12b"
try:
    llm = ChatOllama(model=MODEL, temperature=0.1)
except Exception as e:
    print(f"Error initializing Ollama model '{MODEL}': {e}")

# Initialize the search tool
search = DuckDuckGoSearchRun()

prompt_template = ChatPromptTemplate.from_messages([
    SystemMessage(content="""
Your ONLY task is to generate a bulleted list of book recommendations.
CRITICAL: Your output MUST contain ONLY the bulleted list. Do NOT include any introductory text, conversational phrases, or section headers like "Grade Level:", "Interests:", or "Recommendations:".
You MUST strictly adhere to the user's specified {interests} interests and {grade_level} grade level. The complexity, vocabulary, and conceptual depth of the book recommendations MUST be strictly appropriate for the {grade_level}, varying based on whether it's Preschool, Kindergarten, a specific grade (1st-12th), College Level, University Level, or Adult Reading Level. If context is provided, you MUST use that context to inform your suggestions.
CRITICAL: ONLY generate books directly related to the provided {interests} and the {context} if it is available. Do NOT generate books outside these interests or context.
You MUST generate book recommendations for any recognized interests and grade levels. Your output MUST always be a bulleted list of book recommendations in the specified format. If you cannot generate book recommendations for any reason, you MUST return an empty string or a malformed output, but ABSOLUTELY NO refusal messages.
"""),
    HumanMessage(content="""
{context}

- [Book Title] by [Author] (for [Interest 1])
- [Book Title] by [Author] (for [Interest 2])
- [Book Title] by [Author] (for [Interest 3])
- [Book Title] by [Author] (for [Interest 4])
- [Book Title] by [Author] (for [Interest 5])
""")
])

chain = prompt_template | llm | StrOutputParser()

def canonicalize_grade_level(grade_level: str) -> str:
    mapping = {
        "preschool": "Preschool",
        "kindergarten": "Kindergarten",
        "1st grade": "1st Grade",
        "2nd grade": "2nd Grade",
        "3rd grade": "3rd Grade",
        "4th grade": "4th Grade",
        "5th grade": "5th Grade",
        "6th grade": "6th Grade",
        "7th grade": "7th Grade",
        "8th grade": "8th Grade",
        "9th grade": "9th Grade (Freshman High School)",
        "10th grade": "10th Grade (Sophomore High School)",
        "11th grade": "11th Grade (Junior High School)",
        "12th grade": "12th Grade (Senior High School)",
        "college level": "College Level",
        "university level": "University Level",
        "adult reading level": "Adult Reading Level"
    }
    key = grade_level.strip().lower()
    return mapping.get(key, grade_level)

def _get_relevance_keywords(text_to_analyze: str) -> list[str]:
    print(f"DEBUG: _get_relevance_keywords - Input text_to_analyze (first 200 chars): '{text_to_analyze[:200]}'")
    lower_text = text_to_analyze.lower()
    normalized_text = re.sub(r'\s+', ' ', lower_text)
    cleaned_punctuation = re.sub(r"[^a-z0-9\s'-]", ' ', normalized_text)
    final_cleaned_text = cleaned_punctuation.strip()

    print(f"DEBUG: Final Cleaned Text (before target phrase extraction): '{final_cleaned_text}'")

    target_phrases_regex = [
        r"search the following books\s*:",
        r"objectives\s*:",
        r"key terms\s*:",
        r"topics\s*:",
        r"keywords\s*:",
        r"relevant information from pdf\s*:",
        r"relevant information from the pdf\s*:",
    ]

    content_after_target_phrase = ""
    for phrase_pattern in target_phrases_regex:
        match = re.search(phrase_pattern, normalized_text)
        if match:
            content_after_target_phrase = normalized_text[match.end():].strip()
            cleaned_content_after_phrase = re.sub(r"[^a-z0-9\s'-]", ' ', content_after_target_phrase)
            cleaned_content_after_phrase = re.sub(r'\s+', ' ', cleaned_content_after_phrase).strip()
            print(f"DEBUG: Extracted & Cleaned content after target phrase regex '{phrase_pattern}': '{cleaned_content_after_phrase}'")
            final_cleaned_text = cleaned_content_after_phrase
            break

    text_for_keyword_extraction = final_cleaned_text

    specific_terms_and_phrases = [
        "elf main character", "knight and a princess", "clown and a king", "drawing and cookbook",
        "adventurous journey", "fell in love", "in love", "love story",
        "science fiction", "magical realism", "literary fiction", "contemporary fiction",
        "women's fiction", "true crime", "graphic novel", "short story", "young adult",
        "food and drink", "guide books", "world war", "historical fiction", "family saga",
        "psychological thriller", "space opera", "military sci-fi", "post-apocalyptic",
        "gothic horror", "supernatural horror", "investigative true crime", "epic fantasy",
        "high fantasy", "historical fantasy", "techno-thriller", "dystopian", "cyberpunk",
        "time travel", "social sci-fi", "character study", "character-driven", "philosophical fiction",
        "classic literature", "humorous memoir", "beat generation", "child development",
        "mindful parenting", "forensic psychology", "satirical novel", "interactive humor",
        "animals", "robots", "poetry", "picture books", "fantasy", "action", "adventure",
        "mystery", "horror", "thriller", "history", "romance", "lgbtq", "comedy", "humor",
        "science", "technology", "parenting", "family", "biography", "self-help", "travel",
        "magic", "dragon", "vampire", "werewolf", "wizard", "witch", "space", "alien", "futuristic",
        "detective", "crime", "ghost", "quest", "epic", "memoir", "art", "photography", "essay",
        "religion", "spirituality", "novel", "children's", "elf", "knight",
        "computer programming", "software development", "data science", "machine learning", "artificial intelligence",
        "web development", "cybersecurity", "network engineering", "algorithms and data structures",
        "operating systems", "software engineering principles", "computer architecture", "database management",
        "cloud computing", "mobile app development", "game design", "ethical hacking", "robotics engineering",
        "astrophysics", "quantum physics", "organic chemistry", "molecular biology", "genetics and genomics",
        "neuroscience research", "environmental studies", "mathematical analysis", "statistical inference",
        "calculus concepts", "linear algebra", "geometry theorems", "macroeconomics", "financial markets",
        "business management", "entrepreneurial strategies", "marketing principles", "organizational behavior",
        "cognitive psychology", "sociological theory", "philosophy of mind", "applied ethics", "political philosophy",
        "cultural anthropology", "historical linguistics", "investigative journalism", "creative writing techniques",
        "literary criticism", "art history periods", "music theory and composition", "mechanical engineering design",
        "electrical engineering fundamentals", "civil engineering structures", "biomedical engineering innovations",
        "aerospace engineering systems", "medical diagnostics", "nursing practice", "public health policy",
        "human anatomy", "human physiology", "clinical nutrition", "physical fitness", "personal finance management",
        "investment strategies", "career development skills", "culinary arts", "baking techniques", "gardening tips",
        "diy projects", "survival skills", "wilderness exploration", "mountaineering guides", "sailing adventures",
        "forensic investigation", "criminal justice system", "ancient civilizations", "world war history",
        "european history", "american history", "philosophy of religion", "existentialist philosophy",
        "critical thinking skills", "architectural design", "urban planning strategies", "graphic design principles",
        "leadership development", "teamwork strategies", "communication skills", "problem solving techniques",
        "mental health awareness", "mindfulness practices", "stress management techniques", "emotional intelligence development",
        "artist development", "enhance skills", "improve skills", "skill enhancement",
        "how to draw", "how to paint", "become an artist", "art fundamentals",
        "art techniques", "color theory", "perspective drawing", "character design",
        "concept art", "fashion design", "interior design", "product design",
        "creative skills", "diy projects", "art history", "fine art", "visual arts",
        "drawing skills", "painting skills", "sculpting skills", "sketching skills",
        "illustration skills", "digital art skills", "crafting skills",
        "drawing", "painting", "sculpting", "sketching", "illustration", "digital art",
        "art", "craft", "artist", "skills", "creative", "design", "illustrate", "hobbies",
        "bible verses", "christian book", "catholic book", "spiritual growth", "religious texts",
        "theology", "scripture", "christianity", "catholicism", "spiritual", "bible", "christian", "catholic", "religion",
        "faith", "devotional", "sermons", "parables", "prophecy", "church history", "apologetics",
        "saints", "philosophy of religion", "comparative religion", "religious studies", "ethics in religion",
        "spiritual journey", "meditation", "mindfulness", "contemplation", "sacred texts", "religious fiction",
        "inspirational books", "devotionals", "prayer", "worship", "discipleship", "evangelism",
        "biblical studies", "new testament", "old testament", "church fathers", "christian living",
        "elf main character", "knight and a princess", "clown and a king", "drawing and cookbook",
        "knight and princess", "fell in love", "in love", "love story"
    ]

    found_keywords = set()
    processed_text_for_words = text_to_analyze.lower() # Use a copy for word extraction

    # Prioritize multi-word phrases
    # Sort by length in descending order to match longer phrases first
    sorted_specific_terms = sorted(specific_terms_and_phrases, key=len, reverse=True)

    for term_or_phrase in sorted_specific_terms:
        # Use word boundary for phrases to avoid partial matches within other words
        # And also ensure it's not a substring of a larger, already identified phrase
        if re.search(r'\b' + re.escape(term_or_phrase) + r'\b', processed_text_for_words):
            found_keywords.add(term_or_phrase)
            # Replace the found phrase with spaces to prevent re-matching parts of it
            # Using regex to replace with spaces to maintain word boundaries for subsequent word splitting
            processed_text_for_words = re.sub(r'\b' + re.escape(term_or_phrase) + r'\b', ' ' * len(term_or_phrase), processed_text_for_words)

    print(f"DEBUG: Found specific phrases: {list(found_keywords)}")

    # Now extract remaining single words
    words = re.findall(r'\b\w+\b', processed_text_for_words)

    filler_words = {
        "a", "an", "and", "are", "as", "at", "be", "but", "by", "for", "if", "in", "into", "is", "it",
        "no", "not", "of", "on", "or", "such", "that", "the", "their", "then", "there", "these",
        "they", "this", "to", "was", "will", "with", "i", "me", "my", "you", "your", "he", "she",
        "him", "her", "us", "them", "what", "where", "when", "why", "how", "which", "who", "whom",
        "whose", "can", "could", "would", "should", "may", "might", "must", "about", "above", "after",
        "again", "against", "all", "am", "any", "around", "away", "back", "bad", "be", "because", "been",
        "before", "being", "below", "between", "both", "down", "during", "each", "few", "from",
        "further", "had", "has", "have", "having", "here", "hers", "herself", "himself", "his",
        "its", "itself", "just", "more", "most", "myself", "nor", "now", "off", "once", "only",
        "other", "our", "ours", "ourselves", "out", "over", "own", "same", "see", "so", "some",
        "than", "theirs", "themselves", "through", "too", "under", "until", "up", "very", "we",
        "were", "while", "yourself", "yourselves", "do", "does", "did", "doing", "done", "go",
        "goes", "went", "gone", "get", "gets", "got", "getting", "gotten", "say", "says", "said",
        "saying", "make", "makes", "made", "making", "shall", "book", "books", "suggest",
        "recommend", "interests", "grade", "level", "following", "search", "objectives",
        "chapter", "introduction", "conclusion", "section", "appendix", "figure", "table", "page",
        "pages", "example", "examples", "review", "summary", "abstract", "content", "documents",
        "document", "information", "topic", "topics", "subject", "subjects", "theme", "themes", "genre", "genres",
        "type", "types", "style", "styles", "series", "volume", "volumes", "edition", "editions", "version",
        "versions", "overview", "summary", "introduction", "conclusion", "abstract", "index", "appendix",
        "figure", "table", "illustration", "illustrations", "image", "images", "photo", "photos", "picture",
        "pictures", "diagram", "diagrams", "chart", "charts", "graph", "graphs", "map", "maps", "data",
        "example", "examples", "review", "reviews", "criticism", "analysis", "study", "research", "theory",
        "theories", "concept", "concepts", "principle", "principles", "method", "methods", "technique",
        "techniques", "approach", "approaches", "system", "systems", "model", "models", "framework",
        "frameworks", "tool", "tools", "application", "applications", "design", "designs", "development",
        "solution", "solutions", "problem", "problems", "issue", "issues", "challenge", "challenges",
        "question", "questions", "answer", "answers", "guide", "guides", "manual", "manuals", "handbook",
        "handbooks", "reference", "references", "source", "sources", "resource", "resources", "material",
        "materials", "lesson", "lessons", "exercise", "exercises", "activity", "activities", "project",
        "projects", "case", "cases", "example", "examples", "scenario", "scenarios", "practice", "practices",
        "best", "good", "new", "old", "main", "key", "important", "relevant", "specific", "general",
        "different", "various", "many", "much", "more", "less", "most", "least", "first", "second",
        "third", "final", "last", "next", "previous", "current", "present", "future", "past", "only",
        "also", "even", "just", "still", "already", "yet", "since", "once", "then", "now", "here",
        "there", "every", "any", "some", "no", "none", "always", "never", "often", "rarely",
        "usually", "normally", "sometimes", "seldom", "always", "never", "ever", "never", "often",
        "rarely", "usually", "normally", "sometimes", "seldom", "usually", "normally", "sometimes",
        "seldom", "every", "any", "some", "no", "none", "always", "never", "often", "rarely",
        "usually", "normally", "sometimes", "seldom", "usually", "normally", "sometimes", "seldom",
        "an", "the", "this", "that", "these", "those", "and", "or", "but", "nor", "so", "yet", "for", "on", "at", "by", "with", "about", "against", "between", "into", "through", "during", "before", "after", "above", "below", "to", "from", "up", "down", "in", "out", "over", "under", "again", "further", "then", "once", "here", "there", "every", "any", "both", "each", "few", "more", "most", "other", "some", "such", "only", "own", "same", "see", "should", "than", "too", "very", "s", "t", "can", "will", "just", "don", "shouldn", "now", "d", "ll", "m", "o", "re", "ve", "y", "ain", "aren", "couldn", "didn", "doesn", "hadn", "hasn", "haven", "isn", "ma", "mightn", "mustn", "needn", "shan", "wasn", "weren", "won", "wouldn",
        "bible verses", "christian book", "catholic book", "spiritual growth", "religious texts",
        "theology", "scripture", "christianity", "catholicism", "spiritual", "bible", "christian", "catholic", "religion",
        "faith", "devotional", "sermons", "parables", "prophecy", "church history", "apologetics",
        "saints", "philosophy of religion", "comparative religion", "religious studies", "ethics in religion",
        "spiritual journey", "meditation", "mindfulness", "contemplation", "sacred texts", "religious fiction",
        "inspirational books", "devotionals", "prayer", "worship", "discipleship", "evangelism",
        "biblical studies", "new testament", "old testament", "church fathers", "christian living",
        "elf main character", "knight and a princess", "clown and a king", "drawing and cookbook",
        "knight and princess", "fell in love", "in love", "love story"
    }

    # Add common conversational words as filler words
    filler_words.update({"recommend", "suggest", "can", "could", "please", "you", "me", "some", "books", "about", "that", "is", "a", "an", "the", "any"})

    filtered_single_words = [word for word in words if word not in filler_words and len(word) > 2]
    print(f"DEBUG: Filtered single words: {list(filtered_single_words)}")

    # Add relevant single words to the found keywords, ensuring no duplicates with existing phrases
    for word in filtered_single_words:
        is_part_of_phrase = False
        for phrase in found_keywords:
            if word in phrase.split(): # Check if the single word is part of any already found phrase
                is_part_of_phrase = True
                break
        if not is_part_of_phrase:
            found_keywords.add(word)

    print(f"DEBUG: Final Keywords: {list(found_keywords)}")
    return list(found_keywords)

def post_process_ai_output(raw_output: str, original_interests: str, grade_level: str, rag_context_for_keywords: str = "") -> str:
    print(f"DEBUG: Starting post-processing. Original Interests: '{original_interests}', Grade Level: '{grade_level}'")
    print(f"DEBUG: Raw AI Output:\n{raw_output}")

    # Extract only the recommendations section from the raw AI output
    # Modified: Expect the AI to ONLY output the recommendations list now.
    recommendations_match = re.search(r"Recommendations:\s*\n(.*)", raw_output, re.DOTALL | re.IGNORECASE)
    
    if not recommendations_match:
        print("DEBUG: No 'Recommendations:' section found in raw output. Applying generic fallback.")
        return get_curated_fallback(original_interests, grade_level, rag_context_for_keywords)

    recommendations_section = recommendations_match.group(1).strip()

    if not recommendations_section:
        print("DEBUG: Recommendations section is empty after header removal. Applying generic fallback.")
        return get_curated_fallback(original_interests, grade_level, rag_context_for_keywords)

    # Parse individual recommendations
    lines = recommendations_section.split('\n')
    parsed_recommendations = []
    for line in lines:
        line = line.strip()
        if line.startswith("- "):
            parsed_recommendations.append(line[2:].strip())

    if not parsed_recommendations:
        print("DEBUG: No valid individual recommendations parsed. Applying generic fallback.")
        return get_curated_fallback(original_interests, grade_level, rag_context_for_keywords)

    # Determine keywords to check based on whether RAG context was used or not
    keywords_to_check = []
    if rag_context_for_keywords:
        print("DEBUG: Using RAG context for keyword extraction for filtering.")
        keywords_to_check = _get_relevance_keywords(rag_context_for_keywords)
    elif original_interests:
        print("DEBUG: Using original interests for keyword extraction for filtering (no RAG context).")
        keywords_to_check = _get_relevance_keywords(original_interests)
    
    print(f"DEBUG: Keywords for relevance check: {keywords_to_check}")

    # Set relevance threshold
    # If RAG context was used OR if a significant number of keywords are extracted from interests, be more lenient.
    # Otherwise, require at least one keyword match.
    relevance_score_threshold = 1 if (rag_context_for_keywords or len(keywords_to_check) > 0) else 0

    # Adjust the threshold more strictly if it's a non-PDF request and keywords are found
    if original_interests.lower() != "document content" and len(keywords_to_check) > 0:
        # If there are specific keywords, require at least 75% of them to be present in a recommendation
        # This makes the filtering stricter for specific user interests
        relevance_score_threshold = max(1, int(len(keywords_to_check) * 0.75))

    print(f"DEBUG: Total Possible Score: {len(keywords_to_check)}, Threshold: {relevance_score_threshold}")

    filtered_recommendations = []
    for rec in parsed_recommendations:
        score = calculate_relevance_score(rec, keywords_to_check)
        print(f"DEBUG: Recommendation: '{rec}', Score: {score}")
        if score >= relevance_score_threshold:
            filtered_recommendations.append(rec)
            print(f"DEBUG: Recommendation '{rec}' PASSED filter.")
        else:
            print(f"DEBUG: Recommendation '{rec}' FAILED filter (Score: {score} < Threshold: {relevance_score_threshold}).")

    if not filtered_recommendations:
        print("DEBUG: All recommendations filtered out or no relevant ones found. Applying generic fallback.")
        return get_curated_fallback(original_interests, grade_level, rag_context_for_keywords)

    # Reconstruct the output with the filtered recommendations
    response_parts = [
        f"Grade Level: {grade_level}", # Use the actual requested grade level
        f"Interests: {original_interests}",
        "\nRecommendations:"
    ]
    for rec in filtered_recommendations:
        response_parts.append(f"- {rec}")
    final_output = "\n".join(response_parts)
    print(f"DEBUG: Final Filtered Output:\n{final_output}")

    print(f"DEBUG: Final Output before hallucination cleanup:\n{final_output}")

    # NEW: Aggressively remove PDF-related hallucinations if no PDF was uploaded
    if original_interests.lower() != "document content":
        print("DEBUG: No PDF uploaded. Cleaning up potential PDF-related hallucinations from output.")
        
        pdf_hallucination_patterns = [
            # This order is important: longer, more specific patterns first
            r"\s*\(for Relevant Information from PDF\)",
            r"\s*\(for Document content\)",
            r"\s*\(for relevant information from document\)",
            r"\s*\(based on document content\)",
            r"\s*\(from PDF\)",
            r"\s*\(from document\)",
            r"\s*\(([^)]*?(?:pdf|document|relevant information|context|from document|from context)[^)]*?)\)", # Generic match for anything in parentheses related to docs
            r"\b(relevant information from PDF|document content|from document|from context)\b" # Catch direct mentions outside parentheses
        ]
        
        cleaned_output = final_output # Initialize with the string before cleaning

        for pattern in pdf_hallucination_patterns:
            cleaned_output = re.sub(pattern, '', cleaned_output, flags=re.IGNORECASE)

        # Remove any leftover empty parentheses or parentheses with only whitespace
        cleaned_output = re.sub(r"\(\s*\)", "", cleaned_output)

        # Final general cleanup for multiple spaces and leading/trailing whitespace
        cleaned_output = re.sub(r'\s+', ' ', cleaned_output).strip()
        
        # Ensure correct list formatting after cleanup, specifically for lines like "- "
        cleaned_output = re.sub(r'\n\s*-\s*', '\n- ', cleaned_output)

        print(f"DEBUG: Final Output after hallucination cleanup:\n{cleaned_output}")

        return cleaned_output

    return final_output

def get_curated_fallback(interests: str, grade_level: str, rag_context_for_keywords: str = "") -> str:
    print(f"DEBUG: Inside get_curated_fallback. Interests: '{interests}', Grade Level: '{grade_level}'")

    if interests.lower() == "document content" and rag_context_for_keywords:
        keywords = _get_relevance_keywords(rag_context_for_keywords)
        if keywords:
            fallback_interest_summary = ", ".join(keywords[:3])
            return (f"Grade Level: {grade_level}\n"
                    f"Interests: document content (based on keywords: {fallback_interest_summary})\n\n"
                    f"Recommendations:\n"
                    f"- 'The Art of Reading PDFs' by A.I. Assistant (for exploring document content)\n"
                    f"- 'Mastering Digital Information' by C. Code (for understanding complex texts)\n"
                    f"- 'The Intelligent Document' by D. Reader (for extracting key insights)")
        else:
            pass

    fallbacks = {
        "fantasy": [
            "- 'The Hobbit' by J.R.R. Tolkien (for epic fantasy)",
            "- 'Harry Potter and the Sorcerer's Stone' by J.K. Rowling (for magical adventure)",
            "- 'Percy Jackson & The Lightning Thief' by Rick Riordan (for modern mythology)"
        ],
        "science fiction": [
            "- 'Dune' by Frank Herbert (for intricate sci-fi worlds)",
            "- 'Ender's Game' by Orson Scott Card (for strategic sci-fi)",
            "- 'The Hitchhiker's Guide to the Galaxy' by Douglas Adams (for comedic sci-fi)"
        ],
        "mystery": [
            "- 'Sherlock Holmes: A Study in Scarlet' by Arthur Conan Doyle (for classic detective)",
            "- 'And Then There Were None' by Agatha Christie (for suspenseful mystery)",
            "- 'The Girl with the Dragon Tattoo' by Stieg Larsson (for dark thriller)"
        ],
        "history": [
            "- 'Sapiens: A Brief History of Humankind' by Yuval Noah Harari (for broad historical overview)",
            "- 'The Diary of a Young Girl' by Anne Frank (for personal historical account)",
            "- '1776' by David McCullough (for American history)"
        ],
        "romance": [
            "- 'Pride and Prejudice' by Jane Austen (for classic romance)",
            "- 'The Notebook' by Nicholas Sparks (for heartfelt romance)",
            "- 'Red, White & Royal Blue' by Casey McQuiston (for contemporary romance)"
        ],
        "drawing and cookbook": [
            "- 'The Artist\'s Way' by Julia Cameron (for creative inspiration)",
            "- 'Salt, Fat, Acid, Heat' by Samin Nosrat (for culinary fundamentals)",
            "- 'Zine-Making: The Art of Creative Self-Publishing' by Sarah Sparkles (for combining drawing and creative output)",
            "- 'The New Vegetarian Cooking for Everyone' by Deborah Madison (for healthy recipe inspiration and food drawing)"
        ],
        "elf main character": [
            "- 'Eragon' by Christopher Paolini (for an elf companion and rider)",
            "- 'The Elfstones of Shannara' by Terry Brooks (for high fantasy with elves)",
            "- 'Rhapsody: Child of Blood' by Elizabeth Haydon (for an epic fantasy with elf-like characters)"
        ],
        "knight and a princess": [
            "- 'The Princess Bride' by William Goldman (for a classic fairy tale adventure)",
            "- 'Dealing with Dragons' by Patricia C. Wrede (for a humorous take on princesses and knights)",
            "- 'The Two Princesses of Bamarre' by Gail Carson Levine (for a quest with royal sisters and a knightly figure)"
        ],
        "clown and a king": [
            "- 'The Fool and the King' (a folk tale collection, for allegorical stories)",
            "- 'Rigoletto' by Giuseppe Verdi (opera with a jester and a duke, for thematic parallel)",
            "- 'The Clown of God' by Tomie dePaola (for a heartwarming story of a performer and a king)"
        ],
        "computer programming": [
            "- 'Clean Code' by Robert C. Martin (for software engineering principles)",
            "- 'Automate the Boring Stuff with Python' by Al Sweigart (for practical programming)",
            "- 'Code Complete' by Steve McConnell (for comprehensive software development guidance)"
        ],
        "python programming": [
            "- 'Python Crash Course' by Eric Matthes (for Python beginners)",
            "- 'Fluent Python' by Luciano Ramalho (for advanced Python programming)",
            "- 'Automate the Boring Stuff with Python' by Al Sweigart (for practical Python automation)"
        ],
        "programming language": [
            "- 'Code Complete' by Steve McConnell (for general programming best practices)",
            "- 'The Pragmatic Programmer' by Andrew Hunt and David Thomas (for effective software development)",
            "- 'Clean Code' by Robert C. Martin (for writing maintainable code)"
        ],
        "data science books": [
            "- 'Python for Data Analysis' by Wes McKinney (for data manipulation with Python)",
            "- 'Hands-On Machine Learning with Scikit-Learn, Keras, and TensorFlow' by Aurélien Géron (for practical machine learning)",
            "- 'Data Science for Business' by Foster Provost and Tom Fawcett (for business applications of data science)"
        ],
        "machine learning books": [
            "- 'Pattern Recognition and Machine Learning' by Christopher Bishop (for theoretical foundations)",
            "- 'Deep Learning' by Ian Goodfellow, Yoshua Bengio, and Aaron Courville (for deep learning fundamentals)",
            "- 'Hands-On Machine Learning with Scikit-Learn, Keras, and TensorFlow' by Aurélien Géron (for practical applications)"
        ],
        "bible verses": [
            "- 'Mere Christianity' by C.S. Lewis (for Christian apologetics)",
            "- 'The Case for Christ' by Lee Strobel (for investigative Christian faith)",
            "- 'Experiencing God' by Henry Blackaby (for spiritual growth)"
        ],
        "art": [
            "- 'The Artist's Way' by Julia Cameron (for creative inspiration)",
            "- 'Art Fundamentals: Theory and Practice' by Otto G. Ocvirk (for foundational art concepts)",
            "- 'Drawing on the Right Side of the Brain' by Betty Edwards (for improving drawing skills)",
            "- 'Color and Light: A Guide for the Realist Painter' by James Gurney (for understanding color theory)"
        ],
        "drawing": [
            "- 'Drawing on the Right Side of the Brain' by Betty Edwards (for fundamental drawing techniques)",
            "- 'Keys to Drawing' by Bert Dodson (for unlocking creative potential in drawing)",
            "- 'Figure Drawing for All It's Worth' by Andrew Loomis (for advanced figure drawing)"
        ],
        "crafting": [
            "- 'Crafting a Life: A Guide to the Art of Mindful Creation' by Melanie Falick (for general crafting inspiration)",
            "- 'The Crafter's Guide to Taking Great Photos' by Heidi Adnum (for showcasing craft projects)",
            "- 'Making: The Manual of Craft' by The Editors of Popular Mechanics (for practical crafting skills)"
        ]
    }

    normalized_interests = interests.lower().strip()

    # Try to find a direct match for the normalized interests first
    if normalized_interests in fallbacks:
        print(f"DEBUG: Found direct fallback for normalized_interests: '{normalized_interests}'")
        selected_fallback = fallbacks[normalized_interests]
    else:
        # If no direct match, extract keywords from interests and try to find a fallback
        keywords = _get_relevance_keywords(interests)
        found_specific_fallback = False
        for keyword in keywords:
            if keyword.lower() in fallbacks:
                print(f"DEBUG: Found keyword-based fallback for keyword: '{keyword}'")
                selected_fallback = fallbacks[keyword.lower()]
                found_specific_fallback = True
                break
        
        if not found_specific_fallback:
            print("DEBUG: No specific fallback found, using general fallback.")
            selected_fallback = [
                f"- 'The Little Prince' by Antoine de Saint-Exupéry (for timeless wisdom, {grade_level} appropriate)",
                f"- 'Oh, The Places You\'ll Go!' by Dr. Seuss (for encouragement and adventure, {grade_level} appropriate)",
                f"- 'The Alchemist' by Paulo Coelho (for a journey of self-discovery, {grade_level} appropriate)"
            ]

    response_parts = [
        f"Grade Level: {grade_level}",
        f"Interests: {interests}",
        "\nRecommendations:"
    ]
    response_parts.extend(selected_fallback)
    return "\n".join(response_parts)

def calculate_relevance_score(recommendation: str, keywords: list[str]) -> float:
    print(f"DEBUG: calculate_relevance_score - Recommendation: '{recommendation}', Keywords: {keywords}'")
    lower_recommendation = recommendation.lower()
    score = 0
    for keyword in keywords:
        # Use word boundary for single words, direct 'in' for phrases
        if len(keyword.split()) > 1:
            if keyword in lower_recommendation:
                score += 1
                print(f"DEBUG: Matched phrase: '{keyword}' in '{recommendation}'")
        else:
            if re.search(r'\b' + re.escape(keyword) + r'\b', lower_recommendation):
                score += 1
                print(f"DEBUG: Matched word: '{keyword}' in '{recommendation}'")

    print(f"DEBUG: calculate_relevance_score - Calculated Score: {score}")
    return score

CHROMA_PATH = "chroma"
DATA_PATH = "data"
os.makedirs(DATA_PATH, exist_ok=True)
os.makedirs(CHROMA_PATH, exist_ok=True)

def ingest(pdf_path: str):
    print(f"INFO: Attempting to ingest PDF from: {pdf_path}")
    if not os.path.exists(pdf_path):
        print(f"ERROR: PDF file not found at {pdf_path}. Ingestion aborted.")
        return

    if os.path.exists(CHROMA_PATH):
        print(f"INFO: Clearing existing ChromaDB content at {CHROMA_PATH}")
        try:
            shutil.rmtree(CHROMA_PATH)
            print("INFO: ChromaDB content cleared successfully.")
        except OSError as e:
            if e.winerror == 32:
                print(f"WARNING: WinError 32: ChromaDB directory {CHROMA_PATH} or files within it are in use. Attempting to clear problematic files.")
                try:
                    problematic_files = [
                        os.path.join(CHROMA_PATH, 'chroma.sqlite3'),
                    ]
                    for f_path in problematic_files:
                        if os.path.exists(f_path):
                            print(f"ATTEMPTING to remove locked file: {f_path}")
                            os.remove(f_path)
                            print(f"REMOVED: {f_path}")
                except Exception as file_error:
                    print(f"ERROR: Could not remove individual problematic files: {file_error}")
                
                try:
                    shutil.rmtree(CHROMA_PATH)
                    print("INFO: ChromaDB content cleared successfully after retry.")
                except Exception as retry_e:
                    print(f"CRITICAL ERROR: Failed to clear ChromaDB directory {CHROMA_PATH} even after retry: {retry_e}. Please ensure no other process is accessing this directory.")
                    return
            else:
                print(f"ERROR: Error clearing ChromaDB directory {CHROMA_PATH}: {e}")
                return
    else:
        print(f"INFO: No existing ChromaDB content found at {CHROMA_PATH}. Proceeding with fresh ingestion.")

    print(f"INFO: Loading PDF: {pdf_path}")
    loader = PyPDFLoader(pdf_path)
    documents = loader.load()
    print(f"INFO: Loaded {len(documents)} pages from {pdf_path}")

    text_splitter = RecursiveCharacterTextSplitter(
        chunk_size=700,
        chunk_overlap=100,
        add_start_index=True,
    )
    chunks = text_splitter.split_documents(documents)
    print(f"INFO: Split into {len(chunks)} chunks.")

    print("INFO: Initializing FastEmbed embeddings...")
    embeddings = FastEmbedEmbeddings()
    print("INFO: FastEmbed embeddings initialized successfully.")

    print(f"INFO: Adding {len(chunks)} chunks to ChromaDB...")
    db = Chroma.from_documents(
        documents=chunks,
        embedding=embeddings,
        persist_directory=CHROMA_PATH
    )
    print("INFO: Chunks added to ChromaDB.")

def rag_chain():
    print("INFO: Setting up RAG chain...")
    embeddings = FastEmbedEmbeddings()
    db = Chroma(persist_directory=CHROMA_PATH, embedding_function=embeddings)
    retriever = db.as_retriever(search_kwargs={"k": 5})

    rag_prompt = PromptTemplate(
        template="""
        You are an assistant for question-answering tasks. Use the following pieces of retrieved context to answer the question.
        If you don't know the answer, just say that you don't know. Use three sentences maximum and keep the answer concise.
        Relevant Information: {context}

        CRITICAL: You MUST use the information from the "Relevant Information" section as the sole source of truth for generating interests, book types, and specific character/setting/theme requests.
        CRITICAL: When generating book recommendations, you MUST try to use *exact phrasing* from the "Relevant Information" in the parenthetical interest descriptions (e.g., "(for Elf Main Character)").

        Question: {input}
        Answer:
        """
        .strip(),
        input_variables=["context", "input"],
    )
    print(f"DEBUG: rag_prompt input variables: {rag_prompt.input_variables}")

    rag_chain_runnable = (
        RunnableParallel(
            {
                "context": lambda x: retriever.invoke(x["input"]),
                "input": lambda x: x["input"],
            }
        )
        | rag_prompt
        | llm
        | StrOutputParser()
    )
    print("INFO: RAG chain setup complete.")
    return rag_chain_runnable

@app.get("/")
async def root():
    return {"message": "Book Suggestion Chatbot API is running!"}

@app.on_event("startup")
async def startup_event():
    print("INFO: Application startup event triggered.")

    # Centralized ChromaDB clearing
    if os.path.exists(CHROMA_PATH):
        print(f"INFO: Clearing existing ChromaDB content at {CHROMA_PATH} for fresh ingestion.")
        try:
            shutil.rmtree(CHROMA_PATH)
            print("INFO: ChromaDB content cleared successfully.")
        except OSError as e:
            if e.winerror == 32:
                print(f"WARNING: WinError 32: ChromaDB directory {CHROMA_PATH} or files within it are in use. Attempting to clear problematic files.")
                try:
                    problematic_files = [
                        os.path.join(CHROMA_PATH, 'chroma.sqlite3'),
                    ]
                    for f_path in problematic_files:
                        if os.path.exists(f_path):
                            print(f"ATTEMPTING to remove locked file: {f_path}")
                            os.remove(f_path)
                            print(f"REMOVED: {f_path}")
                except Exception as file_error:
                    print(f"ERROR: Could not remove individual problematic files: {file_error}")
                
                try:
                    shutil.rmtree(CHROMA_PATH)
                    print("INFO: ChromaDB content cleared successfully after retry.")
                except Exception as retry_e:
                    print(f"CRITICAL ERROR: Failed to clear ChromaDB directory {CHROMA_PATH} even after retry: {retry_e}. Please ensure no other process is accessing this directory.")
                    # Do not return here; try to proceed with ingestion if possible, but log severe error
            else:
                print(f"ERROR: Error clearing ChromaDB directory {CHROMA_PATH}: {e}")
                # Do not return here; try to proceed with ingestion if possible, but log severe error
    else:
        print(f"INFO: No existing ChromaDB content found at {CHROMA_PATH}. Proceeding with fresh ingestion.")

    uploaded_pdf = os.path.join(DATA_PATH, "uploaded_document.pdf")

    # Ingest PDF data
    if os.path.exists(uploaded_pdf):
        print(f"INFO: Found '{uploaded_pdf}'. Attempting to ingest this document on startup.")
        ingest(uploaded_pdf)
    else:
        print("WARNING: 'uploaded_document.pdf' not found in the 'data' directory. No PDF ingested on startup.")

    global rag_chain_instance
    if 'rag_chain_instance' not in globals(): # Added this check for robustness
        print("WARNING: rag_chain_instance not found during startup, initializing now.")
        rag_chain_instance = rag_chain()
    else:
        rag_chain_instance = rag_chain() # Re-initialize to pick up any changes
    print("INFO: RAG chain instance created at startup.")

@app.post("/suggest")
async def suggest_book(
    interests: str = Form(""),
    grade_level: str = Form(...),
    pdf_file: UploadFile = File(None)
):
    print(f"INFO: Received request - Interests: '{interests}', Grade Level: '{grade_level}', PDF file: {pdf_file.filename if pdf_file else 'None'}")

    original_user_interests = interests
    llm_interests_param = original_user_interests # Initialize with original interests
    rag_context = ""

    if pdf_file and pdf_file.filename:
        upload_path = os.path.join(DATA_PATH, "uploaded_document.pdf")
        print(f"INFO: Saving uploaded PDF to {upload_path}")
        with open(upload_path, "wb") as buffer:
            shutil.copyfileobj(pdf_file.file, buffer)
        print("INFO: PDF saved successfully. Ingesting...")
        ingest(upload_path)
        print("INFO: PDF ingestion complete.")

        llm_interests_param = "document content" # If PDF, LLM should focus on document content
        print("INFO: LLM interests parameter set to 'document content' due to PDF upload.")

    global rag_chain_instance
    if 'rag_chain_instance' not in globals():
        print("WARNING: rag_chain_instance not found. Initializing now.")
        rag_chain_instance = rag_chain()

    # Decide RAG query text and content context: prioritize original interests if available, otherwise use a generic query for PDFs
    content_context_for_llm = ""
    if pdf_file:
        rag_query_text = "relevant information from document"
        print(f"DEBUG: RAG query text for context retrieval (PDF): '{rag_query_text}'")
        try:
            rag_response_dict = rag_chain_instance.invoke({"input": rag_query_text})
            content_context_for_llm = rag_response_dict.get("context", "")
            print(f"DEBUG: RAG Context obtained from PDF (first 200 chars): '{content_context_for_llm[:200]}'")
        except Exception as e:
            print(f"ERROR: Failed to invoke RAG chain to get PDF context: {e}")
            content_context_for_llm = ""
    elif original_user_interests:
        rag_query_text = original_user_interests
        print(f"DEBUG: Performing web search for RAG context: '{rag_query_text}'")
        try:
            web_search_results = search.run(rag_query_text)
            if web_search_results and web_search_results != "No good DuckDuckGo Search Result was found":
                content_context_for_llm = f"Relevant information from web search: {web_search_results}"
                print(f"DEBUG: Web Search Context obtained (first 200 chars): '{content_context_for_llm[:200]}'")
            else:
                print("WARNING: No good web search results found for user interests.")
                content_context_for_llm = ""
        except Exception as e:
            print(f"ERROR: Failed to perform web search for RAG context: {e}")
            content_context_for_llm = ""
    
    # No longer processing user interests into keywords here if no PDF, as RAG handles it
    # The llm_interests_param is already correctly set to original_user_interests or "document content"

    if not original_user_interests and not pdf_file:
        raise HTTPException(status_code=400, detail="Either 'interests' or a 'pdf_file' must be provided.")

    canonical_grade_level = canonicalize_grade_level(grade_level)

    print(f"INFO: Invoking LLM chain with Interests param: '{llm_interests_param}', Grade Level: '{canonical_grade_level}'")
    print(f"DEBUG: LLM input: interests='{llm_interests_param}', grade_level='{canonical_grade_level}', context='{content_context_for_llm[:100]}'...")
    
    raw_ai_output = chain.invoke({
        "interests": llm_interests_param,
        "grade_level": canonical_grade_level,
        "context": content_context_for_llm # Pass the conditionally generated context
    })
    
    print(f"DEBUG: Raw AI output from chain.invoke:\n{raw_ai_output}")

    final_response = post_process_ai_output(raw_ai_output, original_user_interests, canonical_grade_level, content_context_for_llm)

    print(f"INFO: Final response:\n{final_response}")
    return {"suggestion": final_response}

if __name__ == "__main__":
    uvicorn.run("CkBookSuggestion:app", host="127.0.0.1", port=5001, reload=False)
