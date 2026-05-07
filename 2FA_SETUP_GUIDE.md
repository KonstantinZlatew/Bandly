# 2FA Setup Guide

This guide will help you set up Two-Factor Authentication (2FA) for the IELTS AI Evaluator application.

## Prerequisites

1. PHP 7.4 or higher
2. Composer installed
3. MySQL database
4. An email account (Gmail, Outlook, or any SMTP-compatible email)

## Installation Steps

### 1. Install Dependencies

Run the following command in your project root directory:

```bash
composer install
```

This will install:
- PHPMailer (for sending emails)
- PHP Dotenv (for environment variables)

### 2. Database Setup

Run the SQL migration to create the 2FA codes table:

```sql
-- Execute this in your MySQL database
SOURCE config/two_factor_auth_schema.sql;
```

Or manually run the SQL from `config/two_factor_auth_schema.sql` in phpMyAdmin or your MySQL client.

### 3. Environment Configuration

1. Copy the `.env.example` file to `.env`:
   ```bash
   cp .env.example .env
   ```

2. Edit the `.env` file with your configuration:

#### Database Settings
```env
DB_HOST=localhost
DB_NAME=ielts_evalai
DB_USER=root
DB_PASS=your_password
```

#### Email Settings (Gmail Example)

For Gmail, you'll need to:
1. Enable 2-Step Verification on your Google account
2. Generate an App Password:
   - Go to Google Account settings
   - Security → 2-Step Verification → App passwords
   - Generate a new app password for "Mail"
   - Use this app password (not your regular password) in SMTP_PASSWORD

```env
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_ENCRYPTION=tls
SMTP_USERNAME=your-email@gmail.com
SMTP_PASSWORD=your-16-char-app-password
SMTP_FROM_EMAIL=your-email@gmail.com
SMTP_FROM_NAME=IELTS AI Evaluator
```

#### Email Settings (Outlook/Hotmail)

```env
SMTP_HOST=smtp-mail.outlook.com
SMTP_PORT=587
SMTP_ENCRYPTION=tls
SMTP_USERNAME=your-email@outlook.com
SMTP_PASSWORD=your-password
SMTP_FROM_EMAIL=your-email@outlook.com
SMTP_FROM_NAME=IELTS AI Evaluator
```

#### Email Settings (Yahoo)

```env
SMTP_HOST=smtp.mail.yahoo.com
SMTP_PORT=587
SMTP_ENCRYPTION=tls
SMTP_USERNAME=your-email@yahoo.com
SMTP_PASSWORD=your-app-password
SMTP_FROM_EMAIL=your-email@yahoo.com
SMTP_FROM_NAME=IELTS AI Evaluator
```

### 4. File Permissions

Ensure your web server can read the `.env` file:
- The `.env` file should be readable by the web server user
- **IMPORTANT**: Never commit `.env` to version control (it should be in `.gitignore`)

### 5. Testing

1. Start your web server (XAMPP, WAMP, or built-in PHP server)
2. Navigate to `login.html` or `signup.html`
3. Try logging in or signing up
4. Check your email for the 6-digit verification code
5. Enter the code on the verification page

## How It Works

1. **Registration/Login Flow:**
   - User enters credentials (email/password)
   - System validates credentials
   - System generates a 6-digit code
   - Code is stored in database (expires in 10 minutes)
   - Email is sent with the code
   - User is redirected to verification page

2. **Verification Flow:**
   - User enters the 6-digit code
   - System verifies code matches and hasn't expired
   - Code is marked as used
   - User authentication cookies are set
   - User is redirected to homepage

## Troubleshooting

### Email Not Sending

1. **Check SMTP credentials:**
   - Verify username and password are correct
   - For Gmail, ensure you're using an App Password, not your regular password
   - Check that 2-Step Verification is enabled (for Gmail)

2. **Check SMTP settings:**
   - Verify host, port, and encryption match your email provider
   - Some providers require specific ports (587 for TLS, 465 for SSL)

3. **Check PHP error logs:**
   - Look in your PHP error log for SMTP connection errors
   - Common issues: firewall blocking port 587, incorrect credentials

4. **Test SMTP connection:**
   - You can create a test script to verify SMTP settings work

### Code Not Working

1. **Check expiration:**
   - Codes expire after 10 minutes
   - Request a new code if expired

2. **Check database:**
   - Ensure the `two_factor_codes` table exists
   - Verify codes are being stored correctly

3. **Check session:**
   - Ensure cookies are enabled in the browser
   - Check that the temporary token cookie is being set

### Database Errors

1. **Table doesn't exist:**
   - Run the migration SQL: `config/two_factor_auth_schema.sql`

2. **Connection errors:**
   - Verify database credentials in `.env`
   - Check that MySQL is running
   - Verify database name exists

## Security Notes

- Codes expire after 10 minutes
- Codes can only be used once
- Expired codes are automatically cleaned up
- Temporary tokens expire after 15 minutes
- All database queries use prepared statements to prevent SQL injection

## Manual Steps Required

1. ✅ Create `.env` file from `.env.example`
2. ✅ Configure database credentials in `.env`
3. ✅ Configure SMTP email settings in `.env`
4. ✅ Run database migration (`config/two_factor_auth_schema.sql`)
5. ✅ Install Composer dependencies (`composer install`)
6. ✅ Test the 2FA flow with a real email account

## Files Created/Modified

### New Files:
- `config/two_factor_auth_schema.sql` - Database table for 2FA codes
- `config/email.php` - Email service for sending codes
- `config/two_factor.php` - 2FA helper functions
- `api/verify-2fa.php` - API endpoint for code verification
- `api/resend-2fa.php` - API endpoint for resending codes
- `verify-2fa.html` - Verification page UI
- `scripts/verify-2fa.js` - Verification page JavaScript

### Modified Files:
- `config/db.php` - Now uses `.env` for database configuration
- `api/login.php` - Sends 2FA code instead of direct login
- `api/signup.php` - Sends 2FA code instead of direct login
- `scripts/login.js` - Redirects to 2FA page
- `scripts/signup.js` - Redirects to 2FA page
- `composer.json` - Added PHPMailer dependency

