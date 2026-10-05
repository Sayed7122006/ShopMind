<?php

session_start();

/*
|--------------------------------------------------------------------------
| STORE LOGIN STATUS
|--------------------------------------------------------------------------
*/

$storeLoggedIn =
    isset($_SESSION['store_user_id']) &&
    (int)$_SESSION['store_user_id'] > 0;


/*
|--------------------------------------------------------------------------
| STORE USER DATA
|--------------------------------------------------------------------------
*/

$storeUserName =
    $_SESSION['store_user_name'] ?? '';

$storeUserEmail =
    $_SESSION['store_user_email'] ?? '';


/*
|--------------------------------------------------------------------------
| PROJECT URLS
|--------------------------------------------------------------------------
|
| Current file:
|
| ShopMind-main/
| ├── account/
| │   ├── login.php
| │   ├── register.php
| │   └── logout.php
| │
| └── frontend/
|     └── index.php
|
| Therefore from frontend/index.php:
|
| ../account/
|
|--------------------------------------------------------------------------
*/

$loginUrl =
    is_file(__DIR__ . '/account/login.php') ? 'account/login.php' : '../account/login.php';

$registerUrl =
    is_file(__DIR__ . '/account/register.php') ? 'account/register.php' : '../account/register.php';

$checkoutUrl =
    is_file(__DIR__ . '/account/checkout.php') ? 'account/checkout.php' : '../account/checkout.php';

$logoutUrl =
    is_file(__DIR__ . '/account/logout.php') ? 'account/logout.php' : '../account/logout.php';


/*
|--------------------------------------------------------------------------
| SAFE URLS
|--------------------------------------------------------------------------
*/

$loginUrlSafe =
    htmlspecialchars(
        $loginUrl,
        ENT_QUOTES,
        'UTF-8'
    );

$registerUrlSafe =
    htmlspecialchars(
        $registerUrl,
        ENT_QUOTES,
        'UTF-8'
    );

$checkoutUrlSafe =
    htmlspecialchars(
        $checkoutUrl,
        ENT_QUOTES,
        'UTF-8'
    );

$logoutUrlSafe =
    htmlspecialchars(
        $logoutUrl,
        ENT_QUOTES,
        'UTF-8'
    );

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>FINDO | Online Shopping</title>


    <!-- FAVICON -->

    <link
        rel="icon"
        type="image/jpeg"
        href="img/new_logo.jpg"
    >


    <!-- MAIN CSS -->

    <link
        rel="stylesheet"
        href="CSS/style.css"
    >


    <!-- SWIPER -->

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css"
    >


    <!-- FONT AWESOME -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
    >

</head>


<body>


<!-- =========================================================
     HEADER
========================================================= -->

<header>


    <!-- =====================================================
         TOP HEADER
    ====================================================== -->

    <div class="top_header">

        <div class="container">


            <!-- LOGO -->

            <a
                href="index.php"
                class="logo"
            >

                <img
                    src="img/new_logo.jpg"
                    alt="FINDO Logo"
                >

            </a>


            <!-- SEARCH -->

            <form
                class="search_box"
                id="search-form"
                autocomplete="off"
            >

                <div class="select_box">

                    <select
                        id="category"
                        name="category"
                    >

                        <option value="All Categories">
                            All Categories
                        </option>

                        <option value="electronics">
                            Electronics
                        </option>

                        <option value="mobiles">
                            Mobiles
                        </option>

                        <option value="appliances">
                            Appliances
                        </option>

                    </select>

                </div>


                <input
                    type="text"
                    id="search"
                    name="search"
                    placeholder="Search for products…"
                >


                <button
                    type="submit"
                    aria-label="Search"
                >

                    <i class="fa-solid fa-magnifying-glass"></i>

                </button>

            </form>


            <!-- HEADER ICONS -->

            <div class="header_icons">


                <!-- WISHLIST -->

                <div
                    class="icon"
                    id="wishlist-icon"
                    title="Wishlist"
                >

                    <i class="fa-regular fa-heart"></i>

                    <span class="count_favouite">
                        0
                    </span>

                </div>


                <!-- CART -->

                <div
                    class="icon"
                    id="cart-icon"
                    title="Cart"
                >

                    <i class="fa-solid fa-cart-shopping"></i>

                    <span class="count_item_header">
                        0
                    </span>

                </div>


            </div>

        </div>

    </div>



    <!-- =====================================================
         BOTTOM HEADER
    ====================================================== -->

    <div class="bottom_header">

        <div class="container">


            <!-- NAVIGATION -->

            <nav class="nav">

                <div class="category_dropdown">


                    <div
                        class="category_btn"
                        id="category-btn"
                        role="button"
                        tabindex="0"
                    >

                        <i class="fa-solid fa-bars"></i>

                        <span>
                            Browse Categories
                        </span>

                        <i class="fa-solid fa-angle-down"></i>

                    </div>


                    <ul
                        class="nav_links"
                        id="nav-menu"
                    >

                        <li>

                            <a href="index.php">

                                <i class="fas fa-home"></i>

                                Home

                            </a>

                        </li>


                        <li>

                            <a href="#products-section">

                                <i class="fas fa-tags"></i>

                                Deals

                            </a>

                        </li>


                        <li>
                         <a href="about.php">
                         <i class="fas fa-info-circle"></i>
                            About Us
                            </a>
                            
                        
                        </li>


                        

                    </ul>

                </div>

            </nav>



            <!-- =================================================
                 STORE ACCOUNT
            ================================================== -->

            <div class="login_signup_btns">


                <?php if (!$storeLoggedIn): ?>


                    <!-- LOGIN -->

                    <a
                        href="<?= $loginUrlSafe ?>"
                        class="btn btn-login"
                    >

                        <span>
                            Login
                        </span>

                        <i class="fa-solid fa-right-to-bracket"></i>

                    </a>


                    <!-- SIGN UP -->

                    <a
                        href="<?= $registerUrlSafe ?>"
                        class="btn btn-signup"
                    >

                        <span>
                            Sign Up
                        </span>

                        <i class="fa-solid fa-user-plus"></i>

                    </a>


                <?php else: ?>


                    <!-- USER NAME -->

                    <div
                        class="store-user"
                        title="<?= htmlspecialchars(
                            $storeUserEmail,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    >

                        <i class="fa-solid fa-user"></i>

                        <span>
                            <?= htmlspecialchars(
                                $storeUserName,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </span>

                    </div>


                    <!-- LOGOUT -->

                    <a
                        href="<?= $logoutUrlSafe ?>"
                        class="btn btn-login"
                    >

                        <span>
                            Logout
                        </span>

                        <i class="fa-solid fa-right-from-bracket"></i>

                    </a>


                <?php endif; ?>


            </div>

        </div>

    </div>

</header>



<!-- =========================================================
     HERO SLIDER
========================================================= -->

<div class="slider">

    <div class="container">


        <!-- MAIN SLIDER -->

        <div class="mySwiper swiper">

            <div class="swiper-wrapper">


                <div class="swiper-slide">

                    <img
                        src="img/banner_home2.jpeg"
                        alt="Banner 1"
                    >

                </div>


                <div class="swiper-slide">

                    <img
                        src="img/banner_box4.jpeg"
                        alt="Banner 2"
                    >

                </div>


                <div class="swiper-slide">

                    <img
                        src="img/banner_box5.jpeg"
                        alt="Banner 3"
                    >

                </div>


                <div class="swiper-slide">

                    <img
                        src="img/banner_home3.jpeg"
                        alt="Banner 4"
                    >

                </div>


            </div>


            <div class="swiper-pagination"></div>

        </div>



        <!-- SIDE BANNER -->

        <div class="side-banner">

            <img
                src="img/banner_home1.jpeg"
                alt="Smart Watch"
            >


            <div class="side-banner-text">

                <h3>
                    Smart Watch
                </h3>


                <p>
                    From <strong>$45.50</strong>
                </p>


                <a
                    href="#products-section"
                    class="side-banner-btn"
                >

                    Shop Now

                </a>

            </div>

        </div>

    </div>

</div>



<!-- =========================================================
     FEATURED CARDS
========================================================= -->

<div class="products-grid-section">


    <div class="product-card">

        <img
            src="img/banner_box1.jpeg"
            alt="Smartphone"
        >

        <h3>
            New Generation Smartphone
        </h3>

        <p>
            Designed for better performance
        </p>

        <p class="price">
            $299
        </p>

    </div>



    <div class="product-card">

        <img
            src="img/banner3_3.jpeg"
            alt="Headphone"
        >

        <h3>
            Wireless Headphone
        </h3>

        <p>
            High-definition sound &amp; noise cancellation
        </p>

        <p class="price">
            $32
        </p>

    </div>



    <div class="product-card">

        <img
            src="img/banner3_1.jpeg"
            alt="Laptop"
        >

        <h3>
            Ultra-Slim Laptop
        </h3>

        <p>
            Powerful processing with stunning display
        </p>

        <p class="price">
            $800
        </p>

    </div>


</div>



<!-- =========================================================
     PRODUCTS
========================================================= -->

<section
    class="products-section"
    id="products-section"
>

    <div class="container">

        <div class="section-header">

            <h2>
                Our Products
            </h2>

            <p>
                Discover our best-selling items
            </p>

        </div>


        <div
            class="products-grid"
            id="products-grid"
        ></div>

    </div>

</section>



<!-- =========================================================
     CART OVERLAY
========================================================= -->

<div
    class="cart-overlay"
    id="cart-overlay"
></div>



<!-- =========================================================
     CART SIDEBAR
========================================================= -->

<aside
    class="cart-sidebar"
    id="cart-sidebar"
    aria-label="Shopping Cart"
>

    <div class="cart-header">

        <h3>

            <i class="fa-solid fa-cart-shopping"></i>

            Your Cart

        </h3>


        <button
            class="cart-close-btn"
            id="cart-close"
            type="button"
            aria-label="Close cart"
        >

            <i class="fa-solid fa-xmark"></i>

        </button>

    </div>


    <div
        class="cart-list"
        id="cart-list"
    >

        <p class="empty-msg">
            Your cart is empty 🛒
        </p>

    </div>


    <div class="cart-footer">

        <div class="cart-total-row">

            <span>
                Total:
            </span>

            <span id="cart-total">
                $0.00
            </span>

        </div>


        <button
            class="checkout-btn"
            id="checkout-btn"
            type="button"
        >

            Checkout

            <i class="fa-solid fa-arrow-right"></i>

        </button>

    </div>

</aside>



<!-- =========================================================
     TOAST
========================================================= -->

<div
    id="toast-container"
    aria-live="polite"
></div>



<!-- =========================================================
     FOOTER
========================================================= -->

<footer>

    <div class="container">

        <div class="footer-content">


            <div class="footer-logo">

                <img
                    src="img/new_logo.jpg"
                    alt="FINDO"
                >

                <p>
                    Your one-stop online shopping destination.
                </p>

            </div>



            <div class="footer-links">

                <h4>
                    Quick Links
                </h4>

                <ul>

                    <li>

                        <a href="index.php">
                            Home
                        </a>

                    </li>

                    <li>

                        <a href="#products-section">
                            Deals
                        </a>

                    </li>

                    <li>

                        <a href="about.php">
                            About Us
                        </a>

                    </li>

                    

                </ul>

            </div>



            <div class="footer-links">

                <h4>
                    Categories
                </h4>

                <ul>

                    <li>

                        <a href="#">
                            Electronics
                        </a>

                    </li>

                    <li>

                        <a href="#">
                            Mobiles
                        </a>

                    </li>

                    <li>

                        <a href="#">
                            Appliances
                        </a>

                    </li>

                    <li>

                        <a href="#">
                            Fashion
                        </a>

                    </li>

                </ul>

            </div>



            <div class="footer-contact">

                <h4>
                    Follow Us
                </h4>

                <div class="social-icons">

                    <a
                        href="#"
                        aria-label="Facebook"
                    >

                        <i class="fa-brands fa-facebook"></i>

                    </a>


                    <a
                        href="#"
                        aria-label="Instagram"
                    >

                        <i class="fa-brands fa-instagram"></i>

                    </a>


                    <a
                        href="#"
                        aria-label="Twitter"
                    >

                        <i class="fa-brands fa-twitter"></i>

                    </a>


                    <a
                        href="#"
                        aria-label="WhatsApp"
                    >

                        <i class="fa-brands fa-whatsapp"></i>

                    </a>

                </div>

            </div>


        </div>



        <div class="footer-bottom">

            <p>
                &copy; 2025 FINDO. All rights reserved.
            </p>

            <img
                src="img/payment_method.png"
                alt="Payment Methods"
            >

        </div>

    </div>

</footer>



<!-- =========================================================
     SWIPER JS
========================================================= -->

<script
    src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"
></script>



<!-- =========================================================
     MAIN JS
========================================================= -->

<script src="main.js"></script>



<!-- =========================================================
     STORE URLS + LOGIN STATUS
========================================================= -->

<script>

const STORE_LOGIN_URL =
    <?= json_encode($loginUrl) ?>;

const STORE_REGISTER_URL =
    <?= json_encode($registerUrl) ?>;

const STORE_CHECKOUT_URL =
    <?= json_encode($checkoutUrl) ?>;

const STORE_LOGOUT_URL =
    <?= json_encode($logoutUrl) ?>;

const STORE_LOGGED_IN =
    <?= $storeLoggedIn ? 'true' : 'false' ?>;

</script>


</body>

</html>