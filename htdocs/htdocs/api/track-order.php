
<?php
require_once __DIR__ . "/db.php";

header("Content-Type: application/json; charset=UTF-8");

function respond($success, $message, $data = null, $code = 200)
{
    http_response_code($code);

    echo json_encode([
        "success" => $success,
        "message" => $message,
        "order" => $data
    ]);

    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    respond(false, "Invalid request method.", null, 405);
}

$orderNumber = trim($_GET["order_number"] ?? "");
$email = trim($_GET["email"] ?? "");

if ($orderNumber === "" || $email === "") {
    respond(false, "Enter your order number and email address.", null, 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(false, "Enter a valid email address.", null, 400);
}

/* Find the order using both its order number and customer email. */
$stmt = $conn->prepare(
    "SELECT id, order_number, customer_name, customer_email,
            subtotal, shipping, total, status
     FROM orders
     WHERE order_number = ? AND customer_email = ?
     LIMIT 1"
);

if (!$stmt) {
    respond(false, "Unable to search for the order.", null, 500);
}

$stmt->bind_param("ss", $orderNumber, $email);
$stmt->execute();

$result = $stmt->get_result();
$order = $result->fetch_assoc();
$stmt->close();

if (!$order) {
    respond(false, "No matching order was found. Check your order number and email.", null, 404);
}

/* Retrieve the products saved with this order. */
$orderId = (int)$order["id"];

$itemStmt = $conn->prepare(
    "SELECT product_name, price, quantity, color, storage, image
     FROM order_items
     WHERE order_id = ?
     ORDER BY id ASC"
);

if (!$itemStmt) {
    respond(false, "Unable to load order products.", null, 500);
}

$itemStmt->bind_param("i", $orderId);
$itemStmt->execute();

$itemResult = $itemStmt->get_result();
$items = [];

while ($item = $itemResult->fetch_assoc()) {
    $items[] = $item;
}

$itemStmt->close();
$conn->close();

/* Return only the information needed for tracking. */
respond(true, "Order found.", [
    "order_number" => $order["order_number"],
    "customer_name" => $order["customer_name"],
    "status" => $order["status"],
    "subtotal" => (float)$order["subtotal"],
    "shipping" => (float)$order["shipping"],
    "total" => (float)$order["total"],
    "items" => $items
]);
?>
