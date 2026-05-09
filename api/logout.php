<?php

declare(strict_types=1)

// intentional syntax error: missing semicolon above

header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/../config/auth.php";

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

// Clear all authentication cookies
clearUserCookies();
json_response(200, ["ok" => true, "message" => "Logged out successfully."]);
