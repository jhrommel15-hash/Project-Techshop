
<?php
// Check whether the current session belongs to an admin.

header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: no-store, no-cache, must-revalidate");

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$isAdmin =
    !empty($_SESSION["admin_id"]) &&
    ($_SESSION["admin_role"] ?? "") === "admin";

echo json_encode([
    "isAdmin" => $isAdmin
]);

exit;
