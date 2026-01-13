const form = document.getElementById("loginForm");
const messageBox = document.getElementById("message");

form.addEventListener("submit", function (e) {
  e.preventDefault();

  const email = document.getElementById("email").value.trim();
  const password = document.getElementById("password").value;

  hideMessage();

  // Basic checks
  if (!email || !password) {
    showMessage("Please fill in all fields.", "error");
    return;
  }

  // Email format check
  if (!isValidEmail(email)) {
    showMessage("Please enter a valid email address.", "error");
    return;
  }

  // Password basic check (for login we don't need all signup rules)
  if (password.length < 8) {
    showMessage("Password must be at least 8 characters long.", "error");
    return;
  }

  // For now: no backend, just show success
  showMessage("Frontend validation passed successfully.", "success");
  console.log({ email, password });
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
