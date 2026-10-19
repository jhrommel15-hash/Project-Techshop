
<?php
// ========================================
// TECHSTORE ADMIN ORDER MANAGEMENT
// ========================================

require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/../api/db.php";

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

$allowedStatuses = [
    "Pending",
    "Processing",
    "Shipped",
    "Completed",
    "Cancelled"
];

$message = "";
$error = "";
$orders = [];
$orderItems = [];

// Create CSRF token
if (
    !isset($_SESSION["admin_csrf"]) ||
    !is_string($_SESSION["admin_csrf"]) ||
    strlen($_SESSION["admin_csrf"]) !== 64
) {
    $_SESSION["admin_csrf"] = bin2hex(random_bytes(32));
}

// ========================================
// UPDATE ORDER STATUS
// ========================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $orderId = filter_input(INPUT_POST, "order_id", FILTER_VALIDATE_INT);
    $newStatus = trim($_POST["status"] ?? "");
    $csrfToken = $_POST["csrf_token"] ?? "";

    if (
        !is_string($csrfToken) ||
        !hash_equals($_SESSION["admin_csrf"], $csrfToken)
    ) {
        $error = "Security check failed. Please refresh and try again.";
    } elseif (
        !$orderId ||
        $orderId < 1 ||
        !in_array($newStatus, $allowedStatuses, true)
    ) {
        $error = "Invalid order or status.";
    } else {
        $stmt = $conn->prepare(
            "UPDATE orders SET status = ? WHERE id = ?"
        );

        if (!$stmt) {
            $error = "Could not prepare the status update.";
        } else {
            $stmt->bind_param("si", $newStatus, $orderId);

            if ($stmt->execute()) {
                $stmt->close();

                $_SESSION["admin_csrf"] = bin2hex(random_bytes(32));

                header("Location: orders.php?updated=1");
                exit;
            } else {
                $error = "Could not update the order status.";
                $stmt->close();
            }
        }
    }
}

if (isset($_GET["updated"]) && $_GET["updated"] === "1") {
    $message = "Order status updated successfully.";
}

// ========================================
// SEARCH AND FILTER ORDERS
// ========================================

$search = trim((string)($_GET["search"] ?? ""));
$statusFilter = trim((string)($_GET["status"] ?? ""));

if (strlen($search) > 200) {
    $search = substr($search, 0, 200);
}

if (
    !in_array($statusFilter, $allowedStatuses, true) &&
    $statusFilter !== ""
) {
    $error = "Invalid status filter.";
    $statusFilter = "";
}

$sql = "SELECT
            id,
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
        FROM orders
        WHERE 1 = 1";

$types = "";
$params = [];

if ($search !== "") {
    $sql .= " AND (
                order_number LIKE ?
                OR customer_name LIKE ?
                OR customer_email LIKE ?
              )";

    $term = "%" . $search . "%";

    $types .= "sss";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

if ($statusFilter !== "") {
    $sql .= " AND status = ?";

    $types .= "s";
    $params[] = $statusFilter;
}

$sql .= " ORDER BY id DESC";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    $error = "Could not prepare the orders query.";
} else {
    if ($types !== "") {
        $bindParams = [$types];

        foreach ($params as $key => $value) {
            $bindParams[] = &$params[$key];
        }

        $bound = call_user_func_array(
            [$stmt, "bind_param"],
            $bindParams
        );

        if (!$bound) {
            $error = "Could not bind search parameters.";
        }
    }

    if ($error === "" && $stmt->execute()) {
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
    } elseif ($error === "") {
        $error = "Could not load orders.";
    }

    $stmt->close();
}

// ========================================
// LOAD ITEMS FOR THE DISPLAYED ORDERS
// ========================================

