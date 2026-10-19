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
$newPassword = $_POST["new_password"] ?? "";

if ($email === "" || $newPassword === "") {
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

if (strlen($newPassword) < 6) {
    echo json_encode([
        "success" => false,
        "message" => "Password must be at least 6 characters."
    ]);
    exit;
}

/* Check if account exists */

$check = $conn->prepare(
    "SELECT id FROM users WHERE email = ?"
);

$check->bind_param("s", $email);
$check->execute();

$result = $check->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        "success" => false,
        "message" => "No account was found with that email."
    ]);

    $check->close();
    $conn->close();
    exit;
}

$check->close();

/* Hash the new password */

$hashedPassword = password_hash(
    $newPassword,
    PASSWORD_DEFAULT
);

/* Update password */

$stmt = $conn->prepare(
    "UPDATE users SET password = ? WHERE email = ?"
);

$stmt->bind_param(
    "ss",
    $hashedPassword,
    $email
);

if ($stmt->execute()) {

    echo json_encode([
        "success" => true,
        "message" => "Password reset successfully."
    ]);

} else {

    echo json_encode([
        "success" => false,
        "message" => "Could not reset password."
    ]);
}

$stmt->close();
$conn->close();

?>