<?php
require_once __DIR__ . "/config/auth.php";
require_once __DIR__ . "/config/db.php";

if (!isAuthenticated()) {
  header("Location: login.html");
  exit;
}

$type = $_GET["type"] ?? "academic";
$section = $_GET["section"] ?? "writing";
$task = $_GET["task"] ?? "1";

// Validate inputs
if ($type !== "academic" && $type !== "general") $type = "academic";
if ($section !== "writing") {
  // For now, only handle writing tasks
  header("Location: exam.php?type=" . $type);
  exit;
}
if ($task !== "1" && $task !== "2") $task = "1";

$userId = getUserId() ?? 0;
$username = getUsername() ?? "User";
$profilePic = getProfilePictureUrl();

$initial = strtoupper(mb_substr($username, 0, 1, "UTF-8"));
$colors = ["#d45a6a", "#5b86d6", "#2e8b57", "#0f766e", "#6b21a8", "#b45309", "#111111"];
$bg = $colors[$userId % count($colors)];

// Determine task type for database
$taskType = ($type === "academic") ? "academic_task_" . $task : "general_task_" . $task;
$pageTitle = ($type === "academic") ? "IELTS Academic" : "IELTS General";
$taskTitle = "Writing Task " . $task;

// Fetch task prompt from database (excluding tasks user has already completed)
$prompt = "";
$taskId = null;
$examVariantId = null;
$allTasksCompleted = false;
try {
  $pdo = db();
  // Fetch a task that the user hasn't completed yet
  $stmt = $pdo->prepare("
    SELECT t.id, t.prompt, t.exam_variant_id
    FROM tasks t
    JOIN exam_variants ev ON t.exam_variant_id = ev.id
    JOIN exams e ON ev.exam_id = e.id
    WHERE t.task_type = 'writing' 
    AND t.task_number = ?
    AND e.exam_type = ?
    AND t.id NOT IN (
      SELECT utc.task_id 
      FROM user_task_completions utc 
      WHERE utc.user_id = ?
    )
    ORDER BY RAND()
    LIMIT 1
  ");
  $stmt->execute([$task, $type, $userId]);
  $taskData = $stmt->fetch();
  
  if ($taskData) {
    $prompt = $taskData['prompt'];
    $taskId = $taskData['id'];
    $examVariantId = $taskData['exam_variant_id'];
  } else {
    // If all tasks are completed, show a message or fetch any task
    $stmt = $pdo->prepare("
      SELECT t.id, t.prompt, t.exam_variant_id
      FROM tasks t
      JOIN exam_variants ev ON t.exam_variant_id = ev.id
      JOIN exams e ON ev.exam_id = e.id
      WHERE t.task_type = 'writing' 
      AND t.task_number = ?
      AND e.exam_type = ?
      ORDER BY RAND()
      LIMIT 1
    ");
    $stmt->execute([$task, $type]);
    $taskData = $stmt->fetch();
    
    if ($taskData) {
      $prompt = $taskData['prompt'];
      $taskId = $taskData['id'];
      $examVariantId = $taskData['exam_variant_id'];
      $allTasksCompleted = true; // Flag to show message
    } else {
      $prompt = "No task found in database. Please add a task prompt.";
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
  <link rel="stylesheet" href="css/practice.css">
</head>
<body>

<header class="topbar">
  <div class="topbar-left">
    <a class="back-btn" href="exam.php?type=<?php echo htmlspecialchars($type); ?>">← Back</a>
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
    <h1><?php echo htmlspecialchars($pageTitle . " - " . $taskTitle); ?></h1>
  </div>

  <div class="practice-layout">
    <!-- Left side: Prompt and Essay Input -->
    <div class="practice-main">
      <div class="prompt-section">
        <h2>Task Prompt</h2>
        <div class="prompt-content" id="promptContent">
          <?php echo nl2br(htmlspecialchars($prompt)); ?>
        </div>
        <?php if ($taskId): ?>
          <input type="hidden" id="taskId" value="<?php echo htmlspecialchars($taskId); ?>">
          <input type="hidden" id="examVariantId" value="<?php echo htmlspecialchars($examVariantId ?? ''); ?>">
        <?php endif; ?>
        <input type="hidden" id="taskType" value="<?php echo htmlspecialchars($taskType); ?>">
        <input type="hidden" id="taskPrompt" value="<?php echo htmlspecialchars($prompt); ?>">
        <?php if (isset($allTasksCompleted) && $allTasksCompleted): ?>
          <div class="info-message" style="margin-top: 15px; padding: 10px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 4px; color: #856404;">
            You have completed all available tasks for this type. This is a repeated task.
          </div>
        <?php endif; ?>
      </div>

      <div class="essay-section">
        <h2>Your Response</h2>
        <textarea 
          id="essayInput" 
          class="essay-input" 
          placeholder="Type your essay here..."
          rows="20"
        ></textarea>
        <div class="word-count">
          <span id="wordCount">0</span> words
        </div>
        <!-- Image upload for academic_task_1 -->
        <?php if ($taskType === 'academic_task_1'): ?>
          <div class="image-upload-section" style="margin-top: 15px;">
            <label for="imageUpload" style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">
              Upload Image (Optional)
            </label>
            <input 
              type="file" 
              id="imageUpload" 
              accept="image/jpeg,image/png,image/gif,image/webp"
              style="padding: 8px; border: 2px solid #ddd; border-radius: 6px; width: 100%; font-size: 14px;"
            >
            <div id="imagePreview" style="margin-top: 10px; display: none;">
              <img id="previewImg" src="" alt="Preview" style="max-width: 300px; max-height: 200px; border-radius: 6px; border: 2px solid #ddd;">
              <button type="button" id="removeImage" style="margin-top: 5px; padding: 5px 15px; background: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer;">
                Remove Image
              </button>
            </div>
          </div>
        <?php endif; ?>
      </div>

      <div class="action-section">
        <button id="analyzeBtn" class="analyze-btn">Analyze</button>
      </div>
    </div>

    <!-- Right side: Results Panel -->
    <div class="results-panel">
      <h2>Analysis Results</h2>
      <div class="results-content" id="resultsContent">
        <div class="results-placeholder">
          <p>-</p>
          <p>-</p>
          <p>-</p>
        </div>
      </div>
    </div>
  </div>
</main>

<script>
// Word count functionality
const essayInput = document.getElementById('essayInput');
const wordCount = document.getElementById('wordCount');

essayInput.addEventListener('input', function() {
  const text = this.value.trim();
  const words = text ? text.split(/\s+/).filter(word => word.length > 0) : [];
  wordCount.textContent = words.length;
});

// Image upload handling (for academic_task_1)
const imageUpload = document.getElementById('imageUpload');
const imagePreview = document.getElementById('imagePreview');
const previewImg = document.getElementById('previewImg');
const removeImage = document.getElementById('removeImage');
let selectedImageFile = null;

if (imageUpload) {
  imageUpload.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
      if (file.size > 5 * 1024 * 1024) { // 5MB limit
        alert('Image size must be less than 5MB');
        this.value = '';
        return;
      }
      selectedImageFile = file;
      const reader = new FileReader();
      reader.onload = function(e) {
        previewImg.src = e.target.result;
        imagePreview.style.display = 'block';
      };
      reader.readAsDataURL(file);
    }
  });
}

if (removeImage) {
  removeImage.addEventListener('click', function() {
    selectedImageFile = null;
    imageUpload.value = '';
    imagePreview.style.display = 'none';
  });
}

// Poll for submission status
let pollInterval = null;

function pollSubmissionStatus(submissionId) {
  return fetch(`api/essay-status.php?id=${submissionId}`)
    .then(res => res.json())
    .then(data => {
      if (!data.ok) {
        throw new Error(data.error || 'Failed to check status');
      }
      return data.submission;
    });
}

function displayAnalysisResult(result) {
  const resultsContent = document.getElementById('resultsContent');
  
  if (!result) {
    resultsContent.innerHTML = '<div class="results-placeholder"><p>No results available</p></div>';
    return;
  }
  
  let html = '<div class="analysis-result">';
  
  // Overall Band Score
  html += `<div class="score-section">
    <h3>Overall Band Score</h3>
    <div class="band-score">${result.overall_band || 'N/A'}</div>
  </div>`;
  
  // Individual Scores
  html += '<div class="criteria-scores">';
  html += `<div class="score-item"><strong>TR:</strong> ${result.TR || 'N/A'}</div>`;
  html += `<div class="score-item"><strong>CC:</strong> ${result.CC || 'N/A'}</div>`;
  html += `<div class="score-item"><strong>LR:</strong> ${result.LR || 'N/A'}</div>`;
  html += `<div class="score-item"><strong>GRA:</strong> ${result.GRA || 'N/A'}</div>`;
  html += '</div>';
  
  // Notes
  if (result.notes) {
    html += '<div class="notes-section"><h3>Notes</h3>';
    if (result.notes.TR) html += `<p><strong>TR:</strong> ${result.notes.TR}</p>`;
    if (result.notes.CC) html += `<p><strong>CC:</strong> ${result.notes.CC}</p>`;
    if (result.notes.LR) html += `<p><strong>LR:</strong> ${result.notes.LR}</p>`;
    if (result.notes.GRA) html += `<p><strong>GRA:</strong> ${result.notes.GRA}</p>`;
    html += '</div>';
  }
  
  // Overall Comment
  if (result.overall_comment) {
    html += `<div class="comment-section"><h3>Overall Comment</h3><p>${result.overall_comment}</p></div>`;
  }
  
  // Improvement Plan
  if (result.improvement_plan && Array.isArray(result.improvement_plan)) {
    html += '<div class="improvement-section"><h3>Improvement Plan</h3><ul>';
    result.improvement_plan.forEach(item => {
      html += `<li>${item}</li>`;
    });
    html += '</ul></div>';
  }
  
  html += '</div>';
  resultsContent.innerHTML = html;
}

function startPolling(submissionId) {
  // Clear any existing polling
  if (pollInterval) {
    clearInterval(pollInterval);
  }
  
  // Poll every 2 seconds
  pollInterval = setInterval(async () => {
    try {
      const submission = await pollSubmissionStatus(submissionId);
      
      if (submission.status === 'done') {
        clearInterval(pollInterval);
        pollInterval = null;
        displayAnalysisResult(submission.analysis_result);
        document.getElementById('analyzeBtn').disabled = false;
        document.getElementById('analyzeBtn').textContent = 'Analyze';
      } else if (submission.status === 'failed') {
        clearInterval(pollInterval);
        pollInterval = null;
        document.getElementById('resultsContent').innerHTML = 
          `<div class="error-message"><p>Analysis failed: ${submission.error_message || 'Unknown error'}</p></div>`;
        document.getElementById('analyzeBtn').disabled = false;
        document.getElementById('analyzeBtn').textContent = 'Analyze';
      } else if (submission.status === 'processing') {
        document.getElementById('resultsContent').innerHTML = 
          '<div class="processing-message"><p>Processing your essay... Please wait.</p></div>';
      }
    } catch (error) {
      console.error('Polling error:', error);
    }
  }, 2000);
}

// Analyze button - save submission and start polling
document.getElementById('analyzeBtn').addEventListener('click', async function() {
  const taskId = document.getElementById('taskId')?.value;
  const taskType = document.getElementById('taskType')?.value;
  const taskPrompt = document.getElementById('taskPrompt')?.value;
  const examVariantId = document.getElementById('examVariantId')?.value;
  const essayContent = document.getElementById('essayInput').value.trim();
  
  if (!taskId || !taskType || !taskPrompt) {
    alert('Error: Task information missing. Please refresh the page.');
    return;
  }
  
  if (!essayContent) {
    alert('Please write your essay before analyzing.');
    return;
  }
  
  // Disable button and show loading
  const btn = this;
  btn.disabled = true;
  btn.textContent = 'Submitting...';
  
  // Show pending message
  document.getElementById('resultsContent').innerHTML = 
    '<div class="processing-message"><p>Submitting your essay...</p></div>';
  
  try {
    // Prepare form data
    const formData = new FormData();
    formData.append('task_id', taskId);
    formData.append('task_type', taskType);
    formData.append('task_prompt', taskPrompt);
    formData.append('exam_variant_id', examVariantId);
    formData.append('essay', essayContent);
    
    // Add image if present
    if (selectedImageFile) {
      formData.append('image', selectedImageFile);
    }
    
    // Send to API
    const response = await fetch('api/submission-save.php', {
      method: 'POST',
      body: formData
    });
    
    const result = await response.json();
    
    if (result.ok && result.submission_id) {
      // Start polling for status
      btn.textContent = 'Analyzing...';
      startPolling(result.submission_id);
    } else {
      alert('Error saving submission: ' + (result.error || 'Unknown error'));
      btn.disabled = false;
      btn.textContent = 'Analyze';
      document.getElementById('resultsContent').innerHTML = 
        '<div class="results-placeholder"><p>-</p><p>-</p><p>-</p></div>';
    }
  } catch (error) {
    console.error('Error:', error);
    alert('Error saving submission. Please try again.');
    btn.disabled = false;
    btn.textContent = 'Analyze';
    document.getElementById('resultsContent').innerHTML = 
      '<div class="results-placeholder"><p>-</p><p>-</p><p>-</p></div>';
  }
});
</script>

</body>
</html>
