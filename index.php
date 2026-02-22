<?php
require_once __DIR__ . "/config/auth.php";
require_once __DIR__ . "/config/db.php";

if (!isAuthenticated()) {
  header("Location: login.html");
  exit;
}

$userId = getUserId() ?? 0;

// Fetch user submissions
$submissions = [];
$speakingSubmissions = [];
$chartData = [];
try {
  $pdo = db();
  
  // Fetch writing submissions
  $stmt = $pdo->prepare("
    SELECT 
      ws.id,
      ws.task_type,
      ws.task_prompt,
      ws.submitted_at,
      ws.status,
      ws.analysis_result,
      ws.word_count,
      t.task_number,
      e.exam_type
    FROM writing_submissions ws
    JOIN tasks t ON ws.task_id = t.id
    JOIN exam_variants ev ON ws.exam_variant_id = ev.id
    JOIN exams e ON ev.exam_id = e.id
    WHERE ws.user_id = ?
    ORDER BY ws.submitted_at DESC
  ");
  $stmt->execute([$userId]);
  $submissions = $stmt->fetchAll();
  
  // Fetch speaking submissions
  $stmt = $pdo->prepare("
    SELECT 
      ss.id,
      ss.task_prompt,
      ss.submitted_at,
      ss.status,
      ss.analysis_result,
      t.task_number,
      e.exam_type
    FROM speaking_submissions ss
    JOIN tasks t ON ss.task_id = t.id
    JOIN exam_variants ev ON ss.exam_variant_id = ev.id
    JOIN exams e ON ev.exam_id = e.id
    WHERE ss.user_id = ?
    ORDER BY ss.submitted_at DESC
  ");
  $stmt->execute([$userId]);
  $speakingSubmissions = $stmt->fetchAll();
  
  // Prepare chart data (only for completed submissions)
  foreach ($submissions as $sub) {
    if ($sub['status'] === 'done' && $sub['analysis_result']) {
      $result = json_decode($sub['analysis_result'], true);
      if ($result && isset($result['overall_band'])) {
        $chartData[] = [
          'date' => date('Y-m-d', strtotime($sub['submitted_at'])),
          'label' => date('M d', strtotime($sub['submitted_at'])),
          'score' => (float)$result['overall_band']
        ];
      }
    }
  }
} catch (Exception $e) {
  // Handle error silently or log it
  error_log("Error fetching submissions: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Home</title>
  <link rel="stylesheet" href="css/home.css">
  <link rel="stylesheet" href="css/dashboard.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>

<?php require_once __DIR__ . "/includes/navbar.php"; ?>

<?php require_once __DIR__ . "/includes/entitlements-display.php"; ?>

<main class="container">
  <div class="page-title">Choose your mode</div>

  <div class="card-grid">
    <a class="mode-card" href="exam.php?type=general">
      <h2>IELTS General</h2>
      <p>General Training tasks, letter writing and real-life reading passages.</p>
      <div class="accent-line"></div>
    </a>

    <a class="mode-card" href="exam.php?type=academic">
      <h2>IELTS Academic</h2>
      <p>Academic tasks, graphs/processes and university-style reading passages.</p>
      <div class="accent-line"></div>
    </a>
  </div>

  <!-- Dashboard Section -->
  <div class="dashboard-section">
    <h2 class="dashboard-title">My Submissions</h2>
    
    <?php if (count($chartData) > 0): ?>
    <!-- Score Chart -->
    <div class="chart-container">
      <canvas id="scoreChart"></canvas>
    </div>
    <?php endif; ?>
    
    <!-- Writing Submissions Table -->
    <div class="submissions-table-container">
      <h3 class="table-section-title">Writing Submissions</h3>
      <?php if (count($submissions) > 0): ?>
        <table class="submissions-table writing-submissions-table">
          <thead>
            <tr>
              <th>Date</th>
              <th>Task Type</th>
              <th>Overall Band</th>
              <th>TR</th>
              <th>CC</th>
              <th>LR</th>
              <th>GRA</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php 
            $writingIndex = 0;
            foreach ($submissions as $sub): 
              $result = null;
              if ($sub['status'] === 'done' && $sub['analysis_result']) {
                $result = json_decode($sub['analysis_result'], true);
              }
              $taskTypeLabel = ucfirst(str_replace('_', ' ', $sub['task_type'] ?? 'Unknown'));
              if ($sub['exam_type']) {
                $taskTypeLabel = ucfirst($sub['exam_type']) . ' Task ' . ($sub['task_number'] ?? '?');
              }
              $writingIndex++;
              $isHidden = $writingIndex > 5 ? 'hidden-row' : '';
            ?>
            <tr class="submission-row writing-row <?php echo $isHidden; ?>" data-submission-id="<?php echo htmlspecialchars($sub['id']); ?>" data-submission-type="writing">
              <td><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($sub['submitted_at']))); ?></td>
              <td><?php echo htmlspecialchars($taskTypeLabel); ?></td>
              <td class="score-cell">
                <?php if ($result && isset($result['overall_band'])): ?>
                  <span class="band-score"><?php echo htmlspecialchars($result['overall_band']); ?></span>
                <?php else: ?>
                  <span class="status-badge status-<?php echo htmlspecialchars($sub['status']); ?>">
                    <?php echo htmlspecialchars(ucfirst($sub['status'])); ?>
                  </span>
                <?php endif; ?>
              </td>
              <td><?php echo $result && isset($result['TR']) ? htmlspecialchars($result['TR']) : '-'; ?></td>
              <td><?php echo $result && isset($result['CC']) ? htmlspecialchars($result['CC']) : '-'; ?></td>
              <td><?php echo $result && isset($result['LR']) ? htmlspecialchars($result['LR']) : '-'; ?></td>
              <td><?php echo $result && isset($result['GRA']) ? htmlspecialchars($result['GRA']) : '-'; ?></td>
              <td>
                <span class="status-badge status-<?php echo htmlspecialchars($sub['status']); ?>">
                  <?php echo htmlspecialchars(ucfirst($sub['status'])); ?>
                </span>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php if (count($submissions) > 5): ?>
          <div class="show-more-container" style="text-align: center; margin-top: 16px;">
            <button class="show-more-btn" data-table="writing">Show More</button>
          </div>
        <?php endif; ?>
      <?php else: ?>
        <div class="no-submissions">
          <p>You haven't submitted any essays yet.</p>
          <p>Start practicing by choosing a mode above!</p>
        </div>
      <?php endif; ?>
    </div>
    
    <!-- Speaking Submissions Table -->
    <div class="submissions-table-container" style="margin-top: 32px;">
      <h3 class="table-section-title">Speaking Submissions</h3>
      <?php if (count($speakingSubmissions) > 0): ?>
        <table class="submissions-table speaking-submissions-table">
          <thead>
            <tr>
              <th>Date</th>
              <th>Task Type</th>
              <th>Overall Band</th>
              <th>FC</th>
              <th>LR</th>
              <th>GRA</th>
              <th>PR</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php 
            $speakingIndex = 0;
            foreach ($speakingSubmissions as $sub): 
              $result = null;
              if ($sub['status'] === 'done' && $sub['analysis_result']) {
                $result = json_decode($sub['analysis_result'], true);
              }
              $taskTypeLabel = 'Speaking Task';
              if ($sub['exam_type']) {
                $taskTypeLabel = ucfirst($sub['exam_type']) . ' Task ' . ($sub['task_number'] ?? '?');
              }
              $speakingIndex++;
              $isHidden = $speakingIndex > 5 ? 'hidden-row' : '';
            ?>
            <tr class="submission-row speaking-row <?php echo $isHidden; ?>" data-submission-id="<?php echo htmlspecialchars($sub['id']); ?>" data-submission-type="speaking">
              <td><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($sub['submitted_at']))); ?></td>
              <td><?php echo htmlspecialchars($taskTypeLabel); ?></td>
              <td class="score-cell">
                <?php if ($result && isset($result['overall_band'])): ?>
                  <span class="band-score"><?php echo htmlspecialchars($result['overall_band']); ?></span>
                <?php else: ?>
                  <span class="status-badge status-<?php echo htmlspecialchars($sub['status']); ?>">
                    <?php echo htmlspecialchars(ucfirst($sub['status'])); ?>
                  </span>
                <?php endif; ?>
              </td>
              <td><?php echo $result && isset($result['FC']) ? htmlspecialchars($result['FC']) : '-'; ?></td>
              <td><?php echo $result && isset($result['LR']) ? htmlspecialchars($result['LR']) : '-'; ?></td>
              <td><?php echo $result && isset($result['GRA']) ? htmlspecialchars($result['GRA']) : '-'; ?></td>
              <td><?php echo $result && isset($result['PR']) ? htmlspecialchars($result['PR']) : '-'; ?></td>
              <td>
                <span class="status-badge status-<?php echo htmlspecialchars($sub['status']); ?>">
                  <?php echo htmlspecialchars(ucfirst($sub['status'])); ?>
                </span>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php if (count($speakingSubmissions) > 5): ?>
          <div class="show-more-container" style="text-align: center; margin-top: 16px;">
            <button class="show-more-btn" data-table="speaking">Show More</button>
          </div>
        <?php endif; ?>
      <?php else: ?>
        <div class="no-submissions">
          <p>You haven't submitted any speaking recordings yet.</p>
          <p>Start practicing by choosing a mode above!</p>
        </div>
      <?php endif; ?>
    </div>
  </div>
  
  <!-- Plan Purchase Link -->
  <div style="text-align: center; margin-top: 48px; margin-bottom: 32px;">
    <a href="payment.php" class="mode-card" style="display: inline-block; text-decoration: none; max-width: 400px;">
      <h2>See Our Plans</h2>
      <p>Choose a subscription plan or purchase credits to continue practicing.</p>
      <div class="accent-line"></div>
    </a>
  </div>
