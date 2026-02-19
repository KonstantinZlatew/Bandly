-- =====================================================
-- Speaking Submissions Async Processing Schema
-- =====================================================
-- Adds status tracking and analysis result fields to
-- speaking_submissions table for async processing
-- =====================================================

-- 1. Add status and analysis fields to speaking_submissions
ALTER TABLE speaking_submissions
ADD COLUMN status ENUM('pending', 'processing', 'done', 'failed') DEFAULT 'pending' AFTER submitted_at,
ADD COLUMN analysis_result JSON NULL AFTER status,
ADD COLUMN error_message TEXT NULL AFTER analysis_result,
ADD COLUMN processed_at TIMESTAMP NULL AFTER error_message,
ADD COLUMN task_prompt TEXT NULL AFTER task_id;

-- Add indexes for efficient worker queries
ALTER TABLE speaking_submissions
ADD INDEX idx_status (status),
ADD INDEX idx_status_submitted (status, submitted_at);

-- =====================================================
-- Notes:
-- =====================================================
-- Status values:
--   - 'pending': Recording submitted, waiting for processing
--   - 'processing': Currently being analyzed by a worker
--   - 'done': Analysis complete, result available
--   - 'failed': Analysis failed, error_message contains details
--
-- analysis_result JSON structure:
-- {
--   "overall_band": 7.5,
--   "FC": 7.0,  (Fluency and Coherence)
--   "LR": 8.0,  (Lexical Resource)
--   "GRA": 7.0, (Grammar)
--   "PR": 7.5,  (Pronunciation)
--   "transcript": "The transcribed text...",
--   "notes": {
--     "FC": "Feedback on fluency and coherence",
--     "LR": "Feedback on lexical resource",
--     "GRA": "Feedback on grammar",
--     "PR": "Feedback on pronunciation"
--   },
--   "overall_comment": "Overall assessment...",
--   "improvement_plan": ["Suggestion 1", "Suggestion 2"]
-- }
-- =====================================================
