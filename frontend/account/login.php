<?php

session_start();

/*
|--------------------------------------------------------------------------
| DATABASE CONNECTION
|--------------------------------------------------------------------------
| Database: store
|--------------------------------------------------------------------------
*/
// Load database connection
$dbCandidates = [
    __DIR__ . '/../fun/db_connection.php',
    __DIR__ . '/../../fun/db_connection.php',
    __DIR__ . '/../../backend/fun/db_connection.php',
    dirname(__DIR__, 2) . '/backend/fun/db_connection.php',
    dirname(__DIR__, 3) . '/backend/fun/db_connection.php',
    dirname(__DIR__, 4) . '/fun/db_connection.php'
];
foreach ($dbCandidates as $file) {
    if (file_exists($file)) {
        require_once $file;
        break;
    }
}
if (!isset($conn) || !$conn) {
    die('Database connection failed.');
}


/*
|--------------------------------------------------------------------------
| VARIABLES
|--------------------------------------------------------------------------
*/

$error = "";

$email = "";


/*
|--------------------------------------------------------------------------
| CHECKOUT FLAG
|--------------------------------------------------------------------------
*/

$fromCheckout =
    isset($_GET['checkout']) &&
    $_GET['checkout'] === '1';


/*
|--------------------------------------------------------------------------
| IF ALREADY LOGGED IN
|--------------------------------------------------------------------------
*/

if (
    isset($_SESSION['store_user_id']) &&
    (int)$_SESSION['store_user_id'] > 0
) {

    if ($fromCheckout) {

        header("Location: checkout.php");
        exit();

    }

    header("Location: ../frontend/index.php");
    exit();
}


/*
|--------------------------------------------------------------------------
| MOVE GUEST CART TO DATABASE
|--------------------------------------------------------------------------
*/

