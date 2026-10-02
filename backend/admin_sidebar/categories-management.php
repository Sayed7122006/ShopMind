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

if (!isset($conn) || !($conn instanceof mysqli)) {

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
| EXTRA ADMIN SAFETY CHECK
|--------------------------------------------------------------------------
*/

if (($_SESSION['role'] ?? '') !== 'admin') {

    header('Location: ../account/login.php');

    exit;

}


/*
|--------------------------------------------------------------------------
| HELPER
|--------------------------------------------------------------------------
*/

function h(mixed $value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}


/*
|--------------------------------------------------------------------------
| CREATE CATEGORIES TABLE IF NOT EXISTS
|--------------------------------------------------------------------------
*/

$createTableSql = "

    CREATE TABLE IF NOT EXISTS categories (

        id INT AUTO_INCREMENT PRIMARY KEY,

        name VARCHAR(100) NOT NULL UNIQUE,

        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP

    )

    ENGINE=InnoDB

    DEFAULT CHARSET=utf8mb4

";


if (!$conn->query($createTableSql)) {

    die(
        'Database Error: ' .
        h($conn->error)
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
| ADD / DELETE CATEGORY
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';


    /*
    |--------------------------------------------------------------------------
    | ADD CATEGORY
    |--------------------------------------------------------------------------
    */

    if ($action === 'add') {

        $name = trim(
            $_POST['name'] ?? ''
        );


        if ($name === '') {

            $message = 'Please enter a category name.';

            $message_type = 'error';

        }

        elseif (mb_strlen($name) > 100) {

            $message =
                'Category name cannot exceed 100 characters.';

            $message_type = 'error';

        }

        else {

            $stmt = $conn->prepare(
                "INSERT INTO categories (name) VALUES (?)"
            );


            if (!$stmt) {

                $message =
                    'Database error: ' . $conn->error;

                $message_type = 'error';

            }

            else {

                $stmt->bind_param(
                    's',
                    $name
                );


                if ($stmt->execute()) {

                    $message =
                        'Category added successfully.';

                    $message_type = 'success';

                }

                else {

                    if ($stmt->errno === 1062) {

                        $message =
                            'This category already exists.';

                    }

                    else {

                        $message =
                            'Unable to add this category.';

                    }

                    $message_type = 'error';

                }


                $stmt->close();

            }

        }

    }


    /*
    |--------------------------------------------------------------------------
    | DELETE CATEGORY
    |--------------------------------------------------------------------------
    */

    elseif ($action === 'delete') {

        $category_id =
            (int) ($_POST['id'] ?? 0);


        if ($category_id > 0) {

            $stmt = $conn->prepare(
                "DELETE FROM categories WHERE id = ?"
            );


            if (!$stmt) {

                $message =
                    'Database error: ' . $conn->error;

                $message_type = 'error';

            }

            else {

                $stmt->bind_param(
                    'i',
                    $category_id
                );


                if ($stmt->execute()) {

                    $message =
                        'Category deleted successfully.';

                    $message_type = 'success';

                }

                else {

                    $message =
                        'Unable to delete this category.';

                    $message_type = 'error';

                }


                $stmt->close();

            }

        }

    }

}


/*
|--------------------------------------------------------------------------
| GET CATEGORIES
|--------------------------------------------------------------------------
*/

$result = $conn->query(
    "
    SELECT
        id,
        name,
        created_at
    FROM categories
    ORDER BY name ASC
    "
);


if (!$result) {

    die(
        'Database Error: ' .
        h($conn->error)
    );

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

    <title>
        Categories Management - ShopMind
    </title>


    <!-- Font Awesome -->

    <link
        rel="stylesheet"
        href="../vendor/fontawesome-free/css/all.min.css"
    >


    <!-- SB Admin -->

    <link
        rel="stylesheet"
        href="../css/sb-admin-2.min.css"
    >


    <!-- Google Font -->

    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900"
        rel="stylesheet"
    >


    <style>

        /* =====================================================
           GLOBAL
        ===================================================== */

        html,
        body {

            margin: 0;

            padding: 0;

            width: 100%;

            min-height: 100%;

            background: #080b18 !important;

            color: #ffffff;

        }


        body {

            overflow-x: hidden;

        }


        #wrapper {

            width: 100%;

            min-height: 100vh;

            background: #080b18 !important;

        }


        #content-wrapper {

            width: 100%;

            min-height: 100vh;

            background: #080b18 !important;

        }


        #content {

            width: 100%;

            min-height: 100vh;

            background: #080b18 !important;

        }


        /* =====================================================
           MAIN PAGE
        ===================================================== */

        .shopmind-content {

            width: 100%;

            box-sizing: border-box;

            padding: 30px 35px 50px 35px;

            background: #080b18;

        }


        /* =====================================================
           PAGE HEADER
        ===================================================== */

        .shopmind-title {

            margin: 0;

            color: #ffffff;

            font-size: 27px;

            font-weight: 800;

            line-height: 1.3;

        }


        .shopmind-subtitle {

            margin-top: 6px;

            margin-bottom: 28px;

            color: #9ca3af;

            font-size: 14px;

            font-weight: 500;

        }


        /* =====================================================
           CARDS
        ===================================================== */

        .shopmind-card {

            width: 100%;

            box-sizing: border-box;

            margin-bottom: 22px;

            background: #0d1122;

            border: 1px solid #252b45;

            border-radius: 13px;

            overflow: hidden;

        }


        /* =====================================================
           CARD HEADER
        ===================================================== */

        .shopmind-card-header {

            width: 100%;

            box-sizing: border-box;

            padding: 19px 24px;

            background: #0d1122;

            border-bottom: 1px solid #252b45;

        }


        .shopmind-card-title {

            margin: 0;

            color: #ffffff;

            font-size: 18px;

            font-weight: 700;

        }


        /* =====================================================
           ADD CATEGORY FORM
        ===================================================== */

        .category-form {

            width: 100%;

            box-sizing: border-box;

            display: flex;

            align-items: center;

            gap: 12px;

            padding: 20px 24px;

        }


        .category-input {

            flex: 1;

            min-width: 0;

            height: 44px;

            padding: 0 15px;

            background: #111626 !important;

            border: 1px solid #303754 !important;

            border-radius: 8px;

            color: #ffffff !important;

            font-size: 14px;

            outline: none;

            transition: .2s ease;

        }


        .category-input::placeholder {

            color: #737b91;

        }


        .category-input:focus {

            border-color: #7c3aed !important;

            box-shadow:
                0 0 0 2px rgba(
                    124,
                    58,
                    237,
                    .12
                );

        }


        /* =====================================================
           ADD BUTTON
        ===================================================== */

        .add-category-btn {

            height: 44px;

            padding: 0 20px;

            border: none;

            border-radius: 8px;

            background:
                linear-gradient(
                    135deg,
                    #7c3aed,
                    #4f6df5
                );

            color: #ffffff;

            font-size: 13px;

            font-weight: 700;

            white-space: nowrap;

            cursor: pointer;

            transition: .2s ease;

        }


        .add-category-btn:hover {

            transform: translateY(-1px);

            box-shadow:
                0 8px 20px rgba(
                    79,
                    109,
                    245,
                    .25
                );

        }


        .add-category-btn i {

            margin-right: 7px;

        }


        /* =====================================================
           ALERT
        ===================================================== */

        .shopmind-message {

            margin: 0 24px 18px;

            padding: 12px 15px;

            border-radius: 8px;

            font-size: 13px;

            font-weight: 600;

        }


        .shopmind-success {

            background:
                rgba(
                    74,
                    222,
                    128,
                    .10
                );

            border: 1px solid
                rgba(
                    74,
                    222,
                    128,
                    .25
                );

            color: #4ade80;

        }


        .shopmind-error {

            background:
                rgba(
                    248,
                    113,
                    113,
                    .10
                );

            border: 1px solid
                rgba(
                    248,
                    113,
                    113,
                    .25
                );

            color: #f87171;

        }


        /* =====================================================
           TABLE WRAPPER
        ===================================================== */

        .categories-table-wrapper {

            width: 100%;

            overflow-x: auto;

        }


        /* =====================================================
           TABLE
        ===================================================== */

        .shopmind-table {

            width: 100%;

            border-collapse: collapse;

            table-layout: fixed;

        }


        .shopmind-table thead {

            background: #111626;

        }


        .shopmind-table th {

            padding: 16px 24px;

            color: #aeb6cc;

            font-size: 12px;

            font-weight: 700;

            text-align: left;

            text-transform: uppercase;

            letter-spacing: .4px;

            border-bottom: 1px solid #252b45;

        }


        .shopmind-table td {

            padding: 18px 24px;

            color: #ffffff;

            font-size: 14px;

            border-bottom: 1px solid #252b45;

            vertical-align: middle;

        }


        .shopmind-table tbody tr {

            transition: .2s ease;

        }


        .shopmind-table tbody tr:hover {

            background: #111626;

        }


        .shopmind-table tbody tr:last-child td {

            border-bottom: none;

        }


        /* =====================================================
           COLUMN WIDTHS
        ===================================================== */

        .shopmind-table th:nth-child(1),
        .shopmind-table td:nth-child(1) {

            width: 45%;

        }


        .shopmind-table th:nth-child(2),
        .shopmind-table td:nth-child(2) {

            width: 30%;

        }


        .shopmind-table th:nth-child(3),
        .shopmind-table td:nth-child(3) {

            width: 25%;

            text-align: center;

        }


        .shopmind-table th:nth-child(3) {

            text-align: center;

        }


        /* =====================================================
           CATEGORY NAME
        ===================================================== */

        .category-name {

            color: #ffffff;

            font-size: 14px;

            font-weight: 700;

        }


        /* =====================================================
           CREATED
        ===================================================== */

        .category-created {

            color: #9ca3af;

            font-size: 13px;

        }


        /* =====================================================
           DELETE BUTTON
        ===================================================== */

        .delete-category-btn {

            width: 36px;

            height: 36px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            border: 1px solid #252b45;

            border-radius: 8px;

            background: #111626;

            color: #f87171;

            cursor: pointer;

            transition: .2s ease;

        }


        .delete-category-btn:hover {

            background:
                rgba(
                    248,
                    113,
                    113,
                    .10
                );

            border-color: #f87171;

            color: #ff8585;

        }


        /* =====================================================
           EMPTY STATE
        ===================================================== */

        .empty-state {

            padding: 65px 20px !important;

            text-align: center;

            color: #8b93a7 !important;

        }


        .empty-state i {

            display: block;

            margin-bottom: 15px;

            color: #59627b;

            font-size: 42px;

        }


        .empty-state p {

            margin: 0;

            color: #8b93a7;

            font-size: 14px;

        }


        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 992px) {

            .shopmind-content {

                padding: 25px 20px 40px;

            }

        }


        @media (max-width: 768px) {

            .shopmind-content {

                padding: 20px 15px 35px;

            }


            .shopmind-title {

                font-size: 23px;

            }


            .category-form {

                flex-direction: column;

                align-items: stretch;

            }


            .category-input {

                width: 100%;

            }


            .add-category-btn {

                width: 100%;

            }


            .shopmind-table {

                min-width: 650px;

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

    $sidebar_file =
        __DIR__ . '/../includes/sidebar.php';

    if (file_exists($sidebar_file)) {

        include $sidebar_file;

    }
    else {

        die(
            'Sidebar file not found: ' .
            h($sidebar_file)
        );

    }

    ?>


    <!-- =====================================================
         CONTENT WRAPPER
    ====================================================== -->

    <div
        id="content-wrapper"
        class="d-flex flex-column"
    >


        <div id="content">


            <!-- =================================================
                 TOPBAR
            ================================================== -->

            <?php

            $topbar_file =
                __DIR__ . '/../includes/topbar.php';

            if (file_exists($topbar_file)) {

                include $topbar_file;

            }
            else {

                die(
                    'Topbar file not found: ' .
                    h($topbar_file)
                );

            }

            ?>


            <!-- =================================================
                 MAIN CONTENT
            ================================================== -->

            <main class="shopmind-content">


                <!-- PAGE TITLE -->

                <h1 class="shopmind-title">

                    Categories Management

                </h1>


                <div class="shopmind-subtitle">

                    Add and manage product categories.

                </div>


                <!-- =================================================
                     ADD CATEGORY CARD
                ================================================== -->

                <div class="shopmind-card">


                    <div class="shopmind-card-header">

                        <h2 class="shopmind-card-title">

                            Add Category

                        </h2>

                    </div>


                    <form
                        method="POST"
                        class="category-form"
                    >

                        <input
                            type="hidden"
                            name="action"
                            value="add"
                        >


                        <input
                            type="text"
                            name="name"
                            class="category-input"
                            maxlength="100"
                            placeholder="Enter category name"
                            required
                        >


                        <button
                            type="submit"
                            class="add-category-btn"
                        >

                            <i class="fas fa-plus"></i>

                            Add Category

                        </button>

                    </form>


                </div>


                <!-- =================================================
                     ALL CATEGORIES
                ================================================== -->

                <div class="shopmind-card">


                    <div class="shopmind-card-header">

                        <h2 class="shopmind-card-title">

                            All Categories

                        </h2>

                    </div>


                    <!-- MESSAGE -->

                    <?php if ($message !== ''): ?>

                        <div
                            class="
                                shopmind-message
                                <?= $message_type === 'success'
                                    ? 'shopmind-success'
                                    : 'shopmind-error'
                                ?>
                        ">

                            <?= h($message) ?>

                        </div>

                    <?php endif; ?>


                    <!-- TABLE -->

                    <div class="categories-table-wrapper">


                        <table class="shopmind-table">


                            <thead>

                                <tr>

                                    <th>
                                        Category
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


                            <?php if ($result->num_rows > 0): ?>


                                <?php while (
                                    $category =
                                    $result->fetch_assoc()
                                ): ?>


                                    <tr>


                                        <!-- CATEGORY -->

                                        <td>

                                            <span
                                                class="category-name"
                                            >

                                                <?= h(
                                                    $category['name']
                                                ) ?>

                                            </span>

                                        </td>


                                        <!-- CREATED -->

                                        <td>

                                            <span
                                                class="category-created"
                                            >

                                                <?= h(
                                                    $category['created_at']
                                                ) ?>

                                            </span>

                                        </td>


                                        <!-- ACTION -->

                                        <td>


                                            <form
                                                method="POST"
                                                style="display:inline;"
                                                onsubmit="return confirm('Are you sure you want to delete this category?');"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="action"
                                                    value="delete"
                                                >


                                                <input
                                                    type="hidden"
                                                    name="id"
                                                    value="<?= (int) $category['id'] ?>"
                                                >


                                                <button
                                                    type="submit"
                                                    class="delete-category-btn"
                                                    title="Delete Category"
                                                >

                                                    <i
                                                        class="fas fa-trash"
                                                    ></i>

                                                </button>

                                            </form>


                                        </td>


                                    </tr>


                                <?php endwhile; ?>


                            <?php else: ?>


                                <tr>

                                    <td
                                        colspan="3"
                                        class="empty-state"
                                    >

                                        <i
                                            class="fas fa-folder-open"
                                        ></i>


                                        <p>

                                            No categories found.

                                        </p>

                                    </td>

                                </tr>


                            <?php endif; ?>


                            </tbody>


                        </table>


                    </div>


                </div>


            </main>


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