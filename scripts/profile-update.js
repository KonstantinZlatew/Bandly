(() => {
    const els = {
      iUsername: document.getElementById("i_username"),
      iEmail: document.getElementById("i_email"),
      iFullName: document.getElementById("i_full_name"),
      iCountry: document.getElementById("i_country"),
  
      vUsername: document.getElementById("v_username"),
      vEmail: document.getElementById("v_email"),
      vFullName: document.getElementById("v_full_name"),
      vCountry: document.getElementById("v_country"),
  
      editBtn: document.getElementById("editBtn"),
      saveBtn: document.getElementById("saveBtn"),
      cancelBtn: document.getElementById("cancelBtn"),
      changePicBtn: document.getElementById("changePicBtn"),
      logoutBtn: document.getElementById("logoutBtn"),
    };
  
    function setDisplay(el, show) {
      if (!el) return;
      el.style.display = show ? "block" : "none";
    }
  
    function isValidEmail(email) {
      return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }
  
    function isValidUsername(username) {
      return /^[a-zA-Z0-9_]{3,30}$/.test(username);
    }
  
    function fillInputs(user) {
      if (!user) return;
      if (els.iUsername) els.iUsername.value = user.username || "";
      if (els.iEmail) els.iEmail.value = user.email || "";
      if (els.iFullName) els.iFullName.value = user.full_name || "";
      if (els.iCountry) els.iCountry.value = user.country || "";
    }
  
    function toggleEditUI(on) {
      setDisplay(els.iUsername, on);
      setDisplay(els.iEmail, on);
      setDisplay(els.iFullName, on);
      setDisplay(els.iCountry, on);
  
      setDisplay(els.vUsername, !on);
      setDisplay(els.vEmail, !on);
      setDisplay(els.vFullName, !on);
      setDisplay(els.vCountry, !on);
  
      setDisplay(els.editBtn, !on);
      setDisplay(els.saveBtn, on);
      setDisplay(els.cancelBtn, on);
    }
  
    function enterEditMode() {
      const user = window.ProfileUI?.getLastUser?.();
      if (!user) return;
  
      window.ProfileUI.hideMsg?.();
      fillInputs(user);
      toggleEditUI(true);
    }
  
    function exitEditMode() {
      window.ProfileUI.hideMsg?.();
      toggleEditUI(false);
    }
  
    async function saveProfile() {
      window.ProfileUI.hideMsg?.();

      const username = els.iUsername?.value.trim() ?? "";
      const email = els.iEmail?.value.trim() ?? "";
      const full_name = els.iFullName?.value.trim() ?? "";
      const country = els.iCountry?.value.trim() ?? "";

      if (!username || !email) {
        window.ProfileUI.showMsg?.("Username and email are required.");
        return;
      }
      if (!isValidEmail(email)) {
        window.ProfileUI.showMsg?.("Please enter a valid email address.");
        return;
      }
      if (!isValidUsername(username)) {
        window.ProfileUI.showMsg?.("Username must be 3-30 chars (letters, numbers, underscore).");
        return;
      }

      try {
        const res = await fetch("api/profile-update.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ username, email, full_name, country }),
        });

        const data = await res.json().catch(() => ({}));
        if (!res.ok || !data.ok) {
          throw new Error(data.error || `Update failed (${res.status})`);
        }

        await window.ProfileUI.loadProfile?.();
        exitEditMode();
        window.ProfileUI.showMsg?.("Profile updated successfully!", false);
        setTimeout(() => window.ProfileUI.hideMsg?.(), 3000);
      } catch (err) {
        window.ProfileUI.showMsg?.(err.message);
      }
    }

    function changeProfilePicture() {
      const input = document.createElement("input");
      input.type = "file";
      input.accept = "image/jpeg,image/jpg,image/png,image/gif,image/webp";
      input.style.display = "none";
      
      input.addEventListener("change", async (e) => {
        const file = e.target.files?.[0];
        if (!file) return;

        // Validate file size (5MB)
        if (file.size > 5 * 1024 * 1024) {
          window.ProfileUI.showMsg?.("File size exceeds 5MB limit.");
          return;
        }

        const formData = new FormData();
        formData.append("profile_picture", file);

        window.ProfileUI.hideMsg?.();
        
        try {
          const res = await fetch("api/profile-picture-upload.php", {
            method: "POST",
            body: formData,
          });

          const data = await res.json().catch(() => ({}));
          if (!res.ok || !data.ok) {
            throw new Error(data.error || `Upload failed (${res.status})`);
          }

          await window.ProfileUI.loadProfile?.();
          window.ProfileUI.showMsg?.("Profile picture updated successfully!", false);
          setTimeout(() => window.ProfileUI.hideMsg?.(), 3000);
        } catch (err) {
          window.ProfileUI.showMsg?.(err.message);
        }
      });

      document.body.appendChild(input);
      input.click();
      document.body.removeChild(input);
    }

    async function logout() {
      if (!confirm("Are you sure you want to logout?")) {
        return;
      }

      try {
        const res = await fetch("api/logout.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
        });

        const data = await res.json().catch(() => ({}));
        if (!res.ok || !data.ok) {
          throw new Error(data.error || "Logout failed");
        }

        // Redirect to login page
        window.location.href = "login.html";
      } catch (err) {
        window.ProfileUI.showMsg?.(err.message || "Failed to logout. Please try again.");
      }
    }
  
    document.addEventListener("DOMContentLoaded", () => {
      if (!window.ProfileUI) {
        console.error("ProfileUI not found. Load profile-get.js before profile-update.js");
        return;
      }

      if (els.editBtn) els.editBtn.addEventListener("click", enterEditMode);
      if (els.cancelBtn) els.cancelBtn.addEventListener("click", exitEditMode);
      if (els.saveBtn) els.saveBtn.addEventListener("click", saveProfile);
      if (els.changePicBtn) els.changePicBtn.addEventListener("click", changeProfilePicture);
      if (els.logoutBtn) els.logoutBtn.addEventListener("click", logout);

      toggleEditUI(false);
      
      // Enable buttons that were disabled
      if (els.changePicBtn) els.changePicBtn.disabled = false;
      if (els.logoutBtn) els.logoutBtn.disabled = false;
    });
  })();
  