// Entry point — инициализация всего приложения
document.addEventListener('DOMContentLoaded', function() {
    initI18n();
    loadBundles();
    initializeEventListeners();
    updateCartCount();
});

function initializeEventListeners() {
    // Search
    const searchInput = document.getElementById('search-input');
    const searchBtn = document.getElementById('search-btn');

    if (searchInput && searchBtn) {
        searchInput.addEventListener('input', debounce(handleSearch, 300));
        searchBtn.addEventListener('click', handleSearch);
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') handleSearch();
        });
    }

    // Escape key closes modals
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeBundleModal();
            closeCart();
        }
    });

    // Cart link (иконка корзины в шапке)
    const cartLink = document.getElementById("cart-link");
    if (cartLink) {
        cartLink.addEventListener("click", (e) => {
            e.preventDefault();
            openCart();
        });
    }

    // Clear cart button
    const clearCartBtn = document.getElementById("clear-cart");
    if (clearCartBtn) {
        clearCartBtn.addEventListener("click", () => {
            cart = [];
            saveCart();
            updateCartCount();
            renderCart();
        });
    }
}

// Handle search
function handleSearch() {
    const searchInput = document.getElementById('search-input');
    if (!searchInput) return;
    
    const query = searchInput.value.toLowerCase().trim();
    
    if (!query) {
        renderBundles(currentBundles);
        return;
    }
    
    const filteredBundles = currentBundles.filter(bundle => {
        return getLocalized(bundle.name).toLowerCase().includes(query) ||
               getLocalized(bundle.main_service.title).toLowerCase().includes(query) ||
               getLocalized(bundle.main_service.partner.name).toLowerCase().includes(query) ||
               (getLocalized(bundle.description) && getLocalized(bundle.description).toLowerCase().includes(query));
    });
    
    renderBundles(filteredBundles);
}

function showAlert(message, type = 'success') {
    const container = document.getElementById('alert-container');
    if (!container) return;

    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.textContent = message;

    container.appendChild(alert);

    // Показываем
    setTimeout(() => {
        alert.classList.add('show');
    }, 50);

    // Авто‑убираем через 3s
    setTimeout(() => {
        alert.classList.remove('show');
        setTimeout(() => alert.remove(), 300);
    }, 3000);
}

// ================== Bundle Modal ==================

function showBundleModal(bundle) {
    const modal = document.getElementById("bundle-modal");
    const modalBody = document.getElementById("modal-body");
    const priceEl = document.getElementById("modal-price");
    const orderBtn = document.getElementById("order-btn");

    // Заполняем тело
    modalBody.innerHTML = renderBundleDetails(bundle);
    priceEl.textContent = formatPrice(bundle.price);

    // Клик по кнопке «Objednať»
    orderBtn.onclick = () => {
        cart.push(bundle);
        saveCart();
        updateCartCount();
        renderCart();

        // GA4 add_to_cart
        if (typeof pushAddToCart === 'function') {
            pushAddToCart(bundle);
        }

        closeBundleModal();
        window.location.href = "/checkout.php";
    };

    modal.classList.remove("hidden");
}

function closeBundleModal() {
    const modal = document.getElementById("bundle-modal");
    if (modal) modal.classList.add("hidden");
}

function renderBundleDetails(bundle) {
    // упрощённо, можно стилизовать по‑твоему
    return `
        <h3>${escapeHtml(getLocalized(bundle.name))}</h3>
        <p>${escapeHtml(getLocalized(bundle.description || ''))}</p>
        <p><strong>${formatPrice(bundle.price)}</strong></p>
    `;
}