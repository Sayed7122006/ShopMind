<?php

session_start();

/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/fun/db_connection.php';


/*
|--------------------------------------------------------------------------
| NO CACHE
|--------------------------------------------------------------------------
*/

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");


/*
|--------------------------------------------------------------------------
| CHECK LOGIN
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['role']) ||
    !in_array($_SESSION['role'], ['admin', 'vendor'], true)
) {
    header("Location: account/login.php");
    exit();
}


/*
|--------------------------------------------------------------------------
| USER DATA
|--------------------------------------------------------------------------
*/

$role = $_SESSION['role'];

$userId = (int)(
    $_SESSION['user_id']
    ?? 0
);

$displayName =
    $_SESSION['user_name']
    ?? $_SESSION['name']
    ?? 'User';


/*
|--------------------------------------------------------------------------
| DEFAULT DATA
|--------------------------------------------------------------------------
*/

$totalUsers = 0;
$totalProducts = 0;
$totalOrders = 0;

$products = [];
$categories = [];


/*
|--------------------------------------------------------------------------
| USERS
|--------------------------------------------------------------------------
*/

if ($role === 'admin') {

    $result = mysqli_query(
        $conn,
        "SELECT COUNT(*) AS total FROM users"
    );

    if ($result) {

        $row = mysqli_fetch_assoc($result);

        $totalUsers = (int)(
            $row['total'] ?? 0
        );
    }
}


/*
|--------------------------------------------------------------------------
| PRODUCTS
|--------------------------------------------------------------------------
*/

if ($role === 'admin') {

    $result = mysqli_query(
        $conn,
        "SELECT COUNT(*) AS total FROM products"
    );

    if ($result) {

        $row = mysqli_fetch_assoc($result);

        $totalProducts = (int)(
            $row['total'] ?? 0
        );
    }

} else {

    $stmt = mysqli_prepare(
        $conn,
        "
        SELECT COUNT(*) AS total
        FROM products
        WHERE vendor_id = ?
        "
    );

    if ($stmt) {

        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $userId
        );

        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        if ($result) {

            $row = mysqli_fetch_assoc($result);

            $totalProducts = (int)(
                $row['total'] ?? 0
            );
        }

        mysqli_stmt_close($stmt);
    }
}


/*
|--------------------------------------------------------------------------
| ORDERS
|--------------------------------------------------------------------------
*/

if ($role === 'admin') {

    $result = mysqli_query(
        $conn,
        "SELECT COUNT(*) AS total FROM orders"
    );

    if ($result) {

        $row = mysqli_fetch_assoc($result);

        $totalOrders = (int)(
            $row['total'] ?? 0
        );
    }

} else {

    /*
     * Vendor orders
     */

    $stmt = mysqli_prepare(
        $conn,
        "
        SELECT COUNT(DISTINCT o.id) AS total
        FROM orders o
        INNER JOIN order_items oi
            ON oi.order_id = o.id
        INNER JOIN products p
            ON p.id = oi.product_id
        WHERE p.vendor_id = ?
        "
    );

    if ($stmt) {

        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $userId
        );

        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        if ($result) {

            $row = mysqli_fetch_assoc($result);

            $totalOrders = (int)(
                $row['total'] ?? 0
            );
        }

        mysqli_stmt_close($stmt);
    }
}


/*
|--------------------------------------------------------------------------
| CATEGORIES
|--------------------------------------------------------------------------
|
| products.category is VARCHAR
| NOT category_id
|
|--------------------------------------------------------------------------
*/

$categoryResult = null;

if ($role === 'admin') {

    $categoryResult = mysqli_query(
        $conn,
        "
        SELECT
            category,
            COUNT(*) AS total
        FROM products
        WHERE category IS NOT NULL
        AND category <> ''
        GROUP BY category
        ORDER BY total DESC
        LIMIT 6
        "
    );

} else {

    $stmt = mysqli_prepare(
        $conn,
        "
        SELECT
            category,
            COUNT(*) AS total
        FROM products
        WHERE vendor_id = ?
        AND category IS NOT NULL
        AND category <> ''
        GROUP BY category
        ORDER BY total DESC
        LIMIT 6
        "
    );

    if ($stmt) {

        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $userId
        );

        mysqli_stmt_execute($stmt);

        $categoryResult =
            mysqli_stmt_get_result($stmt);
    }
}


