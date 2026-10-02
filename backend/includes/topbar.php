<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<style>

/* =========================================
   SHOPHUB TOPBAR
========================================= */

.shophub-topbar {
    height: 80px !important;
    background: #080b18 !important;
    border-bottom: 1px solid #1d2235 !important;
    box-shadow: none !important;
    padding: 0 25px !important;
}


/* =========================================
   SEARCH
========================================= */

.shophub-topbar .navbar-search {
    width: 530px;
}

.shophub-topbar .navbar-search .input-group {
    border: 1px solid #252b45;
    border-radius: 10px;
    overflow: hidden;
    background: #111626;
}

.shophub-topbar .navbar-search input {
    height: 44px !important;
    background: #111626 !important;
    color: #ffffff !important;
    border: none !important;
    box-shadow: none !important;
    font-size: 13px;
    padding-left: 18px;
}

.shophub-topbar .navbar-search input::placeholder {
    color: #70778c;
}

.shophub-topbar .navbar-search input:focus {
    background: #111626 !important;
    color: #ffffff !important;
    box-shadow: none !important;
}

.shophub-topbar .navbar-search .btn {
    height: 44px;
    width: 55px;
    background: transparent !important;
    color: #8b5cf6 !important;
    border: none !important;
}


/* =========================================
   TOPBAR ICONS
========================================= */

.shophub-topbar .nav-link {
    color: #aeb4ca !important;
    transition: 0.25s;
}

.shophub-topbar .nav-link:hover {
    color: #9b6cff !important;
}

.shophub-topbar .nav-link i {
    font-size: 17px;
}


/* =========================================
   NOTIFICATIONS & MESSAGES
========================================= */

.shophub-topbar #alertsDropdown,
.shophub-topbar #messagesDropdown {

    position: relative !important;

    display: flex !important;
    align-items: center !important;
    justify-content: center !important;

    width: 45px !important;
    height: 55px !important;

    top: 5px !important;
}


/* =========================================
   BADGE
========================================= */

.shophub-topbar #alertsDropdown .badge-counter,
.shophub-topbar #messagesDropdown .badge-counter {

    position: absolute !important;

    top: 12px !important;
    right: 9px !important;

    transform: none !important;

    min-width: 17px !important;
    height: 17px !important;

    padding: 0 3px !important;

    border-radius: 50% !important;

    background: #7c3aed !important;
    color: #ffffff !important;

    font-size: 9px !important;
    line-height: 17px !important;
    text-align: center !important;

    z-index: 10 !important;
}


/* =========================================
   DIVIDER
========================================= */

.shophub-topbar .topbar-divider {
    border-left: 1px solid #252b45 !important;
    height: 35px;
    margin: auto 15px;
}


/* =========================================
   USER NAME
========================================= */

.shophub-topbar .user-name {
    color: #ffffff !important;
    font-size: 14px !important;
    font-weight: 600;
}


/* =========================================
   PROFILE IMAGE
========================================= */

.shophub-topbar .img-profile {

    width: 42px !important;
    height: 42px !important;

    object-fit: cover;

    border: 2px solid #8b5cf6;

    padding: 2px;

    background: #111626;
}


/* =========================================
   DROPDOWN
========================================= */

.shophub-topbar .dropdown-menu {
    background: #111626 !important;
    border: 1px solid #252b45 !important;
    border-radius: 12px !important;
    overflow: hidden;
}

.shophub-topbar .dropdown-item {
    color: #aeb4ca !important;
}

.shophub-topbar .dropdown-item:hover {
    background: rgba(139, 92, 246, 0.12) !important;
    color: #ffffff !important;
}

.shophub-topbar .dropdown-item i {
    color: #8b5cf6 !important;
}

.shophub-topbar .dropdown-divider {
    border-color: #252b45 !important;
}


/* =========================================
   DROPDOWN HEADER
========================================= */

.shophub-topbar .dropdown-header {
    background: linear-gradient(
        90deg,
        #7c3aed,
        #5b5ce2
    ) !important;

    color: #ffffff !important;
}


/* =========================================
   TEXT
========================================= */

.shophub-topbar .text-gray-500,
.shophub-topbar .text-gray-600,
.shophub-topbar .small {
    color: #858ca2 !important;
}


/* =========================================
   ALERT ICONS
========================================= */

.shophub-topbar .icon-circle {
    border-radius: 10px !important;
}

.shophub-topbar .bg-primary {
    background: #7c3aed !important;
}

.shophub-topbar .bg-success {
    background: #16a085 !important;
}

.shophub-topbar .bg-warning {
    background: #e67e22 !important;
}


/* =========================================
   MOBILE
========================================= */

#sidebarToggleTop {
    color: #8b5cf6 !important;
}

#sidebarToggleTop:hover {
    color: #ffffff !important;
}


@media (max-width: 768px) {

    .shophub-topbar {
        padding: 0 10px !important;
    }

    .shophub-topbar .navbar-search {
        width: 100%;
    }

}

</style>


<!-- =========================================
     TOPBAR
========================================= -->

<nav class="navbar navbar-expand navbar-light topbar mb-4 static-top shophub-topbar">


    <!-- MOBILE SIDEBAR BUTTON -->

    <button
        id="sidebarToggleTop"
        class="btn btn-link d-md-none rounded-circle mr-3"
    >

        <i class="fa fa-bars"></i>

    </button>


    <!-- SEARCH -->

    <form
        class="d-none d-sm-inline-block form-inline mr-auto ml-md-3 my-2 my-md-0 mw-100 navbar-search"
    >

        <div class="input-group">

            <input
                type="text"
                class="form-control small"
                placeholder="Search for products, categories..."
                aria-label="Search"
            >

            <div class="input-group-append">

                <button
                    class="btn"
                    type="button"
                >

                    <i class="fas fa-search fa-sm"></i>

                </button>

            </div>

        </div>

    </form>


    <!-- TOPBAR NAVIGATION -->

    <ul class="navbar-nav ml-auto">


        <!-- MOBILE SEARCH -->

        <li class="nav-item dropdown no-arrow d-sm-none">

            <a
                class="nav-link dropdown-toggle"
                href="#"
                id="searchDropdown"
                role="button"
                data-toggle="dropdown"
            >

                <i class="fas fa-search fa-fw"></i>

            </a>

        </li>


        <!-- NOTIFICATIONS -->

        <li class="nav-item dropdown no-arrow mx-1">

            <a
                class="nav-link dropdown-toggle"
                href="#"
                id="alertsDropdown"
                role="button"
                data-toggle="dropdown"
                aria-haspopup="true"
                aria-expanded="false"
            >

                <i class="fas fa-bell fa-fw"></i>

                <span class="badge badge-danger badge-counter">
                    3+
                </span>

            </a>


            <div
                class="dropdown-list dropdown-menu dropdown-menu-right shadow animated--grow-in"
                aria-labelledby="alertsDropdown"
            >

                <h6 class="dropdown-header">
                    Alerts Center
                </h6>


                <a
                    class="dropdown-item d-flex align-items-center"
                    href="#"
                >

                    <div class="mr-3">

                        <div class="icon-circle bg-primary">

                            <i class="fas fa-file-alt text-white"></i>

                        </div>

                    </div>

                    <div>

                        <div class="small">
                            December 12, 2019
                        </div>

                        <span class="font-weight-bold">
                            A new monthly report is ready to download!
                        </span>

                    </div>

                </a>


                <a
                    class="dropdown-item d-flex align-items-center"
                    href="#"
                >

                    <div class="mr-3">

                        <div class="icon-circle bg-success">

                            <i class="fas fa-donate text-white"></i>

                        </div>

                    </div>

                    <div>

                        <div class="small">
                            December 7, 2019
                        </div>

                        $290.29 has been deposited into your account!

                    </div>

                </a>


                <a
                    class="dropdown-item d-flex align-items-center"
                    href="#"
                >

                    <div class="mr-3">

                        <div class="icon-circle bg-warning">

                            <i class="fas fa-exclamation-triangle text-white"></i>

                        </div>

                    </div>

                    <div>

                        <div class="small">
                            December 2, 2019
                        </div>

                        Spending Alert: High spending detected.

                    </div>

                </a>


                <a
                    class="dropdown-item text-center small"
                    href="#"
                >
                    Show All Alerts
                </a>

            </div>

        </li>


        <!-- MESSAGES -->

        <li class="nav-item dropdown no-arrow mx-1">

            <a
                class="nav-link dropdown-toggle"
                href="#"
                id="messagesDropdown"
                role="button"
                data-toggle="dropdown"
                aria-haspopup="true"
                aria-expanded="false"
            >

                <i class="fas fa-envelope fa-fw"></i>

                <span class="badge badge-danger badge-counter">
                    7
                </span>

            </a>


            <div
                class="dropdown-list dropdown-menu dropdown-menu-right shadow animated--grow-in"
                aria-labelledby="messagesDropdown"
            >

                <h6 class="dropdown-header">
                    Message Center
                </h6>


                <a
                    class="dropdown-item d-flex align-items-center"
                    href="#"
                >

                    <div class="dropdown-list-image mr-3">

                        <img
                            class="rounded-circle"
                            src="/ShopMind-main/img/undraw_profile_1.svg"
                            alt="Profile"
                        >

                        <div class="status-indicator bg-success"></div>

                    </div>

                    <div class="font-weight-bold">

                        <div class="text-truncate">
                            Hi there! I am wondering if you can help me.
                        </div>

                        <div class="small">
                            Emily Fowler · 58m
                        </div>

                    </div>

                </a>


                <a
                    class="dropdown-item d-flex align-items-center"
                    href="#"
                >

                    <div class="dropdown-list-image mr-3">

                        <img
                            class="rounded-circle"
                            src="/ShopMind-main/img/undraw_profile_2.svg"
                            alt="Profile"
                        >

                    </div>

                    <div>

                        <div class="text-truncate">
                            I have the photos that you ordered last month.
                        </div>

                        <div class="small">
                            Jae Chun · 1d
                        </div>

                    </div>

                </a>


                <a
                    class="dropdown-item text-center small"
                    href="#"
                >
                    Read More Messages
                </a>

            </div>

        </li>


        <!-- DIVIDER -->

        <div class="topbar-divider d-none d-sm-block"></div>


        <!-- USER -->

        <li class="nav-item dropdown no-arrow">

            <a
                class="nav-link dropdown-toggle d-flex align-items-center"
                href="#"
                id="userDropdown"
                role="button"
                data-toggle="dropdown"
                aria-haspopup="true"
                aria-expanded="false"
            >

                <span class="mr-3 d-none d-lg-inline user-name">

                    <?= htmlspecialchars($_SESSION['name'] ?? 'User') ?>

                </span>


                <img
                    class="img-profile rounded-circle"
                    src="/ShopMind-main/img/undraw_profile.svg"
                    alt="Profile"
                >

            </a>
        </li>

    </ul>

</nav>

<!-- END TOPBAR -->