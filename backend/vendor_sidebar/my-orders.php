<?php

session_start();

require_once __DIR__ . '/../fun/db_connection.php';


/*
|--------------------------------------------------------------------------
| CHECK LOGIN
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['user_id']) ||
    !in_array($_SESSION['role'] ?? '', ['admin', 'vendor'], true)
) {
    header('Location: ../account/login.php');
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
$uid  = (int)$_SESSION['user_id'];


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
| GET VENDOR ORDERS
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT DISTINCT
        o.id,
        o.user_email,
        o.order_date,
        o.status

    FROM orders o

    INNER JOIN order_items oi
        ON oi.order_id = o.id

    INNER JOIN products p
        ON p.id = oi.product_id

    WHERE p.vendor_id = ?

    ORDER BY o.order_date DESC
";


$stmt = mysqli_prepare(
    $conn,
    $sql
);


/*
|--------------------------------------------------------------------------
| CHECK QUERY
|--------------------------------------------------------------------------
*/

if (!$stmt) {

    die(
        'Database query error: ' .
        h(mysqli_error($conn))
    );
}


/*
|--------------------------------------------------------------------------
| BIND
|--------------------------------------------------------------------------
*/

mysqli_stmt_bind_param(
    $stmt,
    'i',
    $uid
);


/*
|--------------------------------------------------------------------------
| EXECUTE
|--------------------------------------------------------------------------
*/

if (!mysqli_stmt_execute($stmt)) {

    die(
        'Database execution error: ' .
        h(mysqli_stmt_error($stmt))
    );
}


/*
|--------------------------------------------------------------------------
| RESULT
|--------------------------------------------------------------------------
*/

$result = mysqli_stmt_get_result($stmt);


/*
|--------------------------------------------------------------------------
| HEADER + SIDEBAR
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../includes/header.php';

require_once __DIR__ . '/../includes/sidebar.php';

?>


<style>

/* =========================================================
   SHOPMIND MY ORDERS
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


.sm-orders-page {

    min-height: calc(100vh - 70px);

    padding: 30px;

    background: var(--sm-bg);

}


.sm-orders-container {

    width: 100%;

    max-width: 1400px;

    margin: 0 auto;

}


/* =========================================================
   HEADER
========================================================= */

.sm-orders-header {

    display: flex;

    justify-content: space-between;

    align-items: center;

    margin-bottom: 25px;

}


.sm-orders-title h1 {

    margin: 0 0 6px;

    color: var(--sm-white);

    font-size: 26px;

    font-weight: 700;

}


.sm-orders-title p {

    margin: 0;

    color: var(--sm-muted);

    font-size: 13px;

}


/* =========================================================
   HEADER BADGE
========================================================= */

.sm-orders-badge {

    display: inline-flex;

    align-items: center;

    gap: 8px;

    padding: 9px 13px;

    border: 1px solid var(--sm-border);

    border-radius: 9px;

    background: var(--sm-card);

    color: #cbd5e1;

    font-size: 11px;

}


.sm-orders-badge i {

    color: #a78bfa;

}


/* =========================================================
   CARD
========================================================= */

.sm-orders-card {

    overflow: hidden;

    background: var(--sm-card);

    border: 1px solid var(--sm-border);

    border-radius: 14px;

}


/* =========================================================
   CARD HEADER
========================================================= */

.sm-card-header {

    display: flex;

    justify-content: space-between;

    align-items: center;

    padding: 18px 20px;

    border-bottom: 1px solid var(--sm-border);

}


.sm-card-header-left {

    display: flex;

    align-items: center;

    gap: 10px;

}


.sm-card-header-icon {

    width: 34px;

    height: 34px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 9px;

    color: #a78bfa;

    background: rgba(139, 92, 246, .10);

}


.sm-card-header h3 {

    margin: 0;

    color: #ffffff;

    font-size: 14px;

    font-weight: 700;

}


.sm-card-header span {

    color: #64748b;

    font-size: 10px;

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

    padding: 13px 20px;

    background: rgba(8, 11, 24, .35);

    border-bottom: 1px solid var(--sm-border);

    color: #64748b;

    font-size: 9px;

    font-weight: 700;

    text-align: left;

    text-transform: uppercase;

    white-space: nowrap;

}


