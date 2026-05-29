<?php

/**
 * Load credits and subscription summary for display/API.
 *
 * @param integer|null $userId The user ID to load.
 * @return array{
 *   credits_balance: int,
 *   has_unlimited: bool,
 *   unlimited_until: string|null,
 *   plan_name: string|null
 * }
 */
function getEntitlementsSummary(?int $userId): array
{
    require_once __DIR__ . "/../config/db.php";

    $summary = [
        'credits_balance' => 0,
        'has_unlimited' => false,
        'unlimited_until' => null,
        'plan_name' => null,
    ];

    if (!$userId) {
        return $summary;
    }

    try {
        $pdo = db();

        $stmt = $pdo->prepare("
            SELECT credits_balance, unlimited_until
            FROM user_entitlements
            WHERE user_id = :user_id
            LIMIT 1
        ");
        $stmt->execute(["user_id" => $userId]);
        $entitlements = $stmt->fetch();

        if (!$entitlements) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO user_entitlements (user_id, credits_balance)
                    VALUES (:user_id, 0)
                ");
                $stmt->execute(["user_id" => $userId]);
            } catch (PDOException $e) {
                $stmt = $pdo->prepare("
                    SELECT credits_balance, unlimited_until
                    FROM user_entitlements
                    WHERE user_id = :user_id
                    LIMIT 1
                ");
                $stmt->execute(["user_id" => $userId]);
                $entitlements = $stmt->fetch();
            }
        }

        $creditsBalance = (int)($entitlements["credits_balance"] ?? 0);
        $unlimitedUntil = $entitlements["unlimited_until"] ?? null;
        $summary['credits_balance'] = $creditsBalance;

        $stmt = $pdo->prepare("
            SELECT us.current_period_end, p.name as plan_name
            FROM user_subscriptions us
            JOIN plans p ON p.id = us.plan_id
            WHERE us.user_id = :user_id
            AND us.status IN ('active', 'trialing')
            AND (us.current_period_end IS NULL OR us.current_period_end > NOW())
            ORDER BY us.current_period_end DESC
            LIMIT 1
        ");
        $stmt->execute(["user_id" => $userId]);
        $subscription = $stmt->fetch();

        if ($subscription) {
            $summary['has_unlimited'] = true;
            $summary['unlimited_until'] = $subscription["current_period_end"];
            $summary['plan_name'] = $subscription["plan_name"];
            return $summary;
        }

        if ($unlimitedUntil) {
            $unlimitedDate = new DateTime($unlimitedUntil);
            $now = new DateTime();
            if ($unlimitedDate > $now) {
                $summary['has_unlimited'] = true;
                $summary['unlimited_until'] = $unlimitedUntil;
            }
        }
    } catch (Exception $e) {
        error_log("Entitlements summary error: " . $e->getMessage());
    }

    return $summary;
}

/**
 * Check if a user can analyze a submission (speaking or writing) (wheather he has subscription or credits)
 *
 * @param integer|null $userId The user ID to check.
 * @return array Returns array with:
 *   - can_analyze: bool - Whether user can analyze
 *   - reason: string - Reason if cannot analyze
 *   - has_subscription: bool - Whether user has active subscription
 *   - credits_remaining: int - Number of credits remaining (0 if subscription)
 */
function checkCanAnalyze(?int $userId)
{

    require_once __DIR__ . "/../config/db.php";
    if (!$userId) {
        return [
            'can_analyze' => false,
            'reason' => 'User not authenticated',
            'has_subscription' => false,
            'credits_remaining' => 0
        ];
    }

    try {
        $pdo = db();
// First, check for active subscription
        $stmt = $pdo->prepare("
            SELECT us.current_period_end, p.name as plan_name
            FROM user_subscriptions us
            JOIN plans p ON p.id = us.plan_id
            WHERE us.user_id = :user_id 
            AND us.status IN ('active', 'trialing')
            AND (us.current_period_end IS NULL OR us.current_period_end > NOW())
            ORDER BY us.current_period_end DESC
            LIMIT 1
        ");
        $stmt->execute(["user_id" => $userId]);
        $subscription = $stmt->fetch();
        if ($subscription) {
        // User has active subscription
            return [
                'can_analyze' => true,
                'reason' => 'Active subscription',
                'has_subscription' => true,
                'credits_remaining' => 0 // Unlimited, so credits don't matter
            ];
        }

        // Check unlimited_until from user_entitlements
        $stmt = $pdo->prepare("
            SELECT unlimited_until 
            FROM user_entitlements 
            WHERE user_id = :user_id
            LIMIT 1
        ");
        $stmt->execute(["user_id" => $userId]);
        $entitlement = $stmt->fetch();
        if ($entitlement && $entitlement['unlimited_until']) {
            $unlimitedDate = new DateTime($entitlement['unlimited_until']);
            $now = new DateTime();
            if ($unlimitedDate > $now) {
                return [
                    'can_analyze' => true,
                    'reason' => 'Unlimited access until ' . $entitlement['unlimited_until'],
                    'has_subscription' => true,
                    'credits_remaining' => 0
                ];
            }
        }

        // No subscription, check credits
        $stmt = $pdo->prepare("
            SELECT credits_balance 
            FROM user_entitlements 
            WHERE user_id = :user_id
            LIMIT 1
        ");
        $stmt->execute(["user_id" => $userId]);
        $entitlement = $stmt->fetch();
        if (!$entitlement) {
        // Create default entitlements if they don't exist
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO user_entitlements (user_id, credits_balance)
                    VALUES (:user_id, 0)
                ");
                $stmt->execute(["user_id" => $userId]);
                $creditsBalance = 0;
            } catch (PDOException $e) {
        // If insert fails, try to fetch again
                $stmt = $pdo->prepare("
                    SELECT credits_balance 
                    FROM user_entitlements 
                    WHERE user_id = :user_id
                    LIMIT 1
                ");
                $stmt->execute(["user_id" => $userId]);
                $entitlement = $stmt->fetch();
                $creditsBalance = $entitlement ? (int)($entitlement["credits_balance"] ?? 0) : 0;
            }
        } else {
            $creditsBalance = (int)($entitlement["credits_balance"] ?? 0);
        }

        if ($creditsBalance > 0) {
            return [
                'can_analyze' => true,
                'reason' => 'Has credits available',
                'has_subscription' => false,
                'credits_remaining' => $creditsBalance
            ];
        } else {
            return [
                'can_analyze' => false,
                'reason' => 'No credits remaining. Please purchase credits or a subscription.',
                'has_subscription' => false,
                'credits_remaining' => 0
            ];
        }
    } catch (Exception $e) {
        error_log("Entitlements check error: " . $e->getMessage());
        return [
            'can_analyze' => false,
            'reason' => 'Error checking entitlements: ' . $e->getMessage(),
            'has_subscription' => false,
            'credits_remaining' => 0
        ];
    }
}
