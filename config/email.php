<?php

/**
 * Email service for sending 2FA codes
 */

declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . "/../vendor/autoload.php";
// Load environment variables if .env file exists
if (file_exists(__DIR__ . "/../.env")) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . "/..");
    $dotenv->load();
}

/**
 * Send 2FA verification code via email
 * @param string $email Recipient email address.
 * @param string $code  The 6-digit verification code.
 * @return boolean True if email sent successfully, false otherwise.
 */
function send2FACode(string $email, string $code): bool
{

    $mail = new PHPMailer(true);
    try {
    // Server settings
        $mail->isSMTP();
        $mail->Host = $_ENV['SMTP_HOST'] ?? getenv('SMTP_HOST') ?: 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $_ENV['SMTP_USERNAME'] ?? getenv('SMTP_USERNAME') ?: '';
        $mail->Password = $_ENV['SMTP_PASSWORD'] ?? getenv('SMTP_PASSWORD') ?: '';
        $mail->SMTPSecure = $_ENV['SMTP_ENCRYPTION'] ?? getenv('SMTP_ENCRYPTION') ?: PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = (int)($_ENV['SMTP_PORT'] ?? getenv('SMTP_PORT') ?: 587);
        $mail->CharSet = 'UTF-8';
    // Sender
        $mail->setFrom($_ENV['SMTP_FROM_EMAIL'] ?? getenv('SMTP_FROM_EMAIL') ?: $mail->Username, $_ENV['SMTP_FROM_NAME'] ?? getenv('SMTP_FROM_NAME') ?: 'IELTS AI Evaluator');
    // Recipient
        $mail->addAddress($email);
    // Content
        $mail->isHTML(true);
        $mail->Subject = 'Your Verification Code';
        $mail->Body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .code-box { 
                    background-color: #f4f4f4; 
                    border: 2px solid #007bff; 
                    border-radius: 8px; 
                    padding: 20px; 
                    text-align: center; 
                    margin: 20px 0;
                    font-size: 32px;
                    font-weight: bold;
                    letter-spacing: 8px;
                    color: #007bff;
                }
                .footer { margin-top: 30px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <h2>Verification Code</h2>
                <p>Your verification code is:</p>
                <div class='code-box'>{$code}</div>
                <p>This code will expire in 10 minutes.</p>
                <p>If you didn't request this code, please ignore this email.</p>
                <div class='footer'>
                    <p>This is an automated message from IELTS AI Evaluator.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        $mail->AltBody = "Your verification code is: {$code}\n\nThis code will expire in 10 minutes.\n\nIf you didn't request this code, please ignore this email.";
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("2FA Email Error: " . $mail->ErrorInfo);
        return false;
    }
}
