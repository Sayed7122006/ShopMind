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
| CHECK LOGIN
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['user_id']) ||
    !in_array(
        $_SESSION['role'] ?? '',
        ['admin', 'vendor'],
        true
    )
) {

    header('Location: ../account/login.php');

    exit;

}


/*
|--------------------------------------------------------------------------
| ADMIN ONLY
|--------------------------------------------------------------------------
*/

$role = $_SESSION['role'] ?? '';

if ($role !== 'admin') {

    http_response_code(403);

    exit('Forbidden');

}


/*
|--------------------------------------------------------------------------
| HELPER FUNCTION
|--------------------------------------------------------------------------
*/

function h($value)
{

    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );

}


/*
|--------------------------------------------------------------------------
| DELETE PRODUCT
|--------------------------------------------------------------------------
*/

$message = '';

$message_type = '';


if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['delete_id'])
) {

    $product_id = (int) $_POST['delete_id'];


    if ($product_id > 0) {

        $stmt = $conn->prepare(
            "DELETE FROM products WHERE id = ?"
        );


        if (!$stmt) {

            die(
                'Database Error: ' .
                htmlspecialchars(
                    $conn->error,
                    ENT_QUOTES,
                    'UTF-8'
                )
            );

        }


        $stmt->bind_param(
            'i',
            $product_id
        );


        if ($stmt->execute()) {

            if ($stmt->affected_rows > 0) {

                $message =
                    'Product deleted successfully.';

                $message_type = 'success';

            } else {

                $message =
                    'Product was not found.';

                $message_type = 'error';

            }

        } else {

            $message =
                'Unable to delete this product.';

            $message_type = 'error';

        }


        $stmt->close();

    }

}


