<?php

declare(strict_types=1);

header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/../config/auth.php";
require_once __DIR__ . "/../includes/entitlements-check.php";

/**
 * Send JSON response and exit.
 *
 * @param integer $code    HTTP status code.
 * @param array   $payload Response data.
 * @return void
 */
function json_response(int $code, array $payload): void
{
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isAuthenticated()) {
    json_response(401, ["ok" => false, "error" => "Not authenticated"]);
}

$userId = getUserId();
if ($userId === null) {
    json_response(401, ["ok" => false, "error" => "Not authenticated"]);
}

try {
    $entitlements = getEntitlementsSummary($userId);

    json_response(200, [
        "ok" => true,
        "credits_balance" => $entitlements['credits_balance'],
        "has_unlimited" => $entitlements['has_unlimited'],
        "unlimited_until" => $entitlements['unlimited_until'],
        "subscription_plan" => $entitlements['plan_name'],
    ]);
} catch (Exception $e) {
    error_log("Entitlements API error: " . $e->getMessage());
    json_response(500, ["ok" => false, "error" => "Server error"]);
}
