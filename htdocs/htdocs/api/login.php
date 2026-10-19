
<?php
require_once __DIR__ . "/db.php";

header("Content-Type: application/json; charset=UTF-8");

function respond($success, $message, $user = null, $code = 200)
{
    http_response_code($code);

    echo json_encode([
        "success" => $success,
        "message" => $message,
        "user" => $user
    ]);

    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    respond(false, "Invalid request method.", null, 405);
}

$email = trim($_POST["email"] ?? "");
$password = $_POST["password"] ?? "";

if ($email === "" || $password === "") {
    respond(false, "Please enter your email and password.", null, 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(false, "Invalid email or password.", null, 401);
}

$stmt = $conn->prepare(
    "SELECT id, name, email, password, role
     FROM users
     WHERE email = ?
     LIMIT 1"
);

if (!$stmt) {
    respond(false, "Unable to process login right now.", null, 500);
}

$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->bind_result(
    $userId,
    $userName,
    $userEmail,
    $userPassword,
    $userRole
);

$found = $stmt->fetch();
$stmt->close();

if (!$found || !password_verify($password, $userPassword)) {
    respond(false, "Invalid email or password.", null, 401);
}

$conn->close();

// Create a server-side session for the customer.
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
session_regenerate_id(true);

// Clear previous login identity before assigning the new one.
$_SESSION = [];

$_SESSION["user_id"] = (int) $userId;
$_SESSION["user_name"] = $userName;
$_SESSION["user_email"] = $userEmail;
$_SESSION["user_role"] = $userRole;
$_SESSION["user_last_activity"] = time();

respond(true, "Login successful.", [
    "id" => (int) $userId,
    "name" => $userName,
    "email" => $userEmail,
    "role" => $userRole
]);
?>
