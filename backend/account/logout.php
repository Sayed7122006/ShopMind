
<?php

session_start();

require_once __DIR__ . '/../fun/db_connection.php';


/*
|--------------------------------------------------------------------------
| GET CURRENT USER ID
|--------------------------------------------------------------------------
*/

$user_id = (int)($_SESSION['user_id'] ?? 0);


/*
|--------------------------------------------------------------------------
| REMOVE REMEMBER TOKEN FROM DATABASE
|--------------------------------------------------------------------------
*/

if ($user_id > 0) {

    $sql = "
        UPDATE users
        SET remember_token = NULL
        WHERE id = ?
    ";

    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {

        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $user_id
        );

        mysqli_stmt_execute($stmt);

        mysqli_stmt_close($stmt);
    }
}


/*
|--------------------------------------------------------------------------
| DELETE REMEMBER TOKEN COOKIE
|--------------------------------------------------------------------------
*/

setcookie(
    "remember_token",
    "",
    [
        "expires" => time() - 3600,
        "path" => "/",
        "secure" => isset($_SERVER["HTTPS"]),
        "httponly" => true,
        "samesite" => "Lax"
    ]
);


/*
|--------------------------------------------------------------------------
| DELETE OLD REMEMBER COOKIE
|--------------------------------------------------------------------------
*/

setcookie(
    "remember_user",
    "",
    [
        "expires" => time() - 3600,
        "path" => "/",
        "secure" => isset($_SERVER["HTTPS"]),
        "httponly" => true,
        "samesite" => "Lax"
    ]
);


/*
|--------------------------------------------------------------------------
| DESTROY SESSION
|--------------------------------------------------------------------------
*/

$_SESSION = [];

session_unset();
session_destroy();


/*
|--------------------------------------------------------------------------
| REDIRECT TO LOGIN
|--------------------------------------------------------------------------
*/

header("Location: login.php");
exit();

?>

