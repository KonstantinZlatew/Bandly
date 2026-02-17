<?php
require_once __DIR__ . "/config/auth.php";

if (!isAuthenticated()) {
  header("Location: login.html");
  exit;
}

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

<?php require_once __DIR__ . "/includes/navbar.php"; ?>

<?php require_once __DIR__ . "/includes/entitlements-display.php"; ?>

<main class="container">
  <div class="page-title">Profile settings</div>

  <div id="profileMsg" class="message error" style="display:none;"></div>

  <div class="profile-layout">

    <section class="profile-card">
        <div class="profile-avatar-wrap">
        <img id="profileAvatarImg" class="profile-avatar" src="" alt="Profile picture" style="display:none;">
        <div id="profileAvatarFallback" class="profile-avatar-fallback" style="background: #5b86d6;">
          <?php echo htmlspecialchars(strtoupper(mb_substr(getUsername() ?? "User", 0, 1, "UTF-8"))); ?>
        </div>
      </div>

      <div class="profile-main">
        <div class="profile-username" id="p_username">Loading...</div>
        <div class="profile-email" id="p_email">Loading...</div>
      </div>

      <div class="profile-actions">
        <button class="btn-outline" id="editBtn" type="button">Edit profile</button>
        <button class="btn-dark" id="saveBtn" type="button" style="display:none;">Save changes</button>
        <button class="btn-outline" id="cancelBtn" type="button" style="display:none;">Cancel</button>

        <button class="btn-dark" id="changePicBtn" type="button" disabled>Change profile picture</button>
        <button class="btn-danger" id="logoutBtn" type="button" disabled>Logout</button>
      </div>

      <div class="profile-note">
        Preferences are UI-only for now.
      </div>
    </section>

    <section class="details-card">
      <h2 class="section-title">Account information</h2>

      <div class="details-grid">
        <div class="field">
          <div class="label">Username</div>
          <div class="value" id="v_username">—</div>
          <input class="input" id="i_username" type="text" style="display:none;">
        </div>

        <div class="field">
          <div class="label">Email</div>
          <div class="value" id="v_email">—</div>
          <input class="input" id="i_email" type="email" style="display:none;">
        </div>

        <div class="field">
          <div class="label">Full name</div>
          <div class="value" id="v_full_name">—</div>
          <input class="input" id="i_full_name" type="text" style="display:none;">
        </div>

        <div class="field">
          <div class="label">Country</div>
          <div class="value" id="v_country">—</div>
          <input class="input" id="i_country" type="text" style="display:none;">
        </div>

        <div class="field">
          <div class="label">Role</div>
          <div class="value" id="v_role">—</div>
        </div>

        <div class="field">
          <div class="label">Created at</div>
          <div class="value" id="v_created_at">—</div>
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
          <div class="label">Default exam</div>
          <div class="value">Academic</div>
        </div>
      </div>
    </section>

  </div>
</main>

<script src="scripts/profile-get.js"></script>
<script src="scripts/profile-update.js"></script>

</body>
</html>
