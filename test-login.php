<?php

// Test script to check login API
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . "/config/db.php";
try {
    $pdo = db();
// Check if two_factor_codes table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'two_factor_codes'");
    if ($stmt->rowCount() > 0) {
        echo "✓ two_factor_codes table exists<br>";
    } else {
        echo "✗ two_factor_codes table DOES NOT exist - you need to run the migration!<br>";
        echo "Run this SQL: <pre>" . file_get_contents(__DIR__ . "/config/two_factor_auth_schema.sql") . "</pre>";
    }

    // Check if users table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        echo "✓ users table exists<br>";
    // Check if there are any users
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
        $result = $stmt->fetch();
        echo "✓ Found " . $result['count'] . " users in database<br>";
    } else {
        echo "✗ users table DOES NOT exist<br>";
    }

    // Test database connection
    echo "✓ Database connection successful<br>";
} catch (PDOException $e) {
    echo "✗ Database Error: " . $e->getMessage() . "<br>";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "<br>";
}