function migrateGuestCartToDatabase($conn, $user_id)
{

    if (
        !isset($_COOKIE['shophub_cart']) ||
        trim($_COOKIE['shophub_cart']) === ''
    ) {

        return;
    }


    /*
    |--------------------------------------------------------------------------
    | READ GUEST CART
    |--------------------------------------------------------------------------
    */

    $guest_cart = json_decode(
        $_COOKIE['shophub_cart'],
        true
    );


    if (
        !is_array($guest_cart) ||
        empty($guest_cart)
    ) {

        return;
    }


    /*
    |--------------------------------------------------------------------------
    | START TRANSACTION
    |--------------------------------------------------------------------------
    */

    mysqli_begin_transaction($conn);


    try {

        /*
        |--------------------------------------------------------------------------
        | GET USER CART
        |--------------------------------------------------------------------------
        */

        $cart_sql = "
            SELECT id
            FROM cart
            WHERE user_id = ?
            LIMIT 1
        ";

        $cart_stmt = mysqli_prepare(
            $conn,
            $cart_sql
        );


        if (!$cart_stmt) {

            throw new Exception(
                "Cart prepare error."
            );
        }


        mysqli_stmt_bind_param(
            $cart_stmt,
            "i",
            $user_id
        );


        mysqli_stmt_execute(
            $cart_stmt
        );


        $cart_result =
            mysqli_stmt_get_result(
                $cart_stmt
            );


        $cart_data =
            mysqli_fetch_assoc(
                $cart_result
            );


        mysqli_stmt_close(
            $cart_stmt
        );


        /*
        |--------------------------------------------------------------------------
        | CREATE CART IF NEEDED
        |--------------------------------------------------------------------------
        */

        if ($cart_data) {

            $cart_id =
                (int)$cart_data['id'];

        } else {

            $create_cart_sql = "
                INSERT INTO cart
                (
                    user_id
                )
                VALUES
                (?)
            ";


            $create_cart_stmt =
                mysqli_prepare(
                    $conn,
                    $create_cart_sql
                );


            if (!$create_cart_stmt) {

                throw new Exception(
                    "Create cart prepare error."
                );
            }


            mysqli_stmt_bind_param(
                $create_cart_stmt,
                "i",
                $user_id
            );


            if (
                !mysqli_stmt_execute(
                    $create_cart_stmt
                )
            ) {

                throw new Exception(
                    "Create cart error."
                );
            }


            $cart_id =
                mysqli_insert_id(
                    $conn
                );


            mysqli_stmt_close(
                $create_cart_stmt
            );
        }


        /*
        |--------------------------------------------------------------------------
        | PROCESS GUEST CART ITEMS
        |--------------------------------------------------------------------------
        */

        foreach (
            $guest_cart as $product_id => $quantity
        ) {

            $product_id =
                (int)$product_id;

            $quantity =
                (int)$quantity;


            if (
                $product_id <= 0 ||
                $quantity <= 0
            ) {

                continue;
            }


            /*
            |--------------------------------------------------------------------------
            | CHECK PRODUCT
            |--------------------------------------------------------------------------
            */

            $product_sql = "
                SELECT
                    id,
                    stock_quantity,
                    status
                FROM products
                WHERE id = ?
                LIMIT 1
            ";


            $product_stmt =
                mysqli_prepare(
                    $conn,
                    $product_sql
                );


            if (!$product_stmt) {

                continue;
            }


            mysqli_stmt_bind_param(
                $product_stmt,
                "i",
                $product_id
            );


            mysqli_stmt_execute(
                $product_stmt
            );


            $product_result =
                mysqli_stmt_get_result(
                    $product_stmt
                );


            $product =
                mysqli_fetch_assoc(
                    $product_result
                );


            mysqli_stmt_close(
                $product_stmt
            );


            if (!$product) {

                continue;
            }


            /*
            |--------------------------------------------------------------------------
            | PRODUCT STATUS
            |--------------------------------------------------------------------------
            */

            if (
                isset($product['status']) &&
                $product['status'] !== 'active'
            ) {

                continue;
            }


            /*
            |--------------------------------------------------------------------------
            | STOCK
            |--------------------------------------------------------------------------
            */

            $stock =
                (int)$product['stock_quantity'];


            if ($stock <= 0) {

                continue;
            }


            if ($quantity > $stock) {

                $quantity = $stock;
            }


            /*
            |--------------------------------------------------------------------------
            | CHECK EXISTING CART ITEM
            |--------------------------------------------------------------------------
            */

            $item_sql = "
                SELECT
                    id,
                    quantity
                FROM cart_items
                WHERE cart_id = ?
                AND product_id = ?
                LIMIT 1
            ";


            $item_stmt =
                mysqli_prepare(
                    $conn,
                    $item_sql
                );


            if (!$item_stmt) {

                continue;
            }


            mysqli_stmt_bind_param(
                $item_stmt,
                "ii",
                $cart_id,
                $product_id
            );


            mysqli_stmt_execute(
                $item_stmt
            );


            $item_result =
                mysqli_stmt_get_result(
                    $item_stmt
                );


            $existing_item =
                mysqli_fetch_assoc(
                    $item_result
                );


            mysqli_stmt_close(
                $item_stmt
            );


            /*
            |--------------------------------------------------------------------------
            | UPDATE EXISTING ITEM
            |--------------------------------------------------------------------------
            */

            if ($existing_item) {

                $old_quantity =
                    (int)$existing_item['quantity'];


                $new_quantity =
                    $old_quantity + $quantity;


                if ($new_quantity > $stock) {

                    $new_quantity = $stock;
                }


                $update_item_sql = "
                    UPDATE cart_items
                    SET quantity = ?
                    WHERE id = ?
                ";


                $update_item_stmt =
                    mysqli_prepare(
                        $conn,
                        $update_item_sql
                    );


                if ($update_item_stmt) {

                    $existing_item_id =
                        (int)$existing_item['id'];


                    mysqli_stmt_bind_param(
                        $update_item_stmt,
                        "ii",
                        $new_quantity,
                        $existing_item_id
                    );


                    mysqli_stmt_execute(
                        $update_item_stmt
                    );


                    mysqli_stmt_close(
                        $update_item_stmt
                    );
                }


            } else {

                /*
                |--------------------------------------------------------------------------
                | INSERT NEW ITEM
                |--------------------------------------------------------------------------
                */

                $insert_item_sql = "
                    INSERT INTO cart_items
                    (
                        cart_id,
                        product_id,
                        quantity
                    )
                    VALUES
                    (?, ?, ?)
                ";


                $insert_item_stmt =
                    mysqli_prepare(
                        $conn,
                        $insert_item_sql
                    );


                if (!$insert_item_stmt) {

                    continue;
                }


                mysqli_stmt_bind_param(
                    $insert_item_stmt,
                    "iii",
                    $cart_id,
                    $product_id,
                    $quantity
                );


                mysqli_stmt_execute(
                    $insert_item_stmt
                );


                mysqli_stmt_close(
                    $insert_item_stmt
                );
            }
        }


        /*
        |--------------------------------------------------------------------------
        | COMMIT
        |--------------------------------------------------------------------------
        */

        mysqli_commit($conn);


        /*
        |--------------------------------------------------------------------------
        | DELETE GUEST CART COOKIE
        |--------------------------------------------------------------------------
        */

        setcookie(
            'shophub_cart',
            '',
            [
                'expires'  => time() - 3600,
                'path'     => '/',
                'samesite' => 'Lax'
            ]
        );


        unset(
            $_COOKIE['shophub_cart']
        );


    } catch (Exception $e) {

        mysqli_rollback($conn);
    }
}


