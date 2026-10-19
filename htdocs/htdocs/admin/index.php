
<?php
require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/../api/db.php";

$stats = [
    "orders" => 0,
    "sales" => 0,
    "pending" => 0
];

$recentOrders = [];
$error = "";

$statsResult = $conn->query(
    "SELECT
        COUNT(*) AS total_orders,
        COALESCE(SUM(total), 0) AS total_sales,
        COALESCE(SUM(CASE WHEN status = 'Pending'
            THEN 1 ELSE 0 END), 0) AS pending_orders
     FROM orders"
);

if ($statsResult) {
    $row = $statsResult->fetch_assoc();
    $stats["orders"] = (int)$row["total_orders"];
    $stats["sales"] = (float)$row["total_sales"];
    $stats["pending"] = (int)$row["pending_orders"];
} else {
    $error = "Could not load order statistics. Check your orders table.";
}

$ordersResult = $conn->query(
    "SELECT id, order_number, customer_name, customer_email,
            total, status
     FROM orders
     ORDER BY id DESC
     LIMIT 10"
);

if ($ordersResult) {
    while ($row = $ordersResult->fetch_assoc()) {
        $recentOrders[] = $row;
    }
} else {
    $error = "Could not load recent orders. Check your orders table.";
}

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | TechStore</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            color: #172033;
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

        aside a.active,
        aside a:hover {
            background: #1e293b;
            color: white;
        }

        main {
            flex: 1;
            min-width: 0;
            padding: 30px;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 28px;
        }

        h1 {
            margin: 0 0 6px;
            font-size: 28px;
        }

        .muted {
            color: #64748b;
        }

        .logout {
            padding: 10px 15px;
            border-radius: 8px;
            background: white;
            border: 1px solid #cbd5e1;
            color: #172033;
            text-decoration: none;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 28px;
        }

        .card,
        .table-card {
            padding: 22px;
            border-radius: 12px;
            background: white;
            box-shadow: 0 3px 12px rgba(15, 23, 42, .04);
        }

        .stat-label {
            color: #64748b;
            font-size: 14px;
        }

        .stat-number {
            margin-top: 12px;
            font-size: 29px;
            font-weight: 800;
        }

        .table-card {
            overflow: hidden;
        }

        .table-heading {
            margin: 0 0 18px;
            font-size: 20px;
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
            white-space: nowrap;
        }

        th {
            color: #64748b;
            font-size: 13px;
        }

        .status {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 20px;
            background: #fef3c7;
            color: #92400e;
            font-size: 12px;
        }

        .notice {
            padding: 12px;
            margin-bottom: 20px;
            background: #fff7ed;
            color: #9a3412;
            border-radius: 8px;
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

            .stats {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
<div class="layout">

    <!-- ADMIN SIDEBAR -->
    <aside>
        <div class="logo">Tech<span>Store</span></div>

        <a class="active" href="index.php">Dashboard</a>
        <a href="orders.php">Orders</a>
        <a href="products.php">Products</a>
        <a href="../index.html">View Store</a>
        <a href="logout.php">Log Out</a>
    </aside>

    <!-- MAIN DASHBOARD -->
    <main>

        <header class="topbar">
            <div>
                <h1>Dashboard</h1>

                <div class="muted">
                    Welcome,
                    <?= h($_SESSION["admin_name"] ?? "Administrator") ?>
                </div>
            </div>

            <a class="logout" href="logout.php">Log Out</a>
        </header>

        <?php if ($error !== ""): ?>
            <div class="notice">
                <?= h($error) ?>
            </div>
        <?php endif; ?>

        <!-- DASHBOARD STATISTICS -->
        <section class="stats">

            <div class="card">
                <div class="stat-label">Total Orders</div>

                <div class="stat-number">
                    <?= number_format($stats["orders"]) ?>
                </div>
            </div>

            <div class="card">
                <div class="stat-label">Total Sales</div>

                <div class="stat-number">
                    ₱<?= number_format($stats["sales"], 2) ?>
                </div>
            </div>

            <div class="card">
                <div class="stat-label">Pending Orders</div>

                <div class="stat-number">
                    <?= number_format($stats["pending"]) ?>
                </div>
            </div>

        </section>

        <!-- RECENT ORDERS -->
        <section class="table-card" id="orders">

            <h2 class="table-heading">Recent Orders</h2>

            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>Order Number</th>
                        <th>Customer</th>
                        <th>Email</th>
                        <th>Total</th>
                        <th>Status</th>
                    </tr>
                    </thead>

                    <tbody>
                    <?php if (count($recentOrders) === 0): ?>

                        <tr>
                            <td colspan="5">No orders found.</td>
                        </tr>

                    <?php else: ?>

                        <?php foreach ($recentOrders as $order): ?>
                            <tr>
                                <td>
                                    <?= h($order["order_number"]) ?>
                                </td>

                                <td>
                                    <?= h($order["customer_name"]) ?>
                                </td>

                                <td>
                                    <?= h($order["customer_email"]) ?>
                                </td>

                                <td>
                                    ₱<?= number_format((float)$order["total"], 2) ?>
                                </td>

                                <td>
                                    <span class="status">
                                        <?= h($order["status"]) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </section>

    </main>
</div>
</body>
</html>
