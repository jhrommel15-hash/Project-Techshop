
<?php
require_once __DIR__ . "/db.php";

header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "Invalid request method."
    ]);
    exit;
}

$name = trim($_POST["name"] ?? "");
$email = trim($_POST["email"] ?? "");
$password = $_POST["password"] ?? "";

if ($name === "" || $email === "" || $password === "") {
    echo json_encode([
        "success" => false,
        "message" => "Please fill in all fields."
    ]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        "success" => false,
        "message" => "Please enter a valid email."
    ]);
    exit;
}

if (strlen($password) < 6) {
    echo json_encode([
        "success" => false,
        "message" => "Password must be at least 6 characters."
    ]);
    exit;
}

// Check whether the email already exists.
$check = $conn->prepare(
    "SELECT id FROM users WHERE email = ? LIMIT 1"
);

if (!$check) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Database error."
    ]);
    exit;
}

$check->bind_param("s", $email);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    $check->close();

    echo json_encode([
        "success" => false,
        "message" => "An account with this email already exists."
    ]);
    exit;
}

$check->close();

// Hash the password and explicitly assign the customer role.
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
$role = "customer";

$stmt = $conn->prepare(
    "INSERT INTO users (name, email, password, role)
     VALUES (?, ?, ?, ?)"
);

if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Database error."
    ]);
    exit;
}

$stmt->bind_param(
    "ssss",
    $name,
    $email,
    $hashedPassword,
    $role
);

if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "message" => "Customer account created successfully."
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Could not create account."
    ]);
}

$stmt->close();
$conn->close();
?>