.sm-table td {

    padding: 15px 20px;

    border-bottom: 1px solid rgba(37, 43, 69, .55);

    color: #cbd5e1;

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
   ORDER ID
========================================================= */

.sm-order-id {

    display: inline-flex;

    align-items: center;

    gap: 7px;

    color: #a78bfa;

    font-weight: 700;

}


.sm-order-icon {

    width: 28px;

    height: 28px;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    border-radius: 7px;

    background: rgba(139, 92, 246, .10);

    color: #a78bfa;

}


/* =========================================================
   CUSTOMER
========================================================= */

.sm-customer {

    color: #e2e8f0;

    font-weight: 500;

}


/* =========================================================
   DATE
========================================================= */

.sm-date {

    color: #94a3b8;

}


/* =========================================================
   STATUS
========================================================= */

.sm-status {

    display: inline-flex;

    align-items: center;

    gap: 6px;

    padding: 5px 9px;

    border: 1px solid rgba(139, 92, 246, .18);

    border-radius: 20px;

    background: rgba(139, 92, 246, .08);

    color: #c4b5fd;

    font-size: 10px;

    font-weight: 600;

}


.sm-status-dot {

    width: 6px;

    height: 6px;

    border-radius: 50%;

    background: #8b5cf6;

}


/* =========================================================
   EMPTY
========================================================= */

.sm-empty {

    padding: 60px 20px;

    text-align: center;

}


.sm-empty-icon {

    width: 58px;

    height: 58px;

    display: flex;

    align-items: center;

    justify-content: center;

    margin: 0 auto 15px;

    border-radius: 14px;

    background: rgba(139, 92, 246, .08);

    color: #8b5cf6;

    font-size: 23px;

}


.sm-empty h4 {

    margin: 0 0 6px;

    color: #e2e8f0;

    font-size: 14px;

    font-weight: 600;

}


.sm-empty p {

    margin: 0;

    color: #64748b;

    font-size: 11px;

}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 700px) {

    .sm-orders-page {

        padding: 20px 12px;

    }


    .sm-orders-header {

        align-items: flex-start;

        flex-direction: column;

        gap: 15px;

    }


    .sm-orders-title h1 {

        font-size: 22px;

    }


    .sm-orders-badge {

        width: fit-content;

    }


    .sm-card-header {

        padding: 15px;

    }


    .sm-table th {

        padding: 11px 14px;

    }


    .sm-table td {

        padding: 13px 14px;

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

        <div class="sm-orders-page">

            <div class="sm-orders-container">


                <!-- =================================================
                     PAGE HEADER
                ================================================== -->

                <div class="sm-orders-header">


                    <div class="sm-orders-title">

                        <h1>
                            My Orders
                        </h1>

                        <p>
                            View orders containing your products.
                        </p>

                    </div>


                    <div class="sm-orders-badge">

                        <i class="fas fa-shopping-bag"></i>

                        Vendor Orders

                    </div>


                </div>


                <!-- =================================================
                     ORDERS CARD
                ================================================== -->

                <div class="sm-orders-card">


                    <!-- CARD HEADER -->

                    <div class="sm-card-header">


                        <div class="sm-card-header-left">


                            <div class="sm-card-header-icon">

                                <i class="fas fa-receipt"></i>

                            </div>


                            <div>

                                <h3>
                                    Orders
                                </h3>

                            </div>

                        </div>


                        <?php

                        $orderCount = $result
                            ? mysqli_num_rows($result)
                            : 0;

                        ?>

                        <span>

                            <?= $orderCount ?>

                            order<?= $orderCount === 1 ? '' : 's' ?>

                        </span>


                    </div>


                    <!-- TABLE -->

                    <div class="sm-table-wrapper">


                        <?php if ($result && $orderCount > 0): ?>


                            <table class="sm-table">


                                <thead>

                                    <tr>

                                        <th>
                                            Order
                                        </th>

                                        <th>
                                            Customer
                                        </th>

                                        <th>
                                            Date
                                        </th>

                                        <th>
                                            Status
                                        </th>

                                    </tr>

                                </thead>


                                <tbody>


                                    <?php while (
                                        $order = mysqli_fetch_assoc($result)
                                    ): ?>


                                        <tr>


                                            <!-- ORDER -->

                                            <td>

                                                <span class="sm-order-id">

                                                    <span class="sm-order-icon">

                                                        <i class="fas fa-shopping-bag"></i>

                                                    </span>

                                                    #<?= h($order['id']) ?>

                                                </span>

                                            </td>


                                            <!-- CUSTOMER -->

                                            <td>

                                                <span class="sm-customer">

                                                    <?= h(
                                                        $order['user_email'] ?? '—'
                                                    ) ?>

                                                </span>

                                            </td>


                                            <!-- DATE -->

                                            <td>

                                                <span class="sm-date">

                                                    <?= h(
                                                        $order['order_date'] ?? '—'
                                                    ) ?>

                                                </span>

                                            </td>


                                            <!-- STATUS -->

                                            <td>

                                                <span class="sm-status">

                                                    <span class="sm-status-dot"></span>

                                                    <?= h(
                                                        $order['status'] ?? 'Pending'
                                                    ) ?>

                                                </span>

                                            </td>


                                        </tr>


                                    <?php endwhile; ?>


                                </tbody>


                            </table>


                        <?php else: ?>


                            <!-- EMPTY -->

                            <div class="sm-empty">


                                <div class="sm-empty-icon">

                                    <i class="fas fa-shopping-bag"></i>

                                </div>


                                <h4>
                                    No orders found
                                </h4>


                                <p>
                                    Orders containing your products will appear here.
                                </p>


                            </div>


                        <?php endif; ?>


                    </div>


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


<?php

/*
|--------------------------------------------------------------------------
| CLOSE STATEMENT
|--------------------------------------------------------------------------
*/

mysqli_stmt_close($stmt);

?>