-- Migration: Reading section — tables + sample passage
-- Run once against ielts_evalai

CREATE TABLE IF NOT EXISTS reading_passages (
  id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title     VARCHAR(255) NOT NULL,
  passage   LONGTEXT     NOT NULL,
  difficulty ENUM('easy','medium','hard') DEFAULT 'medium',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS reading_questions (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  passage_id      INT UNSIGNED NOT NULL,
  question_number TINYINT UNSIGNED NOT NULL,
  question_type   ENUM('mcq','tfng') NOT NULL COMMENT 'mcq = A/B/C/D  |  tfng = TRUE/FALSE/NOT GIVEN',
  question_text   TEXT NOT NULL,
  option_a        VARCHAR(600) NULL,
  option_b        VARCHAR(600) NULL,
  option_c        VARCHAR(600) NULL,
  option_d        VARCHAR(600) NULL,
  correct_answer  VARCHAR(10)  NOT NULL,
  explanation     TEXT         NULL,
  FOREIGN KEY (passage_id) REFERENCES reading_passages(id) ON DELETE CASCADE,
  UNIQUE KEY uq_passage_qnum (passage_id, question_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- =====================================================================
-- PASSAGE 1  –  The Psychology of Colour
-- =====================================================================

INSERT IGNORE INTO reading_passages (id, title, passage, difficulty) VALUES (1,
'The Psychology of Colour',

'The influence of colour on human behaviour and emotion has been a subject of scientific study for decades. While some people dismiss the idea that colour can affect mood or decision-making, a growing body of research suggests that our responses to colour are both profound and consistent across cultures.

The relationship between colour and emotion was first systematically studied in the 1940s by Swiss colour psychologist Max Lüscher, who developed a test based on colour preferences to assess psychological states. Though Lüscher''s methods have since been criticised for lacking scientific rigour, his work inspired subsequent researchers to investigate the mechanisms by which colour affects human cognition and behaviour.

One of the most well-documented effects is the association between the colour red and increased physiological arousal. Studies conducted by researchers at the University of Rochester found that viewing red before an examination caused a temporary but measurable drop in performance. The researchers attributed this to the cultural association between red and danger or failure — a connection so deeply embedded that it can trigger an anxiety response even when the stimulus is irrelevant to the task at hand.

Blue, by contrast, has been consistently associated with feelings of calm and competence. A landmark study published in Science in 2009 demonstrated that exposure to blue environments enhanced creative thinking, while red environments improved performance on detail-oriented tasks. The study''s authors proposed an "approach-avoidance" model: red signals alertness and caution, while blue signals openness and exploration.

The commercial world has long been aware of these associations and exploits them strategically. Marketing research suggests that colour is the primary factor in a consumer''s initial product assessment, accounting for up to 85 per cent of the reason a person buys a particular product. Fast-food chains favour red and yellow because these colours are believed to stimulate appetite and create a sense of urgency, encouraging customers to eat quickly and leave. Banks and financial institutions, on the other hand, tend to prefer blue and grey, colours that project stability, trustworthiness, and professionalism.

The cultural dimension of colour psychology complicates these generalisations, however. In Western cultures, white is associated with purity and is the traditional colour of wedding dresses. In several East Asian cultures, including China, Japan and Korea, white is the colour of mourning and is worn at funerals. Similarly, while green is closely associated with nature and environmental awareness in Europe and North America, its meaning varies significantly in other contexts. In some parts of the Middle East, green carries strong religious connotations, being the favoured colour of the Prophet Muhammad.

Research into the neurological basis of colour perception has revealed that the human eye can distinguish approximately ten million different colours. The processing of colour information begins in the retina and continues through multiple pathways in the brain, eventually reaching the visual cortex. However, colour is not merely a perceptual experience; it also activates areas of the brain associated with emotion and memory. Functional magnetic resonance imaging (fMRI) studies have shown that colour can trigger activity in the amygdala, the brain region most closely associated with emotional responses.

Despite the wealth of evidence that colour affects behaviour, the mechanisms are not fully understood, and some researchers urge caution in drawing firm conclusions. The effects of colour are often subtle, short-lived, and highly context-dependent. A colour that evokes calm in one setting may produce anxiety in another. Furthermore, individual differences — including age, gender, personal history and cultural background — mean that responses to colour cannot be universally predicted.

What is clear, however, is that colour is far more than a passive visual experience. Whether subtly steering our purchasing decisions, affecting our academic performance, or triggering deep emotional memories, the colours that surround us are in constant dialogue with our psychological states.',

'medium'
);


-- =====================================================================
-- QUESTIONS 1–7  :  True / False / Not Given
-- =====================================================================

INSERT IGNORE INTO reading_questions
  (passage_id, question_number, question_type, question_text, correct_answer, explanation)
VALUES
  (1, 1, 'tfng',
   'Max Lüscher developed a colour-preference test to assess psychological states in the 1940s.',
   'TRUE',
   'The passage states he "developed a test based on colour preferences to assess psychological states" in the 1940s.'),

  (1, 2, 'tfng',
   'Lüscher''s methods have been universally accepted by modern psychologists.',
   'FALSE',
   'The passage says his methods "have since been criticised for lacking scientific rigour," which directly contradicts universal acceptance.'),

  (1, 3, 'tfng',
   'The University of Rochester study found that exposure to red improved participants'' examination scores.',
   'FALSE',
   'The passage states viewing red caused "a measurable drop in performance," not an improvement.'),

  (1, 4, 'tfng',
   'The 2009 study published in Science found that blue environments enhance creative thinking.',
   'TRUE',
   'The passage explicitly states "exposure to blue environments enhanced creative thinking."'),

  (1, 5, 'tfng',
   'Fast-food chains use red and yellow to encourage customers to linger and socialise.',
   'FALSE',
   'The passage says these colours encourage customers "to eat quickly and leave," the opposite of lingering.'),

  (1, 6, 'tfng',
   'In China, white is the traditional colour worn at wedding ceremonies.',
   'FALSE',
   'The passage states that in East Asian cultures such as China, white is the colour of mourning, worn at funerals.'),

  (1, 7, 'tfng',
   'fMRI studies have demonstrated that colour triggers activity in the frontal lobe of the brain.',
   'FALSE',
   'The passage specifies the amygdala, not the frontal lobe.');


-- =====================================================================
-- QUESTIONS 8–14  :  Multiple Choice (A / B / C / D)
-- =====================================================================

INSERT IGNORE INTO reading_questions
  (passage_id, question_number, question_type, question_text,
   option_a, option_b, option_c, option_d,
   correct_answer, explanation)
VALUES
  (1, 8, 'mcq',
   'According to the passage, why does viewing red negatively affect examination performance?',
   'Red is physically tiring on the eyes and causes eye strain.',
   'Red is culturally associated with danger and failure, triggering anxiety.',
   'Red reduces blood flow to the areas of the brain used for recall.',
   'Red creates physical discomfort that distracts the test-taker.',
   'B',
   'The passage attributes the effect to the "cultural association between red and danger or failure."'),

  (1, 9, 'mcq',
   'The "approach-avoidance" model described in the passage suggests that:',
   'Red and blue have identical effects on all types of cognitive task.',
   'Blue signals caution while red signals openness and exploration.',
   'Red signals alertness and caution, while blue signals openness and exploration.',
   'Both colours improve academic performance but in different subject areas.',
   'C',
   'The passage states: "red signals alertness and caution, while blue signals openness and exploration."'),

  (1, 10, 'mcq',
   'What proportion of a consumer''s initial product assessment is attributed to colour, according to the passage?',
   '50 per cent',
   '65 per cent',
   '75 per cent',
   '85 per cent',
   'D',
   'The passage says colour accounts for "up to 85 per cent of the reason a person buys a particular product."'),

  (1, 11, 'mcq',
   'Which statement best describes the meaning of the colour green across cultures?',
   'It universally represents environmental awareness and nature.',
   'Its meaning is broadly consistent across most world cultures.',
   'Its meaning varies considerably depending on cultural context.',
   'It is primarily associated with religious symbolism worldwide.',
   'C',
   'The passage notes green''s meaning "varies significantly in other contexts," citing both ecological and religious associations.'),

  (1, 12, 'mcq',
   'How many colours can the human eye distinguish, according to the passage?',
   'Approximately one million',
   'Approximately five million',
   'Approximately ten million',
   'Approximately one hundred million',
   'C',
   'The passage states "the human eye can distinguish approximately ten million different colours."'),

  (1, 13, 'mcq',
   'What do fMRI studies reveal about the brain''s response to colour?',
   'Colour is processed exclusively within the visual cortex.',
   'Colour activates brain regions associated with emotion and memory.',
   'Colour processing is entirely independent of emotional responses.',
   'Colour primarily stimulates the cerebellum.',
   'B',
   'The passage states fMRI studies show "colour can trigger activity in the amygdala … most closely associated with emotional responses."'),

  (1, 14, 'mcq',
   'What is the author''s main conclusion in the final paragraph?',
   'Colour has no reliably proven effect on human behaviour.',
   'Cultural differences make it impossible to draw conclusions about colour.',
   'The effects of colour are too subtle to be commercially useful.',
   'Colour continuously interacts with and influences our psychological states.',
   'D',
   'The author concludes that "the colours that surround us are in constant dialogue with our psychological states."');
