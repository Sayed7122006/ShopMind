//  FINDO E-Commerce – main.js
//  (script loads at bottom of <body> → DOM is ready immediately)

// ── DOM shortcuts ─────────────────────────────────────────────
const qs  = (sel, ctx = document) => ctx.querySelector(sel);
const qsa = (sel, ctx = document) => [...ctx.querySelectorAll(sel)];

// ── App State ─────────────────────────────────────────────────
let allProducts     = [];
let cart            = [];
let wishlist        = [];
let currentCategory = 'All Categories';
let currentSearch   = '';

// ── ML API (Recommendation + Smart Search) ──────────────────────
// Make sure the ML API is running locally first:
//   uvicorn api:app --reload --port 8001
const ML_API = "http://127.0.0.1:8001";

// Calls the Smart Search endpoint. Returns an array of
// {product_id, name, score} on success, or null if the API is
// unreachable (so callers can fall back to local filtering).
async function smartSearch(query) {
    try {
        const res = await fetch(`${ML_API}/search?q=${encodeURIComponent(query)}`);
        if (!res.ok) throw new Error(`Search API returned ${res.status}`);
        const data = await res.json();
        return data.results;
    } catch (err) {
        console.warn('Smart search unavailable, falling back to local search.', err);
        return null;
    }
}

// Calls the Recommendation endpoint for a given user.
// Returns an array of {product_id, name}, or [] on failure.
async function getRecommendations(userId, topN = 5) {
    try {
        const res = await fetch(`${ML_API}/recommend/${userId}?top_n=${topN}`);
        if (!res.ok) throw new Error(`Recommend API returned ${res.status}`);
        const data = await res.json();
        return data.recommendations || [];
    } catch (err) {
        console.warn('Recommendations unavailable.', err);
        return [];
    }
}

// The ML API only returns {product_id, name} — not the full product
// object (image, price, category...). This looks each one up in
// allProducts (loaded from products_api.php) so we can reuse the
// existing renderProducts() function.
function mapResultsToProducts(results) {
    if (!results) return [];
    return results
        .map(r => {
            return allProducts.find(p => String(p.id) === String(r.product_id))
                || allProducts.find(p => Number(p.id) === Number(r.product_id) + 1)
                || allProducts.find(p => p.name.trim().toLowerCase() === (r.name || '').trim().toLowerCase());
        })
        .filter(Boolean); // drop any that aren't found locally
}

//  1. SWIPER SLIDER
if (qs('.mySwiper')) {
    new Swiper('.mySwiper', {
        loop: true,
        pagination: { el: '.swiper-pagination', clickable: true },
        autoplay: { delay: 3000, disableOnInteraction: false },
    });
}

//  2. BROWSE CATEGORIES DROPDOWN  (hamburger button)
const categoryBtn = qs('#category-btn');
const navMenu     = qs('#nav-menu');

if (categoryBtn && navMenu) {
    categoryBtn.addEventListener('click', e => {
        e.stopPropagation();
        navMenu.classList.toggle('show');
    });
    document.addEventListener('click', e => {
        if (!e.target.closest('.category_dropdown')) {
            navMenu.classList.remove('show');
        }
    });
}

//  3. TOAST (defined early — used by everything below)
function showToast(msg) {
    const container = qs('#toast-container');
    if (!container) return;
    const toast = document.createElement('div');
    toast.className = 'toast';
    toast.textContent = msg;
    container.appendChild(toast);
    requestAnimationFrame(() => toast.classList.add('show'));
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 400);
    }, 3000);
}

//  4. LOAD PRODUCTS (From MySQL API with JSON fallback)
async function loadProducts() {
    try {
        const res = await fetch('products_api.php', { headers: { 'Accept': 'application/json' } });
        if (!res.ok) throw new Error(`Products API returned ${res.status}`);
        const data = await res.json();
        if (!Array.isArray(data) || data.length === 0) throw new Error(data.error || 'Empty products response');
        allProducts = data;
        await loadCartFromDatabase();
        renderProducts(allProducts);
    } catch (err) {
        console.warn('products_api.php fallback to products.json:', err);
        try {
            const fallbackRes = await fetch('products.json');
            allProducts = await fallbackRes.json();
            renderProducts(allProducts);
        } catch (jsonErr) {
            const grid = qs('#products-grid');
            if (grid) grid.innerHTML =
                '<p class="no-results">⚠️ Could not load products. Make sure XAMPP Apache and MySQL are running.</p>';
        }
    }
}

