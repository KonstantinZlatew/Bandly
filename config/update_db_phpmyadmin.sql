-- =====================================================
-- Database Schema Updates for IELTS Evaluation Storage
-- Paste this into phpMyAdmin SQL tab
-- =====================================================

-- 1. Add columns to writing_submissions table
ALTER TABLE writing_submissions
ADD COLUMN task_prompt TEXT NULL AFTER task_id,
ADD COLUMN task_type ENUM('academic_task_1', 'general_task_1', 'task_2') NULL AFTER task_prompt,
ADD COLUMN image_file_id BIGINT UNSIGNED NULL AFTER file_id;

-- Add foreign key for image file
ALTER TABLE writing_submissions
ADD CONSTRAINT fk_writing_sub_image_file FOREIGN KEY (image_file_id) REFERENCES files(id) ON DELETE SET NULL;

-- Add index for faster queries
ALTER TABLE writing_submissions
ADD INDEX idx_task_type (task_type);

-- 2. Add columns to evaluations table for detailed scores and feedback
ALTER TABLE evaluations
ADD COLUMN tr_score DECIMAL(3,1) NULL AFTER overall_score,
ADD COLUMN cc_score DECIMAL(3,1) NULL AFTER tr_score,
ADD COLUMN lr_score DECIMAL(3,1) NULL AFTER cc_score,
ADD COLUMN gra_score DECIMAL(3,1) NULL AFTER lr_score,
ADD COLUMN notes JSON NULL AFTER gra_score,
ADD COLUMN improvement_plan JSON NULL AFTER notes,
ADD COLUMN word_count INT UNSIGNED NULL AFTER improvement_plan,
ADD COLUMN used_rag TINYINT(1) DEFAULT 0 AFTER word_count,
ADD COLUMN image_analysis JSON NULL AFTER used_rag;

-- Add indexes for common queries
ALTER TABLE evaluations
ADD INDEX idx_overall_score (overall_score),
ADD INDEX idx_tr_score (tr_score),
ADD INDEX idx_created_at (created_at);

-- 3. Update ai_jobs table to track task type
ALTER TABLE ai_jobs
ADD COLUMN task_type ENUM('academic_task_1', 'general_task_1', 'task_2') NULL AFTER submission_type,
ADD INDEX idx_task_type (task_type);

-- =====================================================
-- Example INSERT statements (for reference)
-- =====================================================

-- Example: Insert a writing submission with task prompt and image
/*
INSERT INTO writing_submissions (
    user_id, 
    exam_variant_id, 
    task_id, 
    content, 
    word_count, 
    task_prompt, 
    task_type, 
    image_file_id
) VALUES (
    1,  -- user_id
    1,  -- exam_variant_id
    1,  -- task_id
    'The graph illustrates the proportion of households...',  -- essay content
    163,  -- word_count
    'The chart below shows the percentage of households with internet access...',  -- task_prompt
    'academic_task_1',  -- task_type
    5  -- image_file_id (reference to files table)
);
*/

-- Example: Insert an evaluation with all details
/*
INSERT INTO evaluations (
    submission_type,
    submission_id,
    evaluator,
    overall_score,
    tr_score,
    cc_score,
    lr_score,
    gra_score,
    notes,
    feedback,
    improvement_plan,
    word_count,
    used_rag,
    image_analysis
) VALUES (
    'writing',
    1,  -- submission_id
    'ai',
    7.0,  -- overall_score
    7.0,  -- tr_score
    7.0,  -- cc_score
    7.0,  -- lr_score
    7.0,  -- gra_score
    '{"TR": "The candidate accurately describes the revenue and expenditures, covering key features and data points.", "CC": "The essay is well-structured and logically organized, making comparisons clear.", "LR": "The vocabulary is appropriate for the task, with varied language used effectively.", "GRA": "Grammar and sentence structure are mostly correct, with few errors."}',
    'The candidate provides a clear and comprehensive summary of the pie chart, effectively highlighting the main features.',
    '["Include more varied sentence structures to enhance readability.", "Add a brief conclusion summarizing the overall financial health.", "Ensure all percentages are consistently rounded for clarity."]',
    163,  -- word_count
    0,  -- used_rag (false)
    '{"image_provided": true, "analysis_status": "completed", "visual_type": ["Pie Chart"], "key_features_identified": ["Dominance of donated food", "High percentage of expenditures"], "data_points_extracted": true}'
);
*/

-- =====================================================
-- Query Examples
-- =====================================================

-- Get all evaluations with submission details
/*
SELECT 
    e.id,
    e.overall_score,
    e.tr_score,
    e.cc_score,
    e.lr_score,
    e.gra_score,
    ws.task_type,
    ws.task_prompt,
    ws.content AS essay,
    e.feedback,
    e.word_count,
    e.created_at
FROM evaluations e
JOIN writing_submissions ws ON e.submission_id = ws.id
WHERE e.submission_type = 'writing'
ORDER BY e.created_at DESC;
*/

-- Get evaluations with image analysis
/*
SELECT 
    e.id,
    e.overall_score,
    ws.task_type,
    e.image_analysis,
    f.storage_key AS image_path
FROM evaluations e
JOIN writing_submissions ws ON e.submission_id = ws.id
LEFT JOIN files f ON ws.image_file_id = f.id
WHERE e.image_analysis IS NOT NULL;
*/