if ($categoryResult) {

    while (
        $row = mysqli_fetch_assoc($categoryResult)
    ) {

        $categories[] = $row;
    }
}


/*
|--------------------------------------------------------------------------
| LATEST PRODUCTS
|--------------------------------------------------------------------------
|
| Current products table contains:
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

$productResult = null;

if ($role === 'admin') {

    $productResult = mysqli_query(
        $conn,
        "
        SELECT
            id,
            name,
            price,
            category,
            img
        FROM products
        ORDER BY id DESC
        LIMIT 8
        "
    );

} else {

    $stmt = mysqli_prepare(
        $conn,
        "
        SELECT
            id,
            name,
            price,
            category,
            img
        FROM products
        WHERE vendor_id = ?
        ORDER BY id DESC
        LIMIT 8
        "
    );

    if ($stmt) {

        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $userId
        );

        mysqli_stmt_execute($stmt);

        $productResult =
            mysqli_stmt_get_result($stmt);
    }
}


if ($productResult) {

    while (
        $row = mysqli_fetch_assoc($productResult)
    ) {

        $products[] = $row;
    }
}


/*
|--------------------------------------------------------------------------
| HEADER + SIDEBAR
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/includes/header.php';

require_once __DIR__ . '/includes/sidebar.php';

?>


<style>

/* =========================================================
   SHOPMIND SIMPLE DASHBOARD
========================================================= */

