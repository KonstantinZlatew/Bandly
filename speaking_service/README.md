# IELTS Speaking Service Setup

## Prerequisites

1. Python 3.8 or higher
2. OpenAI API key

## Setup Instructions

### 1. Install Dependencies

```bash
cd speaking_service
pip install -r requirements.txt
```

Or if using virtual environment:
```bash
cd speaking_service
python -m venv venv
# On Windows:
venv\Scripts\activate
# On Linux/Mac:
source venv/bin/activate

pip install -r requirements.txt
```

### 2. Configure Environment

Create a `.env` file in the `speaking_service` directory:

```env
OPENAI_API_KEY=your_openai_api_key_here
PORT=8001
```

### 3. Start the Service

```bash
cd speaking_service
python -m uvicorn app.main:app --port 8001 --reload
```

Or run directly:
```bash
cd speaking_service
python -m app.main
```

The service will start on `http://localhost:8001`

### 4. Start the PHP Worker

In a separate terminal, from the project root:

```bash
php speaking-worker.php --daemon
```

Or run once to process pending jobs:
```bash
php speaking-worker.php
```

## Testing

1. Check if service is running:
   ```bash
   curl http://localhost:8001/health
   ```

2. Check service status from PHP:
   Visit: `http://localhost/IELTS-AI-Evaluator/api/speaking-service-check.php`

## Troubleshooting

### Service won't start
- Check if port 8001 is already in use
- Verify OPENAI_API_KEY is set in .env file
- Check Python dependencies are installed

### Worker not processing jobs
- Verify Python service is running on port 8001
- Check database connection in config/db.php
- Verify speaking_submissions table has status column (run config/speaking_async_schema.sql)
- Check worker logs for errors

### Analysis fails
- Verify OpenAI API key is valid
- Check audio file exists in uploads/speaking/ directory
- Verify file permissions on uploads directory
