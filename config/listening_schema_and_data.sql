-- Migration: Listening section — tables + sample test
-- Run once against ielts_evalai

CREATE TABLE IF NOT EXISTS listening_tests (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title           VARCHAR(255) NOT NULL,
  youtube_id      VARCHAR(20)  NOT NULL,
  section_label   VARCHAR(50)  DEFAULT 'Section 1',
  difficulty      ENUM('easy','medium','hard') DEFAULT 'medium',
  duration_seconds INT UNSIGNED DEFAULT 1800,
  created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS listening_questions (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  test_id         INT UNSIGNED NOT NULL,
  question_number TINYINT UNSIGNED NOT NULL,
  question_text   TEXT NOT NULL,
  option_a        VARCHAR(600) NOT NULL,
  option_b        VARCHAR(600) NOT NULL,
  option_c        VARCHAR(600) NOT NULL,
  option_d        VARCHAR(600) NOT NULL,
  correct_answer  CHAR(1)      NOT NULL COMMENT 'A / B / C / D',
  explanation     TEXT         NULL,
  FOREIGN KEY (test_id) REFERENCES listening_tests(id) ON DELETE CASCADE,
  UNIQUE KEY uq_test_qnum (test_id, question_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- =====================================================================
-- TEST 1  –  Children's Engineering Workshops  (Section 1)
-- =====================================================================

INSERT IGNORE INTO listening_tests (id, title, youtube_id, section_label, difficulty, duration_seconds)
VALUES (1,
  'Children\'s Engineering Workshops — Telephone Enquiry',
  'CzIDllyDSwc',
  'Section 1',
  'medium',
  1800
);


-- =====================================================================
-- QUESTIONS 1–10  :  Multiple Choice
-- =====================================================================

INSERT IGNORE INTO listening_questions
  (test_id, question_number, question_text, option_a, option_b, option_c, option_d, correct_answer, explanation)
VALUES
  (1, 1,
   'What is the name of the person making the enquiry?',
   'Ms Harrison', 'Mrs Henderson', 'Miss Harrington', 'Ms Hendon',
   'B',
   'The caller gives her name as Mrs Henderson when asked by the receptionist.'),

  (1, 2,
   'What is the minimum age requirement for the workshops?',
   '6 years old', '7 years old', '8 years old', '9 years old',
   'C',
   'The receptionist states children must be at least 8 years old to participate.'),

  (1, 3,
   'Which workshop topic does the caller select for her son?',
   'Robotics and coding', 'Bridges and structures', 'Electronics and circuits', 'Model rockets',
   'B',
   'After hearing the options, she chooses the Bridges and Structures workshop.'),

  (1, 4,
   'On which day of the week do the workshops take place?',
   'Wednesday', 'Thursday', 'Friday', 'Saturday',
   'D',
   'The receptionist confirms that all workshops run on Saturdays.'),

  (1, 5,
   'How long does each workshop session last?',
   '1 hour', '1.5 hours', '2 hours', '2.5 hours',
   'C',
   'Each session is two hours long, running from 10 am until noon.'),

  (1, 6,
   'What are children advised to wear to the workshop?',
   'Their school uniform', 'Smart casual clothes', 'Old clothes they do not mind getting dirty', 'A special overall provided by the centre',
   'C',
   'The receptionist recommends old clothes as the activities can get messy.'),

  (1, 7,
   'What is the cost per child per session?',
   '£10', '£12', '£15', '£18',
   'C',
   'The fee is £15 per child per session, payable in advance.'),

  (1, 8,
   'How far in advance must bookings be made?',
   '24 hours', '48 hours', '3 days', 'One week',
   'D',
   'Bookings must be made at least one week before the chosen session.'),

  (1, 9,
   'What is the maximum number of children allowed per session?',
   '8', '10', '12', '15',
   'C',
   'The receptionist explains that sessions are capped at 12 children to ensure hands-on time.'),

  (1, 10,
   'Where is the engineering centre located?',
   'Next to the public library', 'Behind the sports hall', 'Opposite the swimming pool', 'Next to the town hall',
   'D',
   'The centre is described as being right next to the town hall, easy to find from the main road.');