/*
|--------------------------------------------------------------------------
| LOGIN
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /*
    |--------------------------------------------------------------------------
    | GET FORM DATA
    |--------------------------------------------------------------------------
    */

    $email =
        trim(
            $_POST['email'] ?? ''
        );


    $password =
        $_POST['password'] ?? '';


    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */

    if (
        $email === '' ||
        $password === ''
    ) {

        $error =
            "Please enter your email and password.";


    } elseif (
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $error =
            "Please enter a valid email address.";


    } else {

        /*
        |--------------------------------------------------------------------------
        | GET USER
        |--------------------------------------------------------------------------
        */

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


        $stmt =
            mysqli_prepare(
                $conn,
                $sql
            );


        if (!$stmt) {

            $error =
                "Database error. Please try again.";

        } else {

            mysqli_stmt_bind_param(
                $stmt,
                "s",
                $email
            );


            mysqli_stmt_execute(
                $stmt
            );


            $result =
                mysqli_stmt_get_result(
                    $stmt
                );


            $user =
                mysqli_fetch_assoc(
                    $result
                );


            mysqli_stmt_close(
                $stmt
            );


            /*
            |--------------------------------------------------------------------------
            | USER NOT FOUND
            |--------------------------------------------------------------------------
            */

            if (!$user) {

                $error =
                    "Invalid email or password.";

            } else {

                /*
                |--------------------------------------------------------------------------
                | PASSWORD CHECK
                |--------------------------------------------------------------------------
                */

                $password_valid = false;


                /*
                |--------------------------------------------------------------------------
                | HASHED PASSWORD
                |--------------------------------------------------------------------------
                */

                if (
                    password_get_info(
                        $user['Password']
                    )['algo'] !== 0
                ) {

                    if (
                        password_verify(
                            $password,
                            $user['Password']
                        )
                    ) {

                        $password_valid = true;
                    }
                }


                /*
                |--------------------------------------------------------------------------
                | OLD PLAINTEXT PASSWORD
                |--------------------------------------------------------------------------
                */

                elseif (
                    $password === $user['Password']
                ) {

                    $password_valid = true;


                    /*
                    |--------------------------------------------------------------------------
                    | UPGRADE OLD PASSWORD
                    |--------------------------------------------------------------------------
                    */

                    $new_hashed_password =
                        password_hash(
                            $password,
                            PASSWORD_DEFAULT
                        );


                    $update_sql = "
                        UPDATE users
                        SET Password = ?
                        WHERE id = ?
                    ";


                    $update_stmt =
                        mysqli_prepare(
                            $conn,
                            $update_sql
                        );


                    if ($update_stmt) {

                        $user_id =
                            (int)$user['id'];


                        mysqli_stmt_bind_param(
                            $update_stmt,
                            "si",
                            $new_hashed_password,
                            $user_id
                        );


                        mysqli_stmt_execute(
                            $update_stmt
                        );


                        mysqli_stmt_close(
                            $update_stmt
                        );
                    }
                }


                /*
                |--------------------------------------------------------------------------
                | INVALID PASSWORD
                |--------------------------------------------------------------------------
                */

                if (!$password_valid) {

                    $error =
                        "Invalid email or password.";

                } else {

                    /*
                    |--------------------------------------------------------------------------
                    | LOGIN SUCCESS
                    |--------------------------------------------------------------------------
                    */

                    session_regenerate_id(
                        true
                    );


                    $_SESSION['store_user_id'] =
                        (int)$user['id'];


                    $_SESSION['store_user_name'] =
                        $user['Name'];


                    $_SESSION['store_user_email'] =
                        $user['Email'];


                    $_SESSION['store_user_role'] =
                        $user['role'];


                    /*
                    |--------------------------------------------------------------------------
                    | MOVE GUEST CART
                    |--------------------------------------------------------------------------
                    */

                    migrateGuestCartToDatabase(
                        $conn,
                        (int)$user['id']
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | REDIRECT
                    |--------------------------------------------------------------------------
                    */

                    if ($fromCheckout) {

                        header(
                            "Location: checkout.php"
                        );

                    } else {

                        header(
                            "Location: ../frontend/index.php"
                        );
                    }


                    exit();
                }
            }
        }
    }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Login | FINDO</title>


    <!-- =====================================================
         FONT AWESOME
    ====================================================== -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
    >


    <!-- =====================================================
         NUNITO
    ====================================================== -->

    <link
        href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >


<style>

/* =========================================================
   GLOBAL
========================================================= */

* {
    box-sizing: border-box;
}


html,
body {

    margin: 0;

    padding: 0;

    min-height: 100%;
}


body {

    min-height: 100vh;

    font-family: 'Nunito', sans-serif;

    color: #ffffff;

    background:

        radial-gradient(
            circle at 0% 10%,
            rgba(35, 230, 161, 0.14),
            transparent 30%
        ),

        radial-gradient(
            circle at 100% 90%,
            rgba(35, 230, 161, 0.13),
            transparent 30%
        ),

        #07101f;

    display: flex;

    flex-direction: column;

    align-items: center;

    justify-content: center;

    padding: 30px 20px;

    overflow-x: hidden;

    position: relative;
}


/* =========================================================
   ANIMATED BACKGROUND
========================================================= */

.animated-bg {

    position: fixed;

    inset: 0;

    overflow: hidden;

    pointer-events: none;

    z-index: 0;
}


.glow {

    position: absolute;

    width: 450px;

    height: 450px;

    border-radius: 50%;

    background:

        radial-gradient(
            circle,
            rgba(53, 230, 161, 0.25) 0%,
            rgba(53, 230, 161, 0.12) 30%,
            rgba(53, 230, 161, 0.04) 55%,
            transparent 72%
        );

    filter: blur(40px);

    opacity: 0.8;

    animation:
        glowMove 10s ease-in-out infinite alternate;
}


.glow-1 {

    top: -220px;

    left: -220px;

    animation-delay: 0s;
}


.glow-2 {

    right: -220px;

    bottom: -220px;

    animation-delay: -5s;
}


.glow-3 {

    width: 320px;

    height: 320px;

    top: 48%;

    left: 50%;

    transform:
        translate(-50%, -50%);

    opacity: 0.08;

    animation:
        centerGlow 8s ease-in-out infinite;
}


@keyframes glowMove {

    0% {

        transform:
            translate(0, 0)
            scale(1);

        opacity: 0.45;
    }

    50% {

        transform:
            translate(70px, 50px)
            scale(1.18);

        opacity: 0.85;
    }

    100% {

        transform:
            translate(20px, 100px)
            scale(1.05);

        opacity: 0.55;
    }
}


@keyframes centerGlow {

    0% {

        transform:
            translate(-50%, -50%)
            scale(0.8);

        opacity: 0.03;
    }

    50% {

        transform:
            translate(-50%, -50%)
            scale(1.2);

        opacity: 0.10;
    }

    100% {

        transform:
            translate(-50%, -50%)
            scale(0.8);

        opacity: 0.03;
    }
}


/* =========================================================
   TOP HEADER
========================================================= */

.store-header {

    width: 100%;

    position: relative;

    z-index: 3;

    margin-bottom: 25px;
}


.store-header-inner {

    width: 100%;

    max-width: 1200px;

    margin: auto;

    display: flex;

    align-items: center;

    justify-content: space-between;
}


/* =========================================================
   LOGO
========================================================= */

.store-logo {

    display: flex;

    align-items: center;

    gap: 14px;

    text-decoration: none;
}


.store-logo img {

    width: 55px;

    height: 55px;

    object-fit: cover;

    border-radius: 50%;

    border:
        2px solid
        rgba(53, 230, 161, 0.35);

    box-shadow:
        0 0 25px
        rgba(53, 230, 161, 0.12);
}


.store-logo-text {

    display: flex;

    flex-direction: column;
}


.store-logo-text strong {

    color: #ffffff;

    font-size: 25px;

    font-weight: 800;

    line-height: 1;
}


.store-logo-text span {

    color: #8d9aae;

    font-size: 12px;

    margin-top: 5px;
}


/* =========================================================
   BACK TO STORE TOP
========================================================= */

.top-back {

    display: inline-flex;

    align-items: center;

    gap: 10px;

    padding: 12px 22px;

    border-radius: 9px;

    border:
        1px solid
        rgba(53, 230, 161, 0.25);

    color: #35e6a1;

    text-decoration: none;

    font-size: 13px;

    font-weight: 800;

    transition: 0.25s;

    background:
        rgba(8, 20, 31, 0.35);
}


.top-back:hover {

    color: #ffffff;

    border-color:
        #35e6a1;

    background:
        rgba(53, 230, 161, 0.08);

    transform:
        translateY(-1px);
}


/* =========================================================
   LOGIN CONTAINER
========================================================= */

.login-container {

    width: 100%;

    max-width: 560px;

    position: relative;

    z-index: 2;

    margin: auto;
}


/* =========================================================
   CARD
========================================================= */

.login-card {

    background:
        rgba(9, 20, 35, 0.88);

    border:
        1px solid
        rgba(69, 99, 125, 0.48);

    border-radius: 12px;

    padding: 42px;

    box-shadow:

        0 30px 80px
        rgba(0, 0, 0, 0.48),

        inset 0 1px 0
        rgba(255, 255, 255, 0.025);

    backdrop-filter:
        blur(15px);

    -webkit-backdrop-filter:
        blur(15px);
}


/* =========================================================
   LOGO ICON
========================================================= */

.logo {

    text-align: center;

    margin-bottom: 28px;
}


.logo-icon {

    width: 74px;

    height: 74px;

    margin:
        0 auto 17px;

    border-radius: 50%;

    display: flex;

    align-items: center;

    justify-content: center;

    color: #35e6a1;

    background:
        rgba(53, 230, 161, 0.12);

    box-shadow:
        0 0 30px
        rgba(53, 230, 161, 0.10);

    font-size: 30px;
}


.logo h1 {

    margin: 0;

    color: #ffffff;

    font-size: 30px;

    font-weight: 800;
}


.logo p {

    margin:
        7px 0 0;

    color: #8d9aae;

    font-size: 13px;
}


/* =========================================================
   LOGIN TITLE
========================================================= */

.login-title {

    text-align: center;

    margin-bottom: 27px;
}


.login-title h2 {

    margin: 0;

    color: #ffffff;

    font-size: 25px;

    font-weight: 800;
}


.login-title p {

    margin:
        7px 0 0;

    color: #8d9aae;

    font-size: 13px;
}


/* =========================================================
   ERROR
========================================================= */

.error-message {

    display: flex;

    align-items: center;

    gap: 9px;

    background:
        rgba(231, 76, 60, 0.10);

    border:
        1px solid
        rgba(231, 76, 60, 0.25);

    color: #ff8b82;

    border-radius: 8px;

    padding: 13px 14px;

    font-size: 13px;

    font-weight: 600;

    margin-bottom: 20px;
}


/* =========================================================
   FORM GROUP
========================================================= */

.form-group {

    margin-bottom: 19px;
}


.form-group label {

    display: block;

    margin-bottom: 8px;

    color: #dce4ed;

    font-size: 13px;

    font-weight: 800;
}


/* =========================================================
   INPUT WRAPPER
========================================================= */

.input-wrapper {

    position: relative;
}


.input-wrapper > i {

    position: absolute;

    left: 16px;

    top: 50%;

    transform:
        translateY(-50%);

    color: #35e6a1;

    font-size: 16px;

    pointer-events: none;

    z-index: 2;
}


/* =========================================================
   INPUT
========================================================= */

.form-control {

    width: 100%;

    height: 54px;

    background:
        rgba(12, 25, 42, 0.75);

    border:
        1px solid
        #304159;

    border-radius: 9px;

    color: #ffffff;

    padding:
        0 48px;

    font-family:
        'Nunito',
        sans-serif;

    font-size: 14px;

    outline: none;

    transition:
        0.25s;
}


.form-control::placeholder {

    color: #718096;
}


.form-control:hover {

    border-color:
        #41556f;
}


.form-control:focus {

    border-color:
        #35e6a1;

    box-shadow:
        0 0 0 3px
        rgba(53, 230, 161, 0.08);
}


/* =========================================================
   PASSWORD TOGGLE
========================================================= */

.password-toggle {

    position: absolute;

    right: 14px;

    top: 50%;

    transform:
        translateY(-50%);

    border: none;

    background: transparent;

    color: #8491a3;

    cursor: pointer;

    padding: 5px;

    transition: 0.2s;
}


.password-toggle:hover {

    color:
        #35e6a1;
}


/* =========================================================
   LOGIN BUTTON
========================================================= */

.login-btn {

    width: 100%;

    height: 54px;

    border: none;

    border-radius: 9px;

    background:
        linear-gradient(
            135deg,
            #20c997,
            #35e6a1
        );

    color: #06131a;

    font-family:
        'Nunito',
        sans-serif;

    font-size: 14px;

    font-weight: 800;

    cursor: pointer;

    transition:
        0.25s;

    margin-top: 5px;
}


.login-btn:hover {

    transform:
        translateY(-2px);

    box-shadow:
        0 12px 30px
        rgba(53, 230, 161, 0.22);
}


.login-btn:active {

    transform:
        translateY(0);
}


.login-btn i {

    margin-left: 8px;
}


/* =========================================================
   DIVIDER
========================================================= */

.divider {

    display: flex;

    align-items: center;

    gap: 14px;

    margin:
        27px 0;

    color: #718096;

    font-size: 11px;

    font-weight: 800;
}


.divider::before,
.divider::after {

    content: "";

    flex: 1;

    height: 1px;

    background:
        #26374d;
}


/* =========================================================
   REGISTER
========================================================= */

.register-text {

    text-align: center;

    color: #8d9aae;

    font-size: 13px;
}


.register-text a {

    color: #35e6a1;

    font-weight: 800;

    text-decoration: none;

    margin-left: 4px;

    transition: 0.2s;
}


.register-text a:hover {

    color: #72f3bd;

    text-decoration: underline;
}


/* =========================================================
   BACK TO STORE
========================================================= */

.back-store {

    display: flex;

    align-items: center;

    justify-content: center;

    gap: 8px;

    margin-top: 22px;

    color: #35e6a1;

    text-decoration: none;

    font-size: 13px;

    font-weight: 800;

    transition: 0.2s;
}


.back-store:hover {

    color: #72f3bd;

    transform:
        translateX(-2px);
}


/* =========================================================
   MOBILE
========================================================= */

@media (max-width: 650px) {

    body {

        padding:
            20px 15px;
    }


    .store-header-inner {

        padding:
            0 5px;
    }


    .store-logo-text strong {

        font-size: 20px;
    }


    .store-logo img {

        width: 45px;

        height: 45px;
    }


    .top-back {

        padding:
            10px 14px;

        font-size: 12px;
    }


    .login-card {

        padding:
            30px 22px;

        border-radius: 11px;
    }


    .logo h1 {

        font-size: 26px;
    }


    .login-title h2 {

        font-size: 22px;
    }


    .glow {

        width: 300px;

        height: 300px;
    }
}


@media (max-width: 450px) {

    .store-header-inner {

        align-items:
            flex-start;
    }


    .store-logo-text {

        display: none;
    }


    .top-back {

        padding:
            9px 12px;
    }


    .login-card {

        padding:
            27px 18px;
    }
}

</style>

</head>


<body>


<!-- =====================================================
     ANIMATED BACKGROUND
====================================================== -->

<div class="animated-bg">

    <div class="glow glow-1"></div>

    <div class="glow glow-2"></div>

    <div class="glow glow-3"></div>

</div>


<!-- =====================================================
     STORE HEADER
====================================================== -->

<header class="store-header">

    <div class="store-header-inner">


        <!-- LOGO -->

        <a
            href="../index.php"
            class="store-logo"
        >

            <img
                src="../img/new_logo.jpg"
                alt="FINDO Logo"
            >


            <div class="store-logo-text">

                <strong>
                    FINDO
                </strong>

                <span>
                    Online Shopping
                </span>

            </div>

        </a>


        <!-- BACK TO STORE -->

        <a
            href="../frontend/index.php"
            class="top-back"
        >

            <i class="fa-solid fa-arrow-left"></i>

            Back to Store

        </a>

    </div>

</header>


<!-- =====================================================
     LOGIN
====================================================== -->

<div class="login-container">


    <div class="login-card">


        <!-- =================================================
             LOGIN ICON
        ================================================== -->

        <div class="logo">


            <div class="logo-icon">

                <i class="fa-solid fa-right-to-bracket"></i>

            </div>


            <h1>
                Welcome Back
            </h1>


            <p>
                Sign in to your FINDO account
            </p>


        </div>


        <!-- =================================================
             TITLE
        ================================================== -->

        <div class="login-title">

            <h2>
                Login
            </h2>

            <p>
                Enter your information below
            </p>

        </div>


        <!-- =================================================
             ERROR
        ================================================== -->

        <?php if ($error !== ''): ?>

            <div class="error-message">

                <i
                    class="fa-solid fa-circle-exclamation"
                ></i>

                <span>
                    <?= htmlspecialchars($error) ?>
                </span>

            </div>

        <?php endif; ?>


        <!-- =================================================
             LOGIN FORM
        ================================================== -->

        <form
            method="POST"
            action="<?= htmlspecialchars(
                $_SERVER['PHP_SELF']
            ) ?><?= $fromCheckout ? '?checkout=1' : '' ?>"
            autocomplete="off"
        >


            <!-- EMAIL -->

            <div class="form-group">

                <label for="email">

                    Email Address

                </label>


                <div class="input-wrapper">

                    <i class="fa-solid fa-envelope"></i>


                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-control"
                        placeholder="Enter your email address"
                        value="<?= htmlspecialchars($email) ?>"
                        maxlength="100"
                        autocomplete="email"
                        required
                    >

                </div>

            </div>


            <!-- PASSWORD -->

            <div class="form-group">

                <label for="password">

                    Password

                </label>


                <div class="input-wrapper">

                    <i class="fa-solid fa-lock"></i>


                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        placeholder="Enter your password"
                        autocomplete="current-password"
                        required
                    >


                    <button
                        type="button"
                        class="password-toggle"
                        onclick="togglePassword()"
                        aria-label="Show password"
                    >

                        <i
                            class="fa-solid fa-eye"
                            id="password-icon"
                        ></i>

                    </button>

                </div>

            </div>


            <!-- LOGIN BUTTON -->

            <button
                type="submit"
                class="login-btn"
            >

                Login

                <i
                    class="fa-solid fa-right-to-bracket"
                ></i>

            </button>


        </form>


        <!-- =================================================
             DIVIDER
        ================================================== -->

        <div class="divider">

            <span>
                OR
            </span>

        </div>


        <!-- =================================================
             REGISTER
        ================================================== -->

        <div class="register-text">

            Don't have an account?

            <a href="register.php">

                Create Account

            </a>

        </div>


        <!-- =================================================
             BACK TO STORE
        ================================================== -->

        <a
            href="../frontend/index.php"
            class="back-store"
        >

            <i
                class="fa-solid fa-arrow-left"
            ></i>

            Back to Store

        </a>


    </div>

</div>


<!-- =====================================================
     JAVASCRIPT
====================================================== -->

<script>

function togglePassword() {

    const password =
        document.getElementById('password');


    const icon =
        document.getElementById('password-icon');


    if (!password || !icon) {

        return;
    }


    if (
        password.type === 'password'
    ) {

        password.type = 'text';


        icon.classList.remove(
            'fa-eye'
        );


        icon.classList.add(
            'fa-eye-slash'
        );

    } else {

        password.type = 'password';


        icon.classList.remove(
            'fa-eye-slash'
        );


        icon.classList.add(
            'fa-eye'
        );
    }

}

</script>


</body>

</html>