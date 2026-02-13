# Async Processing Setup Guide

## Quick Start

### 1. Run Database Migrations

Execute these SQL files in order:

```sql
-- 1. Add status and analysis fields
SOURCE config/async_processing_schema.sql;

-- 2. Verify task_type ENUM includes all values
-- (Already done in async_processing_schema.sql)
```

### 2. Start AI Service

```bash
cd ai_service
source venv/bin/activate  # or venv\Scripts\activate on Windows
uvicorn app.main:app --host 0.0.0.0 --port 8000
```

### 3. Start Worker(s)

```bash
# Single worker (processes one job and exits)
php worker.php

# Daemon mode (runs continuously)
php worker.php --daemon

# Process max 10 jobs then exit
php worker.php --max-jobs=10
```

### 4. Test the Flow

1. Go to practice page
2. Write an essay
3. Click "Analyze"
4. Watch the results panel update automatically

## Architecture Summary

### Components

1. **submission-save.php**: Saves essay with `status='pending'`, returns immediately
2. **worker.php**: Background process that:
   - Fetches pending jobs using `SELECT ... FOR UPDATE`
   - Calls AI service
   - Saves results
3. **essay-status.php**: Status endpoint for polling
4. **Frontend**: Polls status endpoint every 2 seconds

### Key Features

- ✅ **Non-blocking**: User gets immediate response
- ✅ **Safe for multiple workers**: Uses row-level locking
- ✅ **Scalable**: Can run multiple workers
- ✅ **Docker-friendly**: Stateless workers
- ✅ **Error handling**: Failed jobs marked with error messages

## Database Schema

### writing_submissions additions:
- `status` ENUM('pending', 'processing', 'done', 'failed')
- `analysis_result` JSON
- `error_message` TEXT
- `processed_at` TIMESTAMP

### worker_instances table:
- Tracks active workers
- Heartbeat mechanism

## Troubleshooting

### Worker not processing jobs

1. Check worker is running: `ps aux | grep worker.php`
2. Check database connection in `config/db.php`
3. Check AI service is accessible: `curl http://localhost:8000/evaluate`
4. Check for pending jobs: `SELECT COUNT(*) FROM writing_submissions WHERE status='pending'`

### Jobs stuck in 'processing'

Jobs may be stuck if worker crashed. You can manually reset:

```sql
UPDATE writing_submissions 
SET status='pending' 
WHERE status='processing' 
AND processed_at < DATE_SUB(NOW(), INTERVAL 10 MINUTE);
```

### task_type not saving

Ensure the ENUM includes all values:
- `academic_task_1`
- `academic_task_2`
- `general_task_1`
- `general_task_2`
- `task_2`

Check with:
```sql
SHOW COLUMNS FROM writing_submissions LIKE 'task_type';
```

## Production Deployment

### Using Supervisor (Linux)

```ini
[program:essay_worker]
command=php /path/to/worker.php --daemon
directory=/path/to/project
autostart=true
autorestart=true
user=www-data
numprocs=3
```

### Using Docker Compose

```yaml
version: '3.8'
services:
  worker:
    build: .
    command: php worker.php --daemon
    environment:
      - AI_SERVICE_URL=http://ai-service:8000
      - WORKER_ID=worker-1
    deploy:
      replicas: 3
```

### Environment Variables

- `AI_SERVICE_URL`: AI service endpoint (default: http://localhost:8000)
- `WORKER_ID`: Unique worker identifier (default: hostname-pid)

## Monitoring

### Check Worker Status

```sql
SELECT * FROM worker_instances 
WHERE last_heartbeat > DATE_SUB(NOW(), INTERVAL 5 MINUTE);
```

### Job Statistics

```sql
SELECT 
  status,
  COUNT(*) as count,
  AVG(TIMESTAMPDIFF(SECOND, submitted_at, processed_at)) as avg_seconds
FROM writing_submissions
GROUP BY status;
```