</main>

<script>
// Chart.js configuration
<?php if (count($chartData) > 0): ?>
const chartData = <?php echo json_encode($chartData); ?>;
const ctx = document.getElementById('scoreChart').getContext('2d');
new Chart(ctx, {
  type: 'line',
  data: {
    labels: chartData.map(d => d.label),
    datasets: [{
      label: 'Overall Band Score',
      data: chartData.map(d => d.score),
      borderColor: '#5b86d6',
      backgroundColor: 'rgba(91, 134, 214, 0.1)',
      borderWidth: 3,
      fill: true,
      tension: 0.4,
      pointRadius: 5,
      pointHoverRadius: 7,
      pointBackgroundColor: '#5b86d6',
      pointBorderColor: '#fff',
      pointBorderWidth: 2
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        display: true,
        position: 'top',
      },
      title: {
        display: true,
        text: 'Your IELTS Score Progress',
        font: {
          size: 18,
          weight: 'bold'
        }
      }
    },
    scales: {
      y: {
        beginAtZero: false,
        min: 0,
        max: 9,
        ticks: {
          stepSize: 0.5
        },
        title: {
          display: true,
          text: 'Overall Band Score'
        }
      },
      x: {
        title: {
          display: true,
          text: 'Submission Date'
        }
      }
    }
  }
});
<?php endif; ?>

