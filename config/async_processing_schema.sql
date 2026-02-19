

-- 1. Add status and analysis fields to writing_submissions
ALTER TABLE writing_submissions
ADD COLUMN status ENUM('pending', 'processing', 'done', 'failed') DEFAULT 'pending' AFTER submitted_at,
ADD COLUMN analysis_result JSON NULL AFTER status,
ADD COLUMN error_message TEXT NULL AFTER analysis_result,
ADD COLUMN processed_at TIMESTAMP NULL AFTER error_message;

-- Add indexes for efficient worker queries
ALTER TABLE writing_submissions
ADD INDEX idx_status (status),
ADD INDEX idx_status_submitted (status, submitted_at);

-- 2. Ensure task_type field exists and has correct values
-- Check if task_type column exists, if not add it
-- Note: This should already exist from update_db_phpmyadmin.sql
-- But we'll ensure it has the correct ENUM values including 'academic_task_2' and 'general_task_2'
ALTER TABLE writing_submissions
MODIFY COLUMN task_type ENUM('academic_task_1', 'academic_task_2', 'general_task_1', 'general_task_2', 'task_2') NULL;

-- 3. Add a table to track worker instances (optional, for monitoring)
CREATE TABLE IF NOT EXISTS worker_instances (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  worker_id VARCHAR(255) NOT NULL UNIQUE,
  last_heartbeat TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  status ENUM('active', 'inactive') DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_status (status),
  INDEX idx_last_heartbeat (last_heartbeat)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- Notes:
-- =====================================================
-- Status values:
--   - 'pending': Essay submitted, waiting for processing
--   - 'processing': Currently being analyzed by a worker
--   - 'done': Analysis complete, result available
--   - 'failed': Analysis failed, error_message contains details
--
-- analysis_result JSON structure:
-- {
--   "overall_band": 7.0,
--   "TR": 7.0,
--   "CC": 7.0,
--   "LR": 7.0,
--   "GRA": 7.0,
--   "notes": {...},
--   "overall_comment": "...",
--   "improvement_plan": [...],
--   "word_count": 180,
--   "used_rag": true,
--   "image_analysis": {...}
-- }
-- =====================================================
