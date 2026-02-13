-- =====================================================
-- Sample IELTS Writing Tasks Data
-- =====================================================
-- This SQL file inserts sample writing tasks for
-- Academic and General Training Task 1 and Task 2
-- =====================================================

-- Insert Academic Exam
INSERT INTO exams (exam_type, code, title, description, created_by)
VALUES (
  'academic',
  'ACADEMIC_001',
  'IELTS Academic Writing Practice',
  'Sample academic writing tasks for IELTS preparation',
  NULL
);

-- Insert General Training Exam
INSERT INTO exams (exam_type, code, title, description, created_by)
VALUES (
  'general',
  'GENERAL_001',
  'IELTS General Training Writing Practice',
  'Sample general training writing tasks for IELTS preparation',
  NULL
);

-- Insert Exam Variants
-- Academic Variant
INSERT INTO exam_variants (exam_id, variant_name, metadata)
VALUES (
  (SELECT id FROM exams WHERE code = 'ACADEMIC_001' LIMIT 1),
  'Variant A',
  '{"version": "1.0", "year": 2024}'
);

-- General Training Variant
INSERT INTO exam_variants (exam_id, variant_name, metadata)
VALUES (
  (SELECT id FROM exams WHERE code = 'GENERAL_001' LIMIT 1),
  'Variant A',
  '{"version": "1.0", "year": 2024}'
);

-- =====================================================
-- ACADEMIC TASK 1 - Charts/Graphs/Processes
-- =====================================================

INSERT INTO tasks (exam_variant_id, task_type, task_number, prompt, max_score, extra)
VALUES (
  (SELECT ev.id FROM exam_variants ev JOIN exams e ON ev.exam_id = e.id WHERE e.code = 'ACADEMIC_001' LIMIT 1),
  'writing',
  1,
  'The chart below shows the percentage of households in owned and rented accommodation in England and Wales between 1918 and 2011.

Summarise the information by selecting and reporting the main features, and make comparisons where relevant.

Write at least 150 words.',
  9.00,
  '{"task_category": "bar_chart", "word_count_min": 150}'
);

INSERT INTO tasks (exam_variant_id, task_type, task_number, prompt, max_score, extra)
VALUES (
  (SELECT ev.id FROM exam_variants ev JOIN exams e ON ev.exam_id = e.id WHERE e.code = 'ACADEMIC_001' LIMIT 1),
  'writing',
  1,
  'The graph below shows the proportion of the population aged 65 and over between 1940 and 2040 in three different countries.

Summarise the information by selecting and reporting the main features, and make comparisons where relevant.

Write at least 150 words.',
  9.00,
  '{"task_category": "line_graph", "word_count_min": 150}'
);

INSERT INTO tasks (exam_variant_id, task_type, task_number, prompt, max_score, extra)
VALUES (
  (SELECT ev.id FROM exam_variants ev JOIN exams e ON ev.exam_id = e.id WHERE e.code = 'ACADEMIC_001' LIMIT 1),
  'writing',
  1,
  'The diagrams below show the life cycle of the silkworm and the stages in the production of silk cloth.

Summarise the information by selecting and reporting the main features, and make comparisons where relevant.

Write at least 150 words.',
  9.00,
  '{"task_category": "process_diagram", "word_count_min": 150}'
);

INSERT INTO tasks (exam_variant_id, task_type, task_number, prompt, max_score, extra)
VALUES (
  (SELECT ev.id FROM exam_variants ev JOIN exams e ON ev.exam_id = e.id WHERE e.code = 'ACADEMIC_001' LIMIT 1),
  'writing',
  1,
  'The table below shows the percentage of the population who owned a computer in different years in five countries.

Summarise the information by selecting and reporting the main features, and make comparisons where relevant.

Write at least 150 words.',
  9.00,
  '{"task_category": "table", "word_count_min": 150}'
);

-- =====================================================
-- ACADEMIC TASK 2 - Essays
-- =====================================================

INSERT INTO tasks (exam_variant_id, task_type, task_number, prompt, max_score, extra)
VALUES (
  (SELECT ev.id FROM exam_variants ev JOIN exams e ON ev.exam_id = e.id WHERE e.code = 'ACADEMIC_001' LIMIT 1),
  'writing',
  2,
  'Some people believe that unpaid community service should be a compulsory part of high school programmes (for example working for a charity, improving the neighbourhood or teaching sports to younger children).

To what extent do you agree or disagree?

Give reasons for your answer and include any relevant examples from your own knowledge or experience.

Write at least 250 words.',
  9.00,
  '{"task_category": "opinion", "word_count_min": 250}'
);

