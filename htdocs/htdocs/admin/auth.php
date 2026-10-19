
<?php
// ========================================
// TECHSTORE ADMIN AUTHENTICATION GUARD
// ========================================

// Start the session if it has not already started.
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Check that an admin is logged in and has the correct role.
if (
    empty($_SESSION["admin_id"]) ||
    ($_SESSION["admin_role"] ?? "") !== "admin"
) {
    header("Location: login.php");
    exit;
}

// Expire the session after 30 minutes of inactivity.
$lastActivity = (int)($_SESSION["admin_last_activity"] ?? 0);

if (
    $lastActivity === 0 ||
    (time() - $lastActivity) > 1800
) {
    // Clear all session data.
    $_SESSION = [];

    // Expire the session cookie in the browser.
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

    // Destroy the expired session.
    session_destroy();

    header("Location: login.php?expired=1");
    exit;
}

// Refresh the activity timestamp after a valid request.
$_SESSION["admin_last_activity"] = time();
?>
```