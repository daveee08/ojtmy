# CK AI Agent

CK AI Agent is an AI-powered educational platform built for CK Children’s Publishing. It enables users to chat with learning materials, auto-generate quizzes, and simplify educational content using local LLMs — making it a private, offline, and intelligent assistant for students and teachers.


## API Reference (FastAPI)

### Leveler

```http
POST /[agent]
```

| Parameter   | Type     | Description                                 |
|-------------|----------|---------------------------------------------|
| `user_id`   | `string` | **Required**. ID of the Laravel user        |
| `text`      | `string` | Optional. Manual text input                 |
| `pdf`       | `file`   | Optional. PDF upload for content extraction |
| `topic`     | `string` | **Required**. Topic of the lesson           |

### Chat (Follow-up)

```http
POST /chat
```

| Parameter     | Type     | Description                        |
|---------------|----------|------------------------------------|
| `user_id`     | `string` | **Required**. Laravel user ID      |
| `session_id`  | `string` | **Required**. From leveler session |
| `question`    | `string` | **Required**. Follow-up query      |

### Quiz Generator

```http
POST /quiz
```

| Parameter     | Type     | Description                          |
|---------------|----------|--------------------------------------|
| `context`     | `string` | **Required**. Text to quiz from      |
| `quiz_type`   | `string` | "multiple choice" or "true or false" |


## Environment Variables

To run this project, add the following to your Laravel `.env` file:

```env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ck_ai_agent
DB_USERNAME=root
DB_PASSWORD=

FASTAPI_BASE_URL=http://localhost:8000
OLLAMA_BASE_URL=http://localhost:11434
```

Also, make sure your FastAPI backend uses a `.env` or config file with access to:

```env
MYSQL_HOST=localhost
MYSQL_USER=root
MYSQL_PASSWORD=
MYSQL_DATABASE=ck_agent
```


## Features

- 📄 Chat with specific PDF chapter content
- 🧠 Auto-generate quizzes from textbook materials
- 📚 35 agents to fit the student and teacher's needs
- 🧵 AI remembers session history for follow-ups
- 🗂️ Sidebar session list in Laravel UI
- ⚙️ Built entirely on local AI (no external API needed)
- 🔐 Designed for privacy, education, and performance


## Deployment

### Laravel

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

### FastAPI (Python)

```bash
cd python
pip install -r requirements.txt
uvicorn app:app --host 0.0.0.0 --port 8000
```

> Make sure you have Ollama installed and running:

```bash
ollama run llama3
```


## Tech Stack

**Client:** Laravel (Blade), Bootstrap, Axios  
**Server:**  
- Laravel (PHP 8+) – web interface and auth  
- FastAPI (Python 3.10+) – AI processing  
- MySQL – persistent storage  
- FAISS – vector similarity search  
- Ollama (LLaMA3) – local LLM inference  
- LangChain – AI workflow & memory


## Running Tests

### Laravel (PHP)

```bash
php artisan test
```


## Appendix

- PDF processing uses PyMuPDF (`fitz`) for fast chapter slicing.
- Document embeddings are stored in FAISS, with metadata in MySQL.
- Full conversational memory is handled via LangChain `RunnableWithMessageHistory`.


## Acknowledgements

- [LangChain](https://www.langchain.com/)  
- [Ollama](https://ollama.com/)  
- [FastAPI](https://fastapi.tiangolo.com/)  
- [FAISS](https://github.com/facebookresearch/faiss)  
- [Laravel](https://laravel.com/)


## Used By

This project is used by:

- CK Children’s Publishing and Printing (for internal AI assistant and educational tools)

