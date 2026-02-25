<?php
declare(strict_types=1);

require_once realpath(__DIR__ . "/../vendor/autoload.php");
require_once __DIR__ . "/../config/db.php";

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . "/..");
$dotenv->load();

\Stripe\Stripe::setApiKey($_ENV["STRIPE_API_KEY"]);
$endpoint_secret = $_ENV["STRIPE_WEBHOOK_SECRET"] ?? "";

$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
$event = null;

// #region agent log
file_put_contents(__DIR__ . '/../.cursor/debug.log', json_encode(['id'=>'log_'.time().'_webhook1','timestamp'=>time()*1000,'location'=>'stripe-webhook.php:15','message'=>'Webhook called','data'=>['hasPayload'=>!empty($payload),'payloadLength'=>strlen($payload),'hasSignature'=>!empty($sig_header),'endpointSecretSet'=>!empty($endpoint_secret),'requestMethod'=>$_SERVER['REQUEST_METHOD']??'unknown'],'sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'A'])."\n", FILE_APPEND);
// #endregion

// If no webhook secret is set, log a warning but still try to process (for testing)
if (empty($endpoint_secret)) {
    // #region agent log
    file_put_contents(__DIR__ . '/../.cursor/debug.log', json_encode(['id'=>'log_'.time().'_webhook_warn','timestamp'=>time()*1000,'location'=>'stripe-webhook.php:22','message'=>'WARNING: No webhook secret configured','data'=>[],'sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'A'])."\n", FILE_APPEND);
    // #endregion
}

try {
    $event = \Stripe\Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
    
    // #region agent log
    file_put_contents(__DIR__ . '/../.cursor/debug.log', json_encode(['id'=>'log_'.time().'_webhook2','timestamp'=>time()*1000,'location'=>'stripe-webhook.php:22','message'=>'Event constructed successfully','data'=>['eventType'=>$event->type,'eventId'=>$event->id],'sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'A'])."\n", FILE_APPEND);
    // #endregion
} catch(\UnexpectedValueException $e) {
    // #region agent log
    file_put_contents(__DIR__ . '/../.cursor/debug.log', json_encode(['id'=>'log_'.time().'_webhook_err1','timestamp'=>time()*1000,'location'=>'stripe-webhook.php:25','message'=>'Invalid payload error','data'=>['error'=>$e->getMessage()],'sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'A'])."\n", FILE_APPEND);
    // #endregion
    http_response_code(400);
    exit('Invalid payload');
} catch(\Stripe\Exception\SignatureVerificationException $e) {
    // #region agent log
    file_put_contents(__DIR__ . '/../.cursor/debug.log', json_encode(['id'=>'log_'.time().'_webhook_err2','timestamp'=>time()*1000,'location'=>'stripe-webhook.php:29','message'=>'Invalid signature error','data'=>['error'=>$e->getMessage()],'sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'A'])."\n", FILE_APPEND);
    // #endregion
    http_response_code(400);
    exit('Invalid signature');
}

// Handle the event
// #region agent log
file_put_contents(__DIR__ . '/../.cursor/debug.log', json_encode(['id'=>'log_'.time().'_webhook3','timestamp'=>time()*1000,'location'=>'stripe-webhook.php:32','message'=>'Processing event','data'=>['eventType'=>$event->type],'sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'A'])."\n", FILE_APPEND);
// #endregion

switch ($event->type) {
    case 'checkout.session.completed':
        $session = $event->data->object;
        // #region agent log
        file_put_contents(__DIR__ . '/../.cursor/debug.log', json_encode(['id'=>'log_'.time().'_webhook4','timestamp'=>time()*1000,'location'=>'stripe-webhook.php:35','message'=>'Checkout session completed event','data'=>['sessionId'=>$session->id,'paymentStatus'=>$session->payment_status,'metadata'=>is_object($session->metadata)?(array)$session->metadata:$session->metadata],'sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'C'])."\n", FILE_APPEND);
        // #endregion
        handleCheckoutSessionCompleted($session);
        break;
    case 'customer.subscription.created':
    case 'customer.subscription.updated':
        $subscription = $event->data->object;
        handleSubscriptionUpdate($subscription);
        break;
    case 'customer.subscription.deleted':
        $subscription = $event->data->object;
        handleSubscriptionDeleted($subscription);
        break;
    default:
        http_response_code(200);
        exit('Event type not handled: ' . $event->type);
}

http_response_code(200);
echo json_encode(['received' => true]);

function handleCheckoutSessionCompleted($session) {
    $pdo = null;
    try {
        // #region agent log
        file_put_contents(__DIR__ . '/../.cursor/debug.log', json_encode(['id'=>'log_'.time().'_handler1','timestamp'=>time()*1000,'location'=>'stripe-webhook.php:handleCheckoutSessionCompleted:52','message'=>'Handler function called','data'=>['sessionId'=>$session->id],'sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'B'])."\n", FILE_APPEND);
        // #endregion
        
        $pdo = db();
        $pdo->beginTransaction();

        // Access metadata correctly - Stripe metadata can be object or array
        $metadata = $session->metadata;
        if (is_object($metadata)) {
            $userId = (int)($metadata->user_id ?? 0);
            $planCode = $metadata->plan_code ?? "";
        } else {
            $userId = (int)($metadata["user_id"] ?? 0);
            $planCode = $metadata["plan_code"] ?? "";
        }
        $checkoutSessionId = $session->id;
        // Handle payment_intent - it can be a string ID, an object, or null
        $paymentIntentId = null;
        if (isset($session->payment_intent)) {
            if (is_object($session->payment_intent)) {
                $paymentIntentId = $session->payment_intent->id ?? null;
            } else {
                $paymentIntentId = (string)$session->payment_intent;
            }
        }
        $amountCents = (int)$session->amount_total;
        $currency = strtoupper($session->currency ?? "EUR");
        $status = ($session->payment_status === "paid") ? "paid" : "pending";

        // #region agent log
        file_put_contents(__DIR__ . '/../.cursor/debug.log', json_encode(['id'=>'log_'.time().'_handler2','timestamp'=>time()*1000,'location'=>'stripe-webhook.php:handleCheckoutSessionCompleted:67','message'=>'Extracted session data','data'=>['userId'=>$userId,'planCode'=>$planCode,'status'=>$status,'amountCents'=>$amountCents],'sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'C'])."\n", FILE_APPEND);
        // #endregion

        error_log("Processing checkout session: user_id=$userId, plan_code=$planCode, status=$status");

        if (!$userId || !$planCode) {
            // #region agent log
            file_put_contents(__DIR__ . '/../.cursor/debug.log', json_encode(['id'=>'log_'.time().'_handler_err1','timestamp'=>time()*1000,'location'=>'stripe-webhook.php:handleCheckoutSessionCompleted:72','message'=>'Missing metadata','data'=>['userId'=>$userId,'planCode'=>$planCode,'metadata'=>$metadata],'sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'C'])."\n", FILE_APPEND);
            // #endregion
            error_log("Missing user_id or plan_code in session metadata. Metadata: " . json_encode($metadata));
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return;
        }

        // Get plan from database
        $stmt = $pdo->prepare("SELECT id, plan_type, credits_amount, duration_days FROM plans WHERE code = :code LIMIT 1");
        $stmt->execute(["code" => $planCode]);
        $plan = $stmt->fetch();

        // #region agent log
        file_put_contents(__DIR__ . '/../.cursor/debug.log', json_encode(['id'=>'log_'.time().'_handler3','timestamp'=>time()*1000,'location'=>'stripe-webhook.php:handleCheckoutSessionCompleted:82','message'=>'Plan lookup result','data'=>['planFound'=>!empty($plan),'planCode'=>$planCode],'sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'D'])."\n", FILE_APPEND);
        // #endregion

        if (!$plan) {
            error_log("Plan not found: " . $planCode);
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return;
        }

        $planId = (int)$plan["id"];

        // Create purchase record
        // #region agent log
        file_put_contents(__DIR__ . '/../.cursor/debug.log', json_encode(['id'=>'log_'.time().'_handler4','timestamp'=>time()*1000,'location'=>'stripe-webhook.php:handleCheckoutSessionCompleted:95','message'=>'Before purchase insert','data'=>['userId'=>$userId,'planId'=>$planId,'amountCents'=>$amountCents,'status'=>$status],'sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'D'])."\n", FILE_APPEND);
        // #endregion
        
        $paidAt = ($status === "paid") ? date("Y-m-d H:i:s") : null;

        $stmt = $pdo->prepare("
            INSERT INTO purchases (
            user_id, plan_id, provider,
            provider_payment_intent_id, provider_checkout_session_id,
            amount_cents, currency, status, paid_at
        ) VALUES (
            :user_id, :plan_id, 'stripe',
            :payment_intent_id, :checkout_session_id,
            :amount_cents, :currency, :status, :paid_at
        )
        ");

        $result = $stmt->execute([
            "user_id" => $userId,
            "plan_id" => $planId,
            "payment_intent_id" => $paymentIntentId,
            "checkout_session_id" => $checkoutSessionId,
            "amount_cents" => $amountCents,
            "currency" => $currency,
            "status" => $status,
            "paid_at" => $paidAt
        ]);

        if (!$result) {
        throw new Exception("Failed to insert purchase record");
        }

        
        // #region agent log
        file_put_contents(__DIR__ . '/../.cursor/debug.log', json_encode(['id'=>'log_'.time().'_handler5','timestamp'=>time()*1000,'location'=>'stripe-webhook.php:handleCheckoutSessionCompleted:108','message'=>'Purchase insert result','data'=>['success'=>$result,'lastInsertId'=>$pdo->lastInsertId()],'sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'D'])."\n", FILE_APPEND);
        // #endregion
        
        if (!$result) {
            throw new Exception("Failed to insert purchase record");
        }
        
        error_log("Purchase record created successfully");

        // Update user entitlements
        if ($plan["plan_type"] === "credits") {
            // Add credits
            $creditsAmount = (int)$plan["credits_amount"];
            // #region agent log
            file_put_contents(__DIR__ . '/../.cursor/debug.log', json_encode(['id'=>'log_'.time().'_handler6','timestamp'=>time()*1000,'location'=>'stripe-webhook.php:handleCheckoutSessionCompleted:116','message'=>'Before credits update','data'=>['userId'=>$userId,'creditsAmount'=>$creditsAmount],'sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'D'])."\n", FILE_APPEND);
            // #endregion
            $stmt = $pdo->prepare("
                INSERT INTO user_entitlements (user_id, credits_balance)
                VALUES (:user_id, :credits_add)
                ON DUPLICATE KEY UPDATE credits_balance = credits_balance + :credits_inc
            ");
            $creditsResult = $stmt->execute([
                "user_id" => $userId,
                "credits_add" => $creditsAmount,
                "credits_inc" => $creditsAmount
            ]);
            // #region agent log
            file_put_contents(__DIR__ . '/../.cursor/debug.log', json_encode(['id'=>'log_'.time().'_handler7','timestamp'=>time()*1000,'location'=>'stripe-webhook.php:handleCheckoutSessionCompleted:125','message'=>'Credits update result','data'=>['success'=>$creditsResult],'sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'D'])."\n", FILE_APPEND);
            // #endregion
        } elseif ($plan["plan_type"] === "subscription") {
            // Check if user already has an active subscription
            $stmt = $pdo->prepare("
                SELECT current_period_end 
                FROM user_subscriptions 
                WHERE user_id = :user_id 
                AND status IN ('active', 'trialing')
                AND (current_period_end IS NULL OR current_period_end > NOW())
                ORDER BY current_period_end DESC
                LIMIT 1
            ");
            $stmt->execute(["user_id" => $userId]);
            $existingSubscription = $stmt->fetch();
            
            $durationDays = (int)$plan["duration_days"];
            
            // If user has an active subscription, extend from the current period_end
            // Otherwise, start from now
            if ($existingSubscription && $existingSubscription['current_period_end']) {
                $currentEnd = new DateTime($existingSubscription['current_period_end']);
                $currentEnd->modify("+{$durationDays} days");
                $unlimitedUntil = $currentEnd->format('Y-m-d H:i:s');
            } else {
                $unlimitedUntil = date('Y-m-d H:i:s', strtotime("+{$durationDays} days"));
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO user_entitlements (user_id, unlimited_until)
                VALUES (:user_id, :until_date)
                ON DUPLICATE KEY UPDATE unlimited_until = GREATEST(COALESCE(unlimited_until, '1970-01-01'), :until_date_update)
            ");
            $stmt->execute([
                "user_id" => $userId,
                "until_date" => $unlimitedUntil,
                "until_date_update" => $unlimitedUntil
            ]);

            // Create or update subscription record
            // If user has an active subscription, extend it; otherwise create new
            if ($existingSubscription && $existingSubscription['current_period_end']) {
                // Update existing active subscription to extend the period
                $currentEnd = new DateTime($existingSubscription['current_period_end']);
                $currentEnd->modify("+{$durationDays} days");
                $newPeriodEnd = $currentEnd->format('Y-m-d H:i:s');
                
                $stmt = $pdo->prepare("
                    UPDATE user_subscriptions 
                    SET plan_id = :plan_id,
                        status = 'active',
                        current_period_end = :period_end,
                        updated_at = NOW()
                    WHERE user_id = :user_id 
                    AND status IN ('active', 'trialing')
                    AND (current_period_end IS NULL OR current_period_end > NOW())
                ");
                $stmt->execute([
                    "user_id" => $userId,
                    "plan_id" => $planId,
                    "period_end" => $newPeriodEnd
                ]);
            } else {
                // Create new subscription record
                $newPeriodEnd = date('Y-m-d H:i:s', strtotime("+{$durationDays} days"));
                
                $stmt = $pdo->prepare("
                    INSERT INTO user_subscriptions (
                        user_id, plan_id, provider, provider_subscription_id,
                        status, current_period_start, current_period_end
                    ) VALUES (
                        :user_id, :plan_id, 'stripe', :subscription_id,
                        'active', NOW(), :period_end
                    )
                ");
                $stmt->execute([
                    "user_id" => $userId,
                    "plan_id" => $planId,
                    "subscription_id" => $checkoutSessionId,
                    "period_end" => $newPeriodEnd
                ]);
            }

        }

        $pdo->commit();
        // #region agent log
        file_put_contents(__DIR__ . '/../.cursor/debug.log', json_encode(['id'=>'log_'.time().'_handler8','timestamp'=>time()*1000,'location'=>'stripe-webhook.php:handleCheckoutSessionCompleted:163','message'=>'Transaction committed successfully','data'=>['userId'=>$userId,'planCode'=>$planCode],'sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'B'])."\n", FILE_APPEND);
        // #endregion
        error_log("Successfully processed checkout session for user $userId, plan $planCode");
    } catch (Exception $e) {
        // #region agent log
        file_put_contents(__DIR__ . '/../.cursor/debug.log', json_encode(['id'=>'log_'.time().'_handler_err2','timestamp'=>time()*1000,'location'=>'stripe-webhook.php:handleCheckoutSessionCompleted:167','message'=>'Exception caught','data'=>['error'=>$e->getMessage(),'file'=>$e->getFile(),'line'=>$e->getLine()],'sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'B'])."\n", FILE_APPEND);
        // #endregion
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $errorMsg = "Error handling checkout session: " . $e->getMessage() . " | Trace: " . $e->getTraceAsString();
        error_log($errorMsg);
        // Also log to a file for easier debugging
        file_put_contents(__DIR__ . "/../logs/webhook-errors.log", date('Y-m-d H:i:s') . " - " . $errorMsg . "\n", FILE_APPEND);
    }
}

function handleSubscriptionUpdate($subscription) {
    // Handle subscription updates from Stripe
    // This would be used if you implement recurring subscriptions
}

function handleSubscriptionDeleted($subscription) {
    try {
        $pdo = db();
        
        $stmt = $pdo->prepare("
            UPDATE user_subscriptions 
            SET status = 'canceled', updated_at = NOW()
            WHERE provider_subscription_id = :subscription_id
        ");
        $stmt->execute(["subscription_id" => $subscription->id]);
    } catch (Exception $e) {
        error_log("Error handling subscription deletion: " . $e->getMessage());
    }
}

