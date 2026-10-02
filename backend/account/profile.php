<?php

session_start();

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
    header("Location: login.php");
    exit();
}


/*
|--------------------------------------------------------------------------
| GET CURRENT USER
|--------------------------------------------------------------------------
*/

$user_id = (int) $_SESSION['user_id'];

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

mysqli_stmt_bind_param($stmt, "i", $user_id);
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

    header("Location: login.php");
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

    <title>My Profile - ShopHub</title>


    <!-- =====================================================
         FONT AWESOME
    ====================================================== -->

    <link
        rel="stylesheet"
        href="/ShopMind-main_dashbord/vendor/fontawesome-free/css/all.min.css"
    >


    <!-- =====================================================
         SB ADMIN 2
    ====================================================== -->

    <link
        rel="stylesheet"
        href="/ShopMind-main_dashbord/css/sb-admin-2.min.css"
    >


    <!-- =====================================================
         NUNITO
    ====================================================== -->

    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900"
        rel="stylesheet"
    >


<style>

/* =========================================================
   GLOBAL
========================================================= */

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

    min-height: 100vh;

}

#content {

    background: #080b18 !important;

    min-height: calc(100vh - 80px);

}


/* =========================================================
   PROFILE PAGE
========================================================= */

.profile-page {

    padding: 30px;

}


/* =========================================================
   PAGE HEADER
========================================================= */

.profile-heading {

    margin-bottom: 25px;

}

.profile-heading h1 {

    color: #ffffff;

    font-size: 25px;

    font-weight: 700;

    margin: 0 0 5px 0;

}

.profile-heading p {

    color: #858ca2;

    font-size: 14px;

    margin: 0;

}


/* =========================================================
   PROFILE CARD
========================================================= */

.profile-card {

    background: #0d1122;

    border: 1px solid #252b45;

    border-radius: 18px;

    overflow: hidden;

    box-shadow:
        0 10px 35px rgba(0, 0, 0, 0.25);

}


/* =========================================================
   PROFILE HEADER
========================================================= */

.profile-card-header {

    padding: 35px;

    background:
        linear-gradient(
            135deg,
            rgba(124, 58, 237, 0.22),
            rgba(79, 93, 245, 0.08)
        );

    border-bottom: 1px solid #252b45;

}

.profile-header-content {

    display: flex;

    align-items: center;

    gap: 22px;

}


/* =========================================================
   AVATAR
========================================================= */

.profile-avatar {

    width: 90px;

    height: 90px;

    border-radius: 50%;

    object-fit: cover;

    background: #111626;

    border: 3px solid #8b5cf6;

    padding: 3px;

    box-shadow:
        0 0 0 5px rgba(139, 92, 246, 0.10),
        0 8px 25px rgba(0, 0, 0, 0.3);

}


/* =========================================================
   USER NAME
========================================================= */

.profile-user-name {

    margin: 0 0 5px 0;

    color: #ffffff;

    font-size: 23px;

    font-weight: 700;

}

.profile-user-email {

    color: #858ca2;

    font-size: 14px;

    margin-bottom: 10px;

}


/* =========================================================
   ROLE
========================================================= */

.profile-role {

    display: inline-flex;

    align-items: center;

    gap: 7px;

    padding: 6px 12px;

    border-radius: 20px;

    background: rgba(139, 92, 246, 0.15);

    border: 1px solid rgba(139, 92, 246, 0.3);

    color: #b79aff;

    font-size: 12px;

    font-weight: 700;

}


/* =========================================================
   BUTTONS
========================================================= */

.profile-actions {

    display: flex;

    flex-wrap: wrap;

    gap: 10px;

    margin-top: 16px;

}


.edit-profile-btn,
.change-password-btn {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 8px;

    padding: 9px 17px;

    border-radius: 9px;

    font-size: 13px;

    font-weight: 700;

    text-decoration: none !important;

    transition: all 0.25s ease;

}


/* =========================================================
   EDIT PROFILE
========================================================= */

.edit-profile-btn {

    background:
        linear-gradient(
            135deg,
            #7c3aed,
            #5b5ce2
        );

    border: 1px solid rgba(139, 92, 246, 0.5);

    color: #ffffff !important;

    box-shadow:
        0 5px 15px rgba(124, 58, 237, 0.20);

}

.edit-profile-btn:hover {

    color: #ffffff !important;

    transform: translateY(-1px);

    box-shadow:
        0 7px 20px rgba(124, 58, 237, 0.35);

}


/* =========================================================
   CHANGE PASSWORD
========================================================= */

.change-password-btn {

    background: #111626;

    border: 1px solid #252b45;

    color: #aeb4ca !important;

}

.change-password-btn:hover {

    color: #ffffff !important;

    border-color: #8b5cf6;

    background: rgba(139, 92, 246, 0.10);

    transform: translateY(-1px);

}

.change-password-btn i {

    color: #8b5cf6;

}


/* =========================================================
   PROFILE BODY
========================================================= */

.profile-body {

    padding: 30px 35px;

}


/* =========================================================
   SECTION TITLE
========================================================= */

.section-title {

    color: #ffffff;

    font-size: 16px;

    font-weight: 700;

    margin-bottom: 18px;

}

