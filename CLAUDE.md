# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

### PHP (XAMPP)
The project runs under XAMPP. PHP files are served from `http://localhost/IELTS-AI-Evaluator/`.

```bash
# Run tests
vendor/bin/phpunit

# Run a single test file
vendor/bin/phpunit tests/Unit/ValidationTest.php

# Run tests with coverage
vendor/bin/phpunit --coverage-html coverage

# Lint PHP code (PSR-12 standard)
vendor/bin/phpcs

# Auto-fix lint issues
vendor/bin/phpcbf

# Run background essay worker (process one job)
php worker.php

# Run background essay worker (daemon mode)
php worker.php --daemon

# Run speaking worker
php speaking-worker.php --daemon
```

### AI Services (Python)

```bash
# Writing/essay AI service (FastAPI on port 8000)
cd ai_service
python -m venv venv && venv/Scripts/activate  # Windows
pip install -r requirements.txt
uvicorn app.main:app --port 8000 --reload

# Speaking AI service (FastAPI on port 8001)
cd speaking_service
python -m venv venv && venv/Scripts/activate  # Windows
pip install -r requirements.txt
python -m uvicorn app.main:app --port 8001 --reload
```

## Environment Setup

Copy `.env` from `ENV_CONFIGURATION.md` template. Required variables:
- `DB_HOST`, `DB_NAME` (default: `ielts_evalai`), `DB_USER`, `DB_PASS`
- SMTP variables for 2FA email: `SMTP_HOST`, `SMTP_PORT`, `SMTP_ENCRYPTION`, `SMTP_USERNAME`, `SMTP_PASSWORD`, `SMTP_FROM_EMAIL`, `SMTP_FROM_NAME`

The AI services need their own `.env` in `ai_service/` and `speaking_service/` with `OPENAI_API_KEY`.

## Database

MySQL database `ielts_evalai`. Schema files in `config/`:
- `sql_code_for_db.sql` — core schema (users, exams, tasks, writing_submissions)
- `async_processing_schema.sql` — adds async status/result columns to writing_submissions
- `speaking_async_schema.sql` — speaking_submissions table
- `two_factor_auth_schema.sql` — 2FA tables
- `user_task_tracking.sql` — user_task_completions table

## Architecture

### Request Flow
1. PHP pages (`index.php`, `practice.php`, `exam.php`, etc.) serve HTML, guarded by `isAuthenticated()` from `config/auth.php`
2. JS in `scripts/` calls REST endpoints in `api/` via fetch
3. `api/` endpoints use `config/db.php` (singleton PDO via `db()`) and `config/auth.php` for session cookies

### Authentication
Cookie-based: `user_id`, `username`, `email`, `is_admin`, `profile_picture_url` set via helpers in `config/auth.php`. 2FA via email (PHPMailer) using `config/email.php` and `config/two_factor.php`.

### Async Processing (Writing)
Essays are saved to `writing_submissions` with `status='pending'`, returned immediately to the user. `worker.php` polls the DB using `SELECT ... FOR UPDATE` to safely claim jobs, calls the Python AI service at `http://localhost:8000`, and saves results back. Frontend polls `api/essay-status.php` every 2 seconds.

### Async Processing (Speaking)
Same pattern as writing but uses `speaking_submissions` table, `speaking-worker.php`, and the speaking Python service at `http://localhost:8001`. Audio files stored in `uploads/speaking/`.

### AI Services
- `ai_service/` — FastAPI service using OpenAI + ChromaDB (RAG) for IELTS writing evaluation
- `speaking_service/` — FastAPI service using OpenAI Whisper (transcription) + GPT for speaking evaluation

### Payments
Stripe integration via `vendor/stripe/stripe-php`. Webhook handled in `api/stripe-webhook.php`. Entitlements (credits) managed through `includes/entitlements-check.php` and `includes/entitlements-deduct.php`.

### Key Includes
- `config/db.php` — `db()` returns singleton PDO
- `config/auth.php` — cookie auth helpers (`isAuthenticated()`, `getUserId()`, etc.)
- `includes/navbar.php` — shared navigation
- `includes/entitlements-*.php` — credit system logic (covered by unit tests)
