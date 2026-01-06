const form = document.getElementById("signupForm");
const messageBox = document.getElementById("message");

form.addEventListener("submit", async function (e) {
  e.preventDefault();

  const username = document.getElementById("username").value.trim();
  const email = document.getElementById("email").value.trim();
  const password = document.getElementById("password").value;
  const confirmPassword = document.getElementById("confirmPassword").value;

  hideMessage();

  // Frontend validation (UX)
  if (!username || !email || !password || !confirmPassword) {
    showMessage("Please fill in all fields.", "error");
    return;
  }
  if (password !== confirmPassword) {
    showMessage("Passwords do not match.", "error");
    return;
  }
  if (password.length < 8) {
    showMessage("Password must be at least 8 characters long.", "error");
    return;
  }
  if (!/[A-Z]/.test(password)) {
    showMessage("Password must contain at least one uppercase letter.", "error");
    return;
  }
  if (!/[0-9]/.test(password)) {
    showMessage("Password must contain at least one number.", "error");
    return;
  }

  // Call backend
  try {
    const res = await fetch("api/signup.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ username, email, password, confirmPassword }),
    });

    const data = await res.json().catch(() => ({}));

    if (!res.ok) {
      throw new Error(data.error || `Request failed (${res.status})`);
    }

    showMessage(data.message || "Account created successfully!", "success");
    form.reset();
  } catch (err) {
    showMessage(err.message, "error");
  }
});

function showMessage(text, type) {
  messageBox.textContent = text;
  messageBox.className = `message ${type}`;
  messageBox.style.display = "block";
}

function hideMessage() {
  messageBox.style.display = "none";
}
