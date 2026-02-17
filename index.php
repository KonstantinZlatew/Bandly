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

// Fetch user submissions
$submissions = [];
$chartData = [];
try {
  $pdo = db();
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

<header class="topbar">
  <div class="topbar-left">
    <div class="hello">Hi, <span class="name"><?php echo htmlspecialchars($username); ?></span></div>
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
    
    <!-- Submissions Table -->
    <div class="submissions-table-container">
      <?php if (count($submissions) > 0): ?>
        <table class="submissions-table">
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
            <?php foreach ($submissions as $sub): 
              $result = null;
              if ($sub['status'] === 'done' && $sub['analysis_result']) {
                $result = json_decode($sub['analysis_result'], true);
              }
              $taskTypeLabel = ucfirst(str_replace('_', ' ', $sub['task_type'] ?? 'Unknown'));
              if ($sub['exam_type']) {
                $taskTypeLabel = ucfirst($sub['exam_type']) . ' Task ' . ($sub['task_number'] ?? '?');
              }
            ?>
            <tr class="submission-row" data-submission-id="<?php echo htmlspecialchars($sub['id']); ?>">
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
      <?php else: ?>
        <div class="no-submissions">
          <p>You haven't submitted any essays yet.</p>
          <p>Start practicing by choosing a mode above!</p>
        </div>
      <?php endif; ?>
    </div>
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
    window.location.href = 'submission-detail.php?id=' + submissionId;
  });
  row.addEventListener('mouseenter', function() {
    this.style.backgroundColor = '#f0f7ff';
  });
  row.addEventListener('mouseleave', function() {
    this.style.backgroundColor = '';
  });
});
</script>

</body>
</html>
