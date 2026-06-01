<?php
require_once __DIR__ . "/config/auth.php";
require_once __DIR__ . "/config/db.php";

if (!isAuthenticated()) {
    header("Location: login.html");
    exit;
}

$username = getUsername() ?? "User";
$pdo = db();

// Load a random passage (extend to multi-passage later)
$passage = $pdo->query(
    "SELECT p.* FROM reading_passages p
     WHERE EXISTS (SELECT 1 FROM reading_questions q WHERE q.passage_id = p.id)
     ORDER BY RAND() LIMIT 1"
)->fetch();
if (!$passage) {
    die("No reading passages found. Please run config/reading_schema_and_data.sql first.");
}

$stmt = $pdo->prepare(
    "SELECT * FROM reading_questions WHERE passage_id = ? ORDER BY question_number ASC"
);
$stmt->execute([$passage['id']]);
$questions = $stmt->fetchAll();
$total     = count($questions);

// Handle submission
$submitted = false;
$userAnswers = [];
$score = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $submitted = true;
    foreach ($questions as $q) {
        $given = strtoupper(trim($_POST['q' . $q['id']] ?? ''));
        $userAnswers[$q['id']] = $given;
        if ($given === strtoupper($q['correct_answer'])) {
            $score++;
        }
    }
}

// Band estimation scaled to 40-question IELTS standard
function estimateBand(int $correct, int $total): string
{
    if ($total === 0) {
        return "N/A";
    }
    $scaled = ($correct / $total) * 40;
    if ($scaled >= 39) return "9.0";
    if ($scaled >= 37) return "8.5";
    if ($scaled >= 35) return "8.0";
    if ($scaled >= 33) return "7.5";
    if ($scaled >= 30) return "7.0";
    if ($scaled >= 27) return "6.5";
    if ($scaled >= 23) return "6.0";
    if ($scaled >= 19) return "5.5";
    if ($scaled >= 15) return "5.0";
    if ($scaled >= 13) return "4.5";
    if ($scaled >= 10) return "4.0";
    if ($scaled >= 8)  return "3.5";
    if ($scaled >= 6)  return "3.0";
    return "2.5";
}

$band = $submitted ? estimateBand($score, $total) : null;

// Colour per band for the result header
function bandColour(string $band): string
{
    $b = (float)$band;
    if ($b >= 7.0) return "#198754";
    if ($b >= 5.5) return "#e6a817";
    return "#dc3545";
}