.section-title i {

    color: #8b5cf6;

    margin-right: 8px;

}


/* =========================================================
   INFO GRID
========================================================= */

.info-grid {

    display: grid;

    grid-template-columns: repeat(2, 1fr);

    gap: 16px;

}

.info-item {

    background: #111626;

    border: 1px solid #252b45;

    border-radius: 12px;

    padding: 18px 20px;

}

.info-label {

    display: block;

    color: #70778c;

    font-size: 11px;

    font-weight: 700;

    text-transform: uppercase;

    letter-spacing: 0.6px;

    margin-bottom: 7px;

}

.info-value {

    color: #ffffff;

    font-size: 14px;

    font-weight: 600;

    word-break: break-word;

}

.info-value i {

    color: #8b5cf6;

    margin-right: 7px;

}


/* =========================================================
   MOBILE
========================================================= */

@media (max-width: 768px) {

    .profile-page {

        padding: 15px;

    }

    .profile-card-header {

        padding: 25px;

    }

    .profile-header-content {

        align-items: flex-start;

    }

    .profile-avatar {

        width: 70px;

        height: 70px;

    }

    .profile-user-name {

        font-size: 19px;

    }

    .profile-body {

        padding: 25px;

    }

    .info-grid {

        grid-template-columns: 1fr;

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

    <div id="content-wrapper" class="d-flex flex-column">


        <div id="content">


            <!-- =================================================
                 TOPBAR
            ================================================== -->

            <?php

            include __DIR__ . '/../includes/topbar.php';

            ?>


            <!-- =================================================
                 PROFILE CONTENT
            ================================================== -->

            <div class="profile-page">


                <!-- PAGE HEADER -->

                <div class="profile-heading">

                    <h1>
                        My Profile
                    </h1>

                    <p>
                        Manage and view your ShopHub account information.
                    </p>

                </div>


                <!-- PROFILE CARD -->

                <div class="profile-card">


                    <!-- =================================================
                         PROFILE HEADER
                    ================================================== -->

                    <div class="profile-card-header">


                        <div class="profile-header-content">


                            <!-- AVATAR -->

                            <img
                                src="/ShopMind-main_dashbord/img/undraw_profile.svg"
                                alt="Profile"
                                class="profile-avatar"
                            >


                            <!-- USER DATA -->

                            <div>


                                <h2 class="profile-user-name">

                                    <?= htmlspecialchars($user_name) ?>

                                </h2>


                                <div class="profile-user-email">

                                    <?= htmlspecialchars($user_email) ?>

                                </div>


                                <span class="profile-role">

                                    <i class="fas fa-user-shield"></i>

                                    <?= htmlspecialchars($role_name) ?>

                                </span>


                                <!-- ACTIONS -->

                                <div class="profile-actions">


                                    <a
                                        href="edit-profile.php"
                                        class="edit-profile-btn"
                                    >

                                        <i class="fas fa-edit"></i>

                                        Edit Profile

                                    </a>


                                    <a
                                        href="change-password.php"
                                        class="change-password-btn"
                                    >

                                        <i class="fas fa-key"></i>

                                        Change Password

                                    </a>


                                </div>


                            </div>


                        </div>


                    </div>


                    <!-- =================================================
                         PROFILE BODY
                    ================================================== -->

                    <div class="profile-body">


                        <!-- ACCOUNT INFORMATION -->

                        <div class="section-title">

                            <i class="fas fa-user"></i>

                            Account Information

                        </div>


                        <div class="info-grid">


                            <!-- NAME -->

                            <div class="info-item">

                                <span class="info-label">
                                    Full Name
                                </span>

                                <div class="info-value">

                                    <i class="fas fa-user"></i>

                                    <?= htmlspecialchars($user_name) ?>

                                </div>

                            </div>


                            <!-- EMAIL -->

                            <div class="info-item">

                                <span class="info-label">
                                    Email Address
                                </span>

                                <div class="info-value">

                                    <i class="fas fa-envelope"></i>

                                    <?= htmlspecialchars($user_email) ?>

                                </div>

                            </div>


                            <!-- ROLE -->

                            <div class="info-item">

                                <span class="info-label">
                                    Account Role
                                </span>

                                <div class="info-value">

                                    <i class="fas fa-user-tag"></i>

                                    <?= htmlspecialchars($role_name) ?>

                                </div>

                            </div>


                            <!-- ID -->

                            <div class="info-item">

                                <span class="info-label">
                                    Account ID
                                </span>

                                <div class="info-value">

                                    <i class="fas fa-id-badge"></i>

                                    <?= (int) $user['id'] ?>

                                </div>

                            </div>


                        </div>


                    </div>


                </div>


            </div>


        </div>


        <!-- =====================================================
             FOOTER
        ====================================================== -->

        <?php

        include __DIR__ . '/../includes/footer.php';

        ?>


    </div>


</div>


<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script src="/ShopMind-main_dashbord/vendor/jquery/jquery.min.js"></script>

<script src="/ShopMind-main_dashbord/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

<script src="/ShopMind-main_dashbord/vendor/jquery-easing/jquery.easing.min.js"></script>

<script src="/ShopMind-main_dashbord/js/sb-admin-2.min.js"></script>


</body>

</html>