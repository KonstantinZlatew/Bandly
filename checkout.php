<?php

require_once realpath(__DIR__ . "/vendor/autoload.php");

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$stripe_sk = $_ENV["STRIPE_API_KEY"];;

\Stripe\Stripe::setApiKey($stripe_sk);

$plan = $_POST["plan"] ?? "";

$plans = [
    "basic" => ["amount" => 399, "name" => "10 Credits"],
    "standard" => ["amount" => 1299, "name" => "Monthly Subscription"],
    "pro" => ["amount" => 9999, "name" => "Yearly Subscription"],
];

if (!isset($plans[$plan])) {
    http_response_code(400);
    exit("Invalid plan");
}

$price = $plans[$plan]["amount"];
$product = $plans[$plan]["name"];

$checkout_session = Stripe\Checkout\Session::create([
    "mode" => "payment",
    "success_url" => "http://localhost/IELTS-AI-EVALUATOR/exam.php?type=general",
    "cancel_url" => "http://localhost/IELTS-AI-EVALUATOR/pricing.php",
    "line_items" => [
        [
            "quantity" => 1,
            "price_data" => [
                "currency" => "eur",
                "unit_amount" => $price,
                "product_data" => [
                    "name" => $product
                ]
            ]
        ]
    ]
]);

header("Location: " . $checkout_session->url);
exit;
?>