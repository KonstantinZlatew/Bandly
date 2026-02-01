<?php
// Test database connection and verify table structure
require_once __DIR__ . "/config/db.php";

echo "<h2>Database Connection Test</h2>";

try {
    $pdo = db();
    echo "<p style='color: green;'>✓ Database connection successful</p>";
    
    // Check if tables exist
    $tables = ['users', 'plans', 'purchases', 'user_entitlements', 'user_subscriptions'];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<p style='color: green;'>✓ Table '$table' exists</p>";
            
            // Show table structure
            $stmt = $pdo->query("DESCRIBE $table");
            $columns = $stmt->fetchAll();
            echo "<details><summary>Columns in '$table':</summary><pre>";
            foreach ($columns as $col) {
                echo $col['Field'] . " - " . $col['Type'] . "\n";
            }
            echo "</pre></details>";
        } else {
            echo "<p style='color: red;'>✗ Table '$table' does NOT exist</p>";
        }
    }
    
    // Check plans
    echo "<h3>Plans in Database:</h3>";
    $stmt = $pdo->query("SELECT id, code, name, plan_type, credits_amount, duration_days, price_cents, is_active FROM plans");
    $plans = $stmt->fetchAll();
    if ($plans) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Code</th><th>Name</th><th>Type</th><th>Credits</th><th>Days</th><th>Price</th><th>Active</th></tr>";
        foreach ($plans as $plan) {
            echo "<tr>";
            echo "<td>" . $plan['id'] . "</td>";
            echo "<td>" . htmlspecialchars($plan['code']) . "</td>";
            echo "<td>" . htmlspecialchars($plan['name']) . "</td>";
            echo "<td>" . htmlspecialchars($plan['plan_type']) . "</td>";
            echo "<td>" . ($plan['credits_amount'] ?? 'N/A') . "</td>";
            echo "<td>" . ($plan['duration_days'] ?? 'N/A') . "</td>";
            echo "<td>" . ($plan['price_cents'] ?? '0') . " cents</td>";
            echo "<td>" . ($plan['is_active'] ? 'Yes' : 'No') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>⚠ No plans found in database</p>";
    }
    
    // Test insert into purchases (dry run)
    echo "<h3>Test Purchase Insert (Structure Check):</h3>";
    $testStmt = $pdo->prepare("
        INSERT INTO purchases (user_id, plan_id, provider, provider_payment_intent_id, provider_checkout_session_id, amount_cents, currency, status, paid_at)
        VALUES (:user_id, :plan_id, 'stripe', :payment_intent_id, :checkout_session_id, :amount_cents, :currency, :status, IF(:status = 'paid', NOW(), NULL))
    ");
    echo "<p style='color: green;'>✓ Purchase INSERT statement is valid</p>";
    
    // Test entitlements update
    echo "<h3>Test Entitlements Update (Structure Check):</h3>";
    $testStmt = $pdo->prepare("
        INSERT INTO user_entitlements (user_id, credits_balance)
        VALUES (:user_id, :credits)
        ON DUPLICATE KEY UPDATE credits_balance = credits_balance + :credits
    ");
    echo "<p style='color: green;'>✓ Entitlements UPDATE statement is valid</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>


