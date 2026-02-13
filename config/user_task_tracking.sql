-- =====================================================
-- User Task Tracking Table
-- =====================================================
-- This table tracks which tasks users have completed
-- to ensure they don't get the same prompt repeatedly
-- =====================================================

CREATE TABLE user_task_completions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  task_id BIGINT UNSIGNED NOT NULL,
  exam_variant_id BIGINT UNSIGNED NOT NULL,
  completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
  FOREIGN KEY (exam_variant_id) REFERENCES exam_variants(id) ON DELETE CASCADE,
  UNIQUE KEY unique_user_task (user_id, task_id),
  INDEX(user_id),
  INDEX(task_id),
  INDEX(exam_variant_id),
  INDEX(completed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- Notes:
-- =====================================================
-- This table tracks when a user completes a task.
-- The UNIQUE constraint ensures a user can only complete
-- each task once (prevents duplicates).
--
-- When querying for available tasks, exclude tasks where
-- user_id and task_id exist in this table.
-- =====================================================