:root {

    --sm-bg: #080b18;

    --sm-card: #0d1122;

    --sm-card-hover: #11162a;

    --sm-border: #252b45;

    --sm-purple: #8b5cf6;

    --sm-blue: #4f46e5;

    --sm-white: #ffffff;

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


.sm-dashboard {

    min-height: calc(100vh - 70px);

    padding: 30px;

    background: var(--sm-bg);

}


.sm-container {

    width: 100%;

    max-width: 1400px;

    margin: auto;

}


/* =========================================================
   HEADER
========================================================= */

.sm-page-header {

    display: flex;

    justify-content: space-between;

    align-items: center;

    margin-bottom: 28px;

}


.sm-page-header h1 {

    margin: 0 0 6px;

    color: var(--sm-white);

    font-size: 26px;

    font-weight: 700;

}


.sm-page-header p {

    margin: 0;

    color: var(--sm-muted);

    font-size: 13px;

}


.sm-user-badge {

    display: flex;

    align-items: center;

    gap: 10px;

    padding: 10px 15px;

    border: 1px solid var(--sm-border);

    border-radius: 10px;

    background: var(--sm-card);

    color: white;

    font-size: 12px;

}


.sm-user-badge i {

    color: #a78bfa;

}


/* =========================================================
   STAT CARDS
========================================================= */

.sm-stats {

    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 18px;

    margin-bottom: 30px;

}


.sm-stat {

    display: flex;

    align-items: center;

    gap: 15px;

    padding: 20px;

    border: 1px solid var(--sm-border);

    border-radius: 14px;

    background: var(--sm-card);

    transition: .25s ease;

}


.sm-stat:hover {

    transform: translateY(-3px);

    border-color: rgba(139,92,246,.45);

}


.sm-stat-icon {

    width: 48px;

    height: 48px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 12px;

    color: #c4b5fd;

    background: rgba(139,92,246,.12);

}


.sm-stat:nth-child(2) .sm-stat-icon {

    color: #93c5fd;

    background: rgba(59,130,246,.12);

}


.sm-stat:nth-child(3) .sm-stat-icon {

    color: #86efac;

    background: rgba(34,197,94,.10);

}


.sm-stat-text span {

    display: block;

    margin-bottom: 4px;

    color: #64748b;

    font-size: 10px;

    font-weight: 600;

    text-transform: uppercase;

}


.sm-stat-text strong {

    color: white;

    font-size: 24px;

    font-weight: 700;

}


/* =========================================================
   MAIN GRID
========================================================= */

.sm-main-grid {

    display: grid;

    grid-template-columns:
        1fr 2fr;

    gap: 20px;

}


/* =========================================================
   CARD
========================================================= */

.sm-card {

    border: 1px solid var(--sm-border);

    border-radius: 14px;

    background: var(--sm-card);

    overflow: hidden;

}


.sm-card-header {

    display: flex;

    justify-content: space-between;

    align-items: center;

    padding: 18px 20px;

    border-bottom: 1px solid var(--sm-border);

}


.sm-card-header h3 {

    margin: 0;

    color: white;

    font-size: 15px;

    font-weight: 700;

}


.sm-card-header span {

    color: #64748b;

    font-size: 10px;

}


/* =========================================================
   CATEGORIES
========================================================= */

.sm-category-list {

    padding: 8px 0;

}


.sm-category {

    display: flex;

    align-items: center;

    justify-content: space-between;

    padding: 13px 20px;

    border-bottom: 1px solid rgba(37,43,69,.5);

}


.sm-category:last-child {

    border-bottom: none;

}


.sm-category-left {

    display: flex;

    align-items: center;

    gap: 10px;

}


.sm-category-icon {

    width: 32px;

    height: 32px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 8px;

    color: #a78bfa;

    background: rgba(139,92,246,.10);

}


.sm-category-name {

    color: #e2e8f0;

    font-size: 12px;

}


.sm-category-count {

    padding: 4px 8px;

    border-radius: 6px;

    color: #a78bfa;

    background: rgba(139,92,246,.08);

    font-size: 10px;

}


/* =========================================================
   PRODUCTS
========================================================= */

.sm-products {

    width: 100%;

    border-collapse: collapse;

}


.sm-products th {

    padding: 12px 18px;

    color: #64748b;

    background: rgba(8,11,24,.35);

    border-bottom: 1px solid var(--sm-border);

    font-size: 9px;

    font-weight: 700;

    text-align: left;

    text-transform: uppercase;

}


.sm-products td {

    padding: 13px 18px;

    color: #cbd5e1;

    border-bottom: 1px solid rgba(37,43,69,.5);

    font-size: 11px;

}


.sm-products tr:last-child td {

    border-bottom: none;

}


.sm-product-name {

    display: flex;

    align-items: center;

    gap: 10px;

}


.sm-product-image {

    width: 36px;

    height: 36px;

    display: flex;

    align-items: center;

    justify-content: center;

    overflow: hidden;

    border-radius: 8px;

    background: #11162a;

    flex-shrink: 0;

}


.sm-product-image img {

    width: 100%;

    height: 100%;

    object-fit: cover;

}


.sm-product-image i {

    color: #64748b;

    font-size: 14px;

}


.sm-product-title {

    color: white;

    font-weight: 600;

}


.sm-category-label {

    color: #94a3b8;

}


.sm-price {

    color: #a78bfa !important;

    font-weight: 700;

}


/* =========================================================
   EMPTY
========================================================= */

.sm-empty {

    padding: 35px 20px;

    text-align: center;

    color: #64748b;

    font-size: 11px;

}


.sm-empty i {

    display: block;

    margin-bottom: 8px;

    color: #8b5cf6;

    font-size: 25px;

}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 1000px) {

    .sm-main-grid {

        grid-template-columns: 1fr;

    }

}


@media (max-width: 700px) {

    .sm-dashboard {

        padding: 20px 12px;

    }


    .sm-page-header {

        align-items: flex-start;

        flex-direction: column;

        gap: 15px;

    }


    .sm-stats {

        grid-template-columns: 1fr;

    }


    .sm-products th:nth-child(3),
    .sm-products td:nth-child(3) {

        display: none;

    }

}

</style>


<!-- =========================================================
     CONTENT
========================================================= -->

