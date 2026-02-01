<?php
require_once __DIR__ . "/config/auth.php";

if (!isAuthenticated()) {
  header("Location: login.html");
  exit;
}

$type = $_GET["type"] ?? "academic";
if ($type !== "academic" && $type !== "general") $type = "academic";
$title = ($type === "general") ? "IELTS General" : "IELTS Academic";

$userId = getUserId() ?? 0;
$username = getUsername() ?? "User";
$profilePic = getProfilePictureUrl();

$initial = strtoupper(mb_substr($username, 0, 1, "UTF-8"));
$colors = ["#d45a6a", "#5b86d6", "#2e8b57", "#0f766e", "#6b21a8", "#b45309", "#111111"];
$bg = $colors[$userId % count($colors)];
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

    <a class="section-card" href="practice.php?type=<?php echo $type; ?>&section=speaking&part=1">
      <h3>Speaking Part 1</h3>
      <p>Short questions about familiar topics.</p>
      <div class="badge">speaking</div>
    </a>

    <a class="section-card" href="practice.php?type=<?php echo $type; ?>&section=speaking&part=2">
      <h3>Speaking Part 2</h3>
      <p>Cue card (1–2 minute talk).</p>
      <div class="badge">speaking</div>
    </a>

    <a class="section-card" href="practice.php?type=<?php echo $type; ?>&section=speaking&part=3">
      <h3>Speaking Part 3</h3>
      <p>Deeper discussion related to Part 2.</p>
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
