<?php
require_once __DIR__ . "/config/auth.php";
require_once __DIR__ . "/config/db.php";

if (!isAuthenticated()) {
  header("Location: login.html");
  exit;
}

$userId = getUserId() ?? 0;
$username = getUsername() ?? "User";
$profilePic = getProfilePictureUrl();

$initial = strtoupper(mb_substr($username, 0, 1, "UTF-8"));
$colors = ["#d45a6a", "#5b86d6", "#2e8b57", "#0f766e", "#6b21a8", "#b45309", "#111111"];
$bg = $colors[$userId % count($colors)];

$submissionId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch submission details
$submission = null;
try {
  $pdo = db();
  $stmt = $pdo->prepare("
    SELECT 
      ws.id,
      ws.task_type,
      ws.task_prompt,
      ws.content,
      ws.submitted_at,
      ws.status,
      ws.analysis_result,
      ws.word_count,
      ws.error_message,
      t.task_number,
      e.exam_type
    FROM writing_submissions ws
    JOIN tasks t ON ws.task_id = t.id
    JOIN exam_variants ev ON ws.exam_variant_id = ev.id
    JOIN exams e ON ev.exam_id = e.id
    WHERE ws.id = ? AND ws.user_id = ?
    LIMIT 1
  ");
  $stmt->execute([$submissionId, $userId]);
  $submission = $stmt->fetch();
  
  if (!$submission) {
    header("Location: index.php");
    exit;
  }
  
  $result = null;
  if ($submission['status'] === 'done' && $submission['analysis_result']) {
    $result = json_decode($submission['analysis_result'], true);
  }
  
  $taskTypeLabel = ucfirst(str_replace('_', ' ', $submission['task_type'] ?? 'Unknown'));
  if ($submission['exam_type']) {
    $taskTypeLabel = ucfirst($submission['exam_type']) . ' Task ' . ($submission['task_number'] ?? '?');
  }
} catch (Exception $e) {
  header("Location: index.php");
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Submission Details</title>
  <link rel="stylesheet" href="css/home.css">
  <link rel="stylesheet" href="css/practice.css">
</head>
<body>

<header class="topbar">
  <div class="topbar-left">
    <a class="back-btn" href="index.php">← Back</a>
    <div class="hello"><span class="name"><?php echo htmlspecialchars($username); ?></span></div>
  </div>

  <div class="topbar-center">
    <h1 class="brand">IELTSEVALAI</h1>
  </div>

  <a class="avatar-link" href="profile.php" title="Profile">
    <?php if ($profilePic): ?>
      <img class="avatar" src="<?php echo htmlspecialchars($profilePic); ?>" alt="Profile picture">
    <?php else: ?>
      <div class="avatar-fallback" style="background: <?php echo htmlspecialchars($bg); ?>;">
        <?php echo htmlspecialchars($initial); ?>
      </div>
    <?php endif; ?>
  </a>
</header>

<?php require_once __DIR__ . "/includes/entitlements-display.php"; ?>

<main class="practice-container">
  <div class="practice-header">
    <h1>Submission Details - <?php echo htmlspecialchars($taskTypeLabel); ?></h1>
    <p class="submission-meta">
      Submitted: <?php echo htmlspecialchars(date('F j, Y g:i A', strtotime($submission['submitted_at']))); ?>
      | Status: <span class="status-badge status-<?php echo htmlspecialchars($submission['status']); ?>">
        <?php echo htmlspecialchars(ucfirst($submission['status'])); ?>
      </span>
    </p>
  </div>

  <div class="practice-layout">
    <div class="practice-main">
      <div class="prompt-section">
        <h2>Task Prompt</h2>
        <div class="prompt-content">
          <?php echo nl2br(htmlspecialchars($submission['task_prompt'])); ?>
        </div>
      </div>

      <div class="essay-section">
        <h2>Your Response</h2>
        <div class="essay-display">
          <?php echo nl2br(htmlspecialchars($submission['content'])); ?>
        </div>
        <div class="word-count">
          Word Count: <span><?php echo htmlspecialchars($submission['word_count'] ?? 0); ?></span>
        </div>
      </div>
    </div>

    <div class="results-panel">
      <h2>Analysis Results</h2>
      <div class="results-content">
        <?php if ($result): ?>
          <div class="analysis-result">
            <div class="score-section">
              <h3>Overall Band Score</h3>
              <div class="band-score"><?php echo htmlspecialchars($result['overall_band'] ?? 'N/A'); ?></div>
            </div>

            <div class="criteria-scores">
              <div class="score-item"><strong>TR:</strong> <?php echo htmlspecialchars($result['TR'] ?? 'N/A'); ?></div>
              <div class="score-item"><strong>CC:</strong> <?php echo htmlspecialchars($result['CC'] ?? 'N/A'); ?></div>
              <div class="score-item"><strong>LR:</strong> <?php echo htmlspecialchars($result['LR'] ?? 'N/A'); ?></div>
              <div class="score-item"><strong>GRA:</strong> <?php echo htmlspecialchars($result['GRA'] ?? 'N/A'); ?></div>
            </div>

            <?php if (isset($result['notes'])): ?>
              <div class="notes-section">
                <h3>Notes</h3>
                <?php if (isset($result['notes']['TR'])): ?>
                  <p><strong>TR:</strong> <?php echo htmlspecialchars($result['notes']['TR']); ?></p>
                <?php endif; ?>
                <?php if (isset($result['notes']['CC'])): ?>
                  <p><strong>CC:</strong> <?php echo htmlspecialchars($result['notes']['CC']); ?></p>
                <?php endif; ?>
                <?php if (isset($result['notes']['LR'])): ?>
                  <p><strong>LR:</strong> <?php echo htmlspecialchars($result['notes']['LR']); ?></p>
                <?php endif; ?>
                <?php if (isset($result['notes']['GRA'])): ?>
                  <p><strong>GRA:</strong> <?php echo htmlspecialchars($result['notes']['GRA']); ?></p>
                <?php endif; ?>
              </div>
            <?php endif; ?>

            <?php if (isset($result['overall_comment'])): ?>
              <div class="comment-section">
                <h3>Overall Comment</h3>
                <p><?php echo htmlspecialchars($result['overall_comment']); ?></p>
              </div>
            <?php endif; ?>

            <?php if (isset($result['improvement_plan']) && is_array($result['improvement_plan'])): ?>
              <div class="improvement-section">
                <h3>Improvement Plan</h3>
                <ul>
                  <?php foreach ($result['improvement_plan'] as $item): ?>
                    <li><?php echo htmlspecialchars($item); ?></li>
                  <?php endforeach; ?>
                </ul>
              </div>
            <?php endif; ?>
          </div>
        <?php elseif ($submission['status'] === 'failed'): ?>
          <div class="error-message">
            <p>Analysis failed: <?php echo htmlspecialchars($submission['error_message'] ?? 'Unknown error'); ?></p>
          </div>
        <?php else: ?>
          <div class="processing-message">
            <p>Analysis <?php echo htmlspecialchars($submission['status']); ?>...</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</main>

</body>
</html>
