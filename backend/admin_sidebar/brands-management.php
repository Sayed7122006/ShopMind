
<?php

session_start();


/*
|--------------------------------------------------------------------------
| DATABASE CONNECTION
|--------------------------------------------------------------------------
*/

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
| CREATE BRANDS TABLE IF NOT EXISTS
|--------------------------------------------------------------------------
*/

$createTableSQL = "
    CREATE TABLE IF NOT EXISTS brands (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
    ENGINE=InnoDB
    DEFAULT CHARSET=utf8mb4
";

if (!$conn->query($createTableSQL)) {

    die(
        'Database Error: ' .
        htmlspecialchars(
            $conn->error,
            ENT_QUOTES,
            'UTF-8'
        )
    );

}


/*
|--------------------------------------------------------------------------
| MESSAGE
|--------------------------------------------------------------------------
*/

$message = '';
$message_type = '';


/*
|--------------------------------------------------------------------------
| HANDLE POST
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';


    /*
    |--------------------------------------------------------------------------
    | ADD BRAND
    |--------------------------------------------------------------------------
    */

    if ($action === 'add') {

        $name = trim($_POST['name'] ?? '');

        if ($name === '') {

            $message = 'Please enter a brand name.';
            $message_type = 'danger';

        } elseif (mb_strlen($name) > 100) {

            $message = 'Brand name cannot exceed 100 characters.';
            $message_type = 'danger';

        } else {

            $stmt = $conn->prepare(
                "INSERT INTO brands (name) VALUES (?)"
            );

            if (!$stmt) {

                $message = 'Database Error: ' . $conn->error;
                $message_type = 'danger';

            } else {

                $stmt->bind_param("s", $name);

                if ($stmt->execute()) {

                    $message = 'Brand added successfully.';
                    $message_type = 'success';

                } else {

                    if ($stmt->errno === 1062) {

                        $message = 'This brand already exists.';
                        $message_type = 'warning';

                    } else {

                        $message = 'Could not add brand.';
                        $message_type = 'danger';

                    }

                }

                $stmt->close();

            }

        }

    }


    /*
    |--------------------------------------------------------------------------
    | DELETE BRAND
    |--------------------------------------------------------------------------
    */

    elseif ($action === 'delete') {

        $brand_id = isset($_POST['id'])
            ? (int) $_POST['id']
            : 0;


        if ($brand_id <= 0) {

            $message = 'Invalid brand ID.';
            $message_type = 'danger';

        } else {

            $stmt = $conn->prepare(
                "DELETE FROM brands WHERE id = ?"
            );

            if (!$stmt) {

                $message = 'Database Error: ' . $conn->error;
                $message_type = 'danger';

            } else {

                $stmt->bind_param("i", $brand_id);

                if ($stmt->execute()) {

                    $message = 'Brand deleted successfully.';
                    $message_type = 'success';

                } else {

                    $message = 'Could not delete brand.';
                    $message_type = 'danger';

                }

                $stmt->close();

            }

        }

    }

}


