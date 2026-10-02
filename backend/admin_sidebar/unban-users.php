
<?php

session_start();

/*
|--------------------------------------------------------------------------
| DATABASE CONNECTION
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../fun/db_connection.php';
require_once __DIR__ . '/../fun/check.php';


/*
|--------------------------------------------------------------------------
| ADMIN CHECK
|--------------------------------------------------------------------------
*/

checkRole(['admin']);


/*
|--------------------------------------------------------------------------
| GET USER ID
|--------------------------------------------------------------------------
*/

$user_id = isset($_GET['id'])
    ? (int) $_GET['id']
    : 0;


if ($user_id <= 0) {

    header("Location: users-management.php");
    exit;

}


/*
|--------------------------------------------------------------------------
| CHECK USER EXISTS
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT id
    FROM users
    WHERE id = ?
      AND role != 'admin'
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

$stmt->bind_param("i", $user_id);

$stmt->execute();

$result = $stmt->get_result();


if (!$result || $result->num_rows !== 1) {

    $stmt->close();

    header("Location: users-management.php");
    exit;

}

$stmt->close();


/*
|--------------------------------------------------------------------------
| UNBAN USER
|--------------------------------------------------------------------------
|
| جدول users عندك يحتوي على:
|
| is_banned
|
| 0 = Allowed
| 1 = Banned
|
*/

$sql = "
    UPDATE users
    SET is_banned = 0
    WHERE id = ?
      AND role != 'admin'
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

$stmt->bind_param("i", $user_id);


/*
|--------------------------------------------------------------------------
| EXECUTE
|--------------------------------------------------------------------------
*/

if (!$stmt->execute()) {

    $error = $stmt->error;

    $stmt->close();

    die(
        "Database Error: " .
        htmlspecialchars(
            $error,
            ENT_QUOTES,
            'UTF-8'
        )
    );

}

$stmt->close();


/*
|--------------------------------------------------------------------------
| RETURN
|--------------------------------------------------------------------------
*/

header("Location: users-management.php");
exit;

?>

