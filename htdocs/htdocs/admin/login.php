
<?php
// ========================================
// TECHSTORE ADMIN LOGIN
// Secure session, CSRF protection, and login
// ========================================

$isHttps = (
    isset($_SERVER["HTTPS"]) &&
    $_SERVER["HTTPS"] !== "off"
);

ini_set("session.use_strict_mode", "1");
ini_set("session.use_only_cookies", "1");

session_set_cookie_params([
    "lifetime" => 0,
    "path" => "/",
    "secure" => $isHttps,
    "httponly" => true,
    "samesite" => "Lax"
]);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// ========================================
// SESSION TIMEOUT
// ========================================

if (isset($_SESSION["admin_id"])) {
    $lastActivity = (int) (
        $_SESSION["admin_last_activity"] ?? 0
    );

    if (
        $lastActivity > 0 &&
        time() - $lastActivity <= 1800 &&
        ($_SESSION["admin_role"] ?? "") === "admin"
    ) {
        $_SESSION["admin_last_activity"] = time();

        header("Location: index.php");
        exit;
    }

    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        $cookie = session_get_cookie_params();

        setcookie(session_name(), "", [
            "expires" => time() - 42000,
            "path" => $cookie["path"],
            "domain" => $cookie["domain"],
            "secure" => $cookie["secure"],
            "httponly" => $cookie["httponly"],
            "samesite" => $cookie["samesite"] ?? "Lax"
        ]);
    }

    session_destroy();
    session_start();
}

// ========================================
// CSRF TOKEN
// ========================================

if (empty($_SESSION["admin_login_csrf"])) {
    $_SESSION["admin_login_csrf"] = bin2hex(
        random_bytes(32)
    );
}

// ========================================
// DATABASE CONNECTION
// ========================================

require_once __DIR__ . "/../api/db.php";

$error = "";

// ========================================
// PROCESS LOGIN
// ========================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $csrfToken = $_POST["csrf_token"] ?? "";

    if (
        !is_string($csrfToken) ||
        !hash_equals(
            $_SESSION["admin_login_csrf"],
            $csrfToken
        )
    ) {
        $error = "Your session expired. Please refresh and try again.";

    } elseif ($email === "" || $password === "") {
        $error = "Please enter your email and password.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";

    } else {
        $stmt = $conn->prepare(
            "SELECT id, name, email, password, role
             FROM users
             WHERE email = ?
             LIMIT 1"
        );

        if ($stmt) {
            $stmt->bind_param("s", $email);

            if ($stmt->execute()) {
                $stmt->bind_result(
                    $userId,
                    $userName,
                    $userEmail,
                    $userPassword,
                    $userRole
                );

                $user = null;

                if ($stmt->fetch()) {
                    $user = [
                        "id" => $userId,
                        "name" => $userName,
                        "email" => $userEmail,
                        "password" => $userPassword,
                        "role" => $userRole
                    ];
                }

                $stmt->close();

                if (
                    $user !== null &&
                    $user["role"] === "admin" &&
                    password_verify(
                        $password,
                        $user["password"]
                    )
                ) {
                    // Prevent session fixation.
                    session_regenerate_id(true);

                    unset($_SESSION["admin_login_csrf"]);

                    $_SESSION["admin_id"] = (int) $user["id"];
                    $_SESSION["admin_name"] = $user["name"];
                    $_SESSION["admin_email"] = $user["email"];
                    $_SESSION["admin_role"] = "admin";
                    $_SESSION["admin_last_activity"] = time();

                    header("Location: index.php");
                    exit;
                }
            } else {
                error_log(
                    "TechStore admin login query execution failed."
                );

                $stmt->close();
            }
        } else {
            error_log(
                "TechStore admin login query preparation failed."
            );
        }

        $error = "Invalid admin credentials or insufficient permissions.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Admin Login | TechStore</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 20px;
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            color: #172033;
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            padding: 35px;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 12px 35px rgba(15, 23, 42, 0.1);
        }

        .brand {
            margin-bottom: 8px;
            color: #2563eb;
            font-size: 28px;
            font-weight: 800;
        }

        h1 {
            margin: 0 0 8px;
            font-size: 24px;
        }

        .subtitle {
            margin-bottom: 26px;
            color: #64748b;
        }

        label {
            display: block;
            margin: 16px 0 7px;
            font-weight: 600;
        }

        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 16px;
        }

        input:focus {
            outline: 2px solid #93c5fd;
            border-color: #2563eb;
        }

        button {
            width: 100%;
            margin-top: 22px;
            padding: 13px;
            border: 0;
            border-radius: 8px;
            background: #2563eb;
            color: white;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
        }

        button:hover {
            background: #1d4ed8;
        }

        .error {
            padding: 12px;
            color: #b91c1c;
            background: #fef2f2;
            border-radius: 8px;
        }

        .back {
            display: block;
            margin-top: 22px;
            text-align: center;
            color: #475569;
            text-decoration: none;
        }

        .back:hover {
            color: #2563eb;
        }
    </style>
</head>

<body>
    <main class="login-card">
        <div class="brand">TechStore</div>

        <h1>Admin Login</h1>

        <p class="subtitle">
            Sign in to manage your store.
        </p>

        <?php if ($error !== ""): ?>
            <p class="error" role="alert">
                <?= htmlspecialchars(
                    $error,
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>
            </p>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars(
                    $_SESSION["admin_login_csrf"],
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>"
            >

            <label for="admin-email">Admin Email</label>

            <input
                type="email"
                id="admin-email"
                name="email"
                autocomplete="username"
                maxlength="254"
                required
            >

            <label for="admin-password">Password</label>

            <input
                type="password"
                id="admin-password"
                name="password"
                autocomplete="current-password"
                required
            >

            <button type="submit">Sign In</button>
        </form>

        <a class="back" href="../index.html">
            &larr; Back to TechStore
        </a>
    </main>
</body>
</html>
