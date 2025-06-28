import os
import uvicorn
from fastapi import FastAPI, HTTPException, Form
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
from langchain_ollama import ChatOllama
from langchain_core.prompts import ChatPromptTemplate
from langchain_core.messages import SystemMessage, HumanMessage
from langchain_core.output_parsers import StrOutputParser
import re
import collections
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
    lower_text = text_to_analyze.lower()
    normalized_text = re.sub(r'\s+', ' ', lower_text)
    cleaned_punctuation = re.sub(r"[^a-z0-9\s'-]", ' ', normalized_text)
    final_cleaned_text = cleaned_punctuation.strip()

    target_phrases_regex = [
        r"search the following books\s*:",
        r"objectives\s*:",
        r"key terms\s*:",
        r"topics\s*:",
        r"keywords\s*:",
        # Removed PDF-specific regex patterns
    ]

    content_after_target_phrase = ""
    for phrase_pattern in target_phrases_regex:
        match = re.search(phrase_pattern, normalized_text)
        if match:
            content_after_target_phrase = normalized_text[match.end():].strip()
            cleaned_content_after_phrase = re.sub(r"[^a-z0-9\s'-]", ' ', content_after_target_phrase)
            cleaned_content_after_phrase = re.sub(r'\s+', ' ', cleaned_content_after_phrase).strip()
            final_cleaned_text = cleaned_content_after_phrase
            break

    text_for_keyword_extraction = final_cleaned_text

    specific_terms_and_phrases = [
        "elf main character", "knight and a princess", "clown and a king", "drawing and cookbook",
        "adventurous journey", "fell in love", "in love", "love story",
        "science fiction", "magical realism", "literary fiction", "contemporary fiction",
        "women\'s fiction", "true crime", "graphic novel", "short story", "young adult",
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
        "religion", "spirituality", "novel", "children\'s", "elf", "knight",
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
        # And also ensure it\'s not a substring of a larger, already identified phrase
        if re.search(r'\b' + re.escape(term_or_phrase) + r'\b', processed_text_for_words):
            found_keywords.add(term_or_phrase)
            # Replace the found phrase with spaces to prevent re-matching parts of it
            # Using regex to replace with spaces to maintain word boundaries for subsequent word splitting
            processed_text_for_words = re.sub(r'\b' + re.escape(term_or_phrase) + r'\b', ' ' * len(term_or_phrase), processed_text_for_words)

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

    # Add relevant single words to the found keywords, ensuring no duplicates with existing phrases
    for word in filtered_single_words:
        is_part_of_phrase = False
        for phrase in found_keywords:
            if word in phrase.split(): # Check if the single word is part of any already found phrase
                is_part_of_phrase = True
                break
        if not is_part_of_phrase:
            found_keywords.add(word)

    return list(found_keywords)

def post_process_ai_output(raw_output: str, original_interests: str, grade_level: str, search_context: str = "") -> str:
    # This function is adapted to work without RAG, using search_context from DuckDuckGo
    # The prompt should be structured so that the AI *only* outputs the bulleted list.
    # Therefore, post-processing here is primarily for fallback and additional cleanup if the AI deviates.

    # Assume AI *should* only output the bulleted list based on the new prompt
    # No "Recommendations:" header is expected from the prompt, so directly process lines
    lines = raw_output.strip().split('\n')
    parsed_recommendations = []
    for line in lines:
        line = line.strip()
        if line.startswith("- "):
            parsed_recommendations.append(line[2:].strip())

    if not parsed_recommendations:
        # Fallback if AI fails to produce a list.
        # This fallback mechanism can be simplified since the prompt is stricter.
        return get_curated_fallback(original_interests, grade_level, search_context)

    # Perform relevance filtering based on interests or search context
    keywords_to_check = []
    if search_context:
        keywords_to_check = _get_relevance_keywords(search_context)
    elif original_interests:
        keywords_to_check = _get_relevance_keywords(original_interests)
    
    relevance_score_threshold = 1 if (search_context or len(keywords_to_check) > 0) else 0
    if len(keywords_to_check) > 0:
        relevance_score_threshold = max(1, int(len(keywords_to_check) * 0.75))

    filtered_recommendations = []
    for rec in parsed_recommendations:
        score = calculate_relevance_score(rec, keywords_to_check)
        if score >= relevance_score_threshold:
            filtered_recommendations.append(rec)

    if not filtered_recommendations:
        return get_curated_fallback(original_interests, grade_level, search_context)

    final_output_list = []
    for rec in filtered_recommendations:
        # General cleanup for empty parentheses (not specific to PDF now)
        rec_cleaned = re.sub(r"\(\s*\)", "", rec).strip() 
        final_output_list.append(f"- {rec_cleaned}")
    
    # The frontend will add "Grade Level: Interests: Recommendations:"
    return "\n".join(final_output_list)

def get_curated_fallback(interests: str, grade_level: str, search_context: str = "") -> str:
    # This fallback now directly returns a bulleted list to match AI output expectation
    # and to be consistent with the new frontend handling of headers.

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
            "- 'The Artist\'s Way' by Julia Cameron (for creative inspiration)",
            "- 'Art Fundamentals: Theory and Practice' by Otto G. Ocvirk (for foundational art concepts)",
            "- 'Drawing on the Right Side of the Brain' by Betty Edwards (for improving drawing skills)",
            "- 'Color and Light: A Guide for the Realist Painter' by James Gurney (for understanding color theory)"
        ],
        "drawing": [
            "- 'Drawing on the Right Side of the Brain' by Betty Edwards (for fundamental drawing techniques)",
            "- 'Keys to Drawing' by Bert Dodson (for unlocking creative potential in drawing)",
            "- 'Figure Drawing for All It\'s Worth' by Andrew Loomis (for advanced figure drawing)"
        ],
        "crafting": [
            "- 'Crafting a Life: A Guide to the Art of Mindful Creation' by Melanie Falick (for general crafting inspiration)",
            "- 'The Crafter\'s Guide to Taking Great Photos' by Heidi Adnum (for showcasing craft projects)",
            "- 'Making: The Manual of Craft' by The Editors of Popular Mechanics (for practical crafting skills)"
        ]
    }

    normalized_interests = interests.lower().strip()

    if normalized_interests in fallbacks:
        selected_fallback = fallbacks[normalized_interests]
    else:
        keywords = _get_relevance_keywords(interests)
        found_specific_fallback = False
        for keyword in keywords:
            if keyword.lower() in fallbacks:
                selected_fallback = fallbacks[keyword.lower()]
                found_specific_fallback = True
                break
        
        if not found_specific_fallback:
            selected_fallback = [
                f"- 'The Little Prince' by Antoine de Saint-Exupéry (for timeless wisdom, {grade_level} appropriate)",
                f"- 'Oh, The Places You\'ll Go!' by Dr. Seuss (for encouragement and adventure, {grade_level} appropriate)",
                f"- 'The Alchemist' by Paulo Coelho (for a journey of self-discovery, {grade_level} appropriate)"
            ]
    return "\n".join(selected_fallback)