/*
|--------------------------------------------------------------------------
| GET PRODUCTS
|--------------------------------------------------------------------------
|
| Actual products table:
|
| id
| vendor_id
| img
| name
| price
| category
|
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        p.id,
        p.vendor_id,
        p.name,
        p.price,
        p.category,
        u.Name AS vendor_name

    FROM products p

    LEFT JOIN users u
        ON u.id = p.vendor_id

    ORDER BY p.id DESC
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


<title>
    Products Management - ShopMind
</title>


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


    /* =====================================================
       GLOBAL
    ===================================================== */

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

        min-height: 100vh;

    }


    /* =====================================================
       MAIN CONTENT
    ===================================================== */

    .shopmind-content {

        padding: 30px;

        background: #080b18;

        min-height: calc(100vh - 80px);

    }


    /* =====================================================
       PAGE TITLE
    ===================================================== */

    .shopmind-title {

        font-size: 26px;

        font-weight: 800;

        color: #ffffff;

        margin-bottom: 5px;

    }


    .shopmind-subtitle {

        color: #9ca3af;

        font-size: 14px;

        margin-bottom: 25px;

    }


    /* =====================================================
       CARD
    ===================================================== */

    .shopmind-card {

        background: #0d1122;

        border: 1px solid #252b45;

        border-radius: 12px;

        overflow: hidden;

        width: 100%;

    }


    /* =====================================================
       CARD HEADER
    ===================================================== */

    .shopmind-card-header {

        padding: 20px 25px;

        border-bottom: 1px solid #252b45;

        background: #0d1122;

    }


    .shopmind-card-title {

        margin: 0;

        color: #ffffff;

        font-size: 18px;

        font-weight: 700;

    }


    /* =====================================================
       MESSAGE
    ===================================================== */

    .shopmind-message {

        margin: 20px 25px 0;

        padding: 12px 15px;

        border-radius: 8px;

        font-size: 13px;

    }


    .shopmind-message.success {

        background: rgba(
            74,
            222,
            128,
            0.10
        );

        border: 1px solid rgba(
            74,
            222,
            128,
            0.25
        );

        color: #4ade80;

    }


    .shopmind-message.error {

        background: rgba(
            248,
            113,
            113,
            0.10
        );

        border: 1px solid rgba(
            248,
            113,
            113,
            0.25
        );

        color: #f87171;

    }


    /* =====================================================
       TABLE WRAPPER
    ===================================================== */

    .products-table-wrapper {

        width: 100%;

        overflow-x: auto;

    }


    /* =====================================================
       TABLE
    ===================================================== */

    .shopmind-table {

        width: 100%;

        border-collapse: collapse;

        margin: 0;

    }


    .shopmind-table thead {

        background: #111626;

    }


    .shopmind-table th {

        padding: 17px 20px;

        color: #aeb6cc;

        font-size: 12px;

        font-weight: 700;

        text-transform: uppercase;

        letter-spacing: .4px;

        border-bottom: 1px solid #252b45;

        white-space: nowrap;

    }


    .shopmind-table td {

        padding: 18px 20px;

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
       PRODUCT
    ===================================================== */

    .product-name {

        color: #ffffff;

        font-weight: 700;

        font-size: 14px;

    }


    .product-id {

        color: #70778c;

        font-size: 12px;

        margin-top: 4px;

    }


    /* =====================================================
       VENDOR
    ===================================================== */

    .vendor-name {

        color: #b18cff;

        font-weight: 600;

    }


    .unknown-vendor {

        color: #70778c;

    }


    /* =====================================================
       CATEGORY
    ===================================================== */

    .category-name {

        color: #aeb4ca;

    }


    .no-category {

        color: #70778c;

    }


    /* =====================================================
       PRICE
    ===================================================== */

    .product-price {

        color: #ffffff;

        font-weight: 700;

    }


    /* =====================================================
       ACTION
    ===================================================== */

    .product-action {

        text-align: center;

    }


    .delete-btn {

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


    .delete-btn:hover {

        background: rgba(
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
    ===================================================== */

    @media (max-width: 992px) {

        .shopmind-content {

            padding: 25px 20px;

        }

    }


    @media (max-width: 768px) {

        .shopmind-content {

            padding: 20px 15px;

        }


        .shopmind-title {

            font-size: 22px;

        }


        .shopmind-subtitle {

            font-size: 13px;

        }


        .shopmind-card-header {

            padding: 18px;

        }


        .shopmind-table {

            min-width: 750px;

        }


        .shopmind-table th,
        .shopmind-table td {

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

$sidebar_file =
    __DIR__ . '/../includes/sidebar.php';


if (file_exists($sidebar_file)) {

    include $sidebar_file;

} else {

    die(
        'Sidebar file not found: ' .
        htmlspecialchars(
            $sidebar_file,
            ENT_QUOTES,
            'UTF-8'
        )
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

        } else {

            die(
                'Topbar file not found: ' .
                htmlspecialchars(
                    $topbar_file,
                    ENT_QUOTES,
                    'UTF-8'
                )
            );

        }

        ?>


        <!-- =================================================
             MAIN CONTENT
        ================================================== -->

        <div class="shopmind-content">


            <!-- PAGE TITLE -->

            <div class="shopmind-title">

                Products Management

            </div>


            <div class="shopmind-subtitle">

                View and manage all products added by vendors.

            </div>


            <!-- =================================================
                 PRODUCTS CARD
            ================================================== -->

            <div class="shopmind-card">


                <!-- CARD HEADER -->

                <div class="shopmind-card-header">

                    <h6 class="shopmind-card-title">

                        All Products

                    </h6>

                </div>


                <!-- =================================================
                     MESSAGE
                ================================================== -->

                <?php if ($message): ?>

                    <div
                        class="shopmind-message <?= h($message_type) ?>"
                    >

                        <?= h($message) ?>

                    </div>

                <?php endif; ?>


                <!-- =================================================
                     TABLE
                ================================================== -->

                <div class="products-table-wrapper">


                    <table class="shopmind-table">


                        <thead>

                            <tr>

                                <th>
                                    Product
                                </th>

                                <th>
                                    Vendor
                                </th>

                                <th>
                                    Category
                                </th>

                                <th>
                                    Price
                                </th>

                                <th>
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php if ($result->num_rows > 0): ?>


                            <?php while (
                                $product =
                                $result->fetch_assoc()
                            ): ?>


                                <tr>


                                    <!-- PRODUCT -->

                                    <td>

                                        <div class="product-name">

                                            <?= h(
                                                $product['name']
                                            ) ?>

                                        </div>


                                        <div class="product-id">

                                            ID:

                                            <?= h(
                                                $product['id']
                                            ) ?>

                                        </div>

                                    </td>


                                    <!-- VENDOR -->

                                    <td>

                                        <?php if (
                                            !empty(
                                                $product['vendor_name']
                                            )
                                        ): ?>

                                            <span class="vendor-name">

                                                <?= h(
                                                    $product['vendor_name']
                                                ) ?>

                                            </span>

                                        <?php else: ?>

                                            <span class="unknown-vendor">

                                                Unknown

                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <!-- CATEGORY -->

                                    <td>

                                        <?php if (
                                            !empty(
                                                $product['category']
                                            )
                                        ): ?>

                                            <span class="category-name">

                                                <?= h(
                                                    $product['category']
                                                ) ?>

                                            </span>

                                        <?php else: ?>

                                            <span class="no-category">

                                                No Category

                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <!-- PRICE -->

                                    <td>

                                        <span class="product-price">

                                            <?= h(
                                                $product['price']
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- ACTION -->

                                    <td class="product-action">


                                        <form
                                            method="POST"
                                            style="display:inline;"
                                            onsubmit="return confirm('Are you sure you want to delete this product?');"
                                        >


                                            <input
                                                type="hidden"
                                                name="delete_id"
                                                value="<?= h(
                                                    $product['id']
                                                ) ?>"
                                            >


                                            <button
                                                type="submit"
                                                class="delete-btn"
                                                title="Delete Product"
                                            >

                                                <i class="fas fa-trash"></i>

                                            </button>


                                        </form>


                                    </td>


                                </tr>


                            <?php endwhile; ?>


                        <?php else: ?>


                            <tr>

                                <td
                                    colspan="5"
                                    class="empty-state"
                                >

                                    <i class="fas fa-box-open"></i>


                                    <p>

                                        No products found.

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
