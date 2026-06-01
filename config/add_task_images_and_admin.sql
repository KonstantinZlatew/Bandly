-- Migration: Task-owned images for Academic Writing Task 1 + admin user
-- Run this once against the ielts_evalai database.

-- 1. Add image_file_id to tasks table
ALTER TABLE tasks
  ADD COLUMN image_file_id BIGINT UNSIGNED NULL,
  ADD CONSTRAINT fk_tasks_image_file
    FOREIGN KEY (image_file_id) REFERENCES files(id) ON DELETE SET NULL;

-- 2. Create admin user (password: Admin1234)
INSERT INTO users (username, email, password_hash, is_admin)
VALUES (
  'admin',
  'admin@gmail.com',
  '$2y$10$44uwohDXKjKrmYO3i0qhJefFCJeVp0ADzYpBt/.Rp.ARgX9Nz98.6',
  1
);