def calculate_relevance_score(recommendation: str, keywords: list[str]) -> float:
    lower_recommendation = recommendation.lower()
    score = 0
    for keyword in keywords:
        if len(keyword.split()) > 1:
            if keyword in lower_recommendation:
                score += 1
        else:
            if re.search(r'\b' + re.escape(keyword) + r'\b', lower_recommendation):
                score += 1
    return score

DATA_PATH = "data"
os.makedirs(DATA_PATH, exist_ok=True)

@app.on_event("startup")
async def startup_event():
    print("INFO: Application startup event triggered for CkBookSuggestion.")
    print("INFO: No PDF ingestion on startup as ChromaDB is not used.")

@app.post("/suggest")
async def suggest_book(
    interests: str = Form(...),
    grade_level: str = Form(...)
):
    print(f"INFO: Received request - Interests: '{interests}', Grade Level: '{grade_level}'")

    original_user_interests = interests
    search_context = ""

    # Perform web search based on original user interests
    print(f"DEBUG: Performing web search for context: '{original_user_interests}'")
    try:
        web_search_results = search.run(original_user_interests)
        if web_search_results and web_search_results != "No good DuckDuckGo Search Result was found":
            search_context = f"Relevant information from web search: {web_search_results}"
            print(f"DEBUG: Web Search Context obtained (first 200 chars): '{search_context[:200]}'")
        else:
            print("WARNING: No good web search results found for user interests.")
            search_context = ""
    except Exception as e:
        print(f"ERROR: Failed to perform web search for context: {e}")
        search_context = ""
    
    if not original_user_interests:
        raise HTTPException(status_code=400, detail="'interests' must be provided.")

    canonical_grade_level = canonicalize_grade_level(grade_level)

    print(f"INFO: Invoking LLM chain with Interests param: '{original_user_interests}', Grade Level: '{canonical_grade_level}'")
    print(f"DEBUG: LLM input: interests='{original_user_interests}', grade_level='{canonical_grade_level}', context='{search_context[:100]}...'")
    
    raw_ai_output = chain.invoke({
        "interests": original_user_interests,
        "grade_level": canonical_grade_level,
        "context": search_context
    })
    
    print(f"DEBUG: Raw AI output from chain.invoke:\n{raw_ai_output}")

    final_response = post_process_ai_output(raw_ai_output, original_user_interests, canonical_grade_level, search_context)

    print(f"INFO: Final response:\n{final_response}")
    return {"suggestion": final_response}

if __name__ == "__main__":
    uvicorn.run("CkBookSuggestion:app", host="127.0.0.1", port=5001, reload=False) 