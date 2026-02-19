<?php
require_once __DIR__ . "/config/auth.php";

if (!isAuthenticated()) {
  header("Location: login.html");
  exit;
}

$type = $_GET["type"] ?? "academic";
if ($type !== "academic" && $type !== "general") $type = "academic";
$title = ($type === "general") ? "IELTS General" : "IELTS Academic";

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?php echo htmlspecialchars($title); ?></title>
  <link rel="stylesheet" href="css/home.css">
</head>
<body>

<?php require_once __DIR__ . "/includes/navbar.php"; ?>

<?php require_once __DIR__ . "/includes/entitlements-display.php"; ?>

<main class="container">
  <div class="page-title"><?php echo htmlspecialchars($title); ?> — choose a section</div>

  <div class="section-grid">
    <a class="section-card" href="practice.php?type=<?php echo $type; ?>&section=writing&task=1">
      <h3>Writing Task 1</h3>
      <p>
        <?php echo ($type === "academic")
          ? "Describe graphs / charts / processes."
          : "Write a letter (formal / semi-formal / informal)."; ?>
      </p>
      <div class="badge"><?php echo $type; ?></div>
    </a>

    <a class="section-card" href="practice.php?type=<?php echo $type; ?>&section=writing&task=2">
      <h3>Writing Task 2</h3>
      <p>Essay (opinion / discussion / problem-solution).</p>
      <div class="badge"><?php echo $type; ?></div>
    </a>

    <a class="section-card" href="speaking-practice.php?type=<?php echo $type; ?>&section=speaking&part=2">
      <h3>Speaking Part 2</h3>
      <p>Cue card (1–2 minute talk).</p>
      <div class="badge">speaking</div>
    </a>

    <a class="section-card" href="practice.php?type=<?php echo $type; ?>&section=reading">
      <h3>Reading</h3>
      <p><?php echo ($type === "academic") ? "Academic passages." : "General training texts."; ?></p>
      <div class="badge"><?php echo $type; ?></div>
    </a>

    <a class="section-card" href="practice.php?type=<?php echo $type; ?>&section=listening">
      <h3>Listening</h3>
      <p>Sections 1–4 (same format for both).</p>
      <div class="badge">listening</div>
    </a>
  </div>
</main>

</body>
</html>
