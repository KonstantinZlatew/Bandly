<?php
declare(strict_types=1);

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.html");
    exit;
}

require_once __DIR__ . "/vendor/autoload.php";
require_once __DIR__ . "/config/db.php";

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

if (empty($_ENV["STRIPE_API_KEY"])) {
    http_response_code(500);
    exit("Stripe API key missing in .env");
}

\Stripe\Stripe::setApiKey($_ENV["STRIPE_API_KEY"]);

$planCode = trim((string)($_POST["plan"] ?? ""));
if ($planCode === "") {
    http_response_code(400);
    exit("Invalid plan");
}

$userId = (int)$_SESSION["user_id"];

try {
    $pdo = db();

    $stmt = $pdo->prepare("
        SELECT id, code, name, plan_type, price_cents, currency
        FROM plans
        WHERE code = :code AND is_active = 1
        LIMIT 1
    ");
    $stmt->execute(["code" => $planCode]);
    $plan = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$plan) {
        http_response_code(400);
        exit("Plan not found or inactive");
    }

    $priceCents = (int)$plan["price_cents"];
    if ($priceCents <= 0) {
        http_response_code(400);
        exit("Invalid plan price");
    }

    $currency = strtolower((string)($plan["currency"] ?? "eur"));
    $productName = (string)$plan["name"];

    // Useful for your webhook handler
    $metadata = [
        "user_id" => (string)$userId,
        "plan_code" => (string)$plan["code"],
        "plan_id" => (string)$plan["id"],
    ];

    // Base URL of your app (for redirects after payment)
    // Change this if your folder name differs
    $baseUrl = "http://localhost/IELTS-AI-Evaluator";

    $session = \Stripe\Checkout\Session::create([
        "mode" => "payment",
        "success_url" => $baseUrl . "/index.php?payment=success&session_id={CHECKOUT_SESSION_ID}",
        "cancel_url"  => $baseUrl . "/payment.php?payment=cancel",
        "metadata" => $metadata,
        "payment_intent_data" => [
            "metadata" => $metadata
        ],
        "line_items" => [
            [
                "quantity" => 1,
                "price_data" => [
                    "currency" => $currency,
                    "unit_amount" => $priceCents,
                    "product_data" => [
                        "name" => $productName
                    ],
                ],
            ],
        ],
    ]);

    header("Location: " . $session->url, true, 303);
    exit;

} catch (\Stripe\Exception\ApiErrorException $e) {
    http_response_code(500);
    error_log("Stripe API error: " . $e->getMessage());
    exit("Stripe error creating checkout session");
} catch (Exception $e) {
    http_response_code(500);
    error_log("Checkout error: " . $e->getMessage());
    exit("Server error creating checkout session");
}
