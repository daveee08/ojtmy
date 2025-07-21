# Copilot Instructions for ojtmy Codebase

## Project Overview
- This is a Laravel-based PHP web application with a modular structure for books, units, chapters, and lessons, supporting a virtual tutor chat and document-based learning.
- The `python/` and `RAG/` directories contain Python scripts for AI, RAG (Retrieval-Augmented Generation), and document processing, which are integrated with the main Laravel app.

## Key Components
- `app/` — Laravel application logic (Controllers, Models, Providers, etc.)
- `resources/views/` — Blade templates for UI. Sidebar navigation is dynamic and uses JavaScript for expand/collapse (see `layouts/chatnavbar.blade.php`).
- `routes/` — Laravel route definitions (`web.php`, `api.php`).
- `public/` — Web root for static assets and entry point (`index.php`).
- `python/` and `RAG/` — Python scripts for AI features, RAG, and document handling.
- `faiss_index/` and `RAG/faiss_index/` — FAISS vector index files for semantic search.

## Developer Workflows
- **Laravel:**
  - Start local server: `php artisan serve`
  - Run migrations: `php artisan migrate`
  - Run tests: `php artisan test`
- **Python AI/RAG:**
  - Install dependencies: `pip install -r python/requirements.txt`
  - Run scripts: `python python/main-feb.py` or other scripts in `python/` or `RAG/`
- **Assets:**
  - Build frontend assets: `npm install && npm run dev` (uses `webpack.mix.js`)

## Project-Specific Patterns
- **Sidebar Navigation:**
  - Uses nested Blade loops and JavaScript for dynamic expand/collapse of books, units, chapters, and lessons.
  - JavaScript toggles visibility by element ID pattern: `units-<bookId>`, `chapters-<unitId>`, `lessons-<chapterId>`.
- **Database Access:**
  - Direct DB queries in Blade templates for navigation (not typical in Laravel, but used here for dynamic sidebar).
- **AI Integration:**
  - Python scripts are invoked separately and may interact with the Laravel app via API endpoints or shared storage (e.g., FAISS index files).

## Conventions & Integration
- **Blade templates** are in `resources/views/`, with layouts in `layouts/`.
- **Python scripts** are organized by feature in subfolders under `python/`.
- **FAISS index files** are stored in `faiss_index/` and `RAG/faiss_index/`.
- **Environment:** Laravel uses `.env` for configuration; Python scripts may require their own config.

## Examples
- To add a new lesson, update the database and the sidebar will reflect changes automatically due to dynamic queries in the Blade template.
- To add a new AI feature, place scripts in `python/` or `RAG/` and ensure dependencies are listed in the appropriate `requirements.txt`.

## References
- See `resources/views/layouts/chatnavbar.blade.php` for sidebar logic and navigation patterns.
- See `python/requirements.txt` and `RAG/` for AI/RAG integration details.

---
For questions about project structure or workflows, check this file first, then see the `README.md` or ask the project maintainers.
