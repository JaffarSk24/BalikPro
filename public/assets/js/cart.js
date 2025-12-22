let cart = JSON.parse(localStorage.getItem("cart")) || [];

function saveCart() {
    localStorage.setItem("cart", JSON.stringify(cart));
}

function updateCartCount() {
    const cartCountEl = document.getElementById("cart-count");
    if (cartCountEl) cartCountEl.textContent = `(${cart.length})`;
}

function renderCart() {
    const cartItemsEl = document.getElementById("cart-items");
    const cartEmptyEl = document.getElementById("cart-empty");
    const cartTotalEl = document.getElementById("cart-total");

    if (!cartItemsEl || !cartEmptyEl || !cartTotalEl) return;

    cartItemsEl.innerHTML = "";

    if (cart.length === 0) {
        cartEmptyEl.style.display = "block";
        cartTotalEl.textContent = "";

        if (currentDict.cart && currentDict.cart.empty) {
            cartEmptyEl.textContent = currentDict.cart.empty;
        }

        return;
    }

    cartEmptyEl.style.display = "none";
    let total = 0;

    cart.forEach((item, index) => {
        const li = document.createElement("li");
        li.classList.add("cart-item");

        const itemInfo = document.createElement("div");
        itemInfo.classList.add("cart-item-info");

        const name = document.createElement("div");
        name.classList.add("cart-item-name");
        name.textContent = getLocalized(item.name);
        itemInfo.appendChild(name);

        if (item.bonus_services && item.bonus_services.length > 0) {
            const bonusLine = document.createElement("div");
            bonusLine.classList.add("cart-item-bonus");
            const tmpl = currentDict.cart?.bonusLine || "+ {count} bonus services";
            bonusLine.textContent = tmpl.replace("{count}", item.bonus_services.length);
            itemInfo.appendChild(bonusLine);
        }

        li.appendChild(itemInfo);

        const price = document.createElement("span");
        price.classList.add("cart-item-price");
        price.textContent = formatPrice(item.price);

        const removeBtn = document.createElement("button");
        removeBtn.classList.add("cart-item-remove");
        removeBtn.textContent = "×";
        removeBtn.setAttribute("aria-label", "Remove item");
        removeBtn.addEventListener("click", () => {
            cart.splice(index, 1);
            saveCart();
            updateCartCount();
            renderCart();
        });

        li.appendChild(price);
        li.appendChild(removeBtn);
        cartItemsEl.appendChild(li);

        total += item.price;
    });

    if (currentDict.cart && currentDict.cart.total) {
        cartTotalEl.textContent = `${currentDict.cart.total}: ${formatPrice(total)}`;
    } else {
        cartTotalEl.textContent = `Total: ${formatPrice(total)}`;
    }
}

function openCart() {
    const cartModal = document.getElementById("cart-modal");
    if (cartModal) {
        cartModal.classList.remove("hidden");
        renderCart();
    }
}

function closeCart() {
    const cartModal = document.getElementById("cart-modal");
    if (cartModal) {
        cartModal.classList.add("hidden");
    }
}

// Переход к оформлению
function startCheckout() {
    const cart = JSON.parse(localStorage.getItem('cart') || '[]');
    if (cart.length === 0) {
        alert(currentDict.cart?.empty || 'Váš košík je prázdny.');
        return;
    }
    window.location.href = '/checkout.php';
}

// ================= Глобал =================
window.cart = cart;
window.addToCart = function (bundle) {
    cart.push(bundle);
    saveCart();
    updateCartCount();
    renderCart();

    // GA4 add_to_cart
    if (typeof pushAddToCart === 'function') {
        pushAddToCart(bundle);
    }
};

window.saveCart = saveCart;
window.closeCart = closeCart;
window.openCart = openCart;
window.renderCart = renderCart;
window.updateCartCount = updateCartCount;
window.startCheckout = startCheckout;