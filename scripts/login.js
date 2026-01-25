const form = document.getElementById("loginForm");
const messageBox = document.getElementById("message");

form.addEventListener("submit", async function (e) {
  e.preventDefault();

  const email = document.getElementById("email").value.trim();
  const password = document.getElementById("password").value;

  hideMessage();

  // Frontend validation
  if (!email || !password) {
    showMessage("Please fill in all fields.", "error");
    return;
  }
  if (!isValidEmail(email)) {
    showMessage("Please enter a valid email address.", "error");
    return;
  }
  if (password.length < 8) {
    showMessage("Password must be at least 8 characters long.", "error");
    return;
  }

  // Backend call
  try {
    const res = await fetch("api/login.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ email, password }),
    });

    const data = await res.json().catch(() => ({}));

    if (!res.ok) {
      throw new Error(data.error || `Login failed (${res.status})`);
    }

    // Redirect to homepage on successful login
    window.location.href = "index.php";

  } catch (err) {
    showMessage(err.message, "error");
  }
});

function isValidEmail(email) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function showMessage(text, type) {
  messageBox.textContent = text;
  messageBox.className = `message ${type}`;
  messageBox.style.display = "block";
}

function hideMessage() {
  messageBox.style.display = "none";
}
