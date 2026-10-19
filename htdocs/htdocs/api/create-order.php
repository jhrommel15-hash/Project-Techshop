<?php

require_once "db.php";

header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode([
        "success" => false,
        "message" => "Invalid request method."
    ]);
    exit;
}

/* ================================
   GET ORDER DATA
================================ */

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid order data."
    ]);
    exit;
}

/* ================================
   CUSTOMER INFORMATION
================================ */

$name = trim($data["customer"]["name"] ?? "");
$email = trim($data["customer"]["email"] ?? "");
$phone = trim($data["customer"]["phone"] ?? "");

$address = trim($data["delivery"]["address"] ?? "");
$city = trim($data["delivery"]["city"] ?? "");
$postal = trim($data["delivery"]["postal"] ?? "");

$payment = $data["payment"] ?? "";

$items = $data["items"] ?? [];

/* ================================
   VALIDATION
================================ */

if (
    $name === "" ||
    $email === "" ||
    $phone === "" ||
    $address === "" ||
    $city === "" ||
    $postal === "" ||
    $payment === "" ||
    empty($items)
) {
    echo json_encode([
        "success" => false,
        "message" => "Missing order information."
    ]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid email address."
    ]);
    exit;
}

/* ================================
   FIND USER
================================ */

$userId = null;

$userStmt = $conn->prepare(
    "SELECT id FROM users WHERE email = ? LIMIT 1"
);

$userStmt->bind_param("s", $email);
$userStmt->execute();

$userResult = $userStmt->get_result();

if ($userResult->num_rows > 0) {
    $user = $userResult->fetch_assoc();
    $userId = $user["id"];
}

$userStmt->close();

/* ================================
   CALCULATE TOTALS
================================ */

$subtotal = 0;

foreach ($items as $item) {

    $price = (float)($item["price"] ?? 0);
    $quantity = (int)($item["quantity"] ?? 0);

    if ($price < 0 || $quantity <= 0) {
        echo json_encode([
            "success" => false,
            "message" => "Invalid product information."
        ]);
        exit;
    }

    $subtotal += $price * $quantity;
}

$shipping = 120;

$total = $subtotal + $shipping;

/* ================================
   CREATE ORDER NUMBER
================================ */

$orderNumber = "ORD-" . date("YmdHis") . "-" . rand(100, 999);

/* ================================
   INSERT ORDER
================================ */

$stmt = $conn->prepare(
    "INSERT INTO orders
    (
        user_id,
        order_number,
        customer_name,
        customer_email,
        customer_phone,
        address,
        city,
        postal_code,
        payment_method,
        subtotal,
        shipping,
        total,
        status
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')"
);

$stmt->bind_param(
    "issssssssddd",
    $userId,
    $orderNumber,
    $name,
    $email,
    $phone,
    $address,
    $city,
    $postal,
    $payment,
    $subtotal,
    $shipping,
    $total
);

if (!$stmt->execute()) {

    echo json_encode([
        "success" => false,
        "message" => "Could not create order."
    ]);

    $stmt->close();
    $conn->close();
    exit;
}

/* Get newly created order ID */

$orderId = $conn->insert_id;

$stmt->close();

/* ================================
   INSERT ORDER ITEMS
================================ */

$itemStmt = $conn->prepare(
    "INSERT INTO order_items
    (
        order_id,
        product_id,
        product_name,
        price,
        quantity,
        color,
        storage,
        image
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
);

foreach ($items as $item) {

    $productId = (int)($item["id"] ?? 0);
    $productName = $item["name"] ?? "";
    $price = (float)($item["price"] ?? 0);
    $quantity = (int)($item["quantity"] ?? 0);
    $color = $item["color"] ?? null;
    $storage = $item["storage"] ?? null;
    $image = $item["image"] ?? null;

    $itemStmt->bind_param(
        "iisdisss",
        $orderId,
        $productId,
        $productName,
        $price,
        $quantity,
        $color,
        $storage,
        $image
    );

    $itemStmt->execute();
}

$itemStmt->close();
$conn->close();

/* ================================
   SUCCESS
================================ */

echo json_encode([
    "success" => true,
    "message" => "Order placed successfully.",
    "orderId" => $orderId,
    "orderNumber" => $orderNumber,
    "subtotal" => $subtotal,
    "shipping" => $shipping,
    "total" => $total
]);

?>