if (count($orders) > 0) {
    $orderIds = array_map(
        "intval",
        array_column($orders, "id")
    );

    $placeholders = implode(
        ",",
        array_fill(0, count($orderIds), "?")
    );

    $itemsSql = "SELECT
                    order_id,
                    product_name,
                    price,
                    quantity,
                    color,
                    storage
                 FROM order_items
                 WHERE order_id IN ($placeholders)
                 ORDER BY order_id DESC, id ASC";

    $itemsStmt = $conn->prepare($itemsSql);

    if (!$itemsStmt) {
        $error = "Orders loaded, but product details could not be prepared.";
    } else {
        $itemTypes = str_repeat("i", count($orderIds));
        $itemParams = [$itemTypes];

        foreach ($orderIds as $key => $id) {
            $itemParams[] = &$orderIds[$key];
        }

        $bound = call_user_func_array(
            [$itemsStmt, "bind_param"],
            $itemParams
        );

        if ($bound && $itemsStmt->execute()) {
            $itemsResult = $itemsStmt->get_result();

            while ($item = $itemsResult->fetch_assoc()) {
                $orderId = (int)$item["order_id"];

                if (!isset($orderItems[$orderId])) {
                    $orderItems[$orderId] = [];
                }

                $orderItems[$orderId][] = $item;
            }
        } else {
            $error = "Could not load the ordered products.";
        }

        $itemsStmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders | TechStore</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            color: #172033;
            background: #f1f5f9;
        }

        .layout {
            display: flex;
            min-height: 100vh;
        }

        aside {
            width: 230px;
            flex-shrink: 0;
            padding: 26px 16px;
            background: #0f172a;
            color: white;
        }

        .logo {
            margin: 0 0 32px;
            font-size: 24px;
            font-weight: 800;
        }

        .logo span {
            color: #60a5fa;
        }

        aside a {
            display: block;
            margin: 8px 0;
            padding: 12px;
            border-radius: 8px;
            color: #cbd5e1;
            text-decoration: none;
        }

        aside a:hover,
        aside a.active {
            background: #1e293b;
            color: white;
        }

        main {
            flex: 1;
            min-width: 0;
            padding: 30px;
        }

        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 14px;
            margin-bottom: 24px;
        }

        h1 {
            margin: 0 0 6px;
        }

        .muted {
            color: #64748b;
        }

        .panel {
            padding: 22px;
            border-radius: 12px;
            background: white;
            box-shadow: 0 3px 12px rgba(15, 23, 42, .04);
        }

        .filters {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }

        input,
        select,
        button {
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            font: inherit;
        }

        input {
            flex: 1;
            min-width: 190px;
        }

        button {
            border: none;
            background: #2563eb;
            color: white;
            cursor: pointer;
        }

        button:hover {
            background: #1d4ed8;
        }

        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 13px 12px;
            border-bottom: 1px solid #e2e8f0;
            text-align: left;
            vertical-align: top;
        }

        th {
            color: #64748b;
            font-size: 13px;
            white-space: nowrap;
        }

        .status-form {
            display: flex;
            align-items: center;
            gap: 7px;
        }

        .status-form select {
            max-width: 145px;
        }

        .notice {
            padding: 12px 15px;
            margin-bottom: 18px;
            border-radius: 8px;
        }

        .success {
            color: #166534;
            background: #dcfce7;
        }

        .error {
            color: #991b1b;
            background: #fee2e2;
        }

        .items-cell {
            min-width: 250px;
            max-width: 360px;
        }

        .item {
            padding: 10px 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .item:first-child {
            padding-top: 0;
        }

        .item:last-child {
            padding-bottom: 0;
            border-bottom: none;
        }

        .item-name {
            margin-bottom: 5px;
            font-weight: 700;
            color: #0f172a;
        }

        .item-meta {
            color: #64748b;
            font-size: 13px;
            line-height: 1.6;
        }

        .item-total {
            margin-top: 4px;
            font-weight: 700;
            color: #1d4ed8;
        }

        .no-items {
            color: #94a3b8;
            font-size: 13px;
        }

        .details {
            margin-top: 22px;
            color: #64748b;
            font-size: 14px;
        }

        @media (max-width: 800px) {
            .layout {
                display: block;
            }

            aside {
                width: 100%;
            }

            aside a {
                display: inline-block;
            }

            main {
                padding: 18px;
            }
        }
    </style>
</head>
<body>
<div class="layout">
    <aside>
        <div class="logo">Tech<span>Store</span></div>
        <a href="index.php">Dashboard</a>
        <a class="active" href="orders.php">Orders</a>
        <a href="products.php">Products</a>
        <a href="../index.html">View Store</a>
        <a href="logout.php">Log Out</a>
    </aside>

    <main>
        <header class="header">
            <div>
                <h1>Order Management</h1>
                <div class="muted">
                    View customer orders, products purchased, and order status.
                </div>
            </div>
        </header>

        <?php if ($message !== ""): ?>
            <div class="notice success"><?= h($message) ?></div>
        <?php endif; ?>

        <?php if ($error !== ""): ?>
            <div class="notice error"><?= h($error) ?></div>
        <?php endif; ?>

        <section class="panel">
            <form class="filters" method="GET" action="orders.php">
                <input
                    type="search"
                    name="search"
                    placeholder="Search order number, customer, or email"
                    value="<?= h($search) ?>"
                    maxlength="200"
                >

                <select name="status">
                    <option value="">All statuses</option>

                    <?php foreach ($allowedStatuses as $status): ?>
                        <option
                            value="<?= h($status) ?>"
                            <?= $statusFilter === $status ? "selected" : "" ?>
                        >
                            <?= h($status) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit">Search</button>
            </form>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Customer</th>
                            <th>Items Ordered</th>
                            <th>Total</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th>Update</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php if (count($orders) === 0): ?>
                        <tr>
                            <td colspan="7">No orders found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                            <?php $orderId = (int)$order["id"]; ?>

                            <tr>
                                <td>
                                    <strong>
                                        <?= h($order["order_number"]) ?>
                                    </strong>
                                    <br>
                                    <small>#<?= $orderId ?></small>
                                </td>

                                <td>
                                    <strong>
                                        <?= h($order["customer_name"]) ?>
                                    </strong>
                                    <br>
                                    <small>
                                        <?= h($order["customer_email"]) ?>
                                    </small>
                                    <br>
                                    <small>
                                        <?= h($order["customer_phone"]) ?>
                                    </small>
                                    <br><br>
                                    <small>
                                        <?= h($order["address"]) ?>
                                        <?php if (!empty($order["city"])): ?>
                                            , <?= h($order["city"]) ?>
                                        <?php endif; ?>
                                        <?php if (!empty($order["postal_code"])): ?>
                                            <?= h($order["postal_code"]) ?>
                                        <?php endif; ?>
                                    </small>
                                </td>

                                <td class="items-cell">
                                    <?php if (!empty($orderItems[$orderId])): ?>
                                        <?php foreach ($orderItems[$orderId] as $item): ?>
                                            <div class="item">
                                                <div class="item-name">
                                                    <?= h($item["product_name"]) ?>
                                                </div>

                                                <div class="item-meta">
                                                    Quantity:
                                                    <?= (int)$item["quantity"] ?>
                                                    <br>

                                                    Unit price:
                                                    ₱<?= number_format(
                                                        (float)$item["price"],
                                                        2
                                                    ) ?>

                                                    <?php if (!empty($item["color"])): ?>
                                                        <br>
                                                        Color:
                                                        <?= h($item["color"]) ?>
                                                    <?php endif; ?>

                                                    <?php if (!empty($item["storage"])): ?>
                                                        <br>
                                                        Storage:
                                                        <?= h($item["storage"]) ?>
                                                    <?php endif; ?>
                                                </div>

                                                <div class="item-total">
                                                    Item total: ₱<?= number_format(
                                                        (float)$item["price"] *
                                                        (int)$item["quantity"],
                                                        2
                                                    ) ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span class="no-items">
                                            No product details saved for this order.
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <strong>
                                        ₱<?= number_format(
                                            (float)$order["total"],
                                            2
                                        ) ?>
                                    </strong>
                                    <br>
                                    <small class="muted">
                                        Subtotal: ₱<?= number_format(
                                            (float)$order["subtotal"],
                                            2
                                        ) ?>
                                    </small>
                                    <br>
                                    <small class="muted">
                                        Shipping: ₱<?= number_format(
                                            (float)$order["shipping"],
                                            2
                                        ) ?>
                                    </small>
                                </td>

                                <td>
                                    <?= h($order["payment_method"]) ?>
                                </td>

                                <td>
                                    <?= h($order["status"]) ?>
                                </td>

                                <td>
                                    <form
                                        class="status-form"
                                        method="POST"
                                        action="orders.php"
                                    >
                                        <input
                                            type="hidden"
                                            name="order_id"
                                            value="<?= $orderId ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= h($_SESSION["admin_csrf"]) ?>"
                                        >

                                        <select name="status" required>
                                            <?php foreach ($allowedStatuses as $status): ?>
                                                <option
                                                    value="<?= h($status) ?>"
                                                    <?= $order["status"] === $status
                                                        ? "selected" : "" ?>
                                                >
                                                    <?= h($status) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>

                                        <button type="submit">Save</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <p class="details">
                Showing <?= count($orders) ?> order(s).
                Product details are retrieved from your order_items table.
            </p>
        </section>
    </main>
</div>
</body>
</html>
