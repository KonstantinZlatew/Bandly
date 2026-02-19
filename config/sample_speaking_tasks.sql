INSERT INTO tasks (exam_variant_id, task_type, task_number, prompt, max_score, extra)
VALUES (
  (SELECT ev.id FROM exam_variants ev JOIN exams e ON ev.exam_id = e.id WHERE e.code = 'ACADEMIC_001' LIMIT 1),
  'speaking',
  2,
  'Describe a person who has influenced you.

You should say:
- who this person is
- how you know this person
- what qualities this person has
- and explain why this person has influenced you.',
  9.00,
  '{"task_category": "person", "time_limit_seconds": 120}'
);

INSERT INTO tasks (exam_variant_id, task_type, task_number, prompt, max_score, extra)
VALUES (
  (SELECT ev.id FROM exam_variants ev JOIN exams e ON ev.exam_id = e.id WHERE e.code = 'ACADEMIC_001' LIMIT 1),
  'speaking',
  2,
  'Describe a place you have visited that you found interesting.

You should say:
- where it is
- when you visited it
- what you did there
- and explain why you found it interesting.',
  9.00,
  '{"task_category": "place", "time_limit_seconds": 120}'
);

INSERT INTO tasks (exam_variant_id, task_type, task_number, prompt, max_score, extra)
VALUES (
  (SELECT ev.id FROM exam_variants ev JOIN exams e ON ev.exam_id = e.id WHERE e.code = 'ACADEMIC_001' LIMIT 1),
  'speaking',
  2,
  'Describe an object that is important to you.

You should say:
- what it is
- where you got it from
- how long you have had it
- and explain why it is important to you.',
  9.00,
  '{"task_category": "object", "time_limit_seconds": 120}'
);

INSERT INTO tasks (exam_variant_id, task_type, task_number, prompt, max_score, extra)
VALUES (
  (SELECT ev.id FROM exam_variants ev JOIN exams e ON ev.exam_id = e.id WHERE e.code = 'ACADEMIC_001' LIMIT 1),
  'speaking',
  2,
  'Describe a skill you have learned that you think is useful.

You should say:
- what the skill is
- how you learned it
- when you use it
- and explain why you think it is useful.',
  9.00,
  '{"task_category": "skill", "time_limit_seconds": 120}'
);

INSERT INTO tasks (exam_variant_id, task_type, task_number, prompt, max_score, extra)
VALUES (
  (SELECT ev.id FROM exam_variants ev JOIN exams e ON ev.exam_id = e.id WHERE e.code = 'ACADEMIC_001' LIMIT 1),
  'speaking',
  2,
  'Describe a memorable event in your life.

You should say:
- what the event was
- when and where it happened
- who was involved
- and explain why it was memorable.',
  9.00,
  '{"task_category": "event", "time_limit_seconds": 120}'
);

INSERT INTO tasks (exam_variant_id, task_type, task_number, prompt, max_score, extra)
VALUES (
  (SELECT ev.id FROM exam_variants ev JOIN exams e ON ev.exam_id = e.id WHERE e.code = 'ACADEMIC_001' LIMIT 1),
  'speaking',
  2,
  'Describe a book you have read that you found interesting.

You should say:
- what the book is
- who wrote it
- what it is about
- and explain why you found it interesting.',
  9.00,
  '{"task_category": "book", "time_limit_seconds": 120}'
);

INSERT INTO tasks (exam_variant_id, task_type, task_number, prompt, max_score, extra)
VALUES (
  (SELECT ev.id FROM exam_variants ev JOIN exams e ON ev.exam_id = e.id WHERE e.code = 'ACADEMIC_001' LIMIT 1),
  'speaking',
  2,
  'Describe a piece of technology that you use regularly.

You should say:
- what it is
- how often you use it
- what you use it for
- and explain why it is important to you.',
  9.00,
  '{"task_category": "technology", "time_limit_seconds": 120}'
);

INSERT INTO tasks (exam_variant_id, task_type, task_number, prompt, max_score, extra)
VALUES (
  (SELECT ev.id FROM exam_variants ev JOIN exams e ON ev.exam_id = e.id WHERE e.code = 'ACADEMIC_001' LIMIT 1),
  'speaking',
  2,
  'Describe a hobby or activity you enjoy doing in your free time.

You should say:
- what the hobby or activity is
- how often you do it
- where you do it
- and explain why you enjoy it.',
  9.00,
  '{"task_category": "hobby", "time_limit_seconds": 120}'
);

INSERT INTO tasks (exam_variant_id, task_type, task_number, prompt, max_score, extra)
VALUES (
  (SELECT ev.id FROM exam_variants ev JOIN exams e ON ev.exam_id = e.id WHERE e.code = 'ACADEMIC_001' LIMIT 1),
  'speaking',
  2,
  'Describe a time when you helped someone.

You should say:
- who you helped
- what you did to help them
- why they needed help
- and explain how you felt about helping them.',
  9.00,
  '{"task_category": "experience", "time_limit_seconds": 120}'
);

INSERT INTO tasks (exam_variant_id, task_type, task_number, prompt, max_score, extra)
VALUES (
  (SELECT ev.id FROM exam_variants ev JOIN exams e ON ev.exam_id = e.id WHERE e.code = 'ACADEMIC_001' LIMIT 1),
  'speaking',
  2,
  'Describe a goal or ambition you have for the future.

You should say:
- what the goal is
- when you hope to achieve it
- what steps you need to take
- and explain why this goal is important to you.',
  9.00,
  '{"task_category": "goal", "time_limit_seconds": 120}'
);

