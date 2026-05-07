# .env Configuration File

Create a `.env` file in the root directory of your project with the following configuration:

## Required Configuration

```env
# Database Configuration
DB_HOST=localhost
DB_NAME=ielts_evalai
DB_USER=root
DB_PASS=

# SMTP Email Configuration (for 2FA)
# Gmail example:
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_ENCRYPTION=tls
SMTP_USERNAME=your-email@gmail.com
SMTP_PASSWORD=your-app-password
SMTP_FROM_EMAIL=your-email@gmail.com
SMTP_FROM_NAME=IELTS AI Evaluator

# For other email providers:
# Outlook/Hotmail: smtp-mail.outlook.com, port 587
# Yahoo: smtp.mail.yahoo.com, port 587
# Custom SMTP: use your provider's settings
```

## Configuration Details

### Database Settings

- `DB_HOST`: Your MySQL host (usually `localhost`)
- `DB_NAME`: Your database name (default: `ielts_evalai`)
- `DB_USER`: Your MySQL username (default: `root`)
- `DB_PASS`: Your MySQL password (leave empty if no password)

### Email Settings

#### Gmail Setup

1. Enable 2-Step Verification on your Google account
2. Generate an App Password:
   - Go to [Google Account Settings](https://myaccount.google.com/)
   - Security → 2-Step Verification → App passwords
   - Generate a new app password for "Mail"
   - Use this 16-character app password (NOT your regular password)

```env
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_ENCRYPTION=tls
SMTP_USERNAME=your-email@gmail.com
SMTP_PASSWORD=xxxx xxxx xxxx xxxx
SMTP_FROM_EMAIL=your-email@gmail.com
SMTP_FROM_NAME=IELTS AI Evaluator
```

#### Outlook/Hotmail Setup

```env
SMTP_HOST=smtp-mail.outlook.com
SMTP_PORT=587
SMTP_ENCRYPTION=tls
SMTP_USERNAME=your-email@outlook.com
SMTP_PASSWORD=your-password
SMTP_FROM_EMAIL=your-email@outlook.com
SMTP_FROM_NAME=IELTS AI Evaluator
```

#### Yahoo Setup

1. Generate an App Password from Yahoo Account Security settings

```env
SMTP_HOST=smtp.mail.yahoo.com
SMTP_PORT=587
SMTP_ENCRYPTION=tls
SMTP_USERNAME=your-email@yahoo.com
SMTP_PASSWORD=your-app-password
SMTP_FROM_EMAIL=your-email@yahoo.com
SMTP_FROM_NAME=IELTS AI Evaluator
```

#### Custom SMTP Server

For other email providers, use their SMTP settings:

```env
SMTP_HOST=your-smtp-server.com
SMTP_PORT=587
SMTP_ENCRYPTION=tls
SMTP_USERNAME=your-email@domain.com
SMTP_PASSWORD=your-password
SMTP_FROM_EMAIL=your-email@domain.com
SMTP_FROM_NAME=IELTS AI Evaluator
```

**Note:** Some providers use port 465 with SSL encryption. In that case:
```env
SMTP_PORT=465
SMTP_ENCRYPTION=ssl
```

## Important Notes

1. **Never commit `.env` to version control** - It's already in `.gitignore`
2. The `.env` file should be readable by your web server
3. For Gmail, you MUST use an App Password, not your regular password
4. Test your email configuration before going live

