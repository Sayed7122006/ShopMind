<?php

session_start();

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
    header('Location: ../account/login.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| ONLY VENDOR
|--------------------------------------------------------------------------
*/

$role = $_SESSION['role'];
$uid  = (int) $_SESSION['user_id'];

if ($role !== 'vendor') {
    http_response_code(403);
    exit('Forbidden');
}


/*
|--------------------------------------------------------------------------
| HELPER
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
| FORM DATA
|--------------------------------------------------------------------------
*/

$name     = '';
$category = '';
$price    = '';

$error   = '';
$success = '';


/*
|--------------------------------------------------------------------------
| ADD PRODUCT
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');

    $category = trim(
        $_POST['category'] ?? ''
    );

    $price = trim(
        $_POST['price'] ?? ''
    );


    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */

    if ($name === '') {

        $error = 'Please enter the product name.';

    } elseif ($category === '') {

        $error = 'Please enter the category.';

    } elseif (
        $price === '' ||
        !is_numeric($price) ||
        (float) $price <= 0
    ) {

        $error = 'Please enter a valid price.';

    }


    /*
    |--------------------------------------------------------------------------
    | IMAGE
    |--------------------------------------------------------------------------
    */

    $img = null;

    if (
        $error === '' &&
        isset($_FILES['img']) &&
        $_FILES['img']['error'] !== UPLOAD_ERR_NO_FILE
    ) {

        if (
            $_FILES['img']['error'] !== UPLOAD_ERR_OK
        ) {

            $error = 'There was a problem uploading the image.';

        } elseif (
            $_FILES['img']['size'] > 3 * 1024 * 1024
        ) {

            $error = 'Image must be smaller than 3MB.';

        } else {

            $imageInfo = @getimagesize(
                $_FILES['img']['tmp_name']
            );

            if (!$imageInfo) {

                $error = 'Please upload a valid image.';

            } else {

                $allowedTypes = [
                    IMAGETYPE_JPEG,
                    IMAGETYPE_PNG,
                    IMAGETYPE_WEBP,
                    IMAGETYPE_GIF
                ];

                if (
                    !in_array(
                        $imageInfo[2],
                        $allowedTypes,
                        true
                    )
                ) {

                    $error =
                        'Only JPG, PNG, WEBP or GIF images are allowed.';

                } else {

                    $img = file_get_contents(
                        $_FILES['img']['tmp_name']
                    );
                }
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | INSERT PRODUCT
    |--------------------------------------------------------------------------
    */

    if ($error === '') {

        $priceValue = (float) $price;


        /*
        |--------------------------------------------------------------------------
        | CURRENT PRODUCTS TABLE
        |--------------------------------------------------------------------------
        |
        | vendor_id
        | img
        | name
        | price
        | category
        |
        */

        $sql = "
            INSERT INTO products
            (
                vendor_id,
                img,
                name,
                price,
                category
            )
            VALUES
            (?, ?, ?, ?, ?)
        ";


        $stmt = mysqli_prepare(
            $conn,
            $sql
        );


        if (!$stmt) {

            $error =
                'Database error: ' .
                mysqli_error($conn);

        } else {

            /*
            |--------------------------------------------------------------------------
            | img = MEDIUMBLOB
            |--------------------------------------------------------------------------
            */

            mysqli_stmt_bind_param(
                $stmt,
                'ibsds',
                $uid,
                $img,
                $name,
                $priceValue,
                $category
            );


            /*
            |--------------------------------------------------------------------------
            | SEND IMAGE
            |--------------------------------------------------------------------------
            */

            if ($img !== null) {

                mysqli_stmt_send_long_data(
                    $stmt,
                    1,
                    $img
                );
            }


            /*
            |--------------------------------------------------------------------------
            | EXECUTE
            |--------------------------------------------------------------------------
            */

            if (
                mysqli_stmt_execute($stmt)
            ) {

                $success =
                    'Product added successfully.';

                $name     = '';
                $category = '';
                $price    = '';

            } else {

                $error =
                    'Could not save product: ' .
                    mysqli_stmt_error($stmt);
            }


            mysqli_stmt_close($stmt);
        }
    }
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
   SHOPMIND ADD PRODUCT
========================================================= */

:root {

    --sm-bg: #080b18;
    --sm-card: #0d1122;
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


.sm-page {

    min-height: calc(100vh - 70px);

    padding: 30px;

    background: var(--sm-bg);

}


.sm-container {

    width: 100%;

    max-width: 900px;

    margin: 0 auto;

}


/* =========================================================
   HEADER
========================================================= */

.sm-page-header {

    margin-bottom: 24px;

}


.sm-page-header h1 {

    margin: 0 0 7px;

    color: #ffffff;

    font-size: 26px;

    font-weight: 700;

}


.sm-page-header p {

    margin: 0;

    color: var(--sm-muted);

    font-size: 13px;

}


/* =========================================================
   CARD
========================================================= */

.sm-card {

    width: 100%;

    padding: 26px;

    background: var(--sm-card);

    border: 1px solid var(--sm-border);

    border-radius: 14px;

    box-sizing: border-box;

}


/* =========================================================
   FORM GROUP
========================================================= */

.sm-form-group {

    margin-bottom: 20px;

}


.sm-label {

    display: block;

    margin-bottom: 8px;

    color: #e2e8f0;

    font-size: 12px;

    font-weight: 600;

}


.sm-required {

    color: #a78bfa;

}


/* =========================================================
   INPUT
========================================================= */

.sm-input {

    width: 100%;

    height: 44px;

    padding: 0 13px;

    box-sizing: border-box;

    background: #111626;

    border: 1px solid var(--sm-border);

    border-radius: 9px;

    outline: none;

    color: #ffffff;

    font-size: 12px;

    transition: .2s ease;

}


.sm-input::placeholder {

    color: #64748b;

}


.sm-input:focus {

    border-color: var(--sm-purple);

    box-shadow:
        0 0 0 3px rgba(139, 92, 246, .10);

}


/* =========================================================
   FILE
========================================================= */

.sm-file {

    width: 100%;

    padding: 10px;

    box-sizing: border-box;

    background: #111626;

    border: 1px dashed var(--sm-border);

    border-radius: 9px;

    color: #94a3b8;

    font-size: 11px;

    cursor: pointer;

}


.sm-file:hover {

    border-color: var(--sm-purple);

}


/* =========================================================
   ACTIONS
========================================================= */

.sm-actions {

    display: flex;

    align-items: center;

    gap: 10px;

    margin-top: 26px;

    padding-top: 22px;

    border-top: 1px solid var(--sm-border);

}


.sm-btn {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 8px;

    min-width: 125px;

    height: 42px;

    padding: 0 17px;

    border: 0;

    border-radius: 9px;

    background:
        linear-gradient(
            135deg,
            #8b5cf6,
            #4f46e5
        );

    color: #ffffff !important;

    font-size: 11px;

    font-weight: 600;

    text-decoration: none !important;

    cursor: pointer;

    transition: .2s ease;

}


.sm-btn:hover {

    transform: translateY(-2px);

    box-shadow:
        0 7px 18px rgba(79, 70, 229, .20);

}


.sm-btn-secondary {

    background: #111626;

    border: 1px solid var(--sm-border);

    color: #cbd5e1 !important;

}


.sm-btn-secondary:hover {

    border-color: #3b4260;

    box-shadow: none;

}


/* =========================================================
   ALERTS
========================================================= */

.sm-alert {

    display: flex;

    align-items: center;

    gap: 10px;

    margin-bottom: 20px;

    padding: 12px 14px;

    border-radius: 9px;

    font-size: 11px;

}


.sm-alert-error {

    color: #fca5a5;

    background: rgba(239, 68, 68, .08);

    border: 1px solid rgba(239, 68, 68, .20);

}


.sm-alert-success {

    color: #86efac;

    background: rgba(34, 197, 94, .08);

    border: 1px solid rgba(34, 197, 94, .20);

}


/* =========================================================
   INFO
========================================================= */

.sm-info {

    display: flex;

    align-items: flex-start;

    gap: 10px;

    margin-top: 20px;

    padding: 13px;

    background: rgba(139, 92, 246, .06);

    border: 1px solid rgba(139, 92, 246, .12);

    border-radius: 9px;

}


.sm-info i {

    margin-top: 2px;

    color: #a78bfa;

    font-size: 13px;

}


.sm-info p {

    margin: 0;

    color: #94a3b8;

    font-size: 10px;

    line-height: 1.6;

}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 700px) {

    .sm-page {

        padding: 20px 12px;

    }


    .sm-card {

        padding: 20px;

    }


    .sm-page-header h1 {

        font-size: 22px;

    }


    .sm-actions {

        flex-direction: column;

        align-items: stretch;

    }


    .sm-btn {

        width: 100%;

    }

}

</style>


<div id="content-wrapper" class="d-flex flex-column">

    <div id="content">


        <!-- TOPBAR -->

        <?php

        require_once __DIR__ . '/../includes/topbar.php';

        ?>


        <!-- PAGE -->

        <div class="sm-page">

            <div class="sm-container">


                <!-- PAGE HEADER -->

                <div class="sm-page-header">

                    <h1>
                        Add Product
                    </h1>

                    <p>
                        Add a new product to your store.
                    </p>

                </div>


                <!-- CARD -->

                <div class="sm-card">


                    <!-- ERROR -->

                    <?php if ($error !== ''): ?>

                        <div class="sm-alert sm-alert-error">

                            <i class="fas fa-exclamation-circle"></i>

                            <span>
                                <?= h($error) ?>
                            </span>

                        </div>

                    <?php endif; ?>


                    <!-- SUCCESS -->

                    <?php if ($success !== ''): ?>

                        <div class="sm-alert sm-alert-success">

                            <i class="fas fa-check-circle"></i>

                            <span>
                                <?= h($success) ?>
                            </span>

                        </div>

                    <?php endif; ?>


                    <!-- FORM -->

                    <form
                        method="POST"
                        enctype="multipart/form-data"
                    >


                        <!-- PRODUCT NAME -->

                        <div class="sm-form-group">

                            <label class="sm-label">

                                Product Name

                                <span class="sm-required">
                                    *
                                </span>

                            </label>

                            <input
                                type="text"
                                name="name"
                                class="sm-input"
                                placeholder="Enter product name"
                                value="<?= h($name) ?>"
                                required
                            >

                        </div>


                        <!-- CATEGORY -->

                        <div class="sm-form-group">

                            <label class="sm-label">

                                Category

                                <span class="sm-required">
                                    *
                                </span>

                            </label>

                            <input
                                type="text"
                                name="category"
                                class="sm-input"
                                placeholder="Enter product category"
                                value="<?= h($category) ?>"
                                required
                            >

                        </div>


                        <!-- PRICE -->

                        <div class="sm-form-group">

                            <label class="sm-label">

                                Price

                                <span class="sm-required">
                                    *
                                </span>

                            </label>

                            <input
                                type="number"
                                name="price"
                                class="sm-input"
                                placeholder="0.00"
                                min="0.01"
                                step="0.01"
                                value="<?= h($price) ?>"
                                required
                            >

                        </div>


                        <!-- IMAGE -->

                        <div class="sm-form-group">

                            <label class="sm-label">

                                Product Image

                            </label>

                            <input
                                type="file"
                                name="img"
                                class="sm-file"
                                accept="image/jpeg,image/png,image/webp,image/gif"
                            >

                        </div>


                        <!-- ACTIONS -->

                        <div class="sm-actions">

                            <button
                                type="submit"
                                class="sm-btn"
                            >

                                <i class="fas fa-plus"></i>

                                Add Product

                            </button>


                            <!-- BACK TO PRODUCTS -->

                            <a
                                href="my-products.php"
                                class="sm-btn sm-btn-secondary"
                            >

                                <i class="fas fa-arrow-left"></i>

                                Back to Products

                            </a>

                        </div>


                    </form>

                </div>

            </div>

        </div>

    </div>


    <!-- FOOTER -->

    <?php

    require_once __DIR__ . '/../includes/footer.php';

    ?>

</div>