(() => {
    const els = {
      // inputs
      iUsername: document.getElementById("i_username"),
      iEmail: document.getElementById("i_email"),
      iFullName: document.getElementById("i_full_name"),
      iCountry: document.getElementById("i_country"),
  
      // view values
      vUsername: document.getElementById("v_username"),
      vEmail: document.getElementById("v_email"),
      vFullName: document.getElementById("v_full_name"),
      vCountry: document.getElementById("v_country"),
  
      // buttons
      editBtn: document.getElementById("editBtn"),
      saveBtn: document.getElementById("saveBtn"),
      cancelBtn: document.getElementById("cancelBtn"),
    };
  
    let editMode = false;
  
    function setDisplay(el, show, displayValue = "block") {
      if (!el) return;
      el.style.display = show ? displayValue : "none";
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
      // show inputs, hide values
      setDisplay(els.iUsername, on);
      setDisplay(els.iEmail, on);
      setDisplay(els.iFullName, on);
      setDisplay(els.iCountry, on);
  
      setDisplay(els.vUsername, !on);
      setDisplay(els.vEmail, !on);
      setDisplay(els.vFullName, !on);
      setDisplay(els.vCountry, !on);
  
      // buttons
      setDisplay(els.editBtn, !on);
      setDisplay(els.saveBtn, on);
      setDisplay(els.cancelBtn, on);
    }
  
    function enterEditMode() {
      const user = window.ProfileUI?.getLastUser?.();
      if (!user) return;
  
      editMode = true;
      window.ProfileUI.hideMsg?.();
      fillInputs(user);
      toggleEditUI(true);
    }
  
    function exitEditMode() {
      editMode = false;
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
      } catch (err) {
        window.ProfileUI.showMsg?.(err.message);
      }
    }
  
    document.addEventListener("DOMContentLoaded", () => {
      if (!window.ProfileUI) {
        console.error("ProfileUI not found. Make sure profile_get.js is loaded before profile_update.js");
        return;
      }
  
      if (els.editBtn) els.editBtn.addEventListener("click", enterEditMode);
      if (els.cancelBtn) els.cancelBtn.addEventListener("click", exitEditMode);
      if (els.saveBtn) els.saveBtn.addEventListener("click", saveProfile);
  
      toggleEditUI(false);
    });
  })();
  