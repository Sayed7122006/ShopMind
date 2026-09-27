# ShopMind 🛒

An e-commerce web app built as a team project. The frontend is done — backend is being handled by another team member using PHP.

---

## Project Structure

```
Ecommerce-Website/
├── frontend/
│   ├── CSS/
│   ├── img/
│   ├── index.html
│   ├── main.js
│   └── products.json
├── .gitignore
└── README.md
```

---

## Tech Stack

- **Frontend:** HTML, CSS, Vanilla JS
- **Backend:** PHP *(in progress — handled separately)*
- **Libraries:** Swiper.js, Font Awesome

---

## Features (Frontend)

- Products loaded dynamically from JSON
- Filter by category + live search
- Cart sidebar with item count
- Wishlist with heart toggle
- Login / Sign Up modals
- Toast notifications
- Auto-playing banner slider
- Responsive layout

---

## Running Locally

Open `frontend/index.html` with **Live Server** in VS Code.
Make sure you use a local server — not just double-clicking the file — so the JSON fetch works.

---

## Notes

- Backend integration is coming later
- The `frontend/` folder contains everything for the UI
- `.gitignore` covers `node_modules/` and `.env`
