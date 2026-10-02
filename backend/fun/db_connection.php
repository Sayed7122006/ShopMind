<?php
/**
 * Shared ShopMind database connection.
 * Local credentials belong in db_config.local.php (ignored by Git),
 * or may be supplied through SHOPMIND_DB_* environment variables.
 */
$configFile = __DIR__ . '/db_config.local.php';
$config = is_file($configFile) ? require $configFile : [];

$host = $config['host'] ?? getenv('SHOPMIND_DB_HOST') ?: 'localhost';
$username = $config['username'] ?? getenv('SHOPMIND_DB_USER') ?: 'root';
$password = $config['password'] ?? getenv('SHOPMIND_DB_PASSWORD') ?: '';
$database = $config['database'] ?? getenv('SHOPMIND_DB_NAME') ?: 'store';

$conn = mysqli_connect($host, $username, $password, $database);
if (!$conn) {
    http_response_code(500);
    die('Database connection failed. Check fun/db_config.local.php and confirm MySQL is running.');
}
mysqli_set_charset($conn, 'utf8mb4');
