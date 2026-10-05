
<?php

session_start();

require_once __DIR__ . '/db_connection.php';


/* =====================================================
   GET FORM DATA
===================================================== */

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$remember_me = isset($_POST['remember_me']);

$redirect = $_POST['redirect'] ?? '';


/* =====================================================
   ALLOWED STORE REDIRECTS
===================================================== */

$allowed_redirects = [
    'cart',
    'checkout',
    'shop'
];

if (!in_array($redirect, $allowed_redirects, true)) {
    $redirect = '';
}


/* =====================================================
   VALIDATE
===================================================== */

if ($email === '' || $password === '') {

    header("Location: ../account/login.php?error=empty");
    exit();

}


/* =====================================================
   GET USER
===================================================== */

$sql = "
    SELECT
        id,
        Name,
        Email,
        Password,
        role
    FROM users
    WHERE Email = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Database error: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$user = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


/* =====================================================
   USER NOT FOUND
===================================================== */

if (!$user) {

    header("Location: ../account/login.php?error=invalid");
    exit();

}


/* =====================================================
   CHECK PASSWORD
===================================================== */

$password_correct = false;


/*
 * Password Hash
 */

if (
    !empty($user['Password']) &&
    password_verify($password, $user['Password'])
) {

    $password_correct = true;

}


/*
 * Temporary support for old plain-text passwords
 */

elseif ($password === $user['Password']) {

    $password_correct = true;

}


/* =====================================================
   WRONG PASSWORD
===================================================== */

if (!$password_correct) {

    header("Location: ../account/login.php?error=invalid");
    exit();

}


/* =====================================================
   CHECK DASHBOARD ACCESS
===================================================== */

if ($redirect === '') {

    /*
     * Dashboard allows ONLY:
     * admin
     * vendor
     */

    if (
        $user['role'] !== 'admin' &&
        $user['role'] !== 'vendor'
    ) {

        header("Location: ../account/login.php?error=dashboard_access");
        exit();

    }

}


/* =====================================================
   REGENERATE SESSION
===================================================== */

session_regenerate_id(true);


/* =====================================================
   SAVE SESSION
===================================================== */

$_SESSION['user_id'] = (int)$user['id'];
$_SESSION['user_name'] = $user['Name'];
$_SESSION['user_email'] = $user['Email'];
$_SESSION['user_role'] = $user['role'];


/*
 * Compatibility with existing dashboard files
 */

$_SESSION['role'] = $user['role'];
$_SESSION['name'] = $user['Name'];
$_SESSION['email'] = $user['Email'];


/* =====================================================
   REMEMBER ME
===================================================== */

if ($remember_me) {

    /*
     * Generate a secure random token
     */

    $remember_token = bin2hex(random_bytes(32));


    /*
     * Save token in database
     */

    $sql = "
        UPDATE users
        SET remember_token = ?
        WHERE id = ?
    ";

    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        die("Remember token database error: " . mysqli_error($conn));
    }

    $user_id = (int)$user['id'];

    mysqli_stmt_bind_param(
        $stmt,
        "si",
        $remember_token,
        $user_id
    );

    mysqli_stmt_execute($stmt);

    mysqli_stmt_close($stmt);


    /*
     * Save token in browser
     */

    setcookie(
        "remember_token",
        $remember_token,
        [
            'expires' => time() + (60 * 60 * 24 * 30),
            'path' => '/',
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax'
        ]
    );


} else {

    /*
     * User did NOT select Remember Me.
     * Remove any old token.
     */

    $sql = "
        UPDATE users
        SET remember_token = NULL
        WHERE id = ?
    ";

    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {

        $user_id = (int)$user['id'];

        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $user_id
        );

        mysqli_stmt_execute($stmt);

        mysqli_stmt_close($stmt);
    }


    /*
     * Delete old cookie
     */

    setcookie(
        "remember_token",
        "",
        [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax'
        ]
    );
}


/* =====================================================
   STORE REDIRECT
===================================================== */

if ($redirect !== '') {

    header("Location: ../" . $redirect . ".php");
    exit();

}


/* =====================================================
   DASHBOARD REDIRECT
===================================================== */

if (
    $user['role'] === 'admin' ||
    $user['role'] === 'vendor'
) {

    header("Location: ../index.php");
    exit();

}


/* =====================================================
   SAFETY FALLBACK
===================================================== */

session_unset();
session_destroy();

header("Location: ../account/login.php?error=dashboard_access");
exit();

?>

