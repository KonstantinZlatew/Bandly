<?php
session_start();
if (!isset($_SESSION["user_id"])) {
  header("Location: login.html");
  exit;
}

$userId = (int)($_SESSION["user_id"] ?? 0);
$username = (string)($_SESSION["username"] ?? "User");
$profilePic = $_SESSION["profile_picture_url"] ?? null;

$initial = strtoupper(mb_substr($username, 0, 1, "UTF-8"));
$colors = ["#d45a6a", "#5b86d6", "#2e8b57", "#0f766e", "#6b21a8", "#b45309", "#111111"];
$bg = $colors[$userId % count($colors)];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Home</title>
  <link rel="stylesheet" href="css/home.css">
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
</main>

</body>
</html>