INSERT INTO tasks (exam_variant_id, task_type, task_number, prompt, max_score, extra)
VALUES (
  (SELECT ev.id FROM exam_variants ev JOIN exams e ON ev.exam_id = e.id WHERE e.code = 'ACADEMIC_001' LIMIT 1),
  'writing',
  2,
  'Some people think that universities should provide graduates with the knowledge and skills needed in the workplace. Others think that the true function of a university should be to give access to knowledge for its own sake, regardless of whether the course is useful to an employer.

What, in your opinion, should be the main function of a university?

Give reasons for your answer and include any relevant examples from your own knowledge or experience.

Write at least 250 words.',
  9.00,
  '{"task_category": "discussion", "word_count_min": 250}'
);

INSERT INTO tasks (exam_variant_id, task_type, task_number, prompt, max_score, extra)
VALUES (
  (SELECT ev.id FROM exam_variants ev JOIN exams e ON ev.exam_id = e.id WHERE e.code = 'ACADEMIC_001' LIMIT 1),
  'writing',
  2,
  'In many countries, the amount of crime is increasing.

What do you think are the main causes of crime? How can we deal with those causes?

Give reasons for your answer and include any relevant examples from your own knowledge or experience.

Write at least 250 words.',
  9.00,
  '{"task_category": "problem_solution", "word_count_min": 250}'
);

INSERT INTO tasks (exam_variant_id, task_type, task_number, prompt, max_score, extra)
VALUES (
  (SELECT ev.id FROM exam_variants ev JOIN exams e ON ev.exam_id = e.id WHERE e.code = 'ACADEMIC_001' LIMIT 1),
  'writing',
  2,
  'Some people believe that it is best to accept a bad situation, such as an unsatisfactory job or shortage of money. Others argue that it is better to try and improve such situations.

Discuss both these views and give your own opinion.

Give reasons for your answer and include any relevant examples from your own knowledge or experience.

Write at least 250 words.',
  9.00,
  '{"task_category": "discussion_opinion", "word_count_min": 250}'
);

-- =====================================================
-- GENERAL TRAINING TASK 1 - Letters
-- =====================================================

INSERT INTO tasks (exam_variant_id, task_type, task_number, prompt, max_score, extra)
VALUES (
  (SELECT ev.id FROM exam_variants ev JOIN exams e ON ev.exam_id = e.id WHERE e.code = 'GENERAL_001' LIMIT 1),
  'writing',
  1,
  'You are going to another country to study. You would like to do a part-time job while you are studying, so you want to ask a friend who lives there for some help.

Write a letter to your friend. In your letter:

- Give details of your study plans
- Explain why you want to get a part-time job
- Suggest how your friend could help you find a job

Write at least 150 words.

You do NOT need to write any addresses.

Begin your letter as follows:

Dear...,',
  9.00,
  '{"task_category": "informal_letter", "word_count_min": 150}'
);

INSERT INTO tasks (exam_variant_id, task_type, task_number, prompt, max_score, extra)
VALUES (
  (SELECT ev.id FROM exam_variants ev JOIN exams e ON ev.exam_id = e.id WHERE e.code = 'GENERAL_001' LIMIT 1),
  'writing',
  1,
  'You recently bought a piece of equipment for your kitchen but it did not work. You phoned the shop but no action was taken.

Write a letter to the shop manager. In your letter:

- Describe the problem with the equipment
- Explain what happened when you phoned the shop
- Say what you would like the manager to do

Write at least 150 words.

You do NOT need to write any addresses.

Begin your letter as follows:

Dear Sir or Madam,',
  9.00,
  '{"task_category": "formal_letter", "word_count_min": 150}'
);

INSERT INTO tasks (exam_variant_id, task_type, task_number, prompt, max_score, extra)
VALUES (
  (SELECT ev.id FROM exam_variants ev JOIN exams e ON ev.exam_id = e.id WHERE e.code = 'GENERAL_001' LIMIT 1),
  'writing',
  1,
  'You are going to have a party and would like to invite a friend from another country.

Write a letter to your friend. In your letter:

- Invite your friend to the party
- Give directions on how to get there
- Tell your friend what you plan to do

Write at least 150 words.

You do NOT need to write any addresses.

Begin your letter as follows:

Dear...,',
  9.00,
  '{"task_category": "semi_formal_letter", "word_count_min": 150}'
);

