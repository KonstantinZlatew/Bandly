<?php

declare(strict_types=1);

/**
 * Process a Stripe checkout session on the success redirect.
 *
 * This is called when Stripe redirects the user back with
 * ?payment=success&session_id=cs_xxx. It verifies the session
 * directly with the Stripe API and credits the user — acting as
 * a reliable fallback when the webhook cannot reach localhost
 * (local dev) or arrives late.
 *
 * Idempotent: checks provider_checkout_session_id before inserting
 * a purchase, so running this twice for the same session is safe.
 *
 * @param integer $userId    The logged-in user's ID.
 * @param string  $sessionId The Stripe checkout session ID (cs_xxx).
 * @return void
 */
function processPaymentSuccess(int $userId, string $sessionId): void
{
    if ($userId <= 0 || $sessionId === '') {
        return;
    }

    require_once __DIR__ . '/../vendor/autoload.php';
    require_once __DIR__ . '/../config/db.php';

    // Load .env (already loaded by caller, but be safe)
    if (!isset($_ENV['STRIPE_API_KEY'])) {
        $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
        $dotenv->load();
    }

    if (empty($_ENV['STRIPE_API_KEY'])) {
        error_log('processPaymentSuccess: STRIPE_API_KEY not set');
        return;
    }

    \Stripe\Stripe::setApiKey($_ENV['STRIPE_API_KEY']);

    try {
        // Retrieve the session from Stripe to verify it is really paid
        $session = \Stripe\Checkout\Session::retrieve($sessionId);
    } catch (\Exception $e) {
        error_log('processPaymentSuccess: could not retrieve session ' . $sessionId . ': ' . $e->getMessage());
        return;
    }

    if ($session->payment_status !== 'paid') {
        // Not paid yet — webhook will handle it if/when it arrives
        return;
    }

    // Validate metadata
    $metadata = $session->metadata;
    $metaUserId = is_object($metadata) ? (int)($metadata->user_id ?? 0) : (int)($metadata['user_id'] ?? 0);
    $planCode   = is_object($metadata) ? ($metadata->plan_code ?? '') : ($metadata['plan_code'] ?? '');

    if ($metaUserId <= 0 || $planCode === '') {
        error_log('processPaymentSuccess: missing metadata in session ' . $sessionId);
        return;
    }

    // Safety check: the session must belong to the logged-in user
    if ($metaUserId !== $userId) {
        error_log("processPaymentSuccess: session user ($metaUserId) != logged-in user ($userId)");
        return;
    }

    $pdo = db();

    // ── Idempotency check ──────────────────────────────────────────────────────
    // If this session was already processed (by a prior visit or the webhook)
    // skip it silently.
    $check = $pdo->prepare(
        'SELECT id FROM purchases WHERE provider_checkout_session_id = :sid LIMIT 1'
    );
    $check->execute(['sid' => $sessionId]);
    if ($check->fetch()) {
        return; // already handled
    }

    // ── Look up the plan ───────────────────────────────────────────────────────
    $stmt = $pdo->prepare(
        'SELECT id, plan_type, credits_amount, duration_days
         FROM plans WHERE code = :code LIMIT 1'
    );
    $stmt->execute(['code' => $planCode]);
    $plan = $stmt->fetch();

    if (!$plan) {
        error_log('processPaymentSuccess: plan not found: ' . $planCode);
        return;
    }

    $planId = (int)$plan['id'];

    $paymentIntentId = null;
    if (isset($session->payment_intent)) {
        $paymentIntentId = is_object($session->payment_intent)
            ? ($session->payment_intent->id ?? null)
            : (string)$session->payment_intent;
    }

    $amountCents = (int)$session->amount_total;
    $currency    = strtoupper($session->currency ?? 'EUR');
    $paidAt      = date('Y-m-d H:i:s');

    // ── Wrap everything in a transaction ──────────────────────────────────────
    $pdo->beginTransaction();
    try {
        // Insert purchase record
        $pdo->prepare('
            INSERT INTO purchases (
                user_id, plan_id, provider,
                provider_payment_intent_id, provider_checkout_session_id,
                amount_cents, currency, status, paid_at
            ) VALUES (
                :user_id, :plan_id, \'stripe\',
                :payment_intent_id, :checkout_session_id,
                :amount_cents, :currency, \'paid\', :paid_at
            )
        ')->execute([
            'user_id'             => $userId,
            'plan_id'             => $planId,
            'payment_intent_id'   => $paymentIntentId,
            'checkout_session_id' => $sessionId,
            'amount_cents'        => $amountCents,
            'currency'            => $currency,
            'paid_at'             => $paidAt,
        ]);

        if ($plan['plan_type'] === 'credits') {
            $credits = (int)$plan['credits_amount'];
            $pdo->prepare('
                INSERT INTO user_entitlements (user_id, credits_balance)
                VALUES (:user_id, :add)
                ON DUPLICATE KEY UPDATE credits_balance = credits_balance + :inc
            ')->execute([
                'user_id' => $userId,
                'add'     => $credits,
                'inc'     => $credits,
            ]);
        } elseif ($plan['plan_type'] === 'subscription') {
            $durationDays = (int)$plan['duration_days'];

            // Extend an existing active subscription, or start a new one
            $existingStmt = $pdo->prepare('
                SELECT current_period_end
                FROM user_subscriptions
                WHERE user_id = :user_id
                  AND status IN (\'active\', \'trialing\')
                  AND (current_period_end IS NULL OR current_period_end > NOW())
                ORDER BY current_period_end DESC
                LIMIT 1
            ');
            $existingStmt->execute(['user_id' => $userId]);
            $existing = $existingStmt->fetch();

            if ($existing && $existing['current_period_end']) {
                $end = new DateTime($existing['current_period_end']);
                $end->modify("+{$durationDays} days");
                $unlimitedUntil = $end->format('Y-m-d H:i:s');
            } else {
                $unlimitedUntil = date('Y-m-d H:i:s', strtotime("+{$durationDays} days"));
            }

            $pdo->prepare('
                INSERT INTO user_entitlements (user_id, unlimited_until)
                VALUES (:user_id, :until)
                ON DUPLICATE KEY UPDATE
                    unlimited_until = GREATEST(COALESCE(unlimited_until, \'1970-01-01\'), :until_update)
            ')->execute([
                'user_id'      => $userId,
                'until'        => $unlimitedUntil,
                'until_update' => $unlimitedUntil,
            ]);

            if ($existing && $existing['current_period_end']) {
                $end = new DateTime($existing['current_period_end']);
                $end->modify("+{$durationDays} days");
                $newEnd = $end->format('Y-m-d H:i:s');

                $pdo->prepare('
                    UPDATE user_subscriptions
                    SET plan_id = :plan_id,
                        status  = \'active\',
                        current_period_end = :period_end,
                        updated_at = NOW()
                    WHERE user_id = :user_id
                      AND status IN (\'active\', \'trialing\')
                      AND (current_period_end IS NULL OR current_period_end > NOW())
                ')->execute([
                    'user_id'    => $userId,
                    'plan_id'    => $planId,
                    'period_end' => $newEnd,
                ]);
            } else {
                $newEnd = date('Y-m-d H:i:s', strtotime("+{$durationDays} days"));

                $pdo->prepare('
                    INSERT INTO user_subscriptions (
                        user_id, plan_id, provider, provider_subscription_id,
                        status, current_period_start, current_period_end
                    ) VALUES (
                        :user_id, :plan_id, \'stripe\', :sub_id,
                        \'active\', NOW(), :period_end
                    )
                ')->execute([
                    'user_id'    => $userId,
                    'plan_id'    => $planId,
                    'sub_id'     => $sessionId,
                    'period_end' => $newEnd,
                ]);
            }
        }

        $pdo->commit();
        error_log("processPaymentSuccess: fulfilled session $sessionId for user $userId, plan $planCode");
    } catch (\Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('processPaymentSuccess: DB error for session ' . $sessionId . ': ' . $e->getMessage());
    }
}
