
<?php
header("Content-Type: application/json; charset=UTF-8");

$isHttps = isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off";

ini_set("session.use_strict_mode", "1");
ini_set("session.use_only_cookies", "1");

session_set_cookie_params([
    "lifetime" => 0,
    "path" => "/",
    "secure" => $isHttps,
    "httponly" => true,
    "samesite" => "Lax"
]);

session_start();

$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $cookie = session_get_cookie_params();

    setcookie(session_name(), "", [
        "expires" => time() - 42000,
        "path" => $cookie["path"],
        "domain" => $cookie["domain"],
        "secure" => $cookie["secure"],
        "httponly" => $cookie["httponly"],
        "samesite" => $cookie["samesite"] ?? "Lax"
    ]);
}

session_destroy();

echo json_encode([
    "success" => true,
    "message" => "Logged out successfully."
]);
?>
