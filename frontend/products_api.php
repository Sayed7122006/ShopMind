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
        $imageSrc = '';

        // 1. Check if img column is a valid file path
        if (!empty($row['img']) && is_string($row['img']) && strlen($row['img']) < 255) {
            $cleanPath = ltrim($row['img'], '/');
            if (file_exists(__DIR__ . '/' . $cleanPath)) {
                $imageSrc = $cleanPath;
            }
        }

        // 2. Check if img column is binary image data (BLOB)
        if (!$imageSrc && !empty($row['img'])) {
            $blob = $row['img'];
            $mime = null;
            if (str_starts_with($blob, "\x89PNG")) {
                $mime = 'image/png';
            } elseif (str_starts_with($blob, "\xFF\xD8\xFF")) {
                $mime = 'image/jpeg';
            } elseif (str_starts_with($blob, "RIFF") && str_contains(substr($blob, 8, 8), "WEBP")) {
                $mime = 'image/webp';
            } elseif (str_starts_with($blob, "GIF8")) {
                $mime = 'image/gif';
            } elseif (function_exists('finfo_open')) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $detected = finfo_buffer($finfo, substr($blob, 0, 1024));
                finfo_close($finfo);
                if ($detected && str_starts_with($detected, 'image/')) {
                    $mime = $detected;
                }
            }
            if ($mime) {
                $imageSrc = "data:{$mime};base64," . base64_encode($blob);
            }
        }

        // 3. Fallback to existing product images on disk
        if (!$imageSrc) {
            $imgIndex = $id - 1;
            if (file_exists(__DIR__ . "/img/product/{$imgIndex}.png")) {
                $imageSrc = "img/product/{$imgIndex}.png";
            } elseif (file_exists(__DIR__ . "/img/product/{$id}.png")) {
                $imageSrc = "img/product/{$id}.png";
            } else {
                // Check if any prod_{id}.* exists
                $matches = glob(__DIR__ . "/img/product/prod_{$id}.*");
                if (!empty($matches)) {
                    $imageSrc = "img/product/" . basename($matches[0]);
                } else {
                    $imageSrc = 'img/logo.png';
                }
            }
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
