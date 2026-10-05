
<?php

/*
|--------------------------------------------------------------------------
| Session
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| Current Page
|--------------------------------------------------------------------------
*/

$currentPage = basename($_SERVER['PHP_SELF']);


/*
|--------------------------------------------------------------------------
| PROJECT BASE URL
|--------------------------------------------------------------------------
|
| index.php موجود في Root المشروع.
| مثال:
| http://localhost/ShopMind-main_dashbord/
|
*/

$projectBaseUrl = rtrim(
    dirname($_SERVER['SCRIPT_NAME']),
    '/'
);


/*
|--------------------------------------------------------------------------
| FIX PROJECT BASE URL
|--------------------------------------------------------------------------
|
| لو الـ sidebar اتعمل include من داخل:
| admin_sidebar
| vendor_sidebar
| account
|
| نرجع للـ Root بتاع المشروع.
|
*/

$scriptDirectory = str_replace(
    '\\',
    '/',
    dirname($_SERVER['SCRIPT_NAME'])
);


if (
    strpos($scriptDirectory, '/admin_sidebar') !== false ||
    strpos($scriptDirectory, '/vendor_sidebar') !== false ||
    strpos($scriptDirectory, '/account') !== false
) {

    $projectBaseUrl = preg_replace(
        '#/(admin_sidebar|vendor_sidebar|account).*#',
        '',
        $scriptDirectory
    );
}


/*
|--------------------------------------------------------------------------
| URL HELPER
|--------------------------------------------------------------------------
*/

function shopmind_url($path = '')
{
    global $projectBaseUrl;

    $path = ltrim($path, '/');

    return $projectBaseUrl . '/' . $path;
}

?>


<style>

/* =========================================
   SHOPMIND SIDEBAR
========================================= */

.sidebar {
    background: #080b18 !important;
    min-height: 100vh;
}

.sidebar .sidebar-brand {
    height: 80px;
    text-decoration: none;
    background: #080b18;
}

.sidebar .sidebar-brand-text {
    color: #ffffff !important;
    font-weight: 700;
}

.sidebar .sidebar-brand-icon {
    color: #8b5cf6 !important;
}

.sidebar .nav-item {
    margin: 3px 12px;
}

.sidebar .nav-link {
    color: #aeb4ca !important;
    border-radius: 10px;
    padding: 12px 15px;
    transition: 0.25s;
}

.sidebar .nav-link i {
    color: #8b5cf6 !important;
    margin-right: 8px;
}

.sidebar .nav-link:hover {
    background: rgba(139, 92, 246, 0.12) !important;
    color: #ffffff !important;
}

.sidebar .nav-item.active .nav-link {
    background: linear-gradient(
        90deg,
        rgba(124, 58, 237, 0.30),
        rgba(91, 92, 226, 0.15)
    ) !important;

    color: #ffffff !important;
}

.sidebar .nav-item.active .nav-link i {
    color: #ffffff !important;
}


/* =========================================
   SECTION TITLES
========================================= */

.sidebar .sidebar-heading {
    color: #70778c !important;
    font-size: 11px;
    font-weight: 700;
    padding: 15px 15px 8px;
    text-transform: uppercase;
}


/* =========================================
   DIVIDER
========================================= */

.sidebar hr.sidebar-divider {
    border-top: 1px solid #1d2235 !important;
    margin: 15px;
}


/* =========================================
   LOGOUT
========================================= */

.sidebar .logout-link {
    color: #ff6b6b !important;
}

.sidebar .logout-link i {
    color: #ff6b6b !important;
}

.sidebar .logout-link:hover {
    background: rgba(255, 107, 107, 0.10) !important;
    color: #ff8585 !important;
}


/* =========================================
   SIDEBAR CARD
========================================= */

.sidebar .sidebar-card {
    background: #111626 !important;
    border: 1px solid #252b45;
}

.sidebar .sidebar-card .text-center {
    color: #858ca2 !important;
}


/* =========================================
   MOBILE
========================================= */

@media (max-width: 768px) {

    .sidebar {
        min-height: 100vh;
    }

}

</style>


<!-- =========================================
     SIDEBAR
========================================= -->

<ul
    class="navbar-nav sidebar sidebar-dark accordion"
    id="accordionSidebar"
>


    <!-- =========================================
         BRAND
    ========================================= -->

    <a
        class="sidebar-brand d-flex align-items-center justify-content-center"
        href="<?= shopmind_url('index.php') ?>"
    >

        <div class="sidebar-brand-icon">

            <i class="fas fa-store"></i>

        </div>

        <div class="sidebar-brand-text mx-3">

            ShopMind

        </div>

    </a>


    <hr class="sidebar-divider my-0">


    <!-- =========================================
         HOME
    ========================================= -->

    <li class="nav-item <?= $currentPage === 'index.php' ? 'active' : '' ?>">

        <a
            class="nav-link"
            href="<?= shopmind_url('index.php') ?>"
        >

            <i class="fas fa-fw fa-home"></i>

            <span>Home</span>

        </a>

    </li>


    <!-- =========================================
         VIEW STORE
    ========================================= -->

    <li class="nav-item">

        <a
            class="nav-link"
            href="/ShopMind/frontend/index.php"
            target="_blank"
        >

            <i class="fas fa-store"></i>

            <span>View Store</span>

        </a>

    </li>


    <hr class="sidebar-divider">


    <!-- =========================================
         ADMIN SECTION
    ========================================= -->

    <?php if (
        isset($_SESSION['role']) &&
        $_SESSION['role'] === 'admin'
    ): ?>


        <div class="sidebar-heading">
            Management
        </div>


        <!-- USERS -->

        <li class="nav-item <?= $currentPage === 'users-management.php' ? 'active' : '' ?>">

            <a
                class="nav-link"
                href="<?= shopmind_url('admin_sidebar/users-management.php') ?>"
            >

                <i class="fas fa-users"></i>

                <span>Users Management</span>

            </a>

        </li>


        <!-- CATEGORIES -->

        <li class="nav-item <?= $currentPage === 'categories-management.php' ? 'active' : '' ?>">

            <a
                class="nav-link"
                href="<?= shopmind_url('admin_sidebar/categories-management.php') ?>"
            >

                <i class="fas fa-list"></i>

                <span>Categories Management</span>

            </a>

        </li>


        <!-- BRANDS -->

        <li class="nav-item <?= $currentPage === 'brands-management.php' ? 'active' : '' ?>">

            <a
                class="nav-link"
                href="<?= shopmind_url('admin_sidebar/brands-management.php') ?>"
            >

                <i class="fas fa-tags"></i>

                <span>Brands Management</span>

            </a>

        </li>


        <!-- PRODUCTS -->

        <li class="nav-item <?= $currentPage === 'products-management.php' ? 'active' : '' ?>">

            <a
                class="nav-link"
                href="<?= shopmind_url('admin_sidebar/products-management.php') ?>"
            >

                <i class="fas fa-boxes"></i>

                <span>Products Management</span>

            </a>

        </li>


        <!-- ORDERS -->

        <li class="nav-item <?= $currentPage === 'orders-management.php' ? 'active' : '' ?>">

            <a
                class="nav-link"
                href="<?= shopmind_url('admin_sidebar/orders-management.php') ?>"
            >

                <i class="fas fa-shopping-cart"></i>

                <span>Orders Management</span>

            </a>

        </li>


        <!-- PROFILE -->

        <li class="nav-item <?= $currentPage === 'profile.php' ? 'active' : '' ?>">

            <a
                class="nav-link"
                href="<?= shopmind_url('account/profile.php') ?>"
            >

                <i class="fas fa-user"></i>

                <span>Profile</span>

            </a>

        </li>


        <!-- LOGOUT -->

        <li class="nav-item">

            <a
                class="nav-link logout-link"
                href="<?= shopmind_url('account/logout.php') ?>"
            >

                <i class="fas fa-sign-out-alt"></i>

                <span>Logout</span>

            </a>

        </li>


    <?php endif; ?>


    <!-- =========================================
         VENDOR SECTION
    ========================================= -->

    <?php if (
        isset($_SESSION['role']) &&
        $_SESSION['role'] === 'vendor'
    ): ?>


        <div class="sidebar-heading">
            Vendor
        </div>


        <!-- MY PRODUCTS -->

        <li class="nav-item <?= $currentPage === 'my-products.php' ? 'active' : '' ?>">

            <a
                class="nav-link"
                href="<?= shopmind_url('vendor_sidebar/my-products.php') ?>"
            >

                <i class="fas fa-box"></i>

                <span>My Products</span>

            </a>

        </li>


        <!-- ADD PRODUCT -->

        <li class="nav-item <?= $currentPage === 'add-products.php' ? 'active' : '' ?>">

            <a
                class="nav-link"
                href="<?= shopmind_url('vendor_sidebar/add-products.php') ?>"
            >

                <i class="fas fa-plus-circle"></i>

                <span>Add Product</span>

            </a>

        </li>


        <!-- MY ORDERS -->

        <li class="nav-item <?= $currentPage === 'my-orders.php' ? 'active' : '' ?>">

            <a
                class="nav-link"
                href="<?= shopmind_url('vendor_sidebar/my-orders.php') ?>"
            >

                <i class="fas fa-shopping-bag"></i>

                <span>My Orders</span>

            </a>

        </li>


        <!-- PROFILE -->

        <li class="nav-item <?= $currentPage === 'profile.php' ? 'active' : '' ?>">

            <a
                class="nav-link"
                href="<?= shopmind_url('account/profile.php') ?>"
            >

                <i class="fas fa-user"></i>

                <span>Profile</span>

            </a>

        </li>


        <!-- LOGOUT -->

        <li class="nav-item">

            <a
                class="nav-link logout-link"
                href="<?= shopmind_url('account/logout.php') ?>"
            >

                <i class="fas fa-sign-out-alt"></i>

                <span>Logout</span>

            </a>

        </li>


    <?php endif; ?>


</ul>

<!-- END SIDEBAR -->

