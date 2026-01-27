CREATE TABLE users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  email VARCHAR(255) DEFAULT NULL,
  password_hash VARCHAR(255) DEFAULT NULL,
  is_admin TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  last_login TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE user_profiles (
  profile_picture_url VARCHAR(1000) DEFAULT NULL,
  user_id BIGINT UNSIGNED PRIMARY KEY,
  full_name VARCHAR(255),
  country VARCHAR(100),
  preferred_lang VARCHAR(10) DEFAULT 'en',
  settings JSON DEFAULT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE exams (
  exam_type ENUM('academic','general') NOT NULL DEFAULT 'academic',
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(50) NOT NULL,
  title VARCHAR(255) NOT NULL,
  description TEXT,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE exam_variants (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  exam_id BIGINT UNSIGNED NOT NULL,
  variant_name VARCHAR(255),
  metadata JSON DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE tasks (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  exam_variant_id BIGINT UNSIGNED NOT NULL,
  task_type ENUM('writing','speaking','listening','reading') NOT NULL,
  task_number INT NOT NULL,
  prompt TEXT NOT NULL,
  max_score DECIMAL(5,2) DEFAULT 9.00,
  rubric_id BIGINT UNSIGNED NULL,
  extra JSON DEFAULT NULL,
  FOREIGN KEY (exam_variant_id) REFERENCES exam_variants(id) ON DELETE CASCADE,
  FOREIGN KEY (rubric_id) REFERENCES rubrics(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE rubrics (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255),
  task_type ENUM('writing','speaking') NOT NULL,
  criteria JSON NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE writing_submissions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  exam_variant_id BIGINT UNSIGNED NOT NULL,
  task_id BIGINT UNSIGNED NOT NULL,
  content TEXT NOT NULL,
  word_count INT NULL,
  submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  file_id BIGINT UNSIGNED NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (exam_variant_id) REFERENCES exam_variants(id) ON DELETE CASCADE,
  FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
  FOREIGN KEY (file_id) REFERENCES files(id) ON DELETE SET NULL,
  INDEX(user_id), INDEX(exam_variant_id), INDEX(task_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE speaking_submissions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  exam_variant_id BIGINT UNSIGNED NOT NULL,
  task_id BIGINT UNSIGNED NOT NULL,
  audio_url VARCHAR(1000) NOT NULL,
  audio_duration_seconds INT DEFAULT NULL,
  transcript TEXT DEFAULT NULL,
  submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  file_id BIGINT UNSIGNED NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (exam_variant_id) REFERENCES exam_variants(id) ON DELETE CASCADE,
  FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
  FOREIGN KEY (file_id) REFERENCES files(id) ON DELETE SET NULL,
  INDEX(user_id), INDEX(exam_variant_id), INDEX(task_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE evaluations (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  submission_type ENUM('writing','speaking') NOT NULL,
  submission_id BIGINT UNSIGNED NOT NULL,
  evaluator ENUM('ai','human') NOT NULL,
  evaluator_id BIGINT UNSIGNED NULL,
  overall_score DECIMAL(4,2) NOT NULL,
  criteria_scores JSON DEFAULT NULL,
  feedback TEXT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (submission_type, submission_id),
  FOREIGN KEY (evaluator_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE files (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  storage_key VARCHAR(1000) NOT NULL,
  mime VARCHAR(100),
  size_bytes BIGINT DEFAULT 0,
  uploaded_by BIGINT UNSIGNED NULL,
  uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE ai_jobs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  submission_type ENUM('writing','speaking') NULL,
  submission_id BIGINT UNSIGNED NULL,
  model VARCHAR(100),
  input JSON,
  output JSON,
  latency_ms INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE plans (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(50) NOT NULL UNIQUE,
  name VARCHAR(100) NOT NULL,
  plan_type ENUM('credits','subscription') NOT NULL,
  credits_amount INT UNSIGNED DEFAULT NULL,
  duration_days INT UNSIGNED DEFAULT NULL,
  price_cents INT UNSIGNED NOT NULL,
  currency CHAR(3) NOT NULL DEFAULT 'EUR',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO plans (code, name, plan_type, credits_amount, duration_days, price_cents, currency)
VALUES
('CREDITS_10', '10 Credits Pack', 'credits', 10, NULL, 999, 'EUR'),
('SUB_MONTH', 'Monthly Unlimited', 'subscription', NULL, 30, 1299, 'EUR'),
('SUB_YEAR', 'Yearly Unlimited', 'subscription', NULL, 365, 9999, 'EUR');


CREATE TABLE user_entitlements (
  user_id BIGINT UNSIGNED PRIMARY KEY,
  credits_balance INT UNSIGNED NOT NULL DEFAULT 0,
  unlimited_until DATETIME DEFAULT NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_ent_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX (unlimited_until)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO user_entitlements (user_id)
SELECT id FROM users
ON DUPLICATE KEY UPDATE user_id = user_id;


CREATE TABLE purchases (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  plan_id BIGINT UNSIGNED NOT NULL,
  provider ENUM('stripe') NOT NULL DEFAULT 'stripe',
  provider_payment_intent_id VARCHAR(255) DEFAULT NULL,
  provider_checkout_session_id VARCHAR(255) DEFAULT NULL,
  amount_cents INT UNSIGNED NOT NULL,
  currency CHAR(3) NOT NULL DEFAULT 'EUR',
  status ENUM('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  paid_at DATETIME DEFAULT NULL,
  CONSTRAINT fk_purch_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_purch_plan FOREIGN KEY (plan_id) REFERENCES plans(id),
  INDEX(user_id),
  INDEX(plan_id),
  INDEX(status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE user_subscriptions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  plan_id BIGINT UNSIGNED NOT NULL,
  provider ENUM('stripe') NOT NULL DEFAULT 'stripe',
  provider_customer_id VARCHAR(255) DEFAULT NULL,
  provider_subscription_id VARCHAR(255) DEFAULT NULL,
  status ENUM('active','trialing','past_due','canceled','incomplete','incomplete_expired') NOT NULL,
  current_period_start DATETIME DEFAULT NULL,
  current_period_end DATETIME DEFAULT NULL,
  cancel_at_period_end TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_sub_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_sub_plan FOREIGN KEY (plan_id) REFERENCES plans(id),
  INDEX(user_id),
  INDEX(status),
  INDEX(current_period_end)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
