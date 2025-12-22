// ================= РЕНДЕР КОРЗИНЫ НА СТРАНИЦЕ CHECKOUT =================

function renderCheckoutCart() {
    const cartItemsList = document.getElementById('cart-items-list');
    const cartTotalEl = document.getElementById('cart-total');
    if (!cartItemsList || !cartTotalEl) return;

    const cart = JSON.parse(localStorage.getItem('cart') || '[]');
    // держим глобальную корзину в синхронизации
    window.cart = cart;

    if (cart.length === 0) {
        cartItemsList.innerHTML = `<li><p data-i18n="checkout.emptyCart">Váš košík je prázdny.</p></li>`;
        cartTotalEl.textContent = formatPrice(0);
        if (typeof applyTranslations === 'function' && typeof currentDict !== 'undefined') {
            applyTranslations(currentDict);
        }
        return;
    }

    let total = 0;
    cartItemsList.innerHTML = cart.map((b, index) => {
        const price = parseFloat(b.price) || 0;
        total += price;

        // бонусная строка (зелёная)
        let bonusLine = '';
        if (b.bonus_services && b.bonus_services.length > 0) {
            const tmpl = (typeof currentDict !== 'undefined' && currentDict.cart?.bonusLine) || "+ {count} bonus services";
            bonusLine = `<div class="co-bonus">${tmpl.replace("{count}", b.bonus_services.length)}</div>`;
        }

        // карточка как на первом скрине, но цена под бонусом слева
        return `
            <li class="co-item">
                <div class="co-left">
                    <div class="co-name">${escapeHtml(getLocalized(b.name))}</div>
                    ${bonusLine}
                    <div class="co-price">${formatPrice(price)}</div>
                </div>
                <button 
                    class="cart-item-remove"
                    aria-label="Remove item"
                    data-index="${index}"
                >×</button>
            </li>
        `;
    }).join('');

    cartTotalEl.textContent = formatPrice(total);

    if (typeof applyTranslations === 'function' && typeof currentDict !== 'undefined') {
        applyTranslations(currentDict);
    }

    // обработчики удаления
    cartItemsList.querySelectorAll('.cart-item-remove').forEach(btn => {
        btn.addEventListener("click", (e) => {
            const idx = parseInt(e.currentTarget.getAttribute("data-index"), 10);
            const newCart = JSON.parse(localStorage.getItem('cart') || '[]');
            newCart.splice(idx, 1);
            localStorage.setItem('cart', JSON.stringify(newCart));
            window.cart = newCart;
            if (typeof updateCartCount === 'function') updateCartCount();
            renderCheckoutCart();
        });
    });
}

// ================= ОБРАБОТЧИК ФОРМЫ CHECKOUT =================

document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('cart-items-list')) renderCheckoutCart();

    const form = document.getElementById('checkout-form');
    if (!form) return;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const submitBtn = form.querySelector('button[type="submit"]');
        if (!submitBtn) return;
        if (submitBtn.disabled) return;

        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span data-i18n="checkout.sending">Odosielam...</span>';
        if (typeof applyTranslations === 'function' && typeof currentDict !== 'undefined') {
            applyTranslations(currentDict);
        }

        const formData = Object.fromEntries(new FormData(form));
        const cart = JSON.parse(localStorage.getItem('cart') || '[]');

        if (cart.length === 0) {
            alert((typeof currentDict !== 'undefined' && currentDict.checkout?.emptyCart) || 'Váš košík je prázdny.');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
            return;
        }

        const bundles = cart.map(b => ({
            main_service_id: (b.main_service && b.main_service.id) ? b.main_service.id : b.main_service_id,
            bonus_service_ids: (b.bonus_services || []).map(s => s.id)
        }));

        const totalAmount = cart.reduce((sum, b) => sum + (parseFloat(b.price) || 0), 0);

        const payload = {
            bundles: bundles,
            customer_name: formData.name || '',
            customer_email: formData.email || '',
            customer_phone: formData.phone || '',
            total_amount: totalAmount,
            currency: 'EUR',
            lang: window.currentLang || 'sk'
        };

        try {
            // GA4 begin_checkout
            pushBeginCheckout(cart);

            const res = await fetch('/api/checkout.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
                credentials: 'same-origin'
            });

            const raw = await res.text();
            let json;
            try {
                json = raw ? JSON.parse(raw) : {};
            } catch (parseErr) {
                throw new Error(`Invalid JSON from server (HTTP ${res.status}): ${raw.substring(0, 200)}`);
            }

            if (!res.ok || !json.success) {
                const msg = json?.message || json?.error || `HTTP ${res.status}`;
                throw new Error(msg);
            }

            // очистка корзины после успешного создания заказа и перед редиректом
            localStorage.removeItem('cart');
            window.cart = [];
            if (typeof updateCartCount === 'function') updateCartCount();

            const orderId = (json.data && json.data.order_id) ? json.data.order_id : '';
            const checkoutUrl = (json.data && json.data.checkout_url) ? json.data.checkout_url : '';

            if (checkoutUrl) {
                window.location.href = checkoutUrl;
            } else {
                window.location.href = `/checkout/success.php?order_id=${encodeURIComponent(orderId)}`;
            }

        } catch (err) {
            console.error('Checkout error:', err);
            alert(((typeof currentDict !== 'undefined' && currentDict.checkout?.error) || 'Chyba pri odoslaní objednávky') + ': ' + (err.message || err));
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    });
});

// ================= DATALAYER HELPERS =================

function pushAddToCart(bundle) {
    if (!window.dataLayer) window.dataLayer = [];
    const coupon = sessionStorage.getItem('appliedCoupon') || null;
    const discount = parseFloat(sessionStorage.getItem('discountAmount')) || 0;

    const item = {
        item_id: String(bundle.id),
        item_name: getLocalized(bundle.name),
        item_category: 'Bundle',
        item_variant: window.currentLang || 'sk',
        price: parseFloat(bundle.price) || 0,
        quantity: 1
    };
    if (coupon) item.coupon = coupon;
    if (discount > 0) item.discount = discount;

    window.dataLayer.push({
        event: 'add_to_cart',
        ecommerce: { currency: 'EUR', value: parseFloat(bundle.price) || 0, items: [item] }
    });
}

function pushBeginCheckout(cartBundles) {
    if (!window.dataLayer) window.dataLayer = [];
    const coupon = sessionStorage.getItem('appliedCoupon') || null;
    const discount = parseFloat(sessionStorage.getItem('discountAmount')) || 0;
    const totalValue = cartBundles.reduce((sum, b) => sum + (parseFloat(b.price) || 0), 0);

    const ecommerceData = {
        currency: 'EUR',
        value: totalValue,
        items: cartBundles.map(bundle => {
            const item = {
                item_id: String(bundle.id),
                item_name: getLocalized(bundle.name),
                item_category: 'Bundle',
                item_variant: window.currentLang || 'sk',
                price: parseFloat(bundle.price) || 0,
                quantity: 1
            };
            if (coupon) item.coupon = coupon;
            if (discount > 0) item.discount = discount;
            return item;
        })
    };
    if (coupon) ecommerceData.coupon = coupon;
    if (discount > 0) ecommerceData.discount = discount;

    window.dataLayer.push({ event: 'begin_checkout', ecommerce: ecommerceData });
}

// ================= ЭКСПОРТ В ГЛОБАЛ =================
window.renderCheckoutCart = renderCheckoutCart;
window.pushAddToCart = pushAddToCart;
window.pushBeginCheckout = pushBeginCheckout;