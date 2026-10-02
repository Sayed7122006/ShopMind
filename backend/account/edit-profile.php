<?php

session_start();

/*
|--------------------------------------------------------------------------
| PROJECT BASE URL
|--------------------------------------------------------------------------
*/

$BASE_URL = '/ShopMind-main_dashbord';


/*
|--------------------------------------------------------------------------
| DATABASE CONNECTION
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../fun/db_connection.php';


/*
|--------------------------------------------------------------------------
| CHECK LOGIN
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['role'])
) {
    header("Location: " . $BASE_URL . "/account/login.php");
    exit();
}


/*
|--------------------------------------------------------------------------
| ALLOW ADMIN AND VENDOR ONLY
|--------------------------------------------------------------------------
*/

if (
    $_SESSION['role'] !== 'admin' &&
    $_SESSION['role'] !== 'vendor'
) {
    header("Location: " . $BASE_URL . "/account/login.php");
    exit();
}


/*
|--------------------------------------------------------------------------
| GET CURRENT USER ID
|--------------------------------------------------------------------------
*/

$user_id = (int) $_SESSION['user_id'];

$message = '';
$message_type = '';


/*
|--------------------------------------------------------------------------
| UPDATE PROFILE
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');


    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */

    if ($name === '' || $email === '') {

        $message = "Please fill in all fields.";
        $message_type = "error";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Please enter a valid email address.";
        $message_type = "error";

    } else {

        /*
        |--------------------------------------------------------------------------
        | CHECK IF EMAIL EXISTS FOR ANOTHER USER
        |--------------------------------------------------------------------------
        */

        $check_sql = "
            SELECT id
            FROM users
            WHERE Email = ?
            AND id != ?
            LIMIT 1
        ";

        $check_stmt = mysqli_prepare($conn, $check_sql);

        if (!$check_stmt) {

            $message = "Database error.";
            $message_type = "error";

        } else {

            mysqli_stmt_bind_param(
                $check_stmt,
                "si",
                $email,
                $user_id
            );

            mysqli_stmt_execute($check_stmt);

            $check_result = mysqli_stmt_get_result($check_stmt);

            $email_exists = mysqli_num_rows($check_result) > 0;

            mysqli_stmt_close($check_stmt);


            /*
            |--------------------------------------------------------------------------
            | EMAIL ALREADY EXISTS
            |--------------------------------------------------------------------------
            */

            if ($email_exists) {

                $message = "This email address is already in use.";
                $message_type = "error";

            } else {

                /*
                |--------------------------------------------------------------------------
                | UPDATE USER
                |--------------------------------------------------------------------------
                */

                $update_sql = "
                    UPDATE users
                    SET Name = ?, Email = ?
                    WHERE id = ?
                ";

                $update_stmt = mysqli_prepare($conn, $update_sql);

                if (!$update_stmt) {

                    $message = "Database error.";
                    $message_type = "error";

                } else {

                    mysqli_stmt_bind_param(
                        $update_stmt,
                        "ssi",
                        $name,
                        $email,
                        $user_id
                    );


                    if (mysqli_stmt_execute($update_stmt)) {

                        /*
                        |--------------------------------------------------------------------------
                        | UPDATE SESSION
                        |--------------------------------------------------------------------------
                        */

                        $_SESSION['user_name']  = $name;
                        $_SESSION['user_email'] = $email;

                        /*
                        |--------------------------------------------------------------------------
                        | KEEP OLD SESSION NAMES
                        |--------------------------------------------------------------------------
                        */

                        $_SESSION['name']  = $name;
                        $_SESSION['email'] = $email;


                        mysqli_stmt_close($update_stmt);


                        /*
                        |--------------------------------------------------------------------------
                        | RETURN TO PROFILE
                        |--------------------------------------------------------------------------
                        */

                        header(
                            "Location: " .
                            $BASE_URL .
                            "/account/profile.php?updated=1"
                        );

                        exit();

                    } else {

                        $message =
                            "Something went wrong while updating your profile.";

                        $message_type = "error";

                        mysqli_stmt_close($update_stmt);
                    }
                }
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| GET CURRENT USER DATA
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        id,
        Name,
        Email,
        role
    FROM users
    WHERE id = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Database error: " . mysqli_error($conn));
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $user_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$user = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


/*
|--------------------------------------------------------------------------
| USER NOT FOUND
|--------------------------------------------------------------------------
*/

if (!$user) {

    session_unset();
    session_destroy();

    header(
        "Location: " .
        $BASE_URL .
        "/account/login.php"
    );

    exit();
}


/*
|--------------------------------------------------------------------------
| USER DATA
|--------------------------------------------------------------------------
*/

$user_name  = $user['Name'];
$user_email = $user['Email'];
$user_role  = $user['role'];


/*
|--------------------------------------------------------------------------
| ROLE NAME
|--------------------------------------------------------------------------
*/

if ($user_role === 'admin') {

    $role_name = 'Administrator';

} elseif ($user_role === 'vendor') {

    $role_name = 'Vendor';

} else {

    $role_name = 'Customer';

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

    <title>Edit Profile - ShopHub</title>


    <!-- =====================================================
         FONT AWESOME
    ====================================================== -->

    <link
        rel="stylesheet"
        href="<?= $BASE_URL ?>/vendor/fontawesome-free/css/all.min.css"
    >


    <!-- =====================================================
         SB ADMIN 2
    ====================================================== -->

    <link
        rel="stylesheet"
        href="<?= $BASE_URL ?>/css/sb-admin-2.min.css"
    >


    <!-- =====================================================
         NUNITO
    ====================================================== -->

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

    min-height: 100%;

    background: #080b18 !important;

    color: #ffffff;

    font-family: 'Nunito', sans-serif;

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

}

#content {

    background: #080b18 !important;

    min-height: calc(100vh - 80px);

}


/* =====================================================
   PAGE
===================================================== */

.edit-profile-page {

    padding: 25px 30px 45px;

}


/* =====================================================
   PAGE HEADER
===================================================== */

.edit-heading {

    margin-bottom: 25px;

}

.edit-heading h1 {

    color: #ffffff;

    font-size: 25px;

    font-weight: 700;

    margin: 0 0 5px;

}

.edit-heading p {

    color: #858ca2;

    font-size: 14px;

    margin: 0;

}


/* =====================================================
   CARD
===================================================== */

.edit-card {

    width: 100%;

    max-width: 850px;

    background: #0d1122;

    border: 1px solid #252b45;

    border-radius: 18px;

    overflow: hidden;

    box-shadow:
        0 10px 35px rgba(0, 0, 0, 0.25);

}


/* =====================================================
   CARD HEADER
===================================================== */

.edit-card-header {

    padding: 30px 35px;

    background:
        linear-gradient(
            135deg,
            rgba(124, 58, 237, 0.22),
            rgba(79, 93, 245, 0.08)
        );

    border-bottom: 1px solid #252b45;

}

.profile-header {

    display: flex;

    align-items: center;

    gap: 18px;

}


/* =====================================================
   AVATAR
===================================================== */

.edit-avatar {

    width: 75px;

    height: 75px;

    border-radius: 50%;

    object-fit: cover;

    background: #111626;

    border: 3px solid #8b5cf6;

    padding: 3px;

    box-shadow:
        0 0 0 5px rgba(139, 92, 246, 0.10);

}


/* =====================================================
   USER NAME
===================================================== */

.edit-user-name {

    margin: 0 0 4px;

    color: #ffffff;

    font-size: 21px;

    font-weight: 700;

}

.edit-user-role {

    color: #b79aff;

    font-size: 13px;

    font-weight: 600;

}


/* =====================================================
   FORM
===================================================== */

.edit-card-body {

    padding: 32px 35px;

}

.form-title {

    color: #ffffff;

    font-size: 16px;

    font-weight: 700;

    margin-bottom: 22px;

}

.form-title i {

    color: #8b5cf6;

    margin-right: 8px;

}

.form-group {

    margin-bottom: 20px;

}

.form-label {

    display: block;

    color: #aeb4ca;

    font-size: 13px;

    font-weight: 700;

    margin-bottom: 8px;

}


/* =====================================================
   INPUT
===================================================== */

.form-control-shophub {

    width: 100%;

    height: 48px;

    padding: 0 15px;

    background: #111626;

    color: #ffffff;

    border: 1px solid #252b45;

    border-radius: 10px;

    outline: none;

    transition: 0.2s;

    box-sizing: border-box;

}

.form-control-shophub:focus {

    border-color: #8b5cf6;

    box-shadow:
        0 0 0 3px rgba(139, 92, 246, 0.10);

}

.form-control-shophub::placeholder {

    color: #70778c;

}


/* =====================================================
   INPUT ICON
===================================================== */

.input-wrapper {

    position: relative;

}

.input-wrapper i {

    position: absolute;

    left: 15px;

    top: 50%;

    transform: translateY(-50%);

    color: #8b5cf6;

    pointer-events: none;

}

.input-wrapper .form-control-shophub {

    padding-left: 43px;

}


/* =====================================================
   ROLE BOX
===================================================== */

.role-box {

    display: flex;

    align-items: center;

    gap: 12px;

    padding: 15px;

    margin-top: 5px;

    background: rgba(139, 92, 246, 0.06);

    border: 1px solid rgba(139, 92, 246, 0.18);

    border-radius: 10px;

}

.role-icon {

    width: 38px;

    height: 38px;

    min-width: 38px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 9px;

    background: rgba(139, 92, 246, 0.12);

    color: #9b6cff;

}

.role-label {

    color: #70778c;

    font-size: 11px;

    text-transform: uppercase;

    letter-spacing: 0.5px;

    margin-bottom: 2px;

}

.role-value {

    color: #ffffff;

    font-size: 13px;

    font-weight: 700;

}


/* =====================================================
   BUTTONS
===================================================== */

.form-actions {

    display: flex;

    align-items: center;

    gap: 12px;

    margin-top: 28px;

    padding-top: 24px;

    border-top: 1px solid #252b45;

}

.btn-save {

    border: none;

    padding: 11px 22px;

    border-radius: 9px;

    background:
        linear-gradient(
            135deg,
            #7c3aed,
            #5b5ce2
        );

    color: #ffffff !important;

    font-size: 13px;

    font-weight: 700;

    cursor: pointer;

    transition: 0.2s;

}

.btn-save:hover {

    transform: translateY(-1px);

    box-shadow:
        0 6px 18px rgba(124, 58, 237, 0.25);

}

.btn-cancel {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    padding: 11px 20px;

    border-radius: 9px;

    background: #111626;

    border: 1px solid #252b45;

    color: #aeb4ca !important;

    text-decoration: none !important;

    font-size: 13px;

    font-weight: 700;

    transition: 0.2s;

}

.btn-cancel:hover {

    color: #ffffff !important;

    border-color: #8b5cf6;

    text-decoration: none !important;

}


/* =====================================================
   ALERT
===================================================== */

.shop-alert {

    padding: 13px 15px;

    border-radius: 10px;

    margin-bottom: 22px;

    font-size: 13px;

    font-weight: 600;

}

.shop-alert-error {

    background: rgba(231, 76, 60, 0.10);

    border: 1px solid rgba(231, 76, 60, 0.25);

    color: #ff8b82;

}


/* =====================================================
   MOBILE
===================================================== */

@media (max-width: 768px) {

    .edit-profile-page {

        padding: 15px;

    }

    .edit-card-header {

        padding: 25px;

    }

    .edit-card-body {

        padding: 25px;

    }

    .profile-header {

        align-items: flex-start;

    }

    .edit-avatar {

        width: 65px;

        height: 65px;

    }

    .edit-user-name {

        font-size: 18px;

    }

    .form-actions {

        flex-direction: column;

        align-items: stretch;

    }

    .btn-save,
    .btn-cancel {

        width: 100%;

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

    include __DIR__ . '/../includes/sidebar.php';

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

            include __DIR__ . '/../includes/topbar.php';

            ?>


            <!-- =================================================
                 EDIT PROFILE CONTENT
            ================================================== -->

            <div class="edit-profile-page">


                <!-- PAGE HEADER -->

                <div class="edit-heading">

                    <h1>
                        Edit Profile
                    </h1>

                    <p>
                        Update your ShopHub account information.
                    </p>

                </div>


                <!-- CARD -->

                <div class="edit-card">


                    <!-- =================================================
                         CARD HEADER
                    ================================================== -->

                    <div class="edit-card-header">


                        <div class="profile-header">


                            <img
                                src="<?= $BASE_URL ?>/img/undraw_profile.svg"
                                alt="Profile"
                                class="edit-avatar"
                            >


                            <div>

                                <h2 class="edit-user-name">

                                    <?= htmlspecialchars($user_name) ?>

                                </h2>


                                <div class="edit-user-role">

                                    <?= htmlspecialchars($role_name) ?>

                                </div>

                            </div>


                        </div>


                    </div>


                    <!-- =================================================
                         CARD BODY
                    ================================================== -->

                    <div class="edit-card-body">


                        <?php if ($message !== ''): ?>

                            <div class="shop-alert shop-alert-error">

                                <i class="fas fa-exclamation-circle mr-2"></i>

                                <?= htmlspecialchars($message) ?>

                            </div>

                        <?php endif; ?>


                        <div class="form-title">

                            <i class="fas fa-user-edit"></i>

                            Personal Information

                        </div>


                        <!-- =================================================
                             FORM
                        ================================================== -->

                        <form
                            method="POST"
                            action=""
                        >


                            <!-- NAME -->

                            <div class="form-group">

                                <label class="form-label">

                                    Full Name

                                </label>


                                <div class="input-wrapper">

                                    <i class="fas fa-user"></i>

                                    <input
                                        type="text"
                                        name="name"
                                        class="form-control-shophub"
                                        value="<?= htmlspecialchars($user_name) ?>"
                                        placeholder="Enter your full name"
                                        maxlength="100"
                                        required
                                    >

                                </div>

                            </div>


                            <!-- EMAIL -->

                            <div class="form-group">

                                <label class="form-label">

                                    Email Address

                                </label>


                                <div class="input-wrapper">

                                    <i class="fas fa-envelope"></i>

                                    <input
                                        type="email"
                                        name="email"
                                        class="form-control-shophub"
                                        value="<?= htmlspecialchars($user_email) ?>"
                                        placeholder="Enter your email address"
                                        maxlength="150"
                                        required
                                    >

                                </div>

                            </div>


                            <!-- ROLE -->

                            <div class="role-box">


                                <div class="role-icon">

                                    <i class="fas fa-user-shield"></i>

                                </div>


                                <div>

                                    <div class="role-label">

                                        Account Role

                                    </div>


                                    <div class="role-value">

                                        <?= htmlspecialchars($role_name) ?>

                                    </div>

                                </div>


                            </div>


                            <!-- ACTIONS -->

                            <div class="form-actions">


                                <button
                                    type="submit"
                                    class="btn-save"
                                >

                                    <i class="fas fa-save mr-2"></i>

                                    Save Changes

                                </button>


                                <a
                                    href="<?= $BASE_URL ?>/account/profile.php"
                                    class="btn-cancel"
                                >

                                    <i class="fas fa-times mr-2"></i>

                                    Cancel

                                </a>


                            </div>


                        </form>


                    </div>


                </div>


            </div>


        </div>


        <!-- =================================================
             FOOTER
        ================================================== -->

        <?php

        include __DIR__ . '/../includes/footer.php';

        ?>


    </div>


</div>


<!-- =====================================================
     JAVASCRIPT
====================================================== -->

<script src="<?= $BASE_URL ?>/vendor/jquery/jquery.min.js"></script>

<script src="<?= $BASE_URL ?>/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

<script src="<?= $BASE_URL ?>/vendor/jquery-easing/jquery.easing.min.js"></script>

<script src="<?= $BASE_URL ?>/js/sb-admin-2.min.js"></script>


</body>

</html>