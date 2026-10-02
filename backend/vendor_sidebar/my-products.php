<?php

session_start();

/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../fun/db_connection.php';


/*
|--------------------------------------------------------------------------
| LOGIN CHECK
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['user_id']) ||
    !in_array($_SESSION['role'] ?? '', ['admin', 'vendor'], true)
) {
    header('Location: /ShopMind-main_dashbord/account/login.php');
    exit();
}


/*
|--------------------------------------------------------------------------
| HELPER
|--------------------------------------------------------------------------
*/

function h($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}


/*
|--------------------------------------------------------------------------
| USER DATA
|--------------------------------------------------------------------------
*/

$role = $_SESSION['role'];
$uid  = (int) $_SESSION['user_id'];


/*
|--------------------------------------------------------------------------
| VENDOR ONLY
|--------------------------------------------------------------------------
*/

if ($role !== 'vendor') {
    http_response_code(403);
    exit('Forbidden');
}


/*
|--------------------------------------------------------------------------
| GET PRODUCTS
|--------------------------------------------------------------------------
| IMPORTANT:
| We only use columns that exist in your products table.
|--------------------------------------------------------------------------
*/

$products = [];

$sql = "
    SELECT
        id,
        name,
        price,
        category
    FROM products
    WHERE vendor_id = ?
    ORDER BY id DESC
";

$stmt = mysqli_prepare($conn, $sql);

if ($stmt) {

    mysqli_stmt_bind_param(
        $stmt,
        'i',
        $uid
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if ($result) {

        while ($row = mysqli_fetch_assoc($result)) {

            $products[] = $row;
        }
    }

    mysqli_stmt_close($stmt);
}


/*
|--------------------------------------------------------------------------
| HEADER
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../includes/header.php';

require_once __DIR__ . '/../includes/sidebar.php';

?>

<style>

/* =========================================================
   SHOPMIND - MY PRODUCTS
========================================================= */

:root {

    --sm-bg: #080b18;
    --sm-card: #0d1122;
    --sm-card-hover: #11162a;
    --sm-border: #252b45;

    --sm-purple: #8b5cf6;
    --sm-blue: #4f46e5;

    --sm-white: #ffffff;
    --sm-text: #e2e8f0;
    --sm-muted: #94a3b8;

}


/* =========================================================
   PAGE
========================================================= */

html,
body {

    background: var(--sm-bg) !important;

}


#content-wrapper,
#content {

    background: var(--sm-bg) !important;

}


.sm-products-page {

    min-height: calc(100vh - 70px);

    padding: 30px;

    background: var(--sm-bg);

}


.sm-products-container {

    width: 100%;

    max-width: 1400px;

    margin: auto;

}


/* =========================================================
   PAGE HEADER
========================================================= */

.sm-page-header {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 20px;

    margin-bottom: 25px;

}


.sm-page-title h1 {

    margin: 0 0 6px;

    color: var(--sm-white);

    font-size: 26px;

    font-weight: 700;

}


.sm-page-title p {

    margin: 0;

    color: var(--sm-muted);

    font-size: 13px;

}


/* =========================================================
   ADD PRODUCT BUTTON
========================================================= */

.sm-add-btn {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 8px;

    min-height: 42px;

    padding: 0 17px;

    border: 0;

    border-radius: 9px;

    background: linear-gradient(
        135deg,
        #8b5cf6,
        #4f46e5
    );

    color: #ffffff !important;

    text-decoration: none !important;

    font-size: 12px;

    font-weight: 600;

    cursor: pointer;

    transition: .25s ease;

}


.sm-add-btn:hover {

    color: #ffffff !important;

    text-decoration: none !important;

    transform: translateY(-2px);

    box-shadow:
        0 8px 20px rgba(79,70,229,.25);

}


.sm-add-btn i {

    font-size: 12px;

}


/* =========================================================
   CARD
========================================================= */

.sm-products-card {

    width: 100%;

    overflow: hidden;

    border: 1px solid var(--sm-border);

    border-radius: 14px;

    background: var(--sm-card);

}


/* =========================================================
   CARD HEADER
========================================================= */

.sm-card-header {

    display: flex;

    align-items: center;

    justify-content: space-between;

    padding: 18px 20px;

    border-bottom: 1px solid var(--sm-border);

}


.sm-card-header h3 {

    margin: 0;

    color: #ffffff;

    font-size: 15px;

    font-weight: 700;

}


.sm-card-header span {

    color: #64748b;

    font-size: 11px;

}


/* =========================================================
   TABLE WRAPPER
========================================================= */

.sm-table-wrapper {

    width: 100%;

    overflow-x: auto;

}


/* =========================================================
   TABLE
========================================================= */

.sm-table {

    width: 100%;

    border-collapse: collapse;

}


.sm-table th {

    padding: 13px 18px;

    color: #64748b;

    background: rgba(8,11,24,.45);

    border-bottom: 1px solid var(--sm-border);

    font-size: 9px;

    font-weight: 700;

    text-align: left;

    text-transform: uppercase;

    white-space: nowrap;

}