INSERT INTO tasks (exam_variant_id, task_type, task_number, prompt, max_score, extra)
VALUES (
  (SELECT ev.id FROM exam_variants ev JOIN exams e ON ev.exam_id = e.id WHERE e.code = 'GENERAL_001' LIMIT 1),
  'writing',
  1,
  'You are working for an international company. You have seen an advertisement for a training course which you think would be useful for your job.

Write a letter to your manager. In your letter:

- Describe the training course you want to do
- Explain what the company could do to help you
- Say how the course would be useful for your job

Write at least 150 words.

You do NOT need to write any addresses.

Begin your letter as follows:

Dear...,',
  9.00,
  '{"task_category": "semi_formal_letter", "word_count_min": 150}'
);

-- =====================================================
-- GENERAL TRAINING TASK 2 - Essays
-- =====================================================

INSERT INTO tasks (exam_variant_id, task_type, task_number, prompt, max_score, extra)
VALUES (
  (SELECT ev.id FROM exam_variants ev JOIN exams e ON ev.exam_id = e.id WHERE e.code = 'GENERAL_001' LIMIT 1),
  'writing',
  2,
  'Some people think that it is better to educate boys and girls in separate schools. Others, however, believe that boys and girls benefit more from attending mixed schools.

Discuss both these views and give your own opinion.

Give reasons for your answer and include any relevant examples from your own knowledge or experience.

Write at least 250 words.',
  9.00,
  '{"task_category": "discussion_opinion", "word_count_min": 250}'
);

INSERT INTO tasks (exam_variant_id, task_type, task_number, prompt, max_score, extra)
VALUES (
  (SELECT ev.id FROM exam_variants ev JOIN exams e ON ev.exam_id = e.id WHERE e.code = 'GENERAL_001' LIMIT 1),
  'writing',
  2,
  'Some people prefer to spend their lives doing the same things and avoiding change. Others, however, think that change is always a good thing.

Discuss both these views and give your own opinion.

Give reasons for your answer and include any relevant examples from your own knowledge or experience.

Write at least 250 words.',
  9.00,
  '{"task_category": "discussion_opinion", "word_count_min": 250}'
);

INSERT INTO tasks (exam_variant_id, task_type, task_number, prompt, max_score, extra)
VALUES (
  (SELECT ev.id FROM exam_variants ev JOIN exams e ON ev.exam_id = e.id WHERE e.code = 'GENERAL_001' LIMIT 1),
  'writing',
  2,
  'Nowadays the way many people interact with each other has changed because of technology.

In what ways has technology affected the types of relationships people make? Has this become a positive or negative development?

Give reasons for your answer and include any relevant examples from your own knowledge or experience.

Write at least 250 words.',
  9.00,
  '{"task_category": "problem_solution", "word_count_min": 250}'
);

INSERT INTO tasks (exam_variant_id, task_type, task_number, prompt, max_score, extra)
VALUES (
  (SELECT ev.id FROM exam_variants ev JOIN exams e ON ev.exam_id = e.id WHERE e.code = 'GENERAL_001' LIMIT 1),
  'writing',
  2,
  'Some people say that the best way to improve public health is by increasing the number of sports facilities. Others, however, say that this would have little effect on public health and that other measures are required.

Discuss both these views and give your own opinion.

Give reasons for your answer and include any relevant examples from your own knowledge or experience.

Write at least 250 words.',
  9.00,
  '{"task_category": "discussion_opinion", "word_count_min": 250}'
);

-- =====================================================
-- Summary
-- =====================================================
-- This file inserts:
-- - 2 Exams (Academic and General Training)
-- - 2 Exam Variants (one for each exam)
-- - 4 Academic Task 1 prompts (bar chart, line graph, process, table)
-- - 4 Academic Task 2 prompts (various essay types)
-- - 4 General Training Task 1 prompts (various letter types)
-- - 4 General Training Task 2 prompts (various essay types)
-- 
-- Total: 16 writing tasks
-- =====================================================
