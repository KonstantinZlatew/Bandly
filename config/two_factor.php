<?php
declare(strict_types=1);

require_once __DIR__ . "/db.php";
require_once __DIR__ . "/email.php";

/**
 * Generate a random 6-digit verification code
 * @return string 6-digit code
 */
function generate2FACode(): string {
    return str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
}

/**
 * Store 2FA code in database
 * @param int $userId User ID
 * @param string $email User email
 * @param string $code 6-digit code
 * @return bool True if stored successfully
 */
function store2FACode(int $userId, string $email, string $code): bool {
    try {
        $pdo = db();
        
        // Check if table exists first
        $stmt = $pdo->query("SHOW TABLES LIKE 'two_factor_codes'");
        if ($stmt->rowCount() === 0) {
            error_log("2FA Code Storage Error: two_factor_codes table does not exist. Run migration: config/two_factor_auth_schema.sql");
            throw new PDOException("two_factor_codes table does not exist");
        }
        
        // Invalidate any existing unused codes for this user
        $stmt = $pdo->prepare("
            UPDATE two_factor_codes 
            SET used = 1 
            WHERE user_id = :user_id AND used = 0
        ");
        $stmt->execute(["user_id" => $userId]);
        
        // Insert new code, letting MySQL calculate expiry so PHP/MySQL timezone can't diverge
        $stmt = $pdo->prepare("
            INSERT INTO two_factor_codes (user_id, email, code, expires_at)
            VALUES (:user_id, :email, :code, DATE_ADD(NOW(), INTERVAL 10 MINUTE))
        ");
        $stmt->execute([
            "user_id" => $userId,
            "email"   => $email,
            "code"    => $code,
        ]);
        
        return true;
    } catch (PDOException $e) {
        error_log("2FA Code Storage Error: " . $e->getMessage());
        error_log("2FA Code Storage Error Trace: " . $e->getTraceAsString());
        return false;
    }
}

/**
 * Verify 2FA code
 * @param int $userId User ID
 * @param string $email User email
 * @param string $code 6-digit code to verify
 * @return bool True if code is valid and not expired
 */
function verify2FACode(int $userId, string $email, string $code): bool {
    try {
        $pdo = db();
        
        // Clean up expired codes
        $pdo->exec("DELETE FROM two_factor_codes WHERE expires_at < NOW()");
        
        // Find valid code
        $stmt = $pdo->prepare("
            SELECT id FROM two_factor_codes
            WHERE user_id = :user_id 
            AND email = :email 
            AND code = :code 
            AND used = 0 
            AND expires_at > NOW()
            LIMIT 1
        ");
        $stmt->execute([
            "user_id" => $userId,
            "email" => $email,
            "code" => $code
        ]);
        
        $result = $stmt->fetch();
        
        if ($result) {
            // Mark code as used
            $updateStmt = $pdo->prepare("UPDATE two_factor_codes SET used = 1 WHERE id = :id");
            $updateStmt->execute(["id" => $result['id']]);
            return true;
        }
        
        return false;
    } catch (PDOException $e) {
        error_log("2FA Verification Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Send 2FA code to user
 * @param int $userId User ID
 * @param string $email User email
 * @return array ['success' => bool, 'code' => string|null, 'error' => string|null]
 */
function send2FAVerification(int $userId, string $email): array {
    $code = generate2FACode();
    
    if (!store2FACode($userId, $email, $code)) {
        return ['success' => false, 'code' => null, 'error' => 'Failed to store verification code'];
    }
    
    if (!send2FACode($email, $code)) {
        return ['success' => false, 'code' => null, 'error' => 'Failed to send email'];
    }
    
    return ['success' => true, 'code' => $code, 'error' => null];
}

