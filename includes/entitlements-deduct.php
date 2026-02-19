<?php
/**
 * Deduct a credit from user's balance when they request analysis
 * Skips deduction if user has active subscription
 * 
 * @param int $userId The user ID
 * @return array Returns array with:
 *   - success: bool - Whether deduction was successful
 *   - message: string - Success or error message
 *   - credits_remaining: int - Credits remaining after deduction
 */
function deductCreditForAnalysis($userId) {
    require_once __DIR__ . "/../config/db.php";
    require_once __DIR__ . "/entitlements-check.php";
    
    if (!$userId) {
        return [
            'success' => false,
            'message' => 'User not authenticated',
            'credits_remaining' => 0
        ];
    }
    
    try {
        $pdo = db();
        
        // First check if user has subscription (no deduction needed)
        $checkResult = checkCanAnalyze($userId);
        
        if ($checkResult['has_subscription']) {
            // User has subscription, no deduction needed
            return [
                'success' => true,
                'message' => 'Subscription active, no credit deduction needed',
                'credits_remaining' => 0
            ];
        }
        
        // User doesn't have subscription, need to deduct credit
        if (!$checkResult['can_analyze']) {
            return [
                'success' => false,
                'message' => $checkResult['reason'],
                'credits_remaining' => 0
            ];
        }
        
        // Start transaction
        $pdo->beginTransaction();
        
        try {
            // Lock the row for update to prevent race conditions
            $stmt = $pdo->prepare("
                SELECT credits_balance 
                FROM user_entitlements 
                WHERE user_id = :user_id
                FOR UPDATE
            ");
            $stmt->execute(["user_id" => $userId]);
            $entitlement = $stmt->fetch();
            
            if (!$entitlement) {
                // Create default entitlements if they don't exist
                $stmt = $pdo->prepare("
                    INSERT INTO user_entitlements (user_id, credits_balance)
                    VALUES (:user_id, 0)
                ");
                $stmt->execute(["user_id" => $userId]);
                $pdo->commit();
                return [
                    'success' => false,
                    'message' => 'No credits available',
                    'credits_remaining' => 0
                ];
            }
            
            $currentBalance = (int)($entitlement["credits_balance"] ?? 0);
            
            if ($currentBalance <= 0) {
                $pdo->commit();
                return [
                    'success' => false,
                    'message' => 'No credits remaining',
                    'credits_remaining' => 0
                ];
            }
            
            // Deduct one credit
            $newBalance = $currentBalance - 1;
            
            $stmt = $pdo->prepare("
                UPDATE user_entitlements 
                SET credits_balance = :new_balance,
                    updated_at = CURRENT_TIMESTAMP
                WHERE user_id = :user_id
            ");
            $stmt->execute([
                "user_id" => $userId,
                "new_balance" => $newBalance
            ]);
            
            // Commit transaction
            $pdo->commit();
            
            return [
                'success' => true,
                'message' => 'Credit deducted successfully',
                'credits_remaining' => $newBalance
            ];
            
        } catch (Exception $e) {
            // Rollback on error
            $pdo->rollBack();
            throw $e;
        }
        
    } catch (Exception $e) {
        error_log("Credit deduction error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error deducting credit: ' . $e->getMessage(),
            'credits_remaining' => 0
        ];
    }
}
