
<?php
// ========================================
// TECHSTORE ADMIN LOGOUT
// ========================================

// Match the session cookie settings used by admin/login.php.
$isHttps = (
    isset($_SERVER["HTTPS"]) &&
    $_SERVER["HTTPS"] !== "off"
);

session_set_cookie_params([
    "lifetime" => 0,
    "path" => "/TechStore/",
    "secure" => $isHttps,
    "httponly" => true,
    "samesite" => "Lax"
]);

// Start the session.
session_start();

// Remove all session data.
$_SESSION = [];

// Expire the session cookie in the browser.
if (ini_get("session.use_cookies")) {
    setcookie(session_name(), "", [
        "expires" => time() - 42000,
        "path" => "/TechStore/",
        "secure" => $isHttps,
        "httponly" => true,
        "samesite" => "Lax"
    ]);
}

// Destroy the server-side session.
session_destroy();

// Return to the admin login page.
header("Location: login.php");
exit;
?>
