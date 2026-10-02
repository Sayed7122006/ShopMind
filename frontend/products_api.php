<?php
header('Content-Type: application/json; charset=utf-8');

// Load the project's existing MariaDB connection configuration.
require_once dirname(__DIR__, 3) . '/fun/db_connection.php';

try {
    $sql = "SELECT id, name, price, category, img FROM products ORDER BY id ASC";
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        throw new RuntimeException(mysqli_error($conn));
    }

    $products = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $imageData = null;
        if (!empty($row['img'])) {
            $bytes = $row['img'];
            $mime = 'image/png';
            if (function_exists('finfo_buffer')) {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $detected = $finfo->buffer($bytes);
                if (is_string($detected) && str_starts_with($detected, 'image/')) {
                    $mime = $detected;
                }
            }
            $imageData = 'data:' . $mime . ';base64,' . base64_encode($bytes);
        }

        $products[] = [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'price' => (float)$row['price'],
            'category' => $row['category'],
            'img' => $imageData,
        ];
    }

    echo json_encode($products, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Could not load products from the database.']);
}