// Make table rows clickable
document.querySelectorAll('.submission-row').forEach(row => {
  row.style.cursor = 'pointer';
  row.addEventListener('click', function() {
    const submissionId = this.getAttribute('data-submission-id');
    const submissionType = this.getAttribute('data-submission-type');
    // For now, both writing and speaking use the same detail page
    // If needed, this can be changed to handle speaking separately
    window.location.href = 'submission-detail.php?id=' + submissionId;
  });
  row.addEventListener('mouseenter', function() {
    this.style.backgroundColor = '#f0f7ff';
  });
  row.addEventListener('mouseleave', function() {
    this.style.backgroundColor = '';
  });
});

// Show More/Less functionality
document.querySelectorAll('.show-more-btn').forEach(btn => {
  btn.addEventListener('click', function() {
    const tableType = this.getAttribute('data-table');
    const rows = document.querySelectorAll(`.${tableType}-row`);
    const isShowingMore = this.textContent === 'Show Less';
    
    if (isShowingMore) {
      // Hide rows beyond first 5
      rows.forEach((row, index) => {
        if (index >= 5) {
          row.classList.add('hidden-row');
        }
      });
      this.textContent = 'Show More';
    } else {
      // Show all rows
      rows.forEach(row => {
        row.classList.remove('hidden-row');
      });
      this.textContent = 'Show Less';
    }
  });
});
</script>

<style>
.hidden-row {
  display: none;
}

.table-section-title {
  font-size: 1.2em;
  font-weight: 600;
  margin-bottom: 16px;
  color: #333;
}

.show-more-btn {
  background-color: #5b86d6;
  color: white;
  border: none;
  padding: 10px 24px;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  transition: background-color 0.2s;
}

.show-more-btn:hover {
  background-color: #4a6fb8;
}
</style>

</body>
</html>
