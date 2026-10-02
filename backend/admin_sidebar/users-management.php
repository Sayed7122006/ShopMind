
<?php

session_start();




$db_connection = __DIR__ . '/../fun/db_connection.php';

if (!file_exists($db_connection)) {

    die(
        'Database connection file not found: ' .
        htmlspecialchars(
            $db_connection,
            ENT_QUOTES,
            'UTF-8'
        )
    );

}

require_once $db_connection;


/*
|--------------------------------------------------------------------------
| CHECK DATABASE CONNECTION
|--------------------------------------------------------------------------
*/

if (
    !isset($conn) ||
    !($conn instanceof mysqli)
) {

    die('Database connection is not available.');

}


/*
|--------------------------------------------------------------------------
| ADMIN CHECK
|--------------------------------------------------------------------------
|
| check.php موجود داخل:
|
| ShopMind-main_dashbord/fun/check.php
|
*/

$check_file = __DIR__ . '/../fun/check.php';

if (file_exists($check_file)) {

    require_once $check_file;

    if (function_exists('checkRole')) {

        checkRole(['admin']);

    }

}


/*
|--------------------------------------------------------------------------
| GET USERS
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        id,
        Name,
        Email,
        role,
        is_banned
    FROM users
    WHERE role != 'admin'
    ORDER BY id DESC
";


$result = $conn->query($sql);

if (!$result) {

    die(
        'Database Error: ' .
        htmlspecialchars(
            $conn->error,
            ENT_QUOTES,
            'UTF-8'
        )
    );

}


$users = [];


while ($row = $result->fetch_assoc()) {

    $users[] = [

        'id' => (int)$row['id'],

        'name' => (string)$row['Name'],

        'email' => (string)$row['Email'],

        'role' => (string)$row['role'],

        'is_banned' => (int)$row['is_banned']

    ];

}


$result->free();

?>


<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="utf-8">

    <meta
        http-equiv="X-UA-Compatible"
        content="IE=edge"
    >

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1, shrink-to-fit=no"
    >

    <title>Users Management - ShopMind</title>


    <!-- =====================================================
         FONT AWESOME
    ====================================================== -->

    <link
        href="../vendor/fontawesome-free/css/all.min.css"
        rel="stylesheet"
        type="text/css"
    >


    <!-- =====================================================
         GOOGLE FONT
    ====================================================== -->

    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900"
        rel="stylesheet"
    >


    <!-- =====================================================
         SB ADMIN 2
    ====================================================== -->

    <link
        href="../css/sb-admin-2.min.css"
        rel="stylesheet"
    >


    <style>

        html,
        body {

            background: #080b18 !important;

            color: #ffffff;

            margin: 0;

            padding: 0;

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


        /* =========================
           MAIN CONTENT
        ========================= */

        .shophub-content {

            padding: 30px;

            background: #080b18;

            min-height: calc(100vh - 80px);

        }


        /* =========================
           PAGE HEADER
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
           CARD
        ========================= */

        .shophub-card {

            background: #0d1122;

            border: 1px solid #252b45;

            border-radius: 12px;

            overflow: visible;

            width: 100%;

        }


        /* =========================
           CARD HEADER
        ========================= */

        .shophub-card-header {

            padding: 20px 25px;

            border-bottom: 1px solid #252b45;

            background: #0d1122;

            display: flex;

            align-items: center;

            justify-content: space-between;

        }


        .shophub-card-title {

            margin: 0;

            font-size: 18px;

            font-weight: 700;

            color: #ffffff;

        }


        /* =========================
           TABLE WRAPPER
        ========================= */

        .users-table-wrapper {

            width: 100%;

            overflow-x: auto;

        }


        /* =========================
           TABLE
        ========================= */

        .shophub-table {

            width: 100%;

            border-collapse: collapse;

            margin: 0;

            table-layout: fixed;

        }


        .shophub-table thead {

            background: #111626;

        }


        .shophub-table th {

            color: #aeb6cc;

            font-size: 13px;

            font-weight: 700;

            text-transform: uppercase;

            letter-spacing: 0.4px;

            padding: 17px 25px;

            border-bottom: 1px solid #252b45;

            white-space: nowrap;

        }


        .shophub-table td {

            padding: 20px 25px;

            color: #ffffff;

            font-size: 14px;

            border-bottom: 1px solid #252b45;

            vertical-align: middle;

        }


        .shophub-table tbody tr {

            transition: 0.2s ease;

        }


        .shophub-table tbody tr:hover {

            background: #111626;

        }


        .shophub-table tbody tr:last-child td {

            border-bottom: none;

        }


        /* =========================
           COLUMN WIDTHS
        ========================= */

        .shophub-table th:nth-child(1),
        .shophub-table td:nth-child(1) {

            width: 25%;

        }


        .shophub-table th:nth-child(2),
        .shophub-table td:nth-child(2) {

            width: 32%;

        }


        .shophub-table th:nth-child(3),
        .shophub-table td:nth-child(3) {

            width: 15%;

            text-align: center;

        }


        .shophub-table th:nth-child(4),
        .shophub-table td:nth-child(4) {

            width: 15%;

            text-align: center;

        }


        .shophub-table th:nth-child(5),
        .shophub-table td:nth-child(5) {

            width: 13%;

            text-align: center;

        }


        /* =========================
           ROLE BADGE
        ========================= */

        .user-role-badge {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            min-width: 65px;

            height: 30px;

            padding: 0 12px;

            border-radius: 20px;

            font-size: 12px;

            font-weight: 700;

        }


        .user-role {

            background: rgba(
                79,
                109,
                245,
                0.13
            );

            color: #8fa4ff;

        }


        .vendor-role {

            background: rgba(
                124,
                58,
                237,
                0.15
            );

            color: #b18cff;

        }


        /* =========================
           LOGIN STATUS
        ========================= */

        .login-status {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 7px;

            font-size: 13px;

            font-weight: 700;

        }


        .login-status i {

            font-size: 7px;

        }


        .login-allowed {

            color: #4ade80;

        }


        .login-banned {

            color: #f87171;

        }


        /* =========================
           ACTION
        ========================= */

        .user-action {

            position: relative;

            display: inline-block;

        }


        .user-action-btn {

            width: 36px;

            height: 36px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            background: #111626 !important;

            border: 1px solid #252b45 !important;

            border-radius: 8px !important;

            color: #d1d5db !important;

            cursor: pointer;

            outline: none !important;

            box-shadow: none !important;

        }


        .user-action-btn:hover {

            border-color: #7c3aed !important;

            color: #ffffff !important;

        }


        .user-action-menu {

            position: absolute;

            top: 42px;

            right: 0;

            z-index: 9999;

            width: 190px;

            padding: 5px 0;

            background: #111626 !important;

            border: 1px solid #252b45 !important;

            border-radius: 8px;

            box-shadow:
                0 12px 30px rgba(
                    0,
                    0,
                    0,
                    0.55
                );

            display: none;

        }


        .user-action-menu.show {

            display: block !important;

        }


        .user-action-menu a {

            display: flex;

            align-items: center;

            gap: 10px;

            min-height: 40px;

            padding: 0 13px;

            color: #e5e7eb !important;

            background: transparent !important;

            text-decoration: none !important;

            font-size: 13px;

            font-weight: 600;

        }


        .user-action-menu a:hover {

            background: #1a2035 !important;

            color: #ffffff !important;

        }


        .user-action-menu i {

            width: 17px;

            text-align: center;

        }


        .update-vendor-link i {

            color: #8b5cf6;

        }


        .update-user-link i {

            color: #60a5fa;

        }


        .ban-users-link i {

            color: #f87171;

        }


        .unban-users-link i {

            color: #4ade80;

        }


        /* =========================
           EMPTY STATE
        ========================= */

        .empty-state {

            text-align: center;

            padding: 60px 20px !important;

            color: #8b93a7 !important;

        }


        .empty-state i {

            display: block;

            font-size: 42px;

            margin-bottom: 15px;

            color: #4f5670;

        }


        .empty-state p {

            margin: 0;

            font-size: 14px;

        }


        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 992px) {

            .shophub-content {

                padding: 25px 20px;

            }

        }


        @media (max-width: 768px) {

            .shophub-content {

                padding: 20px 15px;

            }


            .page-title {

                font-size: 22px;

            }


            .page-subtitle {

                font-size: 13px;

            }


            .shophub-card-header {

                padding: 18px;

            }


            .shophub-table {

                min-width: 750px;

            }


            .shophub-table th,
            .shophub-table td {

                padding: 15px;

            }

        }

    </style>