.sm-table td {

    padding: 15px 18px;

    color: #cbd5e1;

    border-bottom: 1px solid rgba(37,43,69,.5);

    font-size: 11px;

    vertical-align: middle;

}


.sm-table tbody tr {

    transition: .2s ease;

}


.sm-table tbody tr:hover {

    background: var(--sm-card-hover);

}


.sm-table tbody tr:last-child td {

    border-bottom: none;

}


/* =========================================================
   PRODUCT ID
========================================================= */

.sm-product-id {

    color: #64748b;

    font-weight: 600;

}


/* =========================================================
   PRODUCT NAME
========================================================= */

.sm-product-name {

    color: #ffffff;

    font-weight: 600;

}


/* =========================================================
   CATEGORY
========================================================= */

.sm-category {

    color: #94a3b8;

}


/* =========================================================
   PRICE
========================================================= */

.sm-price {

    color: #a78bfa !important;

    font-weight: 700;

}


/* =========================================================
   EMPTY
========================================================= */

.sm-empty {

    padding: 60px 20px;

    text-align: center;

    color: #64748b;

    font-size: 12px;

}


.sm-empty i {

    display: block;

    margin-bottom: 12px;

    color: #8b5cf6;

    font-size: 30px;

}


.sm-empty-title {

    display: block;

    margin-bottom: 5px;

    color: #cbd5e1;

    font-size: 14px;

    font-weight: 600;

}


.sm-empty-text {

    color: #64748b;

    font-size: 11px;

}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 768px) {

    .sm-products-page {

        padding: 20px 12px;

    }


    .sm-page-header {

        align-items: flex-start;

        flex-direction: column;

    }


    .sm-add-btn {

        width: 100%;

    }

}

</style>


<!-- =========================================================
     CONTENT WRAPPER
========================================================= -->

<div id="content-wrapper" class="d-flex flex-column">

    <div id="content">


        <!-- =====================================================
             TOPBAR
        ====================================================== -->

        <?php

        require_once __DIR__ . '/../includes/topbar.php';

        ?>


        <!-- =====================================================
             PAGE
        ====================================================== -->

        <div class="sm-products-page">

            <div class="sm-products-container">


                <!-- =================================================
                     PAGE HEADER
                ================================================== -->

                <div class="sm-page-header">

                    <div class="sm-page-title">

                        <h1>
                            My Products
                        </h1>

                        <p>
                            Manage the products you have added.
                        </p>

                    </div>


                    <!--
                    add-products.php is in the same folder
                    as my-products.php
                    -->

                    <a
                        href="add-products.php"
                        class="sm-add-btn"
                    >

                        <i class="fas fa-plus"></i>

                        Add Product

                    </a>

                </div>


                <!-- =================================================
                     PRODUCTS CARD
                ================================================== -->

                <div class="sm-products-card">


                    <div class="sm-card-header">

                        <h3>
                            Products
                        </h3>

                        <span>
                            <?= count($products) ?> products
                        </span>

                    </div>


                    <?php if (!empty($products)): ?>


                        <div class="sm-table-wrapper">

                            <table class="sm-table">

                                <thead>

                                    <tr>

                                        <th>
                                            ID
                                        </th>

                                        <th>
                                            Product
                                        </th>

                                        <th>
                                            Category
                                        </th>

                                        <th>
                                            Price
                                        </th>

                                    </tr>

                                </thead>


                                <tbody>


                                    <?php foreach ($products as $product): ?>

                                        <tr>


                                            <!-- ID -->

                                            <td class="sm-product-id">

                                                <?= h(
                                                    $product['id']
                                                ) ?>

                                            </td>


                                            <!-- NAME -->

                                            <td class="sm-product-name">

                                                <?= h(
                                                    $product['name']
                                                ) ?>

                                            </td>


                                            <!-- CATEGORY -->

                                            <td class="sm-category">

                                                <?= h(
                                                    $product['category']
                                                    ?: '—'
                                                ) ?>

                                            </td>


                                            <!-- PRICE -->

                                            <td class="sm-price">

                                                $<?= number_format(
                                                    (float)$product['price'],
                                                    2
                                                ) ?>

                                            </td>


                                        </tr>

                                    <?php endforeach; ?>


                                </tbody>

                            </table>

                        </div>


                    <?php else: ?>


                        <div class="sm-empty">

                            <i class="fas fa-box-open"></i>

                            <span class="sm-empty-title">
                                No Products Yet
                            </span>

                            <span class="sm-empty-text">
                                You haven't added any products yet.
                            </span>

                        </div>


                    <?php endif; ?>


                </div>


            </div>

        </div>


    </div>


    <!-- =========================================================
         FOOTER
    ========================================================== -->

    <?php

    require_once __DIR__ . '/../includes/footer.php';

    ?>


</div>