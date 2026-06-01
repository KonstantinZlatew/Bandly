<?php

declare(strict_types=1);

require_once __DIR__ . "/../../config/db.php";
require_once __DIR__ . "/../../config/auth.php";

/**
 * Redirect back to the admin page with a success or error message.
 *
 * @param string  $msg     Message to display.
 * @param boolean $success True for success, false for error.
 * @return never
 */
function redirect(string $msg, bool $success): never
{
    $param = $success ? 'success' : 'error';
    header("Location: ../../admin/index.php?" . $param . "=" . urlencode($msg));
    exit;
}

if (!isAuthenticated() || !isAdmin()) {
    redirect("Access denied.", false);
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    redirect("Invalid request.", false);
}

$pdo     = db();
$taskId  = isset($_POST['task_id']) ? (int)$_POST['task_id'] : 0;
$remove  = !empty($_POST['remove']);

if (!$taskId) {
    redirect("Invalid task ID.", false);
}

// Verify task exists and is an academic writing task 1
$stmt = $pdo->prepare("
    SELECT t.id, t.image_file_id, f.storage_key as image_path
    FROM tasks t
    JOIN exam_variants ev ON t.exam_variant_id = ev.id
    JOIN exams e ON ev.exam_id = e.id
    LEFT JOIN files f ON t.image_file_id = f.id
    WHERE t.id = ?
    AND t.task_type = 'writing'
    AND t.task_number = 1
    AND e.exam_type = 'academic'
    LIMIT 1
");
$stmt->execute([$taskId]);
$task = $stmt->fetch();

if (!$task) {
    redirect("Task not found.", false);
}

// Handle image removal
if ($remove) {
    $pdo->prepare("UPDATE tasks SET image_file_id = NULL WHERE id = ?")->execute([$taskId]);
    if ($task['image_path']) {
        $fullPath = __DIR__ . '/../../uploads/' . $task['image_path'];
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
        $pdo->prepare("DELETE FROM files WHERE storage_key = ?")->execute([$task['image_path']]);
    }
    redirect("Image removed.", true);
}

// Handle image upload
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    redirect("No image uploaded or upload error.", false);
}

$imageFile    = $_FILES['image'];
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$finfo        = finfo_open(FILEINFO_MIME_TYPE);
$mimeType     = finfo_file($finfo, $imageFile['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowedTypes)) {
    redirect("Invalid image type. Allowed: JPEG, PNG, GIF, WebP.", false);
}

if ($imageFile['size'] > 5 * 1024 * 1024) {
    redirect("Image must be under 5MB.", false);
}

$extension  = pathinfo($imageFile['name'], PATHINFO_EXTENSION) ?: 'jpg';
$storageKey = 'tasks/' . uniqid('task_' . $taskId . '_') . '.' . $extension;
$uploadDir  = __DIR__ . '/../../uploads/tasks';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$uploadPath = __DIR__ . '/../../uploads/' . $storageKey;

if (!move_uploaded_file($imageFile['tmp_name'], $uploadPath)) {
    redirect("Failed to save image.", false);
}

$pdo->beginTransaction();
try {
    // Remove old file record if replacing
    if ($task['image_path']) {
        $oldPath = __DIR__ . '/../../uploads/' . $task['image_path'];
        if (file_exists($oldPath)) {
            unlink($oldPath);
        }
        $pdo->prepare("DELETE FROM files WHERE storage_key = ?")->execute([$task['image_path']]);
    }

    $stmt = $pdo->prepare("INSERT INTO files (storage_key, mime, size_bytes, uploaded_by) VALUES (?, ?, ?, ?)");
    $stmt->execute([$storageKey, $mimeType, $imageFile['size'], getUserId()]);
    $fileId = (int)$pdo->lastInsertId();

    $pdo->prepare("UPDATE tasks SET image_file_id = ? WHERE id = ?")->execute([$fileId, $taskId]);
    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    if (file_exists($uploadPath)) {
        unlink($uploadPath);
    }
    redirect("Database error: " . $e->getMessage(), false);
}

redirect("Image uploaded successfully.", true);