/*
|--------------------------------------------------------------------------
| GET BRANDS
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        id,
        name,
        created_at
    FROM brands
    ORDER BY name ASC
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

$brands = [];

while ($row = $result->fetch_assoc()) {

    $brands[] = $row;

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

    <title>Brands Management - ShopMind</title>


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


        /* =====================================================
           MAIN CONTENT
        ====================================================== */

        .shophub-content {

            padding: 30px;

            background: #080b18;

            min-height: calc(100vh - 80px);

        }


        /* =====================================================
           PAGE HEADER
        ====================================================== */

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


        /* =====================================================
           CARD
        ====================================================== */

        .shophub-card {

            background: #0d1122;

            border: 1px solid #252b45;

            border-radius: 12px;

            overflow: hidden;

            width: 100%;

            margin-bottom: 25px;

        }


        /* =====================================================
           CARD HEADER
        ====================================================== */

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


        /* =====================================================
           ADD BRAND FORM
        ====================================================== */

        .brand-form {

            padding: 25px;

            display: flex;

            align-items: center;

            gap: 12px;

        }


        .brand-input {

            flex: 1;

            height: 44px;

            background: #111626 !important;

            border: 1px solid #252b45 !important;

            border-radius: 7px;

            color: #ffffff !important;

            padding: 0 14px;

            outline: none;

        }


        .brand-input::placeholder {

            color: #6b7280;

        }


        .brand-input:focus {

            border-color: #7c3aed !important;

            box-shadow: none !important;

        }


        .btn-add-brand {

            height: 44px;

            padding: 0 20px;

            border: none;

            border-radius: 7px;

            background: linear-gradient(
                135deg,
                #7c3aed,
                #4f5df5
            );

            color: #ffffff;

            font-size: 13px;

            font-weight: 700;

            cursor: pointer;

            white-space: nowrap;

        }


        .btn-add-brand:hover {

            opacity: 0.92;

        }


        /* =====================================================
           TABLE WRAPPER
        ====================================================== */

        .brands-table-wrapper {

            width: 100%;

            overflow-x: auto;

        }


        /* =====================================================
           TABLE
        ====================================================== */

        .shophub-table {

            width: 100%;

            border-collapse: collapse;

            margin: 0;

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


        /* =====================================================
           COLUMNS
        ====================================================== */

        .shophub-table th:nth-child(1),
        .shophub-table td:nth-child(1) {

            width: 10%;

        }


        .shophub-table th:nth-child(2),
        .shophub-table td:nth-child(2) {

            width: 50%;

        }


        .shophub-table th:nth-child(3),
        .shophub-table td:nth-child(3) {

            width: 25%;

        }


        .shophub-table th:nth-child(4),
        .shophub-table td:nth-child(4) {

            width: 15%;

            text-align: center;

        }


        /* =====================================================
           DELETE BUTTON
        ====================================================== */

        .btn-delete {

            width: 36px;

            height: 36px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            background: #111626 !important;

            border: 1px solid #252b45 !important;

            border-radius: 8px;

            color: #f87171 !important;

            cursor: pointer;

        }


        .btn-delete:hover {

            background: rgba(
                220,
                38,
                38,
                0.12
            ) !important;

            border-color: #dc2626 !important;

        }


        /* =====================================================
           ALERT
        ====================================================== */

        .shophub-alert {

            border-radius: 8px;

            padding: 13px 16px;

            margin-bottom: 20px;

            font-size: 13px;

            border: 1px solid;

        }


        .alert-success {

            background: rgba(
                34,
                197,
                94,
                0.10
            );

            border-color: rgba(
                34,
                197,
                94,
                0.30
            );

            color: #86efac;

        }


        .alert-danger {

            background: rgba(
                239,
                68,
                68,
                0.10
            );

            border-color: rgba(
                239,
                68,
                68,
                0.30
            );

            color: #fca5a5;

        }


        .alert-warning {

            background: rgba(
                245,
                158,
                11,
                0.10
            );

            border-color: rgba(
                245,
                158,
                11,
                0.30
            );

            color: #fcd34d;

        }


        /* =====================================================
           EMPTY STATE
        ====================================================== */

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


        /* =====================================================
           RESPONSIVE
        ====================================================== */

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


            .brand-form {

                flex-direction: column;

                align-items: stretch;

            }


            .btn-add-brand {

                width: 100%;

            }


            .shophub-table {

                min-width: 700px;

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

            } else {

                die(
                    'Topbar file not found: ' .
                    htmlspecialchars(
                        $topbar,
                        ENT_QUOTES,
                        'UTF-8'
                    )
                );

            }

            ?>


            <!-- =================================================
                 MAIN CONTENT
            ================================================== -->

            <div class="shophub-content">


                <!-- PAGE HEADER -->

                <div class="page-title">

                    Brands Management

                </div>


                <div class="page-subtitle">

                    Add, view and manage all product brands.

                </div>


                <?php if ($message !== ''): ?>

                    <div
                        class="shophub-alert alert-<?= htmlspecialchars(
                            $message_type,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    >

                        <?= htmlspecialchars(
                            $message,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </div>

                <?php endif; ?>


                <!-- =================================================
                     ADD BRAND CARD
                ================================================== -->

                <div class="shophub-card">


                    <div class="shophub-card-header">

                        <h6 class="shophub-card-title">

                            Add Brand

                        </h6>

                    </div>


                    <form
                        method="POST"
                        class="brand-form"
                    >

                        <input
                            type="text"
                            name="name"
                            class="brand-input"
                            placeholder="Enter brand name"
                            maxlength="100"
                            required
                        >


                        <input
                            type="hidden"
                            name="action"
                            value="add"
                        >


                        <button
                            type="submit"
                            class="btn-add-brand"
                        >

                            <i class="fas fa-plus mr-1"></i>

                            Add Brand

                        </button>

                    </form>


                </div>


                <!-- =================================================
                     BRANDS TABLE
                ================================================== -->

                <div class="shophub-card">


                    <div class="shophub-card-header">

                        <h6 class="shophub-card-title">

                            All Brands

                        </h6>

                    </div>


                    <div class="brands-table-wrapper">


                        <table class="shophub-table">


                            <thead>

                                <tr>

                                    <th>
                                        ID
                                    </th>

                                    <th>
                                        Brand Name
                                    </th>

                                    <th>
                                        Created
                                    </th>

                                    <th>
                                        Action
                                    </th>

                                </tr>

                            </thead>


                            <tbody>


                            <?php if (!empty($brands)): ?>


                                <?php foreach ($brands as $brand): ?>


                                    <tr>


                                        <td>

                                            <?= (int)$brand['id'] ?>

                                        </td>


                                        <td>

                                            <?= htmlspecialchars(
                                                $brand['name'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>

                                        </td>


                                        <td>

                                            <?= htmlspecialchars(
                                                $brand['created_at'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>

                                        </td>


                                        <td>


                                            <form
                                                method="POST"
                                                onsubmit="return confirm('Are you sure you want to delete this brand?');"
                                                style="display:inline;"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="action"
                                                    value="delete"
                                                >


                                                <input
                                                    type="hidden"
                                                    name="id"
                                                    value="<?= (int)$brand['id'] ?>"
                                                >


                                                <button
                                                    type="submit"
                                                    class="btn-delete"
                                                    title="Delete Brand"
                                                >

                                                    <i class="fas fa-trash"></i>

                                                </button>

                                            </form>


                                        </td>


                                    </tr>


                                <?php endforeach; ?>


                            <?php else: ?>


                                <tr>

                                    <td
                                        colspan="4"
                                        class="empty-state"
                                    >

                                        <i class="fas fa-tags"></i>

                                        <p>
                                            No brands found.
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


</body>

</html>

