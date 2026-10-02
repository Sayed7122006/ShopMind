<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__, 3) . '/fun/db_connection.php';

function respond($status, $payload) {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$userId = (int)($_SESSION['store_user_id'] ?? 0);
$email = (string)($_SESSION['store_user_email'] ?? '');
$role = (string)($_SESSION['store_user_role'] ?? '');
if ($userId < 1 || $email === '' || $role !== 'customer') {
    respond(401, ['error' => 'Please log in with a customer account.']);
}
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'GET' && $action === 'list') {
    $sql = "SELECT c.product_id AS id, c.quantity AS qty, p.name, p.price, p.img
            FROM cart c JOIN products p ON p.id = c.product_id
            WHERE c.user_id = ? ORDER BY c.id";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $items = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $image = null;
        if (!empty($row['img'])) {
            $mime = 'image/png';
            if (function_exists('finfo_buffer')) {
                $f = new finfo(FILEINFO_MIME_TYPE);
                $detected = $f->buffer($row['img']);
                if (is_string($detected) && str_starts_with($detected, 'image/')) $mime = $detected;
            }
            $image = 'data:' . $mime . ';base64,' . base64_encode($row['img']);
        }
        $items[] = ['id'=>(int)$row['id'], 'qty'=>(int)$row['qty'], 'name'=>$row['name'], 'price'=>(float)$row['price'], 'img'=>$image];
    }
    respond(200, $items);
}

if ($method !== 'POST') respond(405, ['error'=>'Method not allowed.']);
$data = json_decode(file_get_contents('php://input'), true) ?: [];
$productId = filter_var($data['product_id'] ?? null, FILTER_VALIDATE_INT);
if (!$productId || $productId < 1) respond(400, ['error'=>'Invalid product.']);

if ($action === 'add') {
    $check = mysqli_prepare($conn, 'SELECT id FROM products WHERE id = ?');
    mysqli_stmt_bind_param($check, 'i', $productId);
    mysqli_stmt_execute($check);
    if (!mysqli_stmt_get_result($check)->fetch_assoc()) respond(404, ['error'=>'Product not found.']);
    $find = mysqli_prepare($conn, 'SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ? LIMIT 1');
    mysqli_stmt_bind_param($find, 'ii', $userId, $productId);
    mysqli_stmt_execute($find);
    $existing = mysqli_stmt_get_result($find)->fetch_assoc();
    if ($existing) {
        $newQty = (int)$existing['quantity'] + 1;
        $upd = mysqli_prepare($conn, 'UPDATE cart SET quantity = ? WHERE id = ?');
        mysqli_stmt_bind_param($upd, 'ii', $newQty, $existing['id']);
        mysqli_stmt_execute($upd);
    } else {
        $ins = mysqli_prepare($conn, 'INSERT INTO cart (user_id, user_email, product_id, quantity) VALUES (?, ?, ?, 1)');
        mysqli_stmt_bind_param($ins, 'isi', $userId, $email, $productId);
        mysqli_stmt_execute($ins);
    }
    respond(200, ['ok'=>true]);
}

if ($action === 'remove') {
    $del = mysqli_prepare($conn, 'DELETE FROM cart WHERE user_id = ? AND product_id = ?');
    mysqli_stmt_bind_param($del, 'ii', $userId, $productId);
    mysqli_stmt_execute($del);
    respond(200, ['ok'=>true]);
}
respond(400, ['error'=>'Unknown cart action.']);
