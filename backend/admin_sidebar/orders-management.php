<?php

session_start();

/*
|--------------------------------------------------------------------------
| DATABASE CONNECTION
|--------------------------------------------------------------------------
*/

$db_connection = __DIR__ . '/../fun/db_connection.php';

if (!file_exists($db_connection)) {
    die('Database connection file not found: ' . htmlspecialchars($db_connection, ENT_QUOTES, 'UTF-8'));
}

require_once $db_connection;

/*
|--------------------------------------------------------------------------
| CHECK DATABASE CONNECTION
|--------------------------------------------------------------------------
*/

if (!isset($conn) || !($conn instanceof mysqli)) {
    die('Database connection is not available.');
}

/*
|--------------------------------------------------------------------------
| CHECK LOGIN & ADMIN ROLE
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['admin', 'vendor'], true)) {
    header('Location: ../account/login.php');
    exit;
}

$role = $_SESSION['role'] ?? '';
if ($role !== 'admin') {
    http_response_code(403);
    exit('Forbidden: Admin access only.');
}

/*
|--------------------------------------------------------------------------
| HELPER FUNCTION
|--------------------------------------------------------------------------
*/

function h(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/*
|--------------------------------------------------------------------------
| PROCESS ACTIONS (UPDATE STATUS / DELETE ORDER)
|--------------------------------------------------------------------------
*/

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Action 1: Delete Order
    if (isset($_POST['delete_order_id'])) {
        $del_id = (int)$_POST['delete_order_id'];
        if ($del_id > 0) {
            $del_stmt = $conn->prepare("DELETE FROM orders WHERE id = ?");
            if ($del_stmt) {
                $del_stmt->bind_param('i', $del_id);
                if ($del_stmt->execute()) {
                    $message = "Order #{$del_id} deleted successfully.";
                    $message_type = 'success';
                } else {
                    $message = "Error deleting order: " . $conn->error;
                    $message_type = 'error';
                }
                $del_stmt->close();
            }
        }
    }

    // Action 2: Update Order Status
    elseif (isset($_POST['id']) && isset($_POST['status'])) {
        $order_id = (int)$_POST['id'];
        $status = trim($_POST['status']);
        $allowed_statuses = ['Pending', 'Processing', 'Completed', 'Cancelled'];

        if ($order_id <= 0) {
            $message = 'Invalid order ID.';
            $message_type = 'error';
        } elseif (!in_array($status, $allowed_statuses, true)) {
            $message = 'Invalid order status.';
            $message_type = 'error';
        } else {
            $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param('si', $status, $order_id);
                if ($stmt->execute()) {
                    $message = "Order #{$order_id} status updated to {$status}.";
                    $message_type = 'success';
                } else {
                    $message = 'Unable to update order status.';
                    $message_type = 'error';
                }
                $stmt->close();
            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| GET ORDERS WITH AGGREGATES
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        o.id,
        o.user_id,
        o.user_email,
        o.order_date,
        o.status,
        COALESCE(u.Name, 'Customer') AS customer_name,
        COALESCE(SUM(oi.quantity * oi.price), 0) AS total_amount,
        COALESCE(SUM(oi.quantity), 0) AS total_units,
        COUNT(oi.id) AS total_items
    FROM orders o
    LEFT JOIN users u ON (u.id = o.user_id OR u.Email = o.user_email)
    LEFT JOIN order_items oi ON oi.order_id = o.id
    GROUP BY o.id, o.user_id, o.user_email, o.order_date, o.status, customer_name
    ORDER BY o.order_date DESC, o.id DESC
";

$result = $conn->query($sql);
if (!$result) {
    die('Database Error: ' . h($conn->error));
}

$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}
$result->free();

/*
|--------------------------------------------------------------------------
| GET ORDER ITEMS FOR ALL ORDERS
|--------------------------------------------------------------------------
*/

$items_sql = "
    SELECT
        oi.id,
        oi.order_id,
        oi.product_id,
        oi.quantity,
        oi.price,
        COALESCE(p.name, 'Product Removed') AS product_name,
        COALESCE(p.category, 'General') AS category,
        p.img
    FROM order_items oi
    LEFT JOIN products p ON p.id = oi.product_id
    ORDER BY oi.id ASC
";

$items_result = $conn->query($items_sql);
$order_items = [];
if ($items_result) {
    while ($item = $items_result->fetch_assoc()) {
        // Resolve image
        $imgSrc = '/ShopMind/frontend/img/logo.png';
        if (!empty($item['product_id'])) {
            $pid = (int)$item['product_id'];
            $idx = $pid - 1;
            if (file_exists(__DIR__ . "/../../frontend/img/product/{$idx}.png")) {
                $imgSrc = "/ShopMind/frontend/img/product/{$idx}.png";
            } elseif (file_exists(__DIR__ . "/../../frontend/img/product/{$pid}.png")) {
                $imgSrc = "/ShopMind/frontend/img/product/{$pid}.png";
            } elseif (!empty($item['img']) && is_string($item['img']) && strlen($item['img']) < 255) {
                $imgSrc = "/ShopMind/frontend/" . ltrim($item['img'], '/');
            }
        }
        $item['image_src'] = $imgSrc;
        $order_items[$item['order_id']][] = $item;
    }
    $items_result->free();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Orders Management - ShopMind</title>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">

    <style>
        * { box-sizing: border-box; }
        body { overflow-x: hidden; background: #080b18 !important; color: #e2e8f0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        #wrapper, #content-wrapper, #content { background: #080b18 !important; min-height: 100vh; }
        .shopmind-content { padding: 30px; background: #080b18; min-height: calc(100vh - 80px); }
        .shopmind-title { font-size: 26px; font-weight: 800; color: #ffffff; margin-bottom: 5px; }
        .shopmind-subtitle { color: #9ca3af; font-size: 14px; margin-bottom: 25px; }
        .shopmind-card { background: #0d1122; border: 1px solid #252b45; border-radius: 12px; overflow: hidden; width: 100%; box-shadow: 0 4px 20px rgba(0,0,0,0.4); }
        .shopmind-card-header { padding: 20px 24px; border-bottom: 1px solid #252b45; display: flex; align-items: center; justify-content: space-between; }
        .shopmind-card-title { color: #ffffff; font-size: 18px; font-weight: 700; margin: 0; }
        .shopmind-message { padding: 12px 18px; margin: 20px 24px 0 24px; border-radius: 8px; font-size: 14px; font-weight: 500; }
        .shopmind-message.success { background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.4); color: #34d399; }
        .shopmind-message.error { background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.4); color: #f87171; }

        .orders-table-wrapper { width: 100%; overflow-x: auto; padding: 20px 24px; }
        .shopmind-table { width: 100%; border-collapse: separate; border-spacing: 0; }
        .shopmind-table th { background: transparent; color: #94a3b8; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; padding: 14px 16px; border-bottom: 1px solid #252b45; text-align: left; }
        .shopmind-table td { padding: 16px; border-bottom: 1px solid #1a2236; color: #f1f5f9; font-size: 14px; vertical-align: middle; }
        .shopmind-table tr:hover td { background: rgba(255, 255, 255, 0.02); }

        .order-id { font-weight: 700; color: #ffffff; display: flex; align-items: center; gap: 4px; }
        .order-hash { color: #8b5cf6; }
        .customer-info { display: flex; flex-direction: column; }
        .customer-name { font-weight: 600; color: #f8fafc; }
        .customer-email { color: #94a3b8; font-size: 13px; }
        .order-date { color: #94a3b8; font-size: 13px; white-space: nowrap; }

        .items-badge-btn { display: inline-flex; align-items: center; gap: 8px; background: rgba(139, 92, 246, 0.15); border: 1px solid rgba(139, 92, 246, 0.4); color: #c4b5fd; padding: 7px 14px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.2s; }
        .items-badge-btn:hover { background: #8b5cf6; color: #ffffff; border-color: #8b5cf6; }
        .order-total-price { font-weight: 700; color: #10b981; font-size: 15px; margin-left: 6px; }

        .status-badge { display: inline-block; padding: 6px 14px; border-radius: 9999px; font-size: 12px; font-weight: 600; text-transform: capitalize; }
        .status-pending { background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3); }
        .status-processing { background: rgba(59, 130, 246, 0.15); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.3); }
        .status-completed { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); }
        .status-cancelled { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }

        .action-flex { display: flex; align-items: center; gap: 8px; }
        .status-form { display: flex; align-items: center; gap: 6px; }
        .status-select { background: #13192e; border: 1px solid #2d3755; color: #f8fafc; padding: 7px 10px; border-radius: 6px; font-size: 13px; outline: none; }
        .status-select:focus { border-color: #8b5cf6; }
        .save-btn { background: #8b5cf6; color: #ffffff; border: none; padding: 7px 14px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; transition: background 0.2s; }
        .save-btn:hover { background: #7c3aed; }
        .del-btn { background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); color: #f87171; padding: 7px 10px; border-radius: 6px; font-size: 13px; cursor: pointer; transition: all 0.2s; }
        .del-btn:hover { background: #ef4444; color: #fff; }

        /* Modal Styles */
        .order-modal { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.75); backdrop-filter: blur(4px); align-items: center; justify-content: center; }
        .order-modal.active { display: flex; }
        .modal-content { background: #0d1122; border: 1px solid #252b45; border-radius: 14px; width: 90%; max-width: 720px; max-height: 85vh; overflow-y: auto; padding: 25px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.8); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #252b45; padding-bottom: 16px; margin-bottom: 20px; }
        .modal-title { font-size: 20px; font-weight: 700; color: #fff; margin: 0; }
        .close-modal { background: none; border: none; color: #94a3b8; font-size: 24px; cursor: pointer; }
        .close-modal:hover { color: #fff; }
        .item-row { display: flex; align-items: center; justify-content: space-between; padding: 12px; border-bottom: 1px solid #1a2236; }
        .item-info { display: flex; align-items: center; gap: 14px; }
        .item-img { width: 52px; height: 52px; border-radius: 8px; object-fit: contain; background: #13192e; padding: 4px; border: 1px solid #252b45; }
        .item-text h5 { margin: 0 0 4px 0; font-size: 14px; color: #fff; }
        .item-text span { font-size: 12px; color: #94a3b8; }
        .item-pricing { text-align: right; }
        .item-subtotal { font-weight: 700; color: #10b981; font-size: 15px; }
        .item-calc { font-size: 12px; color: #94a3b8; }
        .modal-summary { margin-top: 20px; padding: 16px; background: #13192e; border-radius: 8px; border: 1px solid #252b45; display: flex; justify-content: space-between; align-items: center; }
        .modal-summary .label { font-size: 16px; font-weight: 600; color: #e2e8f0; }
        .modal-summary .grand-total { font-size: 22px; font-weight: 800; color: #10b981; }
    </style>
</head>

<body id="page-top">
<div id="wrapper">

    <!-- SIDEBAR -->
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">

            <!-- TOPBAR -->
            <?php include __DIR__ . '/../includes/topbar.php'; ?>

            <div class="shopmind-content">
                <div class="shopmind-title">Orders Management</div>
                <div class="shopmind-subtitle">View and manage all customer orders and their purchased products.</div>

                <div class="shopmind-card">
                    <div class="shopmind-card-header">
                        <h6 class="shopmind-card-title">All Customer Orders (<?= count($orders) ?>)</h6>
                    </div>

                    <?php if ($message): ?>
                        <div class="shopmind-message <?= h($message_type) ?>">
                            <?= h($message) ?>
                        </div>
                    <?php endif; ?>

                    <div class="orders-table-wrapper">
                        <table class="shopmind-table">
                            <thead>
                                <tr>
                                    <th>Order</th>
                                    <th>Customer</th>
                                    <th>Products & Total</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (!empty($orders)): ?>
                                <?php foreach ($orders as $order): ?>
                                    <?php
                                        $current_status = (string)($order['status'] ?? 'Pending');
                                        $status_class = match ($current_status) {
                                            'Processing' => 'status-processing',
                                            'Completed'  => 'status-completed',
                                            'Cancelled'  => 'status-cancelled',
                                            default      => 'status-pending',
                                        };
                                        $oid = (int)$order['id'];
                                        $items = $order_items[$oid] ?? [];
                                        $items_count = count($items);
                                        $total_units = (int)$order['total_units'];
                                        $total_price = (float)$order['total_amount'];
                                    ?>
                                    <tr>
                                        <!-- ORDER ID -->
                                        <td>
                                            <div class="order-id">
                                                <span class="order-hash">#</span><?= $oid ?>
                                            </div>
                                        </td>

                                        <!-- CUSTOMER -->
                                        <td>
                                            <div class="customer-info">
                                                <span class="customer-name"><?= h($order['customer_name']) ?></span>
                                                <span class="customer-email"><?= h($order['user_email']) ?></span>
                                            </div>
                                        </td>

                                        <!-- ITEMS & TOTAL -->
                                        <td>
                                            <button type="button" class="items-badge-btn" onclick="openOrderModal(<?= $oid ?>)">
                                                <i class="fas fa-box-open"></i>
                                                <span><?= $items_count ?> item<?= $items_count > 1 ? 's' : '' ?> (<?= $total_units ?> units)</span>
                                                <span class="order-total-price">$<?= number_format($total_price, 2) ?></span>
                                                <i class="fas fa-chevron-right" style="font-size: 11px;"></i>
                                            </button>
                                        </td>

                                        <!-- DATE -->
                                        <td>
                                            <span class="order-date"><?= h($order['order_date']) ?></span>
                                        </td>

                                        <!-- STATUS -->
                                        <td>
                                            <span class="status-badge <?= h($status_class) ?>">
                                                <?= h($current_status) ?>
                                            </span>
                                        </td>

                                        <!-- ACTIONS -->
                                        <td>
                                            <div class="action-flex">
                                                <form method="POST" class="status-form">
                                                    <input type="hidden" name="id" value="<?= $oid ?>">
                                                    <select name="status" class="status-select">
                                                        <option value="Pending" <?= $current_status === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                                        <option value="Processing" <?= $current_status === 'Processing' ? 'selected' : '' ?>>Processing</option>
                                                        <option value="Completed" <?= $current_status === 'Completed' ? 'selected' : '' ?>>Completed</option>
                                                        <option value="Cancelled" <?= $current_status === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                                    </select>
                                                    <button type="submit" class="save-btn" title="Save Status">Save</button>
                                                </form>

                                                <form method="POST" onsubmit="return confirm('Are you sure you want to permanently delete Order #<?= $oid ?>?');">
                                                    <input type="hidden" name="delete_order_id" value="<?= $oid ?>">
                                                    <button type="submit" class="del-btn" title="Delete Order">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 40px; color: #94a3b8;">
                                        <i class="fas fa-inbox" style="font-size: 32px; margin-bottom: 10px; display: block;"></i>
                                        No customer orders found.
                                    </td>
                                </tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- =========================================================================
     ORDER DETAILS MODALS (One for each order)
========================================================================= -->
<?php foreach ($orders as $order): ?>
    <?php
        $oid = (int)$order['id'];
        $items = $order_items[$oid] ?? [];
        $total_price = (float)$order['total_amount'];
    ?>
    <div id="modal-order-<?= $oid ?>" class="order-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">
                    <i class="fas fa-receipt" style="color: #8b5cf6; margin-right: 8px;"></i>
                    Order #<?= $oid ?> Details
                </h4>
                <button type="button" class="close-modal" onclick="closeOrderModal(<?= $oid ?>)">&times;</button>
            </div>

            <div style="margin-bottom: 18px; display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px;">
                <div style="background: #13192e; padding: 12px; border-radius: 8px; border: 1px solid #252b45;">
                    <span style="font-size: 11px; color: #94a3b8; text-transform: uppercase;">Customer</span>
                    <div style="font-weight: 600; color: #fff; margin-top: 4px;"><?= h($order['customer_name']) ?></div>
                    <div style="font-size: 12px; color: #94a3b8;"><?= h($order['user_email']) ?></div>
                </div>
                <div style="background: #13192e; padding: 12px; border-radius: 8px; border: 1px solid #252b45;">
                    <span style="font-size: 11px; color: #94a3b8; text-transform: uppercase;">Date & Status</span>
                    <div style="font-weight: 600; color: #fff; margin-top: 4px;"><?= h($order['order_date']) ?></div>
                    <span class="status-badge <?= match($order['status'] ?? 'Pending') { 'Processing' => 'status-processing', 'Completed' => 'status-completed', 'Cancelled' => 'status-cancelled', default => 'status-pending' } ?>" style="margin-top: 4px;">
                        <?= h($order['status'] ?? 'Pending') ?>
                    </span>
                </div>
            </div>

            <h5 style="color: #cbd5e1; font-size: 15px; margin: 16px 0 10px 0;">Purchased Products (<?= count($items) ?> items):</h5>

            <?php if (!empty($items)): ?>
                <div style="border: 1px solid #252b45; border-radius: 8px; overflow: hidden; background: #080b18;">
                    <?php foreach ($items as $item): ?>
                        <div class="item-row">
                            <div class="item-info">
                                <img src="<?= h($item['image_src']) ?>" alt="<?= h($item['product_name']) ?>" class="item-img" onerror="this.src='/ShopMind/frontend/img/logo.png'">
                                <div class="item-text">
                                    <h5><?= h($item['product_name']) ?></h5>
                                    <span>Category: <?= h($item['category']) ?> | Unit Price: $<?= number_format((float)$item['price'], 2) ?></span>
                                </div>
                            </div>
                            <div class="item-pricing">
                                <div class="item-subtotal">$<?= number_format((float)$item['price'] * (int)$item['quantity'], 2) ?></div>
                                <div class="item-calc"><?= (int)$item['quantity'] ?> × $<?= number_format((float)$item['price'], 2) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="color: #94a3b8; text-align: center; padding: 20px;">No item details recorded for this order.</p>
            <?php endif; ?>

            <div class="modal-summary">
                <span class="label">Total Order Amount:</span>
                <span class="grand-total">$<?= number_format($total_price, 2) ?></span>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<script>
    function openOrderModal(id) {
        const modal = document.getElementById('modal-order-' + id);
        if (modal) modal.classList.add('active');
    }
    function closeOrderModal(id) {
        const modal = document.getElementById('modal-order-' + id);
        if (modal) modal.classList.remove('active');
    }
    window.addEventListener('click', function(e) {
        if (e.target.classList.contains('order-modal')) {
            e.target.classList.remove('active');
        }
    });
</script>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/sb-admin-2.min.js"></script>

</body>
</html>
