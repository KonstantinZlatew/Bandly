<?php

// Diagnostic script to check 2FA setup
error_reporting(E_ALL);
ini_set('display_errors', 1);
echo "<h2>2FA Setup Diagnostic</h2>";
// Check database
echo "<h3>Database Check</h3>";
try {
    require_once __DIR__ . "/config/db.php";
    $pdo = db();
    echo "✓ Database connection successful<br>";
// Check two_factor_codes table
    $stmt = $pdo->query("SHOW TABLES LIKE 'two_factor_codes'");
    if ($stmt->rowCount() > 0) {
        echo "✓ two_factor_codes table exists<br>";
    } else {
        echo "✗ <strong style='color:red'>two_factor_codes table DOES NOT exist</strong><br>";
        echo "→ You need to run the SQL migration: <code>config/two_factor_auth_schema.sql</code><br>";
    }

    // Check users table
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        echo "✓ users table exists<br>";
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
        $result = $stmt->fetch();
        echo "✓ Found " . $result['count'] . " users<br>";
    } else {
        echo "✗ users table DOES NOT exist<br>";
    }
} catch (Exception $e) {
    echo "✗ <strong style='color:red'>Database Error:</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
}

// Check email configuration
echo "<h3>Email Configuration Check</h3>";
$envFile = __DIR__ . "/.env";
if (file_exists($envFile)) {
    echo "✓ .env file exists<br>";
    $envContent = file_get_contents($envFile);
    $requiredVars = ['SMTP_HOST', 'SMTP_USERNAME', 'SMTP_PASSWORD', 'SMTP_PORT'];
    foreach ($requiredVars as $var) {
        if (preg_match("/^{$var}=(.+)$/m", $envContent, $matches)) {
            $value = trim($matches[1]);
            if (!empty($value) && $value !== 'your-email@gmail.com' && $value !== 'your-app-password') {
                echo "✓ {$var} is configured<br>";
            } else {
                echo "✗ <strong style='color:orange'>{$var} is not properly configured</strong><br>";
            }
        } else {
            echo "✗ <strong style='color:orange'>{$var} is missing</strong><br>";
        }
    }
} else {
    echo "✗ <strong style='color:orange'>.env file does not exist</strong><br>";
    echo "→ Email sending will fail. Create .env file with SMTP configuration.<br>";
}

// Check Composer dependencies
echo "<h3>Composer Dependencies</h3>";
$vendorAutoload = __DIR__ . "/vendor/autoload.php";
if (file_exists($vendorAutoload)) {
    echo "✓ vendor/autoload.php exists<br>";
    require_once $vendorAutoload;
    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        echo "✓ PHPMailer is installed<br>";
    } else {
        echo "✗ PHPMailer class not found<br>";
    }

    if (class_exists('Dotenv\Dotenv')) {
        echo "✓ Dotenv is installed<br>";
    } else {
        echo "✗ Dotenv class not found<br>";
    }
} else {
    echo "✗ <strong style='color:red'>vendor/autoload.php does not exist</strong><br>";
    echo "→ Run: <code>composer install</code><br>";
}

// Check required files
echo "<h3>Required Files</h3>";
$requiredFiles = [
    'config/two_factor.php',
    'config/email.php',
    'api/login.php',
    'api/verify-2fa.php',
    'verify-2fa.html'
];
foreach ($requiredFiles as $file) {
    if (file_exists(__DIR__ . "/" . $file)) {
        echo "✓ {$file} exists<br>";
    } else {
        echo "✗ <strong style='color:red'>{$file} is missing</strong><br>";
    }
}

echo "<hr>";
echo "<h3>Next Steps</h3>";
echo "<ol>";
echo "<li>If two_factor_codes table is missing, run the SQL from <code>config/two_factor_auth_schema.sql</code> in phpMyAdmin</li>";
echo "<li>If .env is missing or incomplete, create it with SMTP settings (see ENV_CONFIGURATION.md)</li>";
echo "<li>If vendor/autoload.php is missing, run <code>composer install</code></li>";
echo "</ol>";