//  5. RENDER PRODUCTS
function renderProducts(list) {
    const grid = qs('#products-grid');
    if (!grid) return;

    if (!list || list.length === 0) {
        grid.innerHTML = '<p class="no-results">No products found </p>';
        return;
    }

    grid.innerHTML = list.map(p => {
        const inWish    = wishlist.includes(p.id);
        const inCart    = cart.some(c => c.id === p.id);
        const saleBadge = p.old_price ? '<span class="badge">SALE</span>' : '';
        const oldPrice  = p.old_price ? `<span class="old-price">$${p.old_price}</span>` : '';
        const imgSrc    = p.img || 'img/logo.png';

        return `
        <div class="product-item" data-id="${p.id}">
            ${saleBadge}
            <button class="wish-btn${inWish ? ' active' : ''}" data-id="${p.id}">
                <i class="${inWish ? 'fa-solid' : 'fa-regular'} fa-heart"></i>
            </button>
            <div class="product-img">
                <img src="${imgSrc}" alt="${p.name}" loading="lazy" onerror="this.onerror=null;this.src='img/logo.png';">
            </div>
            <div class="product-info">
                <span class="product-category">${p.category}</span>
                <h4 class="product-name">${p.name}</h4>
                <div class="product-pricing">
                    ${oldPrice}
                    <span class="price">$${p.price}</span>
                </div>
                <button class="add-cart-btn${inCart ? ' added' : ''}" data-id="${p.id}">
                    <i class="fa-solid fa-cart-plus"></i>
                    ${inCart ? 'Added ✓' : 'Add to Cart'}
                </button>
            </div>
        </div>`;
    }).join('');

    // Re-attach events after every render
    qsa('.wish-btn').forEach(btn =>
        btn.addEventListener('click', () => toggleWishlist(Number(btn.dataset.id))));
    qsa('.add-cart-btn').forEach(btn =>
        btn.addEventListener('click', () => addToCart(Number(btn.dataset.id))));
}

//  6. FILTER / SEARCH
function getFiltered() {
    let res = allProducts;
    if (currentCategory !== 'All Categories') {
        res = res.filter(p =>
            p.category.toLowerCase() === currentCategory.toLowerCase());
    }
    if (currentSearch.trim()) {
        const q = currentSearch.toLowerCase();
        res = res.filter(p => p.name.toLowerCase().includes(q));
    }
    return res;
}
function applyFilter() { renderProducts(getFiltered()); }

// Search input — now uses Smart Search (ML API) when available,
// and falls back to the original local filtering if the API is
// unreachable or the search box is empty.
const searchInput    = qs('#search');
const categorySelect = qs('#category');
const searchForm     = qs('.search_box');

let searchDebounceTimer;

async function runSearch(query) {
    currentSearch = query;

    if (!query.trim()) {
        applyFilter();
        return;
    }

    const results = await smartSearch(query);

    if (results && results.length > 0) {
        renderProducts(mapResultsToProducts(results));
    } else if (results && results.length === 0) {
        renderProducts([]); // genuinely no matches
    } else {
        // results === null → API was unreachable, fall back to local filter
        applyFilter();
    }
}

searchInput?.addEventListener('input', () => {
    clearTimeout(searchDebounceTimer);
    searchDebounceTimer = setTimeout(() => runSearch(searchInput.value), 300);
});
searchForm?.addEventListener('submit', e => {
    e.preventDefault();
    clearTimeout(searchDebounceTimer);
    runSearch(searchInput?.value || '');
});

// ✅ Category select (top bar)
categorySelect?.addEventListener('change', function () {
    currentCategory = this.value;
    applyFilter();
    showToast('Showing: ' + (currentCategory === 'All Categories' ? 'All Products' : currentCategory) + ' 📂');
});

//  6. CART (persisted in MariaDB for signed-in customers)
async function cartRequest(action, payload = null) {
    const options = payload ? {
        method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload)
    } : {};
    const response = await fetch(`cart_api.php?action=${encodeURIComponent(action)}`, options);
    const data = await response.json();
    if (!response.ok) throw new Error(data.error || 'Cart request failed.');
    return data;
}

async function loadCartFromDatabase() {
    if (!STORE_LOGGED_IN) { cart = []; updateCartBadge(); renderCartSidebar(); return; }
    try { cart = await cartRequest('list'); }
    catch (err) { showToast(err.message); cart = []; }
    updateCartBadge();
    renderCartSidebar();
}

async function addToCart(id) {
    if (!STORE_LOGGED_IN) {
        showToast('Please log in with a customer account to save your cart.');
        window.location.href = `${STORE_LOGIN_URL}?checkout=0`;
        return;
    }
    try {
        await cartRequest('add', { product_id: id });
        await loadCartFromDatabase();
        applyFilter();
        showToast('Added to cart 🛒');
    } catch (err) { showToast(err.message); }
}

async function removeFromCart(id) {
    try {
        await cartRequest('remove', { product_id: id });
        await loadCartFromDatabase();
        applyFilter();
        showToast('Removed from cart');
    } catch (err) { showToast(err.message); }
}

function updateCartBadge() {
    const total = cart.reduce((s, c) => s + Number(c.qty || 0), 0);
    qsa('.count_item_header').forEach(el => el.textContent = total);
}

