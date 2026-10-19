
<?php
require_once __DIR__ . "/db.php";

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

if (empty($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "message" => "Please log in to view your orders."
    ]);
    exit;
}

$userId = (int) $_SESSION["user_id"];

$stmt = $conn->prepare(
    "SELECT id, order_number, customer_name, subtotal,
            shipping, total, status
     FROM orders
     WHERE user_id = ?
     ORDER BY id DESC"
);

if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Unable to load your orders."
    ]);
    exit;
}

$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->bind_result(
    $id,
    $orderNumber,
    $customerName,
    $subtotal,
    $shipping,
    $total,
    $status
);

$orders = [];

while ($stmt->fetch()) {
    $orders[] = [
        "id" => (int) $id,
        "order_number" => $orderNumber,
        "customer_name" => $customerName,
        "subtotal" => (float) $subtotal,
        "shipping" => (float) $shipping,
        "total" => (float) $total,
        "status" => $status
    ];
}

$stmt->close();
$conn->close();

echo json_encode([
    "success" => true,
    "orders" => $orders
]);
?>
