<?php
session_start();
// Load database connection
$dbCandidates = [
    __DIR__ . '/../fun/db_connection.php',
    __DIR__ . '/../../fun/db_connection.php',
    __DIR__ . '/../../backend/fun/db_connection.php',
    dirname(__DIR__, 2) . '/backend/fun/db_connection.php',
    dirname(__DIR__, 3) . '/backend/fun/db_connection.php',
    dirname(__DIR__, 4) . '/fun/db_connection.php'
];
foreach ($dbCandidates as $file) {
    if (file_exists($file)) {
        require_once $file;
        break;
    }
}
if (!isset($conn) || !$conn) {
    die('Database connection failed.');
}

$userId = (int)($_SESSION['store_user_id'] ?? 0);
$email = (string)($_SESSION['store_user_email'] ?? '');
$role = (string)($_SESSION['store_user_role'] ?? '');
if ($userId < 1 || $email === '' || $role !== 'customer') {
    header('Location: login.php?checkout=1'); exit;
}
$error = '';
$successOrderId = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    mysqli_begin_transaction($conn);
    try {
        $cartSql = "SELECT c.product_id, c.quantity, p.price, p.name
                    FROM cart c JOIN products p ON p.id = c.product_id
                    WHERE c.user_id = ? FOR UPDATE";
        $cartStmt = mysqli_prepare($conn, $cartSql);
        mysqli_stmt_bind_param($cartStmt, 'i', $userId);
        mysqli_stmt_execute($cartStmt);
        $cartResult = mysqli_stmt_get_result($cartStmt);
        $items = [];
        while ($row = mysqli_fetch_assoc($cartResult)) $items[] = $row;
        if (!$items) throw new Exception('Your cart is empty. Add products before checkout.');

        $orderStmt = mysqli_prepare($conn, "INSERT INTO orders (user_id, user_email, status) VALUES (?, ?, 'Pending')");
        mysqli_stmt_bind_param($orderStmt, 'is', $userId, $email);
        if (!mysqli_stmt_execute($orderStmt)) throw new Exception('Could not create order.');
        $orderId = mysqli_insert_id($conn);

        $itemStmt = mysqli_prepare($conn, 'INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)');
        foreach ($items as $item) {
            $productId = (int)$item['product_id'];
            $quantity = (int)$item['quantity'];
            $price = (float)$item['price'];
            if ($quantity < 1) throw new Exception('Invalid item quantity.');
            mysqli_stmt_bind_param($itemStmt, 'iiid', $orderId, $productId, $quantity, $price);
            if (!mysqli_stmt_execute($itemStmt)) throw new Exception('Could not save order item.');
        }
        $delete = mysqli_prepare($conn, 'DELETE FROM cart WHERE user_id = ?');
        mysqli_stmt_bind_param($delete, 'i', $userId);
        if (!mysqli_stmt_execute($delete)) throw new Exception('Order saved but cart could not be cleared.');
        mysqli_commit($conn);
        $successOrderId = $orderId;
    } catch (Throwable $e) {
        mysqli_rollback($conn);
        $error = $e->getMessage();
    }
}

$items = [];
$total = 0.0;
$stmt = mysqli_prepare($conn, 'SELECT c.product_id, c.quantity, p.name, p.price FROM cart c JOIN products p ON p.id = c.product_id WHERE c.user_id = ? ORDER BY c.id');
mysqli_stmt_bind_param($stmt, 'i', $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($result)) { $items[] = $row; $total += (float)$row['price'] * (int)$row['quantity']; }
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Checkout | ShopMind</title>
<style>body{font-family:Arial,sans-serif;background:#f5f7fa;margin:0;color:#222}.wrap{max-width:760px;margin:40px auto;padding:24px;background:#fff;border-radius:12px;box-shadow:0 4px 20px #0001}h1{margin-top:0}.item{display:flex;justify-content:space-between;padding:12px 0;border-bottom:1px solid #eee}.total{font-size:20px;font-weight:bold;text-align:right;padding:18px 0}.btn{background:#198754;color:#fff;border:0;border-radius:7px;padding:12px 20px;font-size:16px;cursor:pointer}.back{display:inline-block;margin-left:10px;color:#198754}.msg{padding:12px;background:#e8f7ee;color:#17653b;border-radius:7px}.err{padding:12px;background:#fff0f0;color:#9b2020;border-radius:7px}</style></head><body><main class="wrap">
<h1>Checkout</h1><p>Signed in as <?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></p>
<?php if ($successOrderId): ?><div class="msg">Order #<?= (int)$successOrderId ?> placed successfully! Status: Pending.</div><p><a class="back" href="../frontend/index.php">Continue shopping</a></p>
<?php else: ?>
<?php if ($error): ?><p class="err"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
<?php if (!$items): ?><p>Your cart is empty.</p><a class="back" href="../frontend/index.php">Back to store</a>
<?php else: ?>
<?php foreach ($items as $item): ?><div class="item"><span><?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?> × <?= (int)$item['quantity'] ?></span><strong>$<?= number_format((float)$item['price'] * (int)$item['quantity'], 2) ?></strong></div><?php endforeach; ?>
<div class="total">Total: $<?= number_format($total, 2) ?></div>
<form method="post"><button class="btn" type="submit" name="place_order" value="1">Place Order</button><a class="back" href="../frontend/index.php">Back to store</a></form>
<?php endif; ?><?php endif; ?></main></body></html>
