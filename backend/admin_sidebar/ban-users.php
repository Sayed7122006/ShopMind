
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
| GET USER
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        id,
        Name,
        role,
        is_banned
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
| USER DATA
|--------------------------------------------------------------------------
*/

$name = (string) $user['Name'];

$user_role = (string) $user['role'];

$is_banned = (int) $user['is_banned'];


/*
|--------------------------------------------------------------------------
| BAN ACCOUNT
|--------------------------------------------------------------------------
|
| عندك في قاعدة البيانات is_banned فقط.
|
| 1 = Banned
| 0 = Allowed
|
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $sql = "
        UPDATE users
        SET is_banned = 1
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


    $stmt->bind_param(
        "i",
        $user_id
    );


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

    <title>Ban Account - ShopMind</title>


    <!-- Font Awesome -->

    <link
        href="../vendor/fontawesome-free/css/all.min.css"
        rel="stylesheet"
    >


    <!-- Google Font -->

    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900"
        rel="stylesheet"
    >


    <!-- SB Admin 2 -->

    <link
        href="../css/sb-admin-2.min.css"
        rel="stylesheet"
    >


    <style>

        html,
        body {

            background: #080b18 !important;

            color: #ffffff;

            min-height: 100%;

        }


        body {

            overflow-x: hidden;

        }


        #wrapper {

            background: #080b18 !important;

            min-height: 100vh;

        }


        #content-wrapper {

            background: #080b18 !important;

            min-height: 100vh;

        }


        #content {

            background: #080b18 !important;

        }


        .shophub-content {

            padding: 30px;

            min-height: calc(100vh - 80px);

        }


        /* =========================
           PAGE TITLE
        ========================= */

        .page-title {

            font-size: 26px;

            font-weight: 800;

            color: #ffffff;

            margin-bottom: 5px;

        }


        .page-subtitle {

            color: #9ca3af;

            font-size: 14px;

            margin-bottom: 25px;

        }


        /* =========================
           BAN CARD
        ========================= */

        .ban-card {

            max-width: 600px;

            background: #0d1122;

            border: 1px solid #252b45;

            border-radius: 12px;

            padding: 30px;

        }


        /* =========================
           ACCOUNT INFO
        ========================= */

        .account-name {

            font-size: 18px;

            font-weight: 700;

            color: #ffffff;

            margin-bottom: 8px;

        }


        .account-role {

            color: #9ca3af;

            font-size: 13px;

            margin-bottom: 25px;

        }


        /* =========================
           FORM LABEL
        ========================= */

        .form-label {

            color: #d1d5db;

            font-size: 13px;

            font-weight: 700;

            margin-bottom: 8px;

        }


        /* =========================
           WARNING
        ========================= */

        .ban-warning {

            background: rgba(220, 38, 38, 0.10);

            border: 1px solid rgba(220, 38, 38, 0.25);

            border-radius: 8px;

            padding: 14px 16px;

            color: #fca5a5;

            font-size: 13px;

            line-height: 1.6;

            margin-bottom: 20px;

        }


        /* =========================
           BUTTONS
        ========================= */

        .ban-actions {

            display: flex;

            gap: 10px;

            margin-top: 25px;

        }


        .btn-ban {

            height: 42px;

            padding: 0 20px;

            border: none;

            border-radius: 7px;

            background: #dc2626;

            color: #ffffff;

            font-size: 13px;

            font-weight: 700;

            cursor: pointer;

        }


        .btn-ban:hover {

            background: #b91c1c;

        }


        .btn-cancel {

            height: 42px;

            padding: 0 20px;

            border: 1px solid #252b45;

            border-radius: 7px;

            background: #111626;

            color: #d1d5db;

            font-size: 13px;

            font-weight: 700;

            text-decoration: none;

            display: inline-flex;

            align-items: center;

            justify-content: center;

        }


        .btn-cancel:hover {

            color: #ffffff;

            border-color: #7c3aed;

            text-decoration: none;

        }


    </style>

</head>


<body id="page-top">


<div id="wrapper">


    <!-- SIDEBAR -->

    <?php

    include __DIR__ . '/../includes/sidebar.php';

    ?>


    <!-- CONTENT WRAPPER -->

    <div id="content-wrapper" class="d-flex flex-column">


        <div id="content">


            <!-- TOPBAR -->

            <?php

            include __DIR__ . '/../includes/topbar.php';

            ?>


            <!-- PAGE CONTENT -->

            <div class="shophub-content">


                <div class="page-title">

                    Ban Account

                </div>


                <div class="page-subtitle">

                    Block this account from logging into ShopMind.

                </div>


                <div class="ban-card">


                    <!-- ACCOUNT NAME -->

                    <div class="account-name">

                        <?= htmlspecialchars(
                            $name,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </div>


                    <!-- ACCOUNT ROLE -->

                    <div class="account-role">

                        <?= $user_role === 'vendor'
                            ? 'Vendor'
                            : 'User' ?>

                    </div>


                    <!-- WARNING -->

                    <div class="ban-warning">

                        <i class="fas fa-exclamation-triangle mr-1"></i>

                        This account will be blocked from logging into
                        ShopMind until an administrator unbans it.

                    </div>


                    <!-- BAN FORM -->

                    <form
                        method="POST"
                        onsubmit="return confirmBan();"
                    >


                        <div class="ban-actions">


                            <button
                                type="submit"
                                class="btn-ban"
                            >

                                <i class="fas fa-ban mr-1"></i>

                                Ban Account

                            </button>


                            <a
                                href="users-management.php"
                                class="btn-cancel"
                            >

                                Cancel

                            </a>


                        </div>


                    </form>


                </div>


            </div>


        </div>


    </div>


</div>


<script>

function confirmBan() {

    return confirm(
        'Are you sure you want to ban this account?'
    );

}

</script>


</body>

</html>

