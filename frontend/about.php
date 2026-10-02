<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us | ShopMind</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f5f7f6;
            color: #263b35;
            line-height: 1.7;
        }

        .about-container {
            max-width: 1100px;
            margin: 60px auto;
            padding: 20px;
        }

        .about-hero {
            background: #173f35;
            color: white;
            text-align: center;
            padding: 65px 25px;
            border-radius: 18px;
            margin-bottom: 35px;
        }

        .about-hero h1 {
            font-size: 42px;
            margin-bottom: 15px;
        }

        .about-hero p {
            font-size: 18px;
            color: #e1eee9;
        }

        .about-section {
            background: white;
            padding: 35px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
        }

        .about-section h2 {
            color: #173f35;
            margin-bottom: 15px;
            font-size: 26px;
        }

        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .feature-card {
            background: #f5f8f6;
            padding: 25px;
            border-radius: 12px;
            border: 1px solid #e3ebe6;
        }

        .feature-card h3 {
            color: #173f35;
            margin-bottom: 10px;
        }

        .about-footer {
            text-align: center;
            padding: 25px;
            color: #64736d;
        }

        .back-button {
            display: inline-block;
            margin-top: 25px;
            padding: 12px 25px;
            background: white;
            color: #173f35;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
        }

        .back-button:hover {
            background: #e8f1ec;
        }

        @media (max-width: 600px) {
            .about-hero h1 {
                font-size: 32px;
            }

            .about-section {
                padding: 25px;
            }
        }
    </style>
</head>

<body>

    <main class="about-container">

        <section class="about-hero">
            <h1>About ShopMind</h1>
            <p>Smart Shopping, Made Simple.</p>

            <a class="back-button" href="index.php">
                Back to Store
            </a>
        </section>

        <section class="about-section">
            <h2>Who We Are</h2>
            <p>
                Welcome to ShopMind, your online shopping destination.
                We provide a simple and convenient platform where customers
                can explore products, view details, and place orders with ease.
            </p>
        </section>

        <section class="about-section">
            <h2>Our Mission</h2>
            <p>
                Our mission is to make online shopping easier by providing
                a user-friendly experience, clear product information,
                and a convenient ordering process.
            </p>
        </section>

        <section class="about-section">
            <h2>What We Offer</h2>

            <div class="features">
                <div class="feature-card">
                    <h3>Explore Products</h3>
                    <p>Browse different products and discover what you need.</p>
                </div>

                <div class="feature-card">
                    <h3>Easy Shopping</h3>
                    <p>Add products to your cart and place your order easily.</p>
                </div>

                <div class="feature-card">
                    <h3>Customer Experience</h3>
                    <p>Enjoy a simple and organized shopping experience.</p>
                </div>
            </div>
        </section>

        <section class="about-section">
            <h2>Our Vision</h2>
            <p>
                We aim to build a convenient online shopping platform
                that connects customers with products through a smooth
                and accessible digital experience.
            </p>
        </section>

        <div class="about-footer">
            <p>Thank you for choosing ShopMind.</p>
            <strong>ShopMind — Smart Shopping, Made Simple.</strong>
        </div>

    </main>

</body>
</html>