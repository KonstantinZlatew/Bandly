# Async Essay Processing Architecture

## Overview

This system processes essay submissions asynchronously to avoid blocking the user interface while AI analysis is performed. The architecture is designed to be scalable, safe for multiple concurrent workers, and Docker-friendly.

## Components

### 1. Database Schema (`config/async_processing_schema.sql`)

**writing_submissions table additions:**
- `status` ENUM('pending', 'processing', 'done', 'failed') - Tracks processing state
- `analysis_result` JSON - Stores complete AI analysis result
- `error_message` TEXT - Stores error details if processing fails
- `processed_at` TIMESTAMP - When processing completed

**worker_instances table:**
- Tracks active worker processes for monitoring
- Heartbeat mechanism to detect dead workers

### 2. Submission Endpoint (`api/submission-save.php`)

**Flow:**
1. User clicks "Analyze" button
2. Essay data is saved to `writing_submissions` with `status='pending'`
3. Response returned immediately (does NOT wait for AI)
4. Returns `submission_id` to frontend

**Key Features:**
- Validates all input data
- Handles image uploads for Academic Task 1
- Marks task as completed in `user_task_completions`
- Returns immediately without blocking

### 3. Background Worker (`worker.php`)

**Flow:**
1. Fetches one pending job using `SELECT ... FOR UPDATE` (row-level lock)
2. Marks job as 'processing' within transaction
3. Calls AI service endpoint
4. Saves result to `analysis_result` field
5. Updates status to 'done' or 'failed'

**Safety Features:**
- **SELECT ... FOR UPDATE**: Prevents multiple workers from processing the same job
- **Transaction-based**: Ensures atomic status updates
- **Error handling**: Failed jobs marked as 'failed' with error message
- **Heartbeat**: Updates worker_instances table for monitoring

**Usage:**
```bash
# Process one job and exit
php worker.php

# Process max 10 jobs then exit
php worker.php --max-jobs=10

# Run continuously (daemon mode)
php worker.php --daemon
```

**Environment Variables:**
- `AI_SERVICE_URL`: URL of AI service (default: http://localhost:8000)
- `WORKER_ID`: Unique worker identifier (default: hostname-pid)

### 4. Status Endpoint (`api/essay-status.php`)

**Flow:**
1. Frontend polls this endpoint with `submission_id`
2. Returns current status and analysis result (if done)
3. User can only access their own submissions

**Response Format:**
```json
{
  "ok": true,
  "submission": {
    "id": 123,
    "status": "done",
    "analysis_result": {...},
    "error_message": null,
    "submitted_at": "2024-01-01 12:00:00",
    "processed_at": "2024-01-01 12:01:30",
    "word_count": 250
  }
}
```

### 5. Frontend Polling (`practice.php`)

**Flow:**
1. After submission, starts polling `essay-status.php` every 2 seconds
2. Displays "Processing..." message while status is 'pending' or 'processing'
3. When status is 'done', displays analysis results
4. If status is 'failed', displays error message
5. Stops polling once status is 'done' or 'failed'

## Data Flow

```
User clicks "Analyze"
    ↓
[Frontend] → POST api/submission-save.php
    ↓
[Backend] → Save to DB (status='pending')
    ↓
[Backend] → Return submission_id immediately
    ↓
[Frontend] → Start polling essay-status.php
    ↓
[Worker] → SELECT ... FOR UPDATE (locks row)
    ↓
[Worker] → Update status='processing'
    ↓
[Worker] → Call AI Service
    ↓
[Worker] → Save result, status='done'
    ↓
[Frontend] → Poll detects 'done', displays results
```

## Scalability

### Multiple Workers

- **Safe**: Uses `SELECT ... FOR UPDATE` to prevent race conditions
- **Efficient**: Each worker processes one job at a time
- **Distributed**: Can run workers on different servers

### Docker Deployment

```dockerfile
# Example Dockerfile for worker
FROM php:8.2-cli
COPY worker.php /app/
WORKDIR /app
CMD ["php", "worker.php", "--daemon"]
```

**Scaling:**
```bash
# Run multiple worker containers
docker run -d worker:latest
docker run -d worker:latest
docker run -d worker:latest
```

### Queue Alternatives

For higher scale, consider:
- **Redis Queue**: Replace SELECT ... FOR UPDATE with Redis
- **RabbitMQ**: Use message queue for job distribution
- **AWS SQS**: Cloud-based queue service

## Monitoring

### Worker Health

Query `worker_instances` table:
```sql
SELECT * FROM worker_instances 
WHERE last_heartbeat > DATE_SUB(NOW(), INTERVAL 5 MINUTE);
```

### Job Statistics

```sql
-- Pending jobs
SELECT COUNT(*) FROM writing_submissions WHERE status = 'pending';

-- Processing jobs
SELECT COUNT(*) FROM writing_submissions WHERE status = 'processing';

-- Failed jobs
SELECT COUNT(*) FROM writing_submissions WHERE status = 'failed';

-- Average processing time
SELECT AVG(TIMESTAMPDIFF(SECOND, submitted_at, processed_at)) 
FROM writing_submissions 
WHERE status = 'done';
```

## Error Handling

### Worker Failures

- If worker crashes while processing, job remains 'processing'
- Can add timeout: mark jobs as 'failed' if processing > 5 minutes
- Heartbeat mechanism helps detect dead workers

### AI Service Failures

- Worker catches exceptions
- Marks job as 'failed' with error message
- Error message stored in `error_message` field
- Frontend displays error to user

## Security

- **Authentication**: All endpoints require authentication
- **Authorization**: Users can only access their own submissions
- **Input Validation**: All inputs validated before processing
- **SQL Injection**: Uses prepared statements
- **File Upload**: Validates file types and sizes

## Performance Considerations

- **Indexes**: Added on `status` and `(status, submitted_at)` for fast queries
- **Polling Interval**: 2 seconds (adjustable)
- **Worker Sleep**: 0.1 seconds between jobs (adjustable)
- **Timeout**: 2 minutes for AI service calls

## Future Enhancements

1. **Webhooks**: Instead of polling, use webhooks for status updates
2. **WebSockets**: Real-time status updates via WebSocket connection
3. **Retry Logic**: Automatically retry failed jobs
4. **Priority Queue**: Process urgent submissions first
5. **Batch Processing**: Process multiple jobs in parallel per worker