function renderCartSidebar() {
    const list = qs('#cart-list');
    const total = qs('#cart-total');
    if (!list) return;
    if (cart.length === 0) {
        list.innerHTML = '<p class="empty-msg">Your cart is empty 🛒</p>';
        if (total) total.textContent = '$0.00';
        return;
    }
    list.innerHTML = cart.map(item => `
        <div class="cart-item">
            <img src="${item.img || 'img/logo.png'}" alt="${item.name}">
            <div class="cart-item-info">
                <p class="cart-item-name">${String(item.name).substring(0, 38)}</p>
                <p class="cart-item-price">$${Number(item.price).toFixed(2)} × ${item.qty}</p>
            </div>
            <button class="remove-cart" data-id="${item.id}" aria-label="Remove item"><i class="fa-solid fa-trash"></i></button>
        </div>`).join('');
    const sum = cart.reduce((s, c) => s + Number(c.price) * Number(c.qty), 0);
    if (total) total.textContent = `$${sum.toFixed(2)}`;
    qsa('.remove-cart').forEach(btn => btn.addEventListener('click', () => removeFromCart(Number(btn.dataset.id))));
}

// Open / Close Cart
const cartIconEl = qs('#cart-icon');
const cartSidebar = qs('#cart-sidebar');
const cartClose = qs('#cart-close');
const cartOverlay = qs('#cart-overlay');
function openCart() { cartSidebar?.classList.add('open'); cartOverlay?.classList.add('open'); renderCartSidebar(); }
function closeCart() { cartSidebar?.classList.remove('open'); cartOverlay?.classList.remove('open'); }
cartIconEl?.addEventListener('click', openCart);
cartClose?.addEventListener('click', closeCart);
cartOverlay?.addEventListener('click', closeCart);
qs('#checkout-btn')?.addEventListener('click', () => {
    if (!cart.length) { showToast('Your cart is empty.'); return; }
    if (!STORE_LOGGED_IN) { window.location.href = `${STORE_LOGIN_URL}?checkout=1`; return; }
    window.location.href = STORE_CHECKOUT_URL;
});

//  8. WISHLIST
function toggleWishlist(id) {
    if (wishlist.includes(id)) {
        wishlist = wishlist.filter(w => w !== id);
        showToast('Removed from wishlist 💔');
    } else {
        wishlist.push(id);
        showToast('Added to wishlist ❤️');
    }
    qsa('.count_favouite').forEach(el => el.textContent = wishlist.length);
    applyFilter();
}

//  9. LOGIN / SIGNUP MODALS
const loginModal   = qs('#login-modal');
const signupModal  = qs('#signup-modal');
const modalOverlay = qs('#modal-overlay');

function openModal(modal) {
    modal?.classList.add('open');
    modalOverlay?.classList.add('open');
}
function closeModal() {
    loginModal?.classList.remove('open');
    signupModal?.classList.remove('open');
    modalOverlay?.classList.remove('open');
}

// Login & Sign Up buttons — attached directly by ID to avoid any class confusion
qs('#btn-login')?.addEventListener('click',  e => { e.preventDefault(); openModal(loginModal);  });
qs('#btn-signup')?.addEventListener('click', e => { e.preventDefault(); openModal(signupModal); });

modalOverlay?.addEventListener('click', closeModal);
qsa('.modal-close').forEach(btn => btn.addEventListener('click', closeModal));

qs('#to-signup')?.addEventListener('click', e => { e.preventDefault(); closeModal(); openModal(signupModal); });
qs('#to-login')?.addEventListener('click',  e => { e.preventDefault(); closeModal(); openModal(loginModal);  });

qs('#login-form')?.addEventListener('submit', e => {
    e.preventDefault();
    showToast('Logged in successfully ✅');
    closeModal();
});
qs('#signup-form')?.addEventListener('submit', e => {
    e.preventDefault();
    showToast('Account created successfully ✅');
    closeModal();
});

//  10. RECOMMENDATIONS (optional)
// Renders "You might also like" into an element with id
// "recommendations-grid", IF one exists in your HTML. Safely does
// nothing if that section doesn't exist yet.
//
// NOTE: pass the real logged-in user's id here once it's available
// on the page (e.g. a STORE_USER_ID global set by the PHP template).
// "guest" is a placeholder that just demonstrates it working.
async function loadRecommendations(userId = 'guest') {
    const container = qs('#recommendations-grid');
    if (!container) return;

    const recs = await getRecommendations(userId);
    const products = mapResultsToProducts(recs);

    if (products.length === 0) {
        container.innerHTML = '';
        return;
    }

    container.innerHTML = products.map(p => `
        <div class="product-item" data-id="${p.id}">
            <div class="product-img"><img src="${p.img || 'img/logo.png'}" alt="${p.name}" loading="lazy"></div>
            <div class="product-info">
                <h4 class="product-name">${p.name}</h4>
                <span class="price">$${p.price}</span>
            </div>
        </div>`).join('');
}

//  INIT
loadProducts();
loadRecommendations();