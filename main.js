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

//  4. LOAD PRODUCTS FROM JSON
async function loadProducts() {
    try {
        const res   = await fetch('products.json');
        allProducts = await res.json();
        renderProducts(allProducts);
    } catch (err) {
        const grid = qs('#products-grid');
        if (grid) grid.innerHTML =
            '<p class="no-results">⚠️ Open via Live Server (not file://) to load products.</p>';
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

        return `
        <div class="product-item" data-id="${p.id}">
            ${saleBadge}
            <button class="wish-btn${inWish ? ' active' : ''}" data-id="${p.id}">
                <i class="${inWish ? 'fa-solid' : 'fa-regular'} fa-heart"></i>
            </button>
            <div class="product-img">
                <img src="${p.img}" alt="${p.name}" loading="lazy">
            </div>
            <div class="product-info">
                <span class="product-category">${p.catetory}</span>
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
            p.catetory.toLowerCase() === currentCategory.toLowerCase());
    }
    if (currentSearch.trim()) {
        const q = currentSearch.toLowerCase();
        res = res.filter(p => p.name.toLowerCase().includes(q));
    }
    return res;
}
function applyFilter() { renderProducts(getFiltered()); }

// Search input
const searchInput    = qs('#search');
const categorySelect = qs('#category');
const searchForm     = qs('.search_box');

searchInput?.addEventListener('input', () => {
    currentSearch = searchInput.value;
    applyFilter();
});
searchForm?.addEventListener('submit', e => {
    e.preventDefault();
    currentSearch = searchInput?.value || '';
    applyFilter();
});

// ✅ Category select (top bar)
categorySelect?.addEventListener('change', function () {
    currentCategory = this.value;
    applyFilter();
    showToast('Showing: ' + (currentCategory === 'All Categories' ? 'All Products' : currentCategory) + ' 📂');
});

//  7. CART
function addToCart(id) {
    const product = allProducts.find(p => p.id === id);
    if (!product) return;
    const existing = cart.find(c => c.id === id);
    if (existing) { existing.qty++; }
    else          { cart.push({ ...product, qty: 1 }); }
    updateCartBadge();
    applyFilter();
    renderCartSidebar();
    showToast('Added to cart 🛒');
}

function removeFromCart(id) {
    cart = cart.filter(c => c.id !== id);
    updateCartBadge();
    applyFilter();
    renderCartSidebar();
    showToast('Removed from cart ');
}

function updateCartBadge() {
    const total = cart.reduce((s, c) => s + c.qty, 0);
    qsa('.count_item_header').forEach(el => el.textContent = total);
}

function renderCartSidebar() {
    const list  = qs('#cart-list');
    const total = qs('#cart-total');
    if (!list) return;

    if (cart.length === 0) {
        list.innerHTML = '<p class="empty-msg">Your cart is empty 🛒</p>';
        if (total) total.textContent = '$0.00';
        return;
    }

    list.innerHTML = cart.map(item => `
        <div class="cart-item">
            <img src="${item.img}" alt="${item.name}">
            <div class="cart-item-info">
                <p class="cart-item-name">${item.name.substring(0, 38)}…</p>
                <p class="cart-item-price">$${item.price} × ${item.qty}</p>
            </div>
            <button class="remove-cart" data-id="${item.id}">
                <i class="fa-solid fa-trash"></i>
            </button>
        </div>`).join('');

    const sum = cart.reduce((s, c) => s + c.price * c.qty, 0);
    if (total) total.textContent = `$${sum.toFixed(2)}`;

    qsa('.remove-cart').forEach(btn =>
        btn.addEventListener('click', () => removeFromCart(Number(btn.dataset.id))));
}

// Open / Close Cart
const cartIconEl  = qs('#cart-icon');
const cartSidebar = qs('#cart-sidebar');
const cartClose   = qs('#cart-close');
const cartOverlay = qs('#cart-overlay');

function openCart()  { cartSidebar?.classList.add('open');    cartOverlay?.classList.add('open');    renderCartSidebar(); }
function closeCart() { cartSidebar?.classList.remove('open'); cartOverlay?.classList.remove('open'); }

cartIconEl?.addEventListener('click',  openCart);
cartClose?.addEventListener('click',   closeCart);
cartOverlay?.addEventListener('click', closeCart);

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

//  INIT
loadProducts();