-- =====================================================
-- GENERAL TRAINING SPEAKING TASK 2 - Cue Cards
-- =====================================================

INSERT INTO tasks (exam_variant_id, task_type, task_number, prompt, max_score, extra)
VALUES (
  (SELECT ev.id FROM exam_variants ev JOIN exams e ON ev.exam_id = e.id WHERE e.code = 'GENERAL_001' LIMIT 1),
  'speaking',
  2,
  'Describe a person you know who is very kind.

You should say:
- who this person is
- how you know them
- what kind things they do
- and explain why you think they are kind.',
  9.00,
  '{"task_category": "person", "time_limit_seconds": 120}'
);

INSERT INTO tasks (exam_variant_id, task_type, task_number, prompt, max_score, extra)
VALUES (
  (SELECT ev.id FROM exam_variants ev JOIN exams e ON ev.exam_id = e.id WHERE e.code = 'GENERAL_001' LIMIT 1),
  'speaking',
  2,
  'Describe a restaurant or café you like to go to.

You should say:
- where it is
- what kind of food or drinks it serves
- how often you go there
- and explain why you like it.',
  9.00,
  '{"task_category": "place", "time_limit_seconds": 120}'
);

INSERT INTO tasks (exam_variant_id, task_type, task_number, prompt, max_score, extra)
VALUES (
  (SELECT ev.id FROM exam_variants ev JOIN exams e ON ev.exam_id = e.id WHERE e.code = 'GENERAL_001' LIMIT 1),
  'speaking',
  2,
  'Describe a gift you have received that was special to you.

You should say:
- what the gift was
- who gave it to you
- when you received it
- and explain why it was special to you.',
  9.00,
  '{"task_category": "object", "time_limit_seconds": 120}'
);

INSERT INTO tasks (exam_variant_id, task_type, task_number, prompt, max_score, extra)
VALUES (
  (SELECT ev.id FROM exam_variants ev JOIN exams e ON ev.exam_id = e.id WHERE e.code = 'GENERAL_001' LIMIT 1),
  'speaking',
  2,
  'Describe a time when you had to wait for something.

You should say:
- what you were waiting for
- where you were waiting
- how long you had to wait
- and explain how you felt about waiting.',
  9.00,
  '{"task_category": "experience", "time_limit_seconds": 120}'
);

INSERT INTO tasks (exam_variant_id, task_type, task_number, prompt, max_score, extra)
VALUES (
  (SELECT ev.id FROM exam_variants ev JOIN exams e ON ev.exam_id = e.id WHERE e.code = 'GENERAL_001' LIMIT 1),
  'speaking',
  2,
  'Describe a festival or celebration in your country.

You should say:
- what the festival is
- when it is celebrated
- what people do during this festival
- and explain why you enjoy it.',
  9.00,
  '{"task_category": "event", "time_limit_seconds": 120}'
);

INSERT INTO tasks (exam_variant_id, task_type, task_number, prompt, max_score, extra)
VALUES (
  (SELECT ev.id FROM exam_variants ev JOIN exams e ON ev.exam_id = e.id WHERE e.code = 'GENERAL_001' LIMIT 1),
  'speaking',
  2,
  'Describe a piece of clothing you like to wear.

You should say:
- what it is
- when and where you wear it
- what it looks like
- and explain why you like wearing it.',
  9.00,
  '{"task_category": "object", "time_limit_seconds": 120}'
);

INSERT INTO tasks (exam_variant_id, task_type, task_number, prompt, max_score, extra)
VALUES (
  (SELECT ev.id FROM exam_variants ev JOIN exams e ON ev.exam_id = e.id WHERE e.code = 'GENERAL_001' LIMIT 1),
  'speaking',
  2,
  'Describe a time when you made a mistake.

You should say:
- what the mistake was
- when it happened
- what you did about it
- and explain what you learned from it.',
  9.00,
  '{"task_category": "experience", "time_limit_seconds": 120}'
);

INSERT INTO tasks (exam_variant_id, task_type, task_number, prompt, max_score, extra)
VALUES (
  (SELECT ev.id FROM exam_variants ev JOIN exams e ON ev.exam_id = e.id WHERE e.code = 'GENERAL_001' LIMIT 1),
  'speaking',
  2,
  'Describe a place in your city or town that you like to visit.

You should say:
- where it is
- what you can do there
- how often you go there
- and explain why you like it.',
  9.00,
  '{"task_category": "place", "time_limit_seconds": 120}'
);

INSERT INTO tasks (exam_variant_id, task_type, task_number, prompt, max_score, extra)
VALUES (
  (SELECT ev.id FROM exam_variants ev JOIN exams e ON ev.exam_id = e.id WHERE e.code = 'GENERAL_001' LIMIT 1),
  'speaking',
  2,
  'Describe a friend you have known for a long time.

You should say:
- how you met
- how long you have known each other
- what you like about them
- and explain why your friendship is important to you.',
  9.00,
  '{"task_category": "person", "time_limit_seconds": 120}'
);

INSERT INTO tasks (exam_variant_id, task_type, task_number, prompt, max_score, extra)
VALUES (
  (SELECT ev.id FROM exam_variants ev JOIN exams e ON ev.exam_id = e.id WHERE e.code = 'GENERAL_001' LIMIT 1),
  'speaking',
  2,
  'Describe a time when you were very busy.

You should say:
- when it was
- what you were busy doing
- why you were so busy
- and explain how you managed the situation.',
  9.00,
  '{"task_category": "experience", "time_limit_seconds": 120}'
);
