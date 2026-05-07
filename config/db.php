<?php
declare(strict_types=1);

// Load environment variables if .env file exists
if (file_exists(__DIR__ . "/../.env")) {
    // Load Composer autoloader first
    if (file_exists(__DIR__ . "/../vendor/autoload.php")) {
        require_once __DIR__ . "/../vendor/autoload.php";
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . "/..");
        $dotenv->load();
    }
}

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: "localhost";
    $dbname = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: "ielts_evalai";
    $user = $_ENV['DB_USER'] ?? getenv('DB_USER') ?: "root";
    $pass = $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?: "";
    $charset = "utf8mb4";

    $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    $pdo = new PDO($dsn, $user, $pass, $options);
    return $pdo;
}