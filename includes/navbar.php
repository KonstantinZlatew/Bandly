<?php
// Determine current page
$currentPage = basename($_SERVER['PHP_SELF']);
$isIndexPage = ($currentPage === 'index.php');

// Get user info
$userId = getUserId() ?? 0;
$username = getUsername() ?? "User";
$profilePic = getProfilePictureUrl();

$initial = strtoupper(mb_substr($username, 0, 1, "UTF-8"));
$colors = ["#d45a6a", "#5b86d6", "#2e8b57", "#0f766e", "#6b21a8", "#b45309", "#111111"];
$bg = $colors[$userId % count($colors)];
?>
<header class="topbar">
  <div class="topbar-left">
    <?php if (!$isIndexPage): ?>
      <a class="back-btn" href="index.php" title="Back to home">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
          <path d="M19 12H5M12 19l-7-7 7-7"/>
        </svg>
      </a>
    <?php endif; ?>
    <div class="hello">
      <?php if ($isIndexPage): ?>
        Hi, <span class="name"><?php echo htmlspecialchars($username); ?></span>
      <?php else: ?>
        <span class="name"><?php echo htmlspecialchars($username); ?></span>
      <?php endif; ?>
    </div>
  </div>

  <div class="topbar-center">
    <h1 class="brand">Bandly</h1>
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