$paragraphs = array_filter(
    array_map('trim', explode("\n", $passage['passage'])),
    fn($p) => $p !== ''
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>IELTS Reading Practice</title>
  <link rel="stylesheet" href="css/home.css">
  <style>
    *, *::before, *::after { box-sizing: border-box; }

    body { background: #f4f6fb; margin: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }

    /* ── Timer bar ── */
    .timer-bar {
      position: sticky;
      top: 0;
      z-index: 100;
      background: #fff;
      border-bottom: 1px solid #e8eaf0;
      padding: 10px 24px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      box-shadow: 0 2px 6px rgba(0,0,0,0.06);
    }
    .timer-bar .section-label {
      font-size: 14px;
      font-weight: 600;
      color: #666;
      letter-spacing: .4px;
      text-transform: uppercase;
    }
    .timer-display {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 20px;
      font-weight: 700;
      color: #333;
    }
    .timer-display svg { color: #5b86d6; }
    .timer-display.warning { color: #e6a817; }
    .timer-display.danger  { color: #dc3545; animation: pulse 1s infinite; }
    @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.55} }
    .timer-bar .q-counter {
      font-size: 13px;
      color: #888;
      font-weight: 500;
    }

    /* ── Main layout ── */
    .reading-wrap {
      max-width: 1380px;
      margin: 0 auto;
      padding: 24px 20px 60px;
    }
    .reading-header { margin-bottom: 20px; }
    .reading-header h1 { font-size: 26px; font-weight: 700; color: #333; margin: 0 0 4px; }
    .reading-header p  { font-size: 14px; color: #888; margin: 0; }

    .reading-grid {
      display: grid;
      grid-template-columns: 1fr 480px;
      gap: 24px;
      align-items: start;
    }

    /* ── Passage card ── */
    .passage-card {
      background: #fff;
      border-radius: 14px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.07);
      position: sticky;
      top: 66px;
      max-height: calc(100vh - 90px);
      overflow-y: auto;
    }
    .passage-card::-webkit-scrollbar { width: 6px; }
    .passage-card::-webkit-scrollbar-thumb { background: #ddd; border-radius: 3px; }
    .passage-card-header {
      padding: 20px 24px 14px;
      border-bottom: 2px solid #eef0f5;
      position: sticky;
      top: 0;
      background: #fff;
      z-index: 2;
    }
    .passage-card-header h2 {
      margin: 0;
      font-size: 18px;
      font-weight: 700;
      color: #222;
    }
    .difficulty-badge {
      display: inline-block;
      margin-top: 6px;
      padding: 2px 10px;
      border-radius: 99px;
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .5px;
      background: #eaf0fb;
      color: #5b86d6;
    }
    .passage-body { padding: 20px 24px 24px; }
    .passage-body p {
      font-size: 15px;
      line-height: 1.8;
      color: #3a3a3a;
      margin: 0 0 18px;
    }
    .passage-body p:last-child { margin-bottom: 0; }

    /* ── Questions panel ── */
    .questions-panel {
      display: flex;
      flex-direction: column;
      gap: 0;
    }
    .section-divider {
      background: #f0f3fa;
      border-radius: 10px;
      padding: 10px 16px;
      margin-bottom: 12px;
      font-size: 12px;
      font-weight: 700;
      color: #5b86d6;
      text-transform: uppercase;
      letter-spacing: .6px;
    }

    .q-card {
      background: #fff;
      border-radius: 12px;
      padding: 18px 20px;
      margin-bottom: 12px;
      box-shadow: 0 1px 6px rgba(0,0,0,0.06);
      border: 2px solid transparent;
      transition: border-color .15s;
    }
    .q-card.answered { border-color: #c8d8f7; }
    .q-card.correct  { border-color: #a8e6bf; background: #f6fff9; }
    .q-card.wrong    { border-color: #f5c6cb; background: #fff8f8; }

    .q-num {
      font-size: 11px;
      font-weight: 700;
      color: #aaa;
      text-transform: uppercase;
      letter-spacing: .5px;
      margin-bottom: 6px;
    }
    .q-text {
      font-size: 14px;
      font-weight: 600;
      color: #333;
      line-height: 1.5;
      margin-bottom: 14px;
    }

    /* TFNG options */
    .tfng-options {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
    }
    .tfng-option {
      flex: 1;
      min-width: 80px;
    }
    .tfng-option input[type="radio"] { display: none; }
    .tfng-option label {
      display: block;
      text-align: center;
      padding: 9px 6px;
      border-radius: 8px;
      border: 2px solid #e0e4ef;
      font-size: 12px;
      font-weight: 700;
      color: #555;
      cursor: pointer;
      transition: all .15s;
      user-select: none;
    }
    .tfng-option input:checked + label {
      background: #5b86d6;
      border-color: #5b86d6;
      color: #fff;
    }
    .tfng-option label:hover { border-color: #5b86d6; color: #5b86d6; }

    /* MCQ options */
    .mcq-options { display: flex; flex-direction: column; gap: 7px; }
    .mcq-option input[type="radio"] { display: none; }
    .mcq-option label {
      display: flex;
      align-items: flex-start;
      gap: 10px;
      padding: 10px 12px;
      border-radius: 8px;
      border: 2px solid #e0e4ef;
      font-size: 13px;
      color: #444;
      cursor: pointer;
      line-height: 1.45;
      transition: all .15s;
    }
    .mcq-option label .opt-key {
      flex-shrink: 0;
      width: 22px;
      height: 22px;
      border-radius: 50%;
      background: #eef1f8;
      font-weight: 700;
      font-size: 12px;
      color: #5b86d6;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .mcq-option input:checked + label {
      border-color: #5b86d6;
      background: #eef3fd;
    }
    .mcq-option input:checked + label .opt-key {
      background: #5b86d6;
      color: #fff;
    }
    .mcq-option label:hover { border-color: #5b86d6; }

    /* Post-submission states */
    .q-card.correct .tfng-option input:checked + label,
    .q-card.correct .mcq-option  input:checked + label {
      background: #198754;
      border-color: #198754;
      color: #fff;
    }
    .q-card.correct .mcq-option input:checked + label .opt-key { background: rgba(255,255,255,.3); color:#fff; }

    .q-card.wrong .tfng-option input:checked + label,
    .q-card.wrong .mcq-option  input:checked + label {
      background: #dc3545;
      border-color: #dc3545;
      color: #fff;
    }
    .q-card.wrong .mcq-option input:checked + label .opt-key { background: rgba(255,255,255,.3); color:#fff; }

    /* Correct-answer reveal after wrong */
    .correct-reveal {
      margin-top: 10px;
      padding: 8px 12px;
      background: #d1e7dd;
      border-radius: 6px;
      font-size: 12px;
      color: #0f5132;
      font-weight: 600;
    }
    .explanation-text {
      margin-top: 6px;
      font-size: 12px;
      color: #555;
      font-weight: 400;
      line-height: 1.5;
    }

    /* ── Result banner ── */
    .result-banner {
      border-radius: 14px;
      padding: 28px 24px;
      margin-bottom: 24px;
      color: #fff;
      display: flex;
      align-items: center;
      gap: 24px;
    }
    .result-band {
      font-size: 56px;
      font-weight: 800;
      line-height: 1;
      flex-shrink: 0;
    }
    .result-info h2 { margin: 0 0 4px; font-size: 22px; }
    .result-info p  { margin: 0; font-size: 14px; opacity: .88; }

    /* ── Submit / Retry buttons ── */
    .actions {
      margin-top: 8px;
      display: flex;
      gap: 12px;
    }
    .btn-submit {
      flex: 1;
      padding: 14px;
      background: #5b86d6;
      color: #fff;
      border: none;
      border-radius: 10px;
      font-size: 16px;
      font-weight: 700;
      cursor: pointer;
      transition: background .2s, transform .1s;
      box-shadow: 0 4px 12px rgba(91,134,214,.35);
    }
    .btn-submit:hover { background: #4a75c5; transform: translateY(-1px); }
    .btn-submit:active { transform: none; }
    .btn-retry {
      padding: 14px 24px;
      background: #fff;
      color: #5b86d6;
      border: 2px solid #5b86d6;
      border-radius: 10px;
      font-size: 15px;
      font-weight: 700;
      cursor: pointer;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      transition: all .2s;
    }
    .btn-retry:hover { background: #eef3fd; }

    @media (max-width: 900px) {
      .reading-grid { grid-template-columns: 1fr; }
      .passage-card { position: static; max-height: 420px; }
    }
  </style>
</head>
<body>

<!-- Topbar -->
<header class="topbar">
  <div class="topbar-left">
    <a class="back-btn" href="index.php">←</a>
    <div class="hello"><span class="name"><?= htmlspecialchars($username) ?></span></div>
  </div>
  <div class="topbar-center">
    <h1 class="brand">IELTSEVALAI</h1>
  </div>
  <div></div>
</header>

<!-- Timer bar -->
<div class="timer-bar">
  <span class="section-label">Reading Practice</span>

  <div class="timer-display" id="timerDisplay">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
      <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
    </svg>
    <span id="timerText">60:00</span>
  </div>

  <span class="q-counter"><?= $total ?> questions</span>
</div>

<div class="reading-wrap">
  <div class="reading-header">
    <h1>Academic Reading</h1>
    <p>Read the passage carefully, then answer all <?= $total ?> questions. You have 60 minutes.</p>
  </div>

  <?php if ($submitted): ?>
  <!-- Result banner -->
  <?php $bc = bandColour((string)$band); ?>
  <div class="result-banner" style="background: <?= $bc ?>;">
    <div class="result-band"><?= $band ?></div>
    <div class="result-info">
      <h2>Estimated Band Score</h2>
      <p>You answered <strong><?= $score ?> out of <?= $total ?></strong> questions correctly.</p>
      <p style="margin-top:6px;font-size:13px;opacity:.78;">
        Scroll down to review each answer. Green = correct &nbsp;·&nbsp; Red = incorrect.
      </p>
    </div>
  </div>
  <?php endif; ?>

  <form method="POST">
    <div class="reading-grid">

      <!-- Left: passage -->
      <div class="passage-card">
        <div class="passage-card-header">
          <h2><?= htmlspecialchars($passage['title']) ?></h2>
          <span class="difficulty-badge"><?= htmlspecialchars($passage['difficulty']) ?></span>
        </div>
        <div class="passage-body">
          <?php foreach ($paragraphs as $para): ?>
            <p><?= nl2br(htmlspecialchars($para)) ?></p>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Right: questions -->
      <div class="questions-panel">

        <?php
        $prevType = null;
        $typeLabels = [
            'tfng' => 'Questions 1–7 &nbsp;·&nbsp; True / False / Not Given',
            'mcq'  => 'Questions 8–14 &nbsp;·&nbsp; Multiple Choice',
        ];
        foreach ($questions as $q):
            $qId   = $q['id'];
            $qNum  = $q['question_number'];
            $qType = $q['question_type'];

            // Section divider
            if ($qType !== $prevType):
                $prevType = $qType;
        ?>
        <div class="section-divider"><?= $typeLabels[$qType] ?></div>
        <?php endif; ?>

        <?php
            // Card state
            $cardClass = 'q-card';
            if ($submitted) {
                $given   = $userAnswers[$qId] ?? '';
                $correct = strtoupper($q['correct_answer']);
                if ($given === '') {
                    $cardClass .= ' wrong';
                } elseif ($given === $correct) {
                    $cardClass .= ' correct';
                } else {
                    $cardClass .= ' wrong';
                }
            } elseif (isset($userAnswers[$qId]) && $userAnswers[$qId] !== '') {
                $cardClass .= ' answered';
            }
        ?>
        <div class="<?= $cardClass ?>">
          <div class="q-num">Question <?= $qNum ?></div>
          <div class="q-text"><?= htmlspecialchars($q['question_text']) ?></div>

          <?php if ($qType === 'tfng'): ?>
          <div class="tfng-options">
            <?php foreach (['TRUE','FALSE','NOT GIVEN'] as $opt): ?>
            <div class="tfng-option">
              <input type="radio"
                     name="q<?= $qId ?>"
                     id="q<?= $qId ?>_<?= $opt ?>"
                     value="<?= $opt ?>"
                     <?= ($submitted || isset($userAnswers[$qId]) && $userAnswers[$qId] === $opt) && ($userAnswers[$qId] ?? '') === $opt ? 'checked' : '' ?>
                     <?= $submitted ? 'disabled' : '' ?>>
              <label for="q<?= $qId ?>_<?= $opt ?>"><?= $opt ?></label>
            </div>
            <?php endforeach; ?>
          </div>

          <?php else: ?>
          <div class="mcq-options">
            <?php
            $opts = [
                'A' => $q['option_a'],
                'B' => $q['option_b'],
                'C' => $q['option_c'],
                'D' => $q['option_d'],
            ];
            foreach ($opts as $letter => $text):
                if (!$text) continue;
                $selected = $submitted
                    ? (($userAnswers[$qId] ?? '') === $letter)
                    : false;
            ?>
            <div class="mcq-option">
              <input type="radio"
                     name="q<?= $qId ?>"
                     id="q<?= $qId ?>_<?= $letter ?>"
                     value="<?= $letter ?>"
                     <?= $selected ? 'checked' : '' ?>
                     <?= $submitted ? 'disabled' : '' ?>>
              <label for="q<?= $qId ?>_<?= $letter ?>">
                <span class="opt-key"><?= $letter ?></span>
                <?= htmlspecialchars($text) ?>
              </label>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>

          <?php if ($submitted && ($userAnswers[$qId] ?? '') !== strtoupper($q['correct_answer'])): ?>
          <div class="correct-reveal">
            Correct answer: <?= htmlspecialchars($q['correct_answer']) ?>
            <?php if ($q['explanation']): ?>
            <div class="explanation-text"><?= htmlspecialchars($q['explanation']) ?></div>
            <?php endif; ?>
          </div>
          <?php endif; ?>
        </div>

        <?php endforeach; ?>

        <!-- Actions -->
        <div class="actions">
          <?php if (!$submitted): ?>
          <button type="submit" name="submit" class="btn-submit">Submit Answers</button>
          <?php else: ?>
          <a href="reading.php" class="btn-retry">Try Again</a>
          <a href="index.php" style="flex:1;text-decoration:none;">
            <button type="button" class="btn-submit">Back to Dashboard</button>
          </a>
          <?php endif; ?>
        </div>

      </div><!-- /questions-panel -->
    </div><!-- /reading-grid -->
  </form>
</div><!-- /reading-wrap -->

<script>
(function () {
  const KEY      = 'ielts_reading_timer_<?= $passage['id'] ?>';
  const DURATION = 60 * 60; // 60 minutes in seconds
  const display  = document.getElementById('timerDisplay');
  const text     = document.getElementById('timerText');
  const submitted = <?= $submitted ? 'true' : 'false' ?>;

  if (submitted) {
    text.textContent = 'Done';
    return;
  }

  let remaining = parseInt(sessionStorage.getItem(KEY) ?? DURATION, 10);
  if (isNaN(remaining) || remaining <= 0) remaining = DURATION;

  function fmt(s) {
    const m = Math.floor(s / 60).toString().padStart(2, '0');
    const sec = (s % 60).toString().padStart(2, '0');
    return m + ':' + sec;
  }

  function tick() {
    text.textContent = fmt(remaining);
    if (remaining <= 300) {
      display.classList.remove('warning');
      display.classList.add('danger');
    } else if (remaining <= 600) {
      display.classList.add('warning');
    }
    if (remaining <= 0) {
      clearInterval(id);
      sessionStorage.removeItem(KEY);
      document.querySelector('[name="submit"]')?.click();
      return;
    }
    remaining--;
    sessionStorage.setItem(KEY, remaining);
  }

  tick();
  const id = setInterval(tick, 1000);

  // Clear timer on submit
  document.querySelector('form')?.addEventListener('submit', () => {
    clearInterval(id);
    sessionStorage.removeItem(KEY);
  });
})();
</script>

</body>
</html>
