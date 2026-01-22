<?php
session_start();
if (!isset($_SESSION["user_id"])) {
  header("Location: login.html");
  exit;
}

$userId = (int)($_SESSION["user_id"] ?? 0);
$username = (string)($_SESSION["username"] ?? "User");
$email = "user@example.com";

$fullName = "Not set";
$country = "Not set";
$preferredLang = "en";
$createdAt = "2026-01-01";
$lastLogin = "2026-01-21 12:34";
$isAdmin = 0;

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
  <title>Profile</title>
  <link rel="stylesheet" href="css/home.css">
  <link rel="stylesheet" href="css/profile.css">
</head>
<body>

<header class="topbar">
  <div class="topbar-left">
    <a class="back-btn" href="index.php">← Back</a>
  </div>

  <div class="topbar-center">
    <h1 class="brand">IELTSEVALAI</h1>
  </div>

  <div class="avatar-link" title="Profile">
    <?php if ($profilePic): ?>
      <img class="avatar" src="<?php echo htmlspecialchars($profilePic); ?>" alt="Profile picture">
    <?php else: ?>
      <div class="avatar-fallback" style="background: <?php echo htmlspecialchars($bg); ?>;">
        <?php echo htmlspecialchars($initial); ?>
      </div>
    <?php endif; ?>
  </div>
</header>

<main class="container">
  <div class="page-title">Profile settings</div>

  <div class="profile-layout">

    <!-- Left card: avatar + quick actions -->
    <section class="profile-card">
      <div class="profile-avatar-wrap">
        <?php if ($profilePic): ?>
          <img class="profile-avatar" src="<?php echo htmlspecialchars($profilePic); ?>" alt="Profile picture">
        <?php else: ?>
          <div class="profile-avatar-fallback" style="background: <?php echo htmlspecialchars($bg); ?>;">
            <?php echo htmlspecialchars($initial); ?>
          </div>
        <?php endif; ?>
      </div>

      <div class="profile-main">
        <div class="profile-username"><?php echo htmlspecialchars($username); ?></div>
        <div class="profile-email"><?php echo htmlspecialchars($email); ?></div>
      </div>

      <div class="profile-actions">
        <button class="btn-dark" type="button">Change profile picture</button>
        <button class="btn-outline" type="button">Edit profile</button>
        <button class="btn-danger" type="button">Logout</button>
      </div>

      <div class="profile-note">
        Tip: later we can add upload + cropping, and save the URL in <b>user_profiles</b>.
      </div>
    </section>

    <!-- Right card: details -->
    <section class="details-card">
      <h2 class="section-title">Account information</h2>

      <div class="details-grid">
        <div class="field">
          <div class="label">Username</div>
          <div class="value"><?php echo htmlspecialchars($username); ?></div>
        </div>

        <div class="field">
          <div class="label">Email</div>
          <div class="value"><?php echo htmlspecialchars($email); ?></div>
        </div>

        <div class="field">
          <div class="label">Full name</div>
          <div class="value"><?php echo htmlspecialchars($fullName); ?></div>
        </div>

        <div class="field">
          <div class="label">Country</div>
          <div class="value"><?php echo htmlspecialchars($country); ?></div>
        </div>

        <div class="field">
          <div class="label">Preferred language</div>
          <div class="value"><?php echo htmlspecialchars($preferredLang); ?></div>
        </div>

        <div class="field">
          <div class="label">Role</div>
          <div class="value"><?php echo $isAdmin ? "Admin" : "User"; ?></div>
        </div>

        <div class="field">
          <div class="label">Created at</div>
          <div class="value"><?php echo htmlspecialchars($createdAt); ?></div>
        </div>

        <div class="field">
          <div class="label">Last login</div>
          <div class="value"><?php echo htmlspecialchars($lastLogin); ?></div>
        </div>
      </div>

      <h2 class="section-title">Preferences</h2>

      <div class="prefs-row">
        <div class="pref-box">
          <div class="label">Theme</div>
          <div class="value">Light</div>
        </div>

        <div class="pref-box">
          <div class="label">Notifications</div>
          <div class="value">Enabled</div>
        </div>

        <div class="pref-box">
          <div class="label">Exam default</div>
          <div class="value">Academic</div>
        </div>
      </div>

    </section>

  </div>
</main>

</body>
</html>
