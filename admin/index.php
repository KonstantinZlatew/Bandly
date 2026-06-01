<?php
require_once __DIR__ . "/../config/auth.php";
require_once __DIR__ . "/../config/db.php";

if (!isAuthenticated() || !isAdmin()) {
    header("Location: ../login.html");
    exit;
}

$username = getUsername() ?? "Admin";
$pdo = db();

// Fetch all Academic Writing Task 1 tasks with their current image
$stmt = $pdo->query("
    SELECT t.id, t.prompt, t.image_file_id, f.storage_key as image_path
    FROM tasks t
    JOIN exam_variants ev ON t.exam_variant_id = ev.id
    JOIN exams e ON ev.exam_id = e.id
    LEFT JOIN files f ON t.image_file_id = f.id
    WHERE t.task_type = 'writing'
    AND t.task_number = 1
    AND e.exam_type = 'academic'
    ORDER BY t.id ASC
");
$tasks = $stmt->fetchAll();

$successMsg = $_GET['success'] ?? null;
$errorMsg   = $_GET['error'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin — Task Images</title>
  <link rel="stylesheet" href="../css/home.css">
  <style>
    .admin-container { max-width: 1100px; margin: 40px auto; padding: 0 20px; }
    .admin-header { margin-bottom: 30px; }
    .admin-header h1 { font-size: 26px; font-weight: 700; color: #333; margin: 0; }
    .admin-header p { color: #666; margin: 6px 0 0; }
    .task-card {
      background: #fff;
      border-radius: 12px;
      padding: 24px;
      margin-bottom: 20px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
      display: grid;
      grid-template-columns: 1fr 260px;
      gap: 24px;
      align-items: start;
    }
    .task-info { min-width: 0; }
    .task-id { font-size: 12px; color: #999; font-weight: 600; margin-bottom: 6px; }
    .task-prompt {
      font-size: 14px;
      color: #444;
      line-height: 1.6;
      background: #f9f9f9;
      padding: 12px;
      border-radius: 6px;
      border-left: 4px solid #5b86d6;
      max-height: 140px;
      overflow-y: auto;
    }
    .task-image-panel { text-align: center; }
    .task-image-panel img {
      width: 100%;
      max-height: 160px;
      object-fit: contain;
      border-radius: 8px;
      border: 2px solid #ddd;
      margin-bottom: 10px;
    }
    .no-image {
      width: 100%;
      height: 120px;
      background: #f0f0f0;
      border-radius: 8px;
      border: 2px dashed #ccc;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #aaa;
      font-size: 14px;
      margin-bottom: 10px;
    }
    .upload-form { display: flex; flex-direction: column; gap: 8px; }
    .upload-form input[type="file"] {
      font-size: 13px;
      padding: 6px;
      border: 2px solid #ddd;
      border-radius: 6px;
    }
    .btn-upload {
      background: #5b86d6;
      color: #fff;
      border: none;
      padding: 9px 18px;
      border-radius: 6px;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.2s;
    }
    .btn-upload:hover { background: #4a75c5; }
    .btn-remove {
      background: none;
      border: 2px solid #dc3545;
      color: #dc3545;
      padding: 7px 18px;
      border-radius: 6px;
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
    }
    .btn-remove:hover { background: #dc3545; color: #fff; }
    .flash {
      padding: 12px 18px;
      border-radius: 8px;
      margin-bottom: 20px;
      font-size: 14px;
      font-weight: 600;
    }
    .flash.success { background: #d1e7dd; color: #0f5132; border-left: 4px solid #198754; }
    .flash.error   { background: #f8d7da; color: #842029; border-left: 4px solid #dc3545; }
    .empty-state { text-align: center; padding: 60px; color: #999; }
    @media (max-width: 700px) {
      .task-card { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>

<header class="topbar">
  <div class="topbar-left">
    <a class="back-btn" href="../index.php">←</a>
    <div class="hello"><span class="name"><?php echo htmlspecialchars($username); ?></span></div>
  </div>
  <div class="topbar-center">
    <h1 class="brand">IELTSEVALAI</h1>
  </div>
  <div></div>
</header>

<div class="admin-container">
  <div class="admin-header">
    <h1>Admin — Task Images</h1>
    <p>Upload chart/graph images for Academic Writing Task 1 tasks.</p>
  </div>

  <?php if ($successMsg) : ?>
    <div class="flash success"><?php echo htmlspecialchars($successMsg); ?></div>
  <?php endif; ?>
  <?php if ($errorMsg) : ?>
    <div class="flash error"><?php echo htmlspecialchars($errorMsg); ?></div>
  <?php endif; ?>

  <?php if (empty($tasks)) : ?>
    <div class="empty-state">No Academic Writing Task 1 tasks found in the database.</div>
  <?php else : ?>
      <?php foreach ($tasks as $task) : ?>
      <div class="task-card">
        <div class="task-info">
          <div class="task-id">Task ID: <?php echo (int)$task['id']; ?></div>
          <div class="task-prompt"><?php echo nl2br(htmlspecialchars($task['prompt'])); ?></div>
        </div>
        <div class="task-image-panel">
            <?php if ($task['image_path']) : ?>
            <img src="../uploads/<?php echo htmlspecialchars($task['image_path']); ?>" alt="Task image">
            <?php else : ?>
            <div class="no-image">No image uploaded</div>
            <?php endif; ?>

          <form class="upload-form" action="../api/admin/upload-task-image.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="task_id" value="<?php echo (int)$task['id']; ?>">
            <input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp" required>
            <button type="submit" class="btn-upload">Upload Image</button>
          </form>

            <?php if ($task['image_file_id']) : ?>
            <form action="../api/admin/upload-task-image.php" method="POST" style="margin-top:8px;">
              <input type="hidden" name="task_id" value="<?php echo (int)$task['id']; ?>">
              <input type="hidden" name="remove" value="1">
              <button type="submit" class="btn-remove"
                onclick="return confirm('Remove this image?')">Remove Image</button>
            </form>
            <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
  <?php endif; ?>
</div>

</body>
</html>
