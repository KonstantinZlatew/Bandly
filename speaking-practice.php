<?php
require_once __DIR__ . "/config/auth.php";
require_once __DIR__ . "/config/db.php";
require_once __DIR__ . "/includes/entitlements-check.php";

if (!isAuthenticated()) {
  header("Location: login.html");
  exit;
}

$type = $_GET["type"] ?? "academic";
$section = $_GET["section"] ?? "speaking";
$part = $_GET["part"] ?? "2";

// Validate inputs
if ($type !== "academic" && $type !== "general") $type = "academic";
if ($section !== "speaking" || $part !== "2") {
  header("Location: exam.php?type=" . $type);
  exit;
}

$userId = getUserId() ?? 0;

// Check entitlements
$entitlementCheck = checkCanAnalyze($userId);

$pageTitle = ($type === "academic") ? "IELTS Academic" : "IELTS General";
$taskTitle = "Speaking Task 2";

// Fetch task prompt from database (excluding tasks user has already seen)
$prompt = "";
$taskId = null;
$examVariantId = null;
$allTasksSeen = false;
try {
  $pdo = db();
  // Fetch a task that the user hasn't seen yet
  $stmt = $pdo->prepare("
    SELECT t.id, t.prompt, t.exam_variant_id
    FROM tasks t
    JOIN exam_variants ev ON t.exam_variant_id = ev.id
    JOIN exams e ON ev.exam_id = e.id
    WHERE t.task_type = 'speaking' 
    AND t.task_number = 2
    AND e.exam_type = ?
    AND t.id NOT IN (
      SELECT utc.task_id 
      FROM user_task_completions utc 
      WHERE utc.user_id = ?
    )
    ORDER BY RAND()
    LIMIT 1
  ");
  $stmt->execute([$type, $userId]);
  $taskData = $stmt->fetch();
  
  if ($taskData) {
    $prompt = $taskData['prompt'];
    $taskId = $taskData['id'];
    $examVariantId = $taskData['exam_variant_id'];
  } else {
    // If all tasks are seen, fetch any task
    $stmt = $pdo->prepare("
      SELECT t.id, t.prompt, t.exam_variant_id
      FROM tasks t
      JOIN exam_variants ev ON t.exam_variant_id = ev.id
      JOIN exams e ON ev.exam_id = e.id
      WHERE t.task_type = 'speaking' 
      AND t.task_number = 2
      AND e.exam_type = ?
      ORDER BY RAND()
      LIMIT 1
    ");
    $stmt->execute([$type]);
    $taskData = $stmt->fetch();
    
    if ($taskData) {
      $prompt = $taskData['prompt'];
      $taskId = $taskData['id'];
      $examVariantId = $taskData['exam_variant_id'];
      $allTasksSeen = true; // Flag to show message
    } else {
      $prompt = "No speaking task found in database. Please add a task prompt.";
    }
  }
} catch (Exception $e) {
  $prompt = "Error loading task prompt. Please try again later.";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?php echo htmlspecialchars($pageTitle . " - " . $taskTitle); ?></title>
  <link rel="stylesheet" href="css/home.css">
  <link rel="stylesheet" href="css/speaking-practice.css">
</head>
<body>

<?php require_once __DIR__ . "/includes/navbar.php"; ?>

<?php require_once __DIR__ . "/includes/entitlements-display.php"; ?>

<main class="practice-container">
  <div class="practice-header">
    <h1><?php echo htmlspecialchars($pageTitle . " - " . $taskTitle); ?></h1>
  </div>

  <div class="practice-layout">
    <!-- Left side: Prompt and Recording -->
    <div class="practice-main">
      <div class="prompt-section">
        <h2>Cue Card</h2>
        <div class="cue-card">
          <div class="prompt-content" id="promptContent">
            <?php echo nl2br(htmlspecialchars($prompt)); ?>
          </div>
          <?php if ($taskId): ?>
            <input type="hidden" id="taskId" value="<?php echo htmlspecialchars($taskId); ?>">
            <input type="hidden" id="examVariantId" value="<?php echo htmlspecialchars($examVariantId ?? ''); ?>">
          <?php endif; ?>
          <input type="hidden" id="taskType" value="speaking">
          <input type="hidden" id="taskPrompt" value="<?php echo htmlspecialchars($prompt); ?>">
          <?php if (isset($allTasksSeen) && $allTasksSeen): ?>
            <div class="info-message" style="margin-top: 15px; padding: 10px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 4px; color: #856404;">
              You have seen all available tasks for this type. This is a repeated task.
            </div>
          <?php endif; ?>
        </div>
      </div>

      <div class="recording-section">
        <h2>Your Recording</h2>
        <div class="recording-controls">
          <button id="recordBtn" class="record-btn">
            <span class="record-icon">●</span>
            <span class="record-text">Record</span>
          </button>
          <button id="stopBtn" class="stop-btn" style="display: none;">
            <span class="stop-text">Stop</span>
          </button>
          <div id="timer" class="timer" style="display: none;">
            <span id="timerText">00:00</span>
          </div>
        </div>
        
        <div id="recordingStatus" class="recording-status"></div>
        
        <div id="audioPlayback" class="audio-playback" style="display: none;">
          <h3>Your Recording</h3>
          <audio id="audioPlayer" controls style="width: 100%; margin: 10px 0;"></audio>
          <div class="audio-actions">
            <button id="deleteRecording" class="btn-outline">Delete & Record Again</button>
            <button id="saveRecording" class="btn-primary">Save Recording</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Right side: Results Panel -->
    <div class="results-panel">
      <h2>Analysis Results</h2>
      <div class="results-content" id="resultsContent">
        <div class="results-placeholder">
          <p>Record your response to see analysis results here.</p>
          <p>You have 1-2 minutes to speak.</p>
          <?php if (!$entitlementCheck['can_analyze']): ?>
            <div class="warning-message" style="margin-top: 15px; padding: 12px; background: #f8d7da; border-left: 4px solid #dc3545; border-radius: 4px; color: #721c24;">
              <strong>Note:</strong> <?php echo htmlspecialchars($entitlementCheck['reason']); ?>
              <br><a href="payment.php" style="color: #c40000; text-decoration: underline;">Purchase credits or subscription</a>
            </div>
          <?php elseif (!$entitlementCheck['has_subscription']): ?>
            <div class="info-message" style="margin-top: 15px; padding: 12px; background: #d1ecf1; border-left: 4px solid #0c5460; border-radius: 4px; color: #0c5460;">
              <strong>Credits:</strong> You have <?php echo $entitlementCheck['credits_remaining']; ?> credit(s) remaining.
              <br>One credit will be deducted when you analyze your recording.
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</main>

<script src="scripts/speaking-recorder.js"></script>
<script>
// Pass entitlement info to JavaScript
const entitlementInfo = <?php echo json_encode($entitlementCheck); ?>;
</script>

</body>
</html>
