const els = {
    pUsername: document.getElementById("p_username"),
    pEmail: document.getElementById("p_email"),
  
    vUsername: document.getElementById("v_username"),
    vEmail: document.getElementById("v_email"),
    vFullName: document.getElementById("v_full_name"),
    vCountry: document.getElementById("v_country"),
    vRole: document.getElementById("v_role"),
    vCreatedAt: document.getElementById("v_created_at"),
  
    avatarImg: document.getElementById("profileAvatarImg"),
    avatarFallback: document.getElementById("profileAvatarFallback"),
  
    msg: document.getElementById("profileMsg"),
  };
  
  document.addEventListener("DOMContentLoaded", () => {
    loadProfile();
  });
  
  async function loadProfile() {
    hideMsg();
  
    try {
      const res = await fetch("api/profile-get.php", {
        method: "GET",
        headers: { "Accept": "application/json" },
      });
  
      const data = await res.json().catch(() => ({}));
  
      if (!res.ok || !data.ok) {
        throw new Error(data.error || `Failed to load profile (${res.status})`);
      }
  
      renderProfile(data.user);
    } catch (err) {
      showMsg(err.message);
    }
  }
  
  function renderProfile(user) {
    const username = user.username || "User";
    const email = user.email || "—";
  
    setText(els.pUsername, username);
    setText(els.pEmail, email);
  
    setText(els.vUsername, username);
    setText(els.vEmail, email);
    setText(els.vFullName, user.full_name || "Not set");
    setText(els.vCountry, user.country || "Not set");
    setText(els.vRole, String(user.is_admin) === "1" ? "Admin" : "User");
    setText(els.vCreatedAt, formatDate(user.created_at));
  
    renderAvatar(user.profile_picture_url, username, user.id);
  }
  
  function renderAvatar(url, username, userId) {
    if (url) {
      els.avatarImg.src = url;
      els.avatarImg.style.display = "block";
      els.avatarFallback.style.display = "none";
      return;
    }
  
    // fallback: first letter + deterministic color
    const initial = (username.trim()[0] || "U").toUpperCase();
    els.avatarFallback.textContent = initial;
  
    const colors = ["#d45a6a", "#5b86d6", "#2e8b57", "#0f766e", "#6b21a8", "#b45309", "#111111"];
    const idx = (Number(userId) || 0) % colors.length;
    els.avatarFallback.style.background = colors[idx];
  
    els.avatarImg.style.display = "none";
    els.avatarFallback.style.display = "flex";
  }
  
  function formatDate(value) {
    if (!value) return "—";
    // value can be "YYYY-MM-DD HH:MM:SS" or ISO
    const d = new Date(value.replace(" ", "T"));
    if (Number.isNaN(d.getTime())) return String(value);
    return d.toLocaleString();
  }
  
  function setText(el, text) {
    if (!el) return;
    el.textContent = text;
  }
  
  function showMsg(text) {
    if (!els.msg) return;
    els.msg.textContent = text;
    els.msg.style.display = "block";
  }
  
  function hideMsg() {
    if (!els.msg) return;
    els.msg.style.display = "none";
  }
  