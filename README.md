# ShopMind 🛒

An e-commerce web app built as a team project. Frontend and admin dashboard are done — backend integration is in progress.

---

## Project Structure

```
ShopMind/
├── frontend/
│   ├── CSS/
│   ├── img/
│   ├── index.php
│   ├── main.js
│   ├── products.json
│   ├── cart_api.php
│   ├── products_api.php
│   └── about.php
├── backend/
│   ├── account/
│   ├── admin_sidebar/
│   ├── fun/
│   ├── includes/
│   ├── SQL/
│   ├── vendor_sidebar/
│   └── index.php
├── .gitignore
└── README.md
```

---

## Tech Stack

- **Frontend:** HTML5, CSS3, JavaScript (Vanilla), Swiper.js, Font Awesome
- **Backend:** PHP, MySQL
- **Styling:** SCSS, Gulp
- **Tools:** Git, VS Code, Live Server

---

## Features

- Products loaded dynamically (JSON → MySQL)
- Filter by category + live search
- Cart with sidebar + database sync
- Wishlist with toggle
- Login / Sign Up modals
- Toast notifications
- Auto-playing banner slider
- Responsive layout
- Admin dashboard (products, orders, users, brands, categories)
- Vendor sidebar
- User account management

---

## Running Locally

Open `frontend/index.php` with a local server (XAMPP / Live Server).
Direct file open won't work — needs a server for PHP and fetch requests.

---

## Notes

- SQL schema is in `backend/SQL/`
- DB config goes in `backend/fun/db_connection.php`
- `.gitignore` covers `node_modules/`, `vendor/`, and `.env`
