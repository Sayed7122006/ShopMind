
<?php

session_start();


/*
|--------------------------------------------------------------------------
| DATABASE CONNECTION
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../fun/db_connection.php';


/*
|--------------------------------------------------------------------------
| ADMIN CHECK
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../fun/check.php';

checkRole(['admin']);


/*
|--------------------------------------------------------------------------
| GET USER ID
|--------------------------------------------------------------------------
*/

$user_id = isset($_GET['id'])
    ? (int)$_GET['id']
    : 0;


if ($user_id <= 0) {

    header("Location: users-management.php");
    exit;

}


/*
|--------------------------------------------------------------------------
| CHECK USER
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        id,
        role
    FROM users
    WHERE id = ?
    LIMIT 1
";


$stmt = $conn->prepare($sql);


if (!$stmt) {

    die(
        "Database Error: " .
        htmlspecialchars(
            $conn->error,
            ENT_QUOTES,
            'UTF-8'
        )
    );

}


$stmt->bind_param(
    "i",
    $user_id
);


$stmt->execute();


$result = $stmt->get_result();


if (!$result || $result->num_rows !== 1) {

    $stmt->close();

    header("Location: users-management.php");
    exit;

}


$user = $result->fetch_assoc();


$stmt->close();


/*
|--------------------------------------------------------------------------
| GET ROLE
|--------------------------------------------------------------------------
*/

$role = $user['role'] ?? '';


/*
|--------------------------------------------------------------------------
| ONLY CUSTOMER CAN BECOME VENDOR
|--------------------------------------------------------------------------
*/

if ($role !== 'customer') {

    header("Location: users-management.php");
    exit;

}


/*
|--------------------------------------------------------------------------
| UPDATE ROLE
|--------------------------------------------------------------------------
*/

$sql = "
    UPDATE users
    SET role = 'vendor'
    WHERE id = ?
";


$stmt = $conn->prepare($sql);


if (!$stmt) {

    die(
        "Database Error: " .
        htmlspecialchars(
            $conn->error,
            ENT_QUOTES,
            'UTF-8'
        )
    );

}


$stmt->bind_param(
    "i",
    $user_id
);


if (!$stmt->execute()) {

    $stmt->close();

    die(
        "Database Error: " .
        htmlspecialchars(
            $conn->error,
            ENT_QUOTES,
            'UTF-8'
        )
    );

}


$stmt->close();


/*
|--------------------------------------------------------------------------
| RETURN TO USERS MANAGEMENT
|--------------------------------------------------------------------------
*/

header("Location: users-management.php");
exit;

?>

