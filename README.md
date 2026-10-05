# ShopMind 🛒

A full-stack e-commerce web platform featuring modern store management, interactive admin control, and an integrated Machine Learning (AI) engine for smart search, recommendations, and customer review sentiment analysis.

---

## 📁 Project Structure

```
ShopMind/
├── frontend/
│   ├── CSS/
│   ├── img/
│   ├── index.php               # Main store storefront
│   ├── main.js                 # Frontend interactions & ML API integration
│   ├── products.json           # Catalog baseline data
│   ├── cart_api.php            # Cart operations & MySQL persistence
│   ├── products_api.php        # Dynamic product & image loading API
│   └── account/                # Login, registration, & checkout
├── backend/
│   ├── account/                # Admin auth & profile
│   ├── admin_sidebar/          # Orders, Products, Users, Categories, Brands
│   ├── fun/                    # Database connection & auth guards
│   ├── includes/               # Admin layout (header, sidebar, topbar, footer)
│   ├── SQL/                    # Database schema & migrations
│   └── index.php               # Admin entry point
├── ml/
│   ├── api.py                  # FastAPI service for AI endpoints (Port 8001)
│   ├── search.py               # Smart Semantic Search (Arabic/English + typo-tolerant)
│   ├── recommender.py          # Collaborative Filtering Recommender
│   ├── sentiment.py            # Review Sentiment Analysis engine
│   ├── data_cleaning.py        # Pipeline data preprocessing
│   ├── data_loader.py          # Ingestion for CSV & JSON datasets
│   ├── reviews.csv             # Customer reviews dataset
│   ├── interactions.csv        # Shopper behavior dataset
│   ├── requirements.txt        # Python dependencies
│   └── tests/                  # Unit & integration test suite
├── index.php                   # Root redirect to frontend
├── .gitignore
└── README.md
```

---

## 🛠️ Tech Stack

- **Frontend:** HTML5, CSS3, JavaScript (ES6+), Swiper.js, Font Awesome
- **Backend & Database:** PHP 8+, MySQL / MariaDB
- **Machine Learning & AI:** Python 3.x, FastAPI, Uvicorn, Scikit-Learn, Pandas, NumPy, Sentence-Transformers
- **Architecture:** RESTful APIs, modular PHP backend, asynchronous fetch calls

---

## 🧠 Machine Learning & AI Engine (`ml/`)

ShopMind includes an autonomous Machine Learning service running locally via **FastAPI** (`http://127.0.0.1:8001`), directly connected with the frontend store:

### 1. Smart Semantic Search (`search.py`)
- **Multilingual Support:** Seamlessly processes queries in Arabic, Egyptian slang, and English.
- **Typo Tolerance & Character N-Grams:** Resolves misspelled queries (e.g., `موبل` or `تلفون` automatically maps to smartphones such as Redmi, Oppo, and Infinix).
- **Category & Keyword Expansion:** Identifies brands and categories even when queries lack exact model names.

### 2. Personalized Recommendation System (`recommender.py`)
- **Collaborative Filtering:** Analyzes user-item interaction matrices (views, cart additions, purchases).
- **Tailored Suggestions:** Delivers customized product recommendations for each shopper profile.

### 3. Customer Review Sentiment Analysis (`sentiment.py`)
- **Text Classification:** Automatically scores and classifies product reviews into *Positive*, *Negative*, or *Neutral*.
- **Review Summaries:** Calculates positive sentiment percentages and average ratings per product to assist buyers and administrators.

### 📡 AI Endpoints:
- `GET /search?q={query}` — Ranked semantic search results.
- `GET /recommend/{user_id}` — Top recommended products for a shopper.
- `GET /sentiment?text={review}` — Sentiment score for any given review text.
- `GET /products/{id}/review-summary` — Aggregated sentiment metrics for a specific product.
- `GET /review-summaries` — Catalog-wide sentiment overview.
- `GET /docs` — Interactive Swagger API documentation and testing interface.

---

## ⚡ Core Features

- **Storefront:** Dynamic product catalog loaded directly from MySQL with instant category filtering.
- **Smart Search:** Real-time semantic search powered by the Python ML service.
- **User Authentication:** Multi-role accounts (Customer, Vendor, Admin) with secure password hashing.
- **Persistent Cart & Orders:** Database-backed cart and checkout process saving complete order line items (`order_items`).
- **Admin Dashboard:**
  - **Orders Management:** Inspect purchased items, quantities, thumbnails, and update order statuses.
  - **Products Management:** Add new products with live image upload previews, edit details, and safely delete items.
  - **Users & Permissions:** Promote/demote user roles (Admin, Vendor, Customer), ban/unban, and create new administrative users.
  - **Direct Store Access:** Quick navigation between the admin control panel and the live store.

---

## 🚀 Running Locally

### 1. Web & Database Server (XAMPP):
1. Start **Apache** and **MySQL** from the XAMPP Control Panel.
2. Import the database schema from `backend/SQL/store.sql` into a database named `store`.
3. Open the store at:
   ```
   http://localhost/ShopMind/frontend/index.php
   ```
4. Access the Admin Dashboard at:
   ```
   http://localhost/ShopMind/backend/index.php
   ```
   *(Admin credentials: `admin@admin.com` / `123456`)*

### 2. Machine Learning API:
1. Navigate to the `ml/` directory:
   ```bash
   cd ml
   ```
2. Install Python dependencies:
   ```bash
   pip install -r requirements.txt
   ```
3. Run the FastAPI server:
   ```bash
   uvicorn api:app --reload --port 8001
   ```
4. Access the ML Dashboard & Swagger Docs:
   ```
   http://127.0.0.1:8001/
   http://127.0.0.1:8001/docs
   ```

---

## 📄 License & Notes
- Built for e-commerce experimentation, AI integration, and scalable store management.