<div id="content-wrapper" class="d-flex flex-column">

    <div id="content">


        <!-- =====================================================
             TOPBAR
        ====================================================== -->

        <?php

        require_once __DIR__ . '/includes/topbar.php';

        ?>


        <!-- =====================================================
             DASHBOARD
        ====================================================== -->

        <div class="sm-dashboard">

            <div class="sm-container">


                <!-- =================================================
                     PAGE HEADER
                ================================================== -->

                <div class="sm-page-header">

                    <div>

                        <h1>
                            Welcome,
                            <?= htmlspecialchars($displayName) ?>
                        </h1>

                    </div>


                    <div class="sm-user-badge">

                        <i class="fas fa-user"></i>

                        <?= htmlspecialchars(ucfirst($role)) ?>

                    </div>

                </div>



                <!-- =================================================
                     STATISTICS
                ================================================== -->

                <div class="sm-stats">


                    <?php if ($role === 'admin'): ?>

                        <div class="sm-stat">

                            <div class="sm-stat-icon">

                                <i class="fas fa-users"></i>

                            </div>

                            <div class="sm-stat-text">

                                <span>
                                    Users
                                </span>

                                <strong>
                                    <?= $totalUsers ?>
                                </strong>

                            </div>

                        </div>

                    <?php endif; ?>


                    <div class="sm-stat">

                        <div class="sm-stat-icon">

                            <i class="fas fa-box"></i>

                        </div>

                        <div class="sm-stat-text">

                            <span>
                                Products
                            </span>

                            <strong>
                                <?= $totalProducts ?>
                            </strong>

                        </div>

                    </div>


                    <div class="sm-stat">

                        <div class="sm-stat-icon">

                            <i class="fas fa-shopping-cart"></i>

                        </div>

                        <div class="sm-stat-text">

                            <span>
                                Orders
                            </span>

                            <strong>
                                <?= $totalOrders ?>
                            </strong>

                        </div>

                    </div>


                </div>



                <!-- =================================================
                     MAIN CONTENT
                ================================================== -->

                <div class="sm-main-grid">


                    <!-- =================================================
                         CATEGORIES
                    ================================================== -->

                    <div class="sm-card">

                        <div class="sm-card-header">

                            <h3>
                                Categories
                            </h3>

                            <span>
                                Top categories
                            </span>

                        </div>


                        <?php if (!empty($categories)): ?>

                            <div class="sm-category-list">

                                <?php foreach ($categories as $category): ?>

                                    <div class="sm-category">

                                        <div class="sm-category-left">

                                            <div class="sm-category-icon">

                                                <i class="fas fa-folder"></i>

                                            </div>


                                            <span class="sm-category-name">

                                                <?= htmlspecialchars(
                                                    $category['category']
                                                ) ?>

                                            </span>

                                        </div>


                                        <span class="sm-category-count">

                                            <?= (int)$category['total'] ?>

                                        </span>

                                    </div>

                                <?php endforeach; ?>

                            </div>

                        <?php else: ?>

                            <div class="sm-empty">

                                <i class="fas fa-folder-open"></i>

                                No categories found.

                            </div>

                        <?php endif; ?>

                    </div>



                    <!-- =================================================
                         LATEST PRODUCTS
                    ================================================== -->

                    <div class="sm-card">

                        <div class="sm-card-header">

                            <h3>
                                Latest Products
                            </h3>

                            <span>
                                Last 8 products
                            </span>

                        </div>


                        <?php if (!empty($products)): ?>

                            <div style="overflow-x:auto;">

                                <table class="sm-products">

                                    <thead>

                                        <tr>

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


                                                <!-- PRODUCT -->

                                                <td>

                                                    <div class="sm-product-name">


                                                        <div class="sm-product-image">


                                                            <?php

                                                            if (
                                                                !empty(
                                                                    $product['img']
                                                                )
                                                            ):

                                                                $imageData =
                                                                    base64_encode(
                                                                        $product['img']
                                                                    );

                                                            ?>

                                                                <img
                                                                    src="data:image/jpeg;base64,<?= $imageData ?>"
                                                                    alt="<?= htmlspecialchars(
                                                                        $product['name']
                                                                    ) ?>"
                                                                >

                                                            <?php else: ?>

                                                                <i
                                                                    class="fas fa-box"
                                                                ></i>

                                                            <?php endif; ?>


                                                        </div>


                                                        <span class="sm-product-title">

                                                            <?= htmlspecialchars(
                                                                $product['name']
                                                            ) ?>

                                                        </span>


                                                    </div>

                                                </td>



                                                <!-- CATEGORY -->

                                                <td class="sm-category-label">

                                                    <?= htmlspecialchars(
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

                                No products found.

                            </div>

                        <?php endif; ?>


                    </div>


                </div>


            </div>

        </div>


    </div>


    <!-- =====================================================
         FOOTER
    ====================================================== -->

    <?php

    require_once __DIR__ . '/includes/footer.php';

    ?>


</div>