</head>


<body id="page-top">


<div id="wrapper">


    <!-- =====================================================
         SIDEBAR
    ====================================================== -->

    <?php

    $sidebar = __DIR__ . '/../includes/sidebar.php';

    if (file_exists($sidebar)) {

        include $sidebar;

    } else {

        die(
            'Sidebar file not found: ' .
            htmlspecialchars(
                $sidebar,
                ENT_QUOTES,
                'UTF-8'
            )
        );

    }

    ?>


    <!-- =====================================================
         CONTENT WRAPPER
    ====================================================== -->

    <div id="content-wrapper" class="d-flex flex-column">


        <div id="content">


            <!-- =================================================
                 TOPBAR
            ================================================== -->

            <?php

            $topbar = __DIR__ . '/../includes/topbar.php';

            if (file_exists($topbar)) {

                include $topbar;

            }

            ?>


            <!-- =================================================
                 MAIN CONTENT
            ================================================== -->

            <div class="shophub-content">


                <!-- PAGE HEADER -->

                <div class="page-title">

                    Users Management

                </div>


                <div class="page-subtitle">

                    View and manage all customers and vendors.

                </div>


                <!-- USERS CARD -->

                <div class="shophub-card">


                    <!-- CARD HEADER -->

                    <div class="shophub-card-header">

                        <h6 class="shophub-card-title">

                            All Users

                        </h6>

                    </div>


                    <!-- TABLE -->

                    <div class="users-table-wrapper">

                        <table class="shophub-table">

                            <thead>

                                <tr>

                                    <th>
                                        User Name
                                    </th>

                                    <th>
                                        Email
                                    </th>

                                    <th>
                                        Role
                                    </th>

                                    <th>
                                        Login
                                    </th>

                                    <th>
                                        Action
                                    </th>

                                </tr>

                            </thead>


                            <tbody>


                            <?php if (!empty($users)): ?>


                                <?php foreach ($users as $user): ?>


                                    <?php

                                    $is_banned =
                                        $user['is_banned'] === 1;

                                    ?>


                                    <tr>


                                        <!-- USER NAME -->

                                        <td>

                                            <?= htmlspecialchars(
                                                $user['name'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>

                                        </td>


                                        <!-- EMAIL -->

                                        <td>

                                            <?= htmlspecialchars(
                                                $user['email'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>

                                        </td>


                                        <!-- ROLE -->

                                        <td>

                                            <?php if (
                                                $user['role'] === 'vendor'
                                            ): ?>

                                                <span class="user-role-badge vendor-role">

                                                    Vendor

                                                </span>

                                            <?php else: ?>

                                                <span class="user-role-badge user-role">

                                                    User

                                                </span>

                                            <?php endif; ?>

                                        </td>


                                        <!-- LOGIN -->

                                        <td>

                                            <?php if ($is_banned): ?>

                                                <span class="login-status login-banned">

                                                    <i class="fas fa-circle"></i>

                                                    Banned

                                                </span>

                                            <?php else: ?>

                                                <span class="login-status login-allowed">

                                                    <i class="fas fa-circle"></i>

                                                    Allowed

                                                </span>

                                            <?php endif; ?>

                                        </td>


                                        <!-- ACTION -->

                                        <td>

                                            <div class="user-action">


                                                <button
                                                    type="button"
                                                    class="user-action-btn"
                                                    onclick="toggleUserAction(this)"
                                                >

                                                    <i class="fas fa-chevron-down"></i>

                                                </button>


                                                <div class="user-action-menu">


                                                    <?php if ($is_banned): ?>


                                                        <!-- UNBAN -->

                                                        <a
                                                            href="unban-users.php?id=<?= (int)$user['id'] ?>"
                                                            class="unban-users-link"
                                                            onclick="return confirm('Are you sure you want to unban this account?');"
                                                        >

                                                            <i class="fas fa-unlock"></i>

                                                            Unban Account

                                                        </a>


                                                    <?php else: ?>


                                                        <?php if (
                                                            $user['role'] === 'customer'
                                                        ): ?>


                                                            <!-- UPDATE VENDOR -->

                                                            <a
                                                                href="update-vendor.php?id=<?= (int)$user['id'] ?>"
                                                                class="update-vendor-link"
                                                                onclick="return confirm('Are you sure you want to make this user a vendor?');"
                                                            >

                                                                <i class="fas fa-store"></i>

                                                                Update Vendor

                                                            </a>


                                                            <!-- BAN USER -->

                                                            <a
                                                                href="ban-users.php?id=<?= (int)$user['id'] ?>"
                                                                class="ban-users-link"
                                                                onclick="return confirm('Are you sure you want to ban this user?');"
                                                            >

                                                                <i class="fas fa-ban"></i>

                                                                Ban User

                                                            </a>


                                                        <?php else: ?>


                                                            <!-- UPDATE USER -->

                                                            <a
                                                                href="update-user.php?id=<?= (int)$user['id'] ?>"
                                                                class="update-user-link"
                                                                onclick="return confirm('Are you sure you want to change this vendor back to a user?');"
                                                            >

                                                                <i class="fas fa-user"></i>

                                                                Update User

                                                            </a>


                                                            <!-- BAN VENDOR -->

                                                            <a
                                                                href="ban-users.php?id=<?= (int)$user['id'] ?>"
                                                                class="ban-users-link"
                                                                onclick="return confirm('Are you sure you want to ban this vendor?');"
                                                            >

                                                                <i class="fas fa-ban"></i>

                                                                Ban Vendor

                                                            </a>


                                                        <?php endif; ?>


                                                    <?php endif; ?>


                                                </div>

                                            </div>

                                        </td>


                                    </tr>


                                <?php endforeach; ?>


                            <?php else: ?>


                                <tr>

                                    <td
                                        colspan="5"
                                        class="empty-state"
                                    >

                                        <i class="fas fa-users"></i>

                                        <p>
                                            No users found.
                                        </p>

                                    </td>

                                </tr>


                            <?php endif; ?>


                            </tbody>

                        </table>

                    </div>


                </div>


            </div>


        </div>


    </div>


</div>


<!-- =========================================================
     JAVASCRIPT
========================================================== -->

<script src="../vendor/jquery/jquery.min.js"></script>

<script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

<script src="../vendor/jquery-easing/jquery.easing.min.js"></script>

<script src="../js/sb-admin-2.min.js"></script>


<script>

function toggleUserAction(button) {

    const menu = button.nextElementSibling;


    document
        .querySelectorAll('.user-action-menu')
        .forEach(function(item) {

            if (item !== menu) {

                item.classList.remove('show');

            }

        });


    menu.classList.toggle('show');

}


/*
|--------------------------------------------------------------------------
| CLOSE ACTION MENU
|--------------------------------------------------------------------------
*/

document.addEventListener(
    'click',
    function(event) {

        if (!event.target.closest('.user-action')) {

            document
                .querySelectorAll('.user-action-menu')
                .forEach(function(menu) {

                    menu.classList.remove('show');

                });

        }

    }
);

</script>


</body>

</html>

