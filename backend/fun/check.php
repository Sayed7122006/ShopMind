
<?php

/*
|--------------------------------------------------------------------------
| START SESSION
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| DATABASE CONNECTION
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/db_connection.php';


/*
|--------------------------------------------------------------------------
| AUTO LOGIN USING REMEMBER TOKEN
|--------------------------------------------------------------------------
|
| لو الـsession مش موجودة ولكن فيه remember_token في الـcookie،
| نحاول نجيب المستخدم من قاعدة البيانات ونعمل Login تلقائي.
|
*/

if (
    !isset($_SESSION['user_id']) &&
    !empty($_COOKIE['remember_token'])
) {

    $remember_token = $_COOKIE['remember_token'];

    /*
     * Get user using remember token
     */

    $sql = "
        SELECT
            id,
            Name,
            Email,
            role
        FROM users
        WHERE remember_token = ?
        LIMIT 1
    ";

    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {

        mysqli_stmt_bind_param(
            $stmt,
            "s",
            $remember_token
        );

        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        $user = mysqli_fetch_assoc($result);

        mysqli_stmt_close($stmt);


        /*
         * User found
         */

        if ($user) {

            /*
             * Regenerate session ID
             */

            session_regenerate_id(true);


            /*
             * Restore user session
             */

            $_SESSION['user_id'] = (int)$user['id'];

            $_SESSION['user_name'] = $user['Name'];

            $_SESSION['user_email'] = $user['Email'];

            $_SESSION['user_role'] = $user['role'];


            /*
             * Compatibility with old dashboard files
             */

            $_SESSION['role'] = $user['role'];

            $_SESSION['name'] = $user['Name'];

            $_SESSION['email'] = $user['Email'];

        } else {

            /*
             * Invalid token.
             *
             * Delete the invalid cookie.
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
        }
    }
}


/*
|--------------------------------------------------------------------------
| CHECK ROLE
|--------------------------------------------------------------------------
*/

function checkRole(array $allowedRoles)
{

    /*
     * If user is not logged in
     */

    if (!isset($_SESSION['user_id'])) {

        header("Location: ../account/login.php");

        exit();
    }


    /*
     * Get current role
     */

    $userRole = $_SESSION['user_role']
        ?? $_SESSION['role']
        ?? '';


    /*
     * Check allowed roles
     */

    if (!in_array($userRole, $allowedRoles, true)) {

        /*
         * Customer or unauthorized user
         */

        header("Location: ../account/login.php?error=dashboard_access");

        exit();
    }
}

?>

