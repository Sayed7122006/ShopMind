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
| UPDATE ORDER STATUS
|--------------------------------------------------------------------------
*/

$message = '';

$message_type = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $order_id = (int) (
        $_POST['id'] ?? 0
    );

    $status = trim(
        $_POST['status'] ?? ''
    );


    /*
    |--------------------------------------------------------------------------
    | ALLOWED STATUSES
    |--------------------------------------------------------------------------
    */

    $allowed_statuses = [

        'Pending',

        'Processing',

        'Completed',

        'Cancelled'

    ];


    if ($order_id <= 0) {

        $message =
            'Invalid order ID.';

        $message_type = 'error';

    } elseif (
        !in_array(
            $status,
            $allowed_statuses,
            true
        )
    ) {

        $message =
            'Invalid order status.';

        $message_type = 'error';

    } else {


        /*
        |--------------------------------------------------------------------------
        | UPDATE
        |--------------------------------------------------------------------------
        */

        $stmt = $conn->prepare(
            "UPDATE orders
             SET status = ?
             WHERE id = ?"
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
            'si',
            $status,
            $order_id
        );


        if ($stmt->execute()) {

            $message =
                'Order status updated successfully.';

            $message_type = 'success';

        } else {

            $message =
                'Unable to update order status.';

            $message_type = 'error';

        }


        $stmt->close();

    }

}


/*
|--------------------------------------------------------------------------
| GET ORDERS
|--------------------------------------------------------------------------
|
| Current orders table is expected to contain:
|
| id
| user_email
| order_date
| status
|
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        id,
        user_email,
        order_date,
        status

    FROM orders

    ORDER BY order_date DESC
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
    Orders Management - ShopMind
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

    .orders-table-wrapper {

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
       ORDER ID
    ===================================================== */

    .order-id {

        color: #ffffff;

        font-weight: 700;

    }


    .order-hash {

        color: #8b93a7;

    }


    /* =====================================================
       CUSTOMER
    ===================================================== */

    .customer-email {

        color: #d1d5db;

    }


    /* =====================================================
       DATE
    ===================================================== */

    .order-date {

        color: #aeb4ca;

    }


    /* =====================================================
       STATUS BADGES
    ===================================================== */

    .status-badge {

        display: inline-flex;

        align-items: center;

        justify-content: center;

        min-width: 95px;

        padding: 7px 12px;

        border-radius: 20px;

        font-size: 12px;

        font-weight: 700;

    }


    .status-pending {

        background: rgba(
            251,
            191,
            36,
            0.12
        );

        color: #fbbf24;

    }


    .status-processing {

        background: rgba(
            96,
            165,
            250,
            0.12
        );

        color: #60a5fa;

    }


    .status-completed {

        background: rgba(
            74,
            222,
            128,
            0.12
        );

        color: #4ade80;

    }


    .status-cancelled {

        background: rgba(
            248,
            113,
            113,
            0.12
        );

        color: #f87171;

    }


    /* =====================================================
       UPDATE FORM
    ===================================================== */

    .status-form {

        display: flex;

        align-items: center;

        gap: 8px;

    }


    .status-select {

        min-width: 130px;

        height: 36px;

        padding: 0 10px;

        background: #111626 !important;

        border: 1px solid #252b45 !important;

        border-radius: 7px;

        color: #ffffff !important;

        font-size: 12px;

        outline: none;

    }


    .status-select:focus {

        border-color: #7c3aed !important;

        box-shadow: none !important;

    }


    .status-select option {

        background: #111626;

        color: #ffffff;

    }


    .save-btn {

        height: 36px;

        padding: 0 13px;

        border: none;

        border-radius: 7px;

        background: #7c3aed;

        color: #ffffff;

        font-size: 12px;

        font-weight: 700;

        cursor: pointer;

        transition: .2s ease;

    }


    .save-btn:hover {

        background: #6d28d9;

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

            min-width: 850px;

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

                Orders Management

            </div>


            <div class="shopmind-subtitle">

                View and manage all customer orders.

            </div>


            <!-- =================================================
                 ORDERS CARD
            ================================================== -->

            <div class="shopmind-card">


                <!-- CARD HEADER -->

                <div class="shopmind-card-header">

                    <h6 class="shopmind-card-title">

                        All Orders

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

                <div class="orders-table-wrapper">


                    <table class="shopmind-table">


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

                                <th>
                                    Update
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php if ($result->num_rows > 0): ?>


                            <?php while (
                                $order =
                                $result->fetch_assoc()
                            ): ?>


                                <?php

                                $current_status =
                                    (string) (
                                        $order['status']
                                        ?? 'Pending'
                                    );


                                $status_class =
                                    match ($current_status) {

                                        'Processing'
                                            => 'status-processing',

                                        'Completed'
                                            => 'status-completed',

                                        'Cancelled'
                                            => 'status-cancelled',

                                        default
                                            => 'status-pending',

                                    };

                                ?>


                                <tr>


                                    <!-- ORDER -->

                                    <td>

                                        <div class="order-id">

                                            <span class="order-hash">
                                                #
                                            </span>

                                            <?= h(
                                                $order['id']
                                            ) ?>

                                        </div>

                                    </td>


                                    <!-- CUSTOMER -->

                                    <td>

                                        <span class="customer-email">

                                            <?= h(
                                                $order['user_email']
                                                ?? 'Unknown'
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- DATE -->

                                    <td>

                                        <span class="order-date">

                                            <?= h(
                                                $order['order_date']
                                                ?? ''
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- STATUS -->

                                    <td>

                                        <span
                                            class="status-badge <?= h($status_class) ?>"
                                        >

                                            <?= h(
                                                $current_status
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- UPDATE -->

                                    <td>


                                        <form
                                            method="POST"
                                            class="status-form"
                                        >


                                            <input
                                                type="hidden"
                                                name="id"
                                                value="<?= h(
                                                    $order['id']
                                                ) ?>"
                                            >


                                            <select
                                                name="status"
                                                class="status-select"
                                                required
                                            >


                                                <option
                                                    value="Pending"
                                                    <?= $current_status === 'Pending'
                                                        ? 'selected'
                                                        : '' ?>
                                                >

                                                    Pending

                                                </option>


                                                <option
                                                    value="Processing"
                                                    <?= $current_status === 'Processing'
                                                        ? 'selected'
                                                        : '' ?>
                                                >

                                                    Processing

                                                </option>


                                                <option
                                                    value="Completed"
                                                    <?= $current_status === 'Completed'
                                                        ? 'selected'
                                                        : '' ?>
                                                >

                                                    Completed

                                                </option>


                                                <option
                                                    value="Cancelled"
                                                    <?= $current_status === 'Cancelled'
                                                        ? 'selected'
                                                        : '' ?>
                                                >

                                                    Cancelled

                                                </option>


                                            </select>


                                            <button
                                                type="submit"
                                                class="save-btn"
                                            >

                                                Save

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

                                    <i class="fas fa-shopping-bag"></i>


                                    <p>

                                        No orders found.

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
