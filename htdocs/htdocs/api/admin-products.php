
<?php
// C:\xampp\htdocs\TechStore\api\admin-products.php

header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: no-store, no-cache, must-revalidate");

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function respond($status, $data) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

// Only authenticated administrators may use this API.
if (
    empty($_SESSION["admin_id"]) ||
    ($_SESSION["admin_role"] ?? "") !== "admin"
) {
    respond(401, ["success" => false, "message" => "Admin login required."]);
}

// Match the admin session's 30-minute idle timeout.
$now = time();

if (
    isset($_SESSION["admin_last_activity"]) &&
    ($now - (int)$_SESSION["admin_last_activity"]) > 1800
) {
    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();

        setcookie(session_name(), "", [
            "expires" => time() - 42000,
            "path" => $params["path"],
            "domain" => $params["domain"],
            "secure" => $params["secure"],
            "httponly" => $params["httponly"],
            "samesite" => $params["samesite"] ?? "Lax"
        ]);
    }

    session_destroy();
    respond(401, ["success" => false, "message" => "Session expired. Please log in again."]);
}

$_SESSION["admin_last_activity"] = $now;

require_once __DIR__ . "/db.php";

if (!isset($conn) || $conn->connect_error) {
    respond(500, ["success" => false, "message" => "Database connection failed."]);
}

$conn->set_charset("utf8mb4");

function cleanText($value, $maxLength = 500) {
    if (!is_string($value) && !is_numeric($value)) {
        return "";
    }

    return trim(mb_substr((string)$value, 0, $maxLength, "UTF-8"));
}

function jsonArrayField($value, $fieldName) {
    if (!is_array($value)) {
        throw new InvalidArgumentException($fieldName . " must be a list.");
    }

    $items = [];

    foreach ($value as $item) {
        if (!is_string($item) && !is_numeric($item)) {
            throw new InvalidArgumentException("Invalid item in " . $fieldName . ".");
        }

        $item = trim((string)$item);

        if ($item !== "") {
            $items[] = mb_substr($item, 0, 500, "UTF-8");
        }
    }

    return json_encode(array_values($items), JSON_UNESCAPED_UNICODE);
}

function productOutput($row) {
    $row["id"] = (int)$row["id"];
    $row["price"] = (float)$row["price"];
    $row["stock"] = (int)$row["stock"];
    $row["rating"] = (float)$row["rating"];
    $row["reviews"] = (int)$row["reviews"];

    $row["images"] = json_decode($row["images"] ?: "[]", true) ?: [];
    $row["colors"] = json_decode($row["colors"] ?: "[]", true) ?: [];
    $row["storage"] = json_decode($row["storage_options"] ?: "[]", true) ?: [];

    unset($row["storage_options"]);

    return $row;
}

$method = $_SERVER["REQUEST_METHOD"] ?? "GET";

if ($method === "GET") {
    $result = $conn->query("SELECT * FROM products ORDER BY id ASC");

    if (!$result) {
        respond(500, ["success" => false, "message" => "Could not load products."]);
    }

    $products = [];

    while ($row = $result->fetch_assoc()) {
        $products[] = productOutput($row);
    }

    respond(200, ["success" => true, "products" => $products]);
}

if ($method !== "POST") {
    header("Allow: GET, POST");
    respond(405, ["success" => false, "message" => "Method not allowed."]);
}

$csrf = $_SERVER["HTTP_X_CSRF_TOKEN"] ?? "";

if (
    empty($_SESSION["admin_csrf_token"]) ||
    !is_string($csrf) ||
    !hash_equals($_SESSION["admin_csrf_token"], $csrf)
) {
    respond(403, ["success" => false, "message" => "Security token invalid. Refresh the page and try again."]);
}

$input = json_decode(file_get_contents("php://input"), true);

if (!is_array($input)) {
    respond(400, ["success" => false, "message" => "Invalid request data."]);
}

$action = $input["action"] ?? "";

try {
    if ($action === "delete") {
        $id = filter_var($input["id"] ?? null, FILTER_VALIDATE_INT);

        if (!$id || $id < 1) {
            respond(400, ["success" => false, "message" => "Invalid product ID."]);
        }

        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        if (!$stmt) {
            respond(500, ["success" => false, "message" => "Could not prepare delete."]);
        }

        $stmt->bind_param("i", $id);
        $stmt->execute();
        $deleted = $stmt->affected_rows;
        $stmt->close();

        if (!$deleted) {
            respond(404, ["success" => false, "message" => "Product not found."]);
        }

        respond(200, ["success" => true, "message" => "Product deleted."]);
    }

    if ($action !== "add" && $action !== "update") {
        respond(400, ["success" => false, "message" => "Unknown action."]);
    }

    $name = cleanText($input["name"] ?? "", 255);
    $brand = cleanText($input["brand"] ?? "", 100);
    $category = cleanText($input["category"] ?? "", 100);
    $description = cleanText($input["description"] ?? "", 5000);
    $image = cleanText($input["image"] ?? "", 500);

    $priceValue = $input["price"] ?? null;
    $stockValue = $input["stock"] ?? null;
    $ratingValue = $input["rating"] ?? 0;
    $reviewsValue = $input["reviews"] ?? 0;

    if ($name === "" || $brand === "" || $category === "" || $description === "" || $image === "") {
        respond(400, ["success" => false, "message" => "Fill in all required fields."]);
    }

    if (!is_numeric($priceValue) || (float)$priceValue < 0) {
        respond(400, ["success" => false, "message" => "Enter a valid price."]);
    }

    if (
        filter_var($stockValue, FILTER_VALIDATE_INT) === false ||
        (int)$stockValue < 0
    ) {
        respond(400, ["success" => false, "message" => "Stock must be a non-negative whole number."]);
    }

    if (!is_numeric($ratingValue) || (float)$ratingValue < 0 || (float)$ratingValue > 5) {
        respond(400, ["success" => false, "message" => "Rating must be between 0 and 5."]);
    }

    if (
        filter_var($reviewsValue, FILTER_VALIDATE_INT) === false ||
        (int)$reviewsValue < 0
    ) {
        respond(400, ["success" => false, "message" => "Reviews must be a non-negative whole number."]);
    }

    $price = (float)$priceValue;
    $stock = (int)$stockValue;
    $rating = (float)$ratingValue;
    $reviews = (int)$reviewsValue;

    $images = jsonArrayField($input["images"] ?? [], "Images");
    $colors = jsonArrayField($input["colors"] ?? [], "Colors");
    $storage = jsonArrayField($input["storage"] ?? [], "Storage");

    if ($action === "add") {
        $stmt = $conn->prepare(
            "INSERT INTO products
            (name, brand, category, price, image, images, colors,
             storage_options, stock, rating, reviews, description)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        if (!$stmt) {
            respond(500, ["success" => false, "message" => "Could not prepare product insert."]);
        }

        $stmt->bind_param(
            "sssdssssidis",
            $name,
            $brand,
            $category,
            $price,
            $image,
            $images,
            $colors,
            $storage,
            $stock,
            $rating,
            $reviews,
            $description
        );

        if (!$stmt->execute()) {
            $stmt->close();
            respond(500, ["success" => false, "message" => "Could not save product."]);
        }

        $id = $stmt->insert_id;
        $stmt->close();

        respond(200, ["success" => true, "message" => "Product added.", "id" => $id]);
    }

    $id = filter_var($input["id"] ?? null, FILTER_VALIDATE_INT);

    if (!$id || $id < 1) {
        respond(400, ["success" => false, "message" => "Invalid product ID."]);
    }

    $stmt = $conn->prepare(
        "UPDATE products SET
            name = ?, brand = ?, category = ?, price = ?, image = ?,
            images = ?, colors = ?, storage_options = ?, stock = ?,
            rating = ?, reviews = ?, description = ?
         WHERE id = ?"
    );

    if (!$stmt) {
        respond(500, ["success" => false, "message" => "Could not prepare product update."]);
    }

    $stmt->bind_param(
        "sssdssssidisi",
        $name,
        $brand,
        $category,
        $price,
        $image,
        $images,
        $colors,
        $storage,
        $stock,
        $rating,
        $reviews,
        $description,
        $id
    );

    if (!$stmt->execute()) {
        $stmt->close();
        respond(500, ["success" => false, "message" => "Could not update product."]);
    }

    $changed = $stmt->affected_rows;
    $stmt->close();

    // Zero affected rows can mean the product exists but its values did not change.
    $check = $conn->prepare("SELECT id FROM products WHERE id = ?");
    $check->bind_param("i", $id);
    $check->execute();
    $check->store_result();
    $exists = $check->num_rows > 0;
    $check->close();

    if (!$exists) {
        respond(404, ["success" => false, "message" => "Product not found."]);
    }

    respond(200, ["success" => true, "message" => "Product updated."]);
} catch (InvalidArgumentException $e) {
    respond(400, ["success" => false, "message" => $e->getMessage()]);
} catch (Throwable $e) {
    respond(500, ["success" => false, "message" => "An unexpected server error occurred."]);
}
