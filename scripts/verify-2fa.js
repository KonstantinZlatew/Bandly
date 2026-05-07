const form = document.getElementById("verifyForm");
const messageBox = document.getElementById("message");
const codeInputs = [
    document.getElementById("code1"),
    document.getElementById("code2"),
    document.getElementById("code3"),
    document.getElementById("code4"),
    document.getElementById("code5"),
    document.getElementById("code6")
];
const fullCodeInput = document.getElementById("fullCode");
const verifyBtn = document.getElementById("verifyBtn");
const resendLink = document.getElementById("resendLink");
const countdownEl = document.getElementById("countdown");
const emailDisplay = document.getElementById("email-display");

let resendCooldown = 60; // 60 seconds cooldown
let countdownInterval = null;

// Get email from URL parameter or localStorage
const urlParams = new URLSearchParams(window.location.search);
const email = urlParams.get('email') || localStorage.getItem('2fa_email') || '';

if (email) {
    emailDisplay.textContent = email;
    localStorage.setItem('2fa_email', email);
} else {
    // If no email, redirect to login
    window.location.href = 'login.html';
}

// Auto-focus first input
codeInputs[0].focus();

// Handle input in code fields
codeInputs.forEach((input, index) => {
    input.addEventListener('input', (e) => {
        const value = e.target.value.replace(/[^0-9]/g, '');
        e.target.value = value;

        if (value && index < codeInputs.length - 1) {
            codeInputs[index + 1].focus();
        }

        updateFullCode();
    });

    input.addEventListener('keydown', (e) => {
        if (e.key === 'Backspace' && !e.target.value && index > 0) {
            codeInputs[index - 1].focus();
        }
    });

    input.addEventListener('paste', (e) => {
        e.preventDefault();
        const pastedData = e.clipboardData.getData('text').replace(/[^0-9]/g, '').slice(0, 6);
        if (pastedData.length === 6) {
            pastedData.split('').forEach((char, i) => {
                if (codeInputs[i]) {
                    codeInputs[i].value = char;
                }
            });
            codeInputs[5].focus();
            updateFullCode();
        }
    });
});

function updateFullCode()
{
    const code = codeInputs.map(input => input.value).join('');
    fullCodeInput.value = code;

    if (code.length === 6) {
        verifyBtn.disabled = false;
    } else {
        verifyBtn.disabled = true;
    }
}

// Form submission
form.addEventListener("submit", async function (e) {
    e.preventDefault();

    const code = fullCodeInput.value;

    if (code.length !== 6) {
        showMessage("Please enter the complete 6-digit code.", "error");
        return;
    }

    hideMessage();
    verifyBtn.disabled = true;
    verifyBtn.textContent = "Verifying...";

    try {
        const res = await fetch("api/verify-2fa.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ code }),
        });

        const data = await res.json().catch(() => ({}));

        if (!res.ok) {
            throw new Error(data.error || `Verification failed(${res.status})`);
        }

        // Clear email from localStorage
        localStorage.removeItem('2fa_email');

        // Redirect to homepage
        showMessage("Verification successful! Redirecting...", "success");
        setTimeout(() => {
            window.location.href = "index.php";
        }, 1000);
    } catch (err) {
        showMessage(err.message, "error");
        verifyBtn.disabled = false;
        verifyBtn.textContent = "Verify";
        // Clear inputs on error
        codeInputs.forEach(input => input.value = '');
        codeInputs[0].focus();
        updateFullCode();
    }
});

// Resend code
resendLink.addEventListener('click', async function (e) {
    e.preventDefault();

    if (resendCooldown > 0) {
        return;
    }

    hideMessage();
    resendLink.style.pointerEvents = 'none';
    resendLink.textContent = 'Sending...';

    try {
        const res = await fetch("api/resend-2fa.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
        });

        const data = await res.json().catch(() => ({}));

        if (!res.ok) {
            throw new Error(data.error || `Failed to resend code(${res.status})`);
        }

        showMessage("New verification code sent to your email.", "success");
        startCountdown(); // Reset countdown
    } catch (err) {
        showMessage(err.message || "Failed to resend code. Please try logging in again.", "error");
        resendLink.style.pointerEvents = 'auto';
        resendLink.textContent = 'Resend Code';
    }
});

// Countdown timer
function startCountdown()
{
    if (countdownInterval) {
        clearInterval(countdownInterval);
    }

    resendCooldown = 60;
    updateCountdown();

    countdownInterval = setInterval(() => {
        resendCooldown--;
        updateCountdown();

        if (resendCooldown <= 0) {
            clearInterval(countdownInterval);
            resendLink.style.pointerEvents = 'auto';
            resendLink.textContent = 'Resend Code';
            countdownEl.textContent = '';
        }
    }, 1000);
}

function updateCountdown()
{
    if (resendCooldown > 0) {
        resendLink.style.pointerEvents = 'none';
        resendLink.textContent = 'Resend Code';
        countdownEl.textContent = `Resend available in ${resendCooldown} seconds`;
    } else {
        resendLink.style.pointerEvents = 'auto';
        countdownEl.textContent = '';
    }
}

// Start countdown on page load
startCountdown();

function showMessage(text, type)
{
    messageBox.textContent = text;
    messageBox.className = `message ${type}`;
    messageBox.style.display = "block";
}

function hideMessage()
{
    messageBox.style.display = "none";
}

// Initialize
updateFullCode();

