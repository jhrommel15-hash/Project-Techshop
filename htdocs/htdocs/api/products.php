<?php
require_once __DIR__ . "/db.php";

header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: no-store");

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Only GET requests are allowed."
    ]);

    exit;
}

$sql = "SELECT
            id,
            name,
            brand,
            category,
            price,
            image,
            images,
            colors,
            storage_options,
            stock,
            rating,
            reviews,
            description
        FROM products
        ORDER BY id ASC";

$result = $conn->query($sql);

if (!$result) {
    error_log("Products query failed: " . $conn->error);

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to load products."
    ]);

    exit;
}

$products = [];

while ($row = $result->fetch_assoc()) {
    $row["id"] = (int) $row["id"];
    $row["price"] = (float) $row["price"];
    $row["stock"] = (int) $row["stock"];
    $row["rating"] = (float) $row["rating"];
    $row["reviews"] = (int) $row["reviews"];

    $row["images"] = json_decode(
        $row["images"] ?? "[]",
        true
    );

    $row["colors"] = json_decode(
        $row["colors"] ?? "[]",
        true
    );

    $row["storage"] = json_decode(
        $row["storage_options"] ?? "[]",
        true
    );

    if (!is_array($row["images"])) {
        $row["images"] = [];
    }

    if (!is_array($row["colors"])) {
        $row["colors"] = [];
    }

    if (!is_array($row["storage"])) {
        $row["storage"] = [];
    }

    unset($row["storage_options"]);

    $products[] = $row;
}

echo json_encode(
    [
        "success" => true,
        "products" => $products
    ],
    JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
);

$conn->close();
?>
