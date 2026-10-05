<?php
header('Content-Type: application/json; charset=utf-8');

// Load database connection
$dbCandidates = [
    __DIR__ . '/fun/db_connection.php',
    __DIR__ . '/../fun/db_connection.php',
    __DIR__ . '/../backend/fun/db_connection.php',
    dirname(__DIR__) . '/backend/fun/db_connection.php',
    dirname(__DIR__, 2) . '/backend/fun/db_connection.php',
    dirname(__DIR__, 3) . '/fun/db_connection.php'
];
foreach ($dbCandidates as $file) {
    if (file_exists($file)) {
        require_once $file;
        break;
    }
}
if (!isset($conn) || !$conn) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed.']);
    exit;
}

try {
    $sql = "SELECT id, name, price, category, img FROM products ORDER BY id ASC";
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        throw new RuntimeException(mysqli_error($conn));
    }

    $products = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $id = (int)$row['id'];
        $imgIndex = $id - 1;
        $localPath = "img/product/{$imgIndex}.png";
        
        if (file_exists(__DIR__ . '/' . $localPath)) {
            $imageSrc = $localPath;
        } elseif (file_exists(__DIR__ . "/img/product/{$id}.png")) {
            $imageSrc = "img/product/{$id}.png";
        } elseif (!empty($row['img']) && str_starts_with($row['img'], "\x89PNG")) {
            $imageSrc = 'data:image/png;base64,' . base64_encode($row['img']);
        } else {
            $imageSrc = 'img/logo.png';
        }

        $products[] = [
            'id' => $id,
            'name' => $row['name'],
            'price' => (float)$row['price'],
            'category' => $row['category'],
            'img' => $imageSrc,
        ];
    }

    echo json_encode($products, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Could not load products from the database.']);
}
