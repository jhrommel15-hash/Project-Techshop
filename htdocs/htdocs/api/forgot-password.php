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

$email = trim($_POST["email"] ?? "");

if ($email === "") {
    echo json_encode([
        "success" => false,
        "message" => "Please enter your email."
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

/* Check if email exists */

$stmt = $conn->prepare(
    "SELECT id, name, email FROM users WHERE email = ?"
);

$stmt->bind_param("s", $email);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        "success" => false,
        "message" => "No account was found with that email."
    ]);

    $stmt->close();
    $conn->close();
    exit;
}

$user = $result->fetch_assoc();

/* Email exists */

echo json_encode([
    "success" => true,
    "message" => "Account found.",
    "user" => $user
]);

$stmt->close();
$conn->close();

?>