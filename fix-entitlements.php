<?php
// Script to create entitlements records for users who don't have them
require_once __DIR__ . "/config/db.php";

try {
    $pdo = db();
    
    // Find users without entitlements
    $stmt = $pdo->query("
        SELECT u.id, u.username
        FROM users u
        LEFT JOIN user_entitlements ue ON ue.user_id = u.id
        WHERE ue.user_id IS NULL
    ");
    $usersWithoutEntitlements = $stmt->fetchAll();
    
    echo "<h2>Fixing Entitlements</h2>";
    echo "<p>Found " . count($usersWithoutEntitlements) . " users without entitlements records.</p>";
    
    if (count($usersWithoutEntitlements) > 0) {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("
            INSERT INTO user_entitlements (user_id, credits_balance)
            VALUES (:user_id, 0)
        ");
        
        foreach ($usersWithoutEntitlements as $user) {
            try {
                $stmt->execute(["user_id" => $user["id"]]);
                echo "<p style='color: green;'>✓ Created entitlements for user: " . htmlspecialchars($user["username"]) . " (ID: " . $user["id"] . ")</p>";
            } catch (PDOException $e) {
                echo "<p style='color: orange;'>⚠ Could not create entitlements for user: " . htmlspecialchars($user["username"]) . " - " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        }
        
        $pdo->commit();
        echo "<p style='color: green;'><strong>Done!</strong></p>";
    } else {
        echo "<p style='color: green;'>All users already have entitlements records.</p>";
    }
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "<p style='color: red;'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>



