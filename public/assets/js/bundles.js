let currentBundles = [];
let selectedBundle = null;

// Load bundles from API
async function loadBundles() {
    showLoading();
    
    try {
        const response = await fetch('/api/bundles');
        const data = await response.json();
        
        if (response.ok && data.success) {
            currentBundles = data.data;
            renderBundles(currentBundles);
        } else {
            showError('Chyba pri načítaní balíkov: ' + (data.message || 'Neznáma chyba'));
        }
    } catch (error) {
        console.error('Load bundles error:', error);
        showError('Chyba pri načítaní balíkov. Skúste to znovu.');
    } finally {
        hideLoading();
    }
}

// Render bundles in the grid
function renderBundles(bundles) {
    const grid = document.getElementById('bundles-grid');
    const noResults = document.getElementById('no-results');
    
    if (!grid) return;
    
    if (bundles.length === 0) {
        grid.innerHTML = '';
        if (noResults) noResults.classList.remove('hidden');
        return;
    }
    
    if (noResults) noResults.classList.add('hidden');
    
    grid.innerHTML = bundles.map((bundle, index) => `
        <div class="bundle-card" onclick="showBundleDetail(${bundle.id})" style="animation-delay: ${(index % 6) * 0.1}s">
            <div class="bundle-header">
                <div class="bundle-title">${escapeHtml(getLocalized(bundle.name))}</div>
                <div class="bundle-partner">${escapeHtml(getLocalized(bundle.main_service.partner.name))}</div>
            </div>
            
            <div class="bundle-content">
                ${getLocalized(bundle.description) ? `<div class="bundle-description">${escapeHtml(getLocalized(bundle.description))}</div>` : ''}
                
                <div class="main-service">
                    <div class="service-title">${escapeHtml(getLocalized(bundle.main_service.title))}</div>
                    ${getLocalized(bundle.main_service.description) ? `<div class="service-description">${escapeHtml(getLocalized(bundle.main_service.description))}</div>` : ''}
                </div>
                
                ${bundle.bonus_services_count > 0 ? `
                    <div class="bonus-services">
                        <div class="bonus-services-title">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26"></polygon>
                            </svg>
                            + ${bundle.bonus_services_count} <span data-i18n="bundle.bonusServices">bonus služieb</span>
                        </div>
                        <div style="font-size: 0.875rem; color: #6b7280;" data-i18n="bundle.clickForDetails">
                            Kliknite pre zobrazenie detailov
                        </div>
                    </div>
                ` : ''}
                
                <div class="bundle-pricing">
                  ${bundle.total_savings > 0 ? `
                    <div class="bundle-savings">
                      <span data-i18n="bundle.savings">Экономия</span>: ${formatPrice(bundle.total_savings)}
                    </div>` : ''}

                  <div class="bundle-price">
                    ${formatPrice(bundle.price)}
                  </div>
                </div>
            </div>
        </div>
    `).join('');

    applyTranslations(currentDict);
}

// Show bundle detail modal
async function showBundleDetail(bundleId) {
    const modal = document.getElementById('bundle-modal');
    const title = document.getElementById('modal-title');
    const body = document.getElementById('modal-body');
    const priceSpan = document.getElementById('modal-price');
    
    if (!modal || !title || !body || !priceSpan) return;
    
    title.textContent = 'Načítavam...';
    body.innerHTML = '<div class="text-center"><div class="spinner"></div></div>';
    modal.classList.remove('hidden');
    
    try {
        const response = await fetch(`/api/bundles/${bundleId}`);
        const data = await response.json();
        
        if (response.ok && data.success) {
            selectedBundle = data.data;
            
            // 🔥 DataLayer: view_item (при открытии модалки с деталями)
            pushViewItem(selectedBundle);
            
            title.textContent = getLocalized(selectedBundle.name);
            priceSpan.textContent = formatPrice(selectedBundle.price);
            
            body.innerHTML = `
                <div class="bundle-detail">
                    ${getLocalized(selectedBundle.description) ? `<p class="mb-4">${escapeHtml(getLocalized(selectedBundle.description))}</p>` : ''}
                    
                    <div class="main-service mb-4">
                        <h3 class="service-title">🎯 <span data-i18n="modal.mainService">Hlavná služba</span></h3>
                        <div class="service-card main-service-card">
                            <h4 class="font-semibold text-lg mb-2">${escapeHtml(getLocalized(selectedBundle.main_service.title))}</h4>
                            ${getLocalized(selectedBundle.main_service.description) ? `<p class="text-gray-400 mb-3">${escapeHtml(getLocalized(selectedBundle.main_service.description))}</p>` : ''}
                            
                            <div class="service-price">
                                <strong data-i18n="bundle.value">Стоимость:</strong> ${formatPrice(selectedBundle.main_service.price)}
                            </div>
                            
                            <div class="mt-3 text-sm text-gray-400">
                                <strong data-i18n="bundle.partner">Partner</strong>: ${escapeHtml(getLocalized(selectedBundle.main_service.partner.name))}
                            </div>
                            
                            ${selectedBundle.main_service.contact_info ? `
                                <div class="mt-2 text-sm text-gray-400">
                                    <strong data-i18n="bundle.contact">Kontakt</strong>: ${escapeHtml(selectedBundle.main_service.contact_info)}
                                </div>
                            ` : ''}
                        </div>
                    </div>
                    
                    ${selectedBundle.bonus_services && selectedBundle.bonus_services.length > 0 ? `
                        <div class="bonus-services">
                            <h3 class="service-title">⭐ <span data-i18n="bundle.bonusServicesFree">Bonus služby (zadarmo)</span></h3>
                            <div class="space-y-3">
                                ${selectedBundle.bonus_services.map(service => `
                                    <div class="service-card bonus-service-card">
                                        <h4 class="font-semibold mb-2">${escapeHtml(getLocalized(service.title))}</h4>
                                        ${getLocalized(service.description) ? `<p class="text-gray-400 mb-3">${escapeHtml(getLocalized(service.description))}</p>` : ''}

                                        <div class="text-lg">
                                           <strong data-i18n="bundle.value">Стоимость:</strong> 
                                           <span class="bonus-old-price line-through text-gray-500">${formatPrice(service.nominal_value)}</span> 
                                           🎁 <strong class="text-accent" data-i18n="bundle.gift">ПОДАРОК</strong>
                                        </div>

                                        <div class="mt-3 text-sm text-gray-400">
                                            <strong data-i18n="bundle.partner">Partner</strong>: ${escapeHtml(getLocalized(service.partner.name))}
                                        </div>
                                        
                                        ${service.contact_info ? `
                                            <div class="mt-2 text-sm text-gray-400">
                                                <strong data-i18n="bundle.contact">Kontakt</strong>: ${escapeHtml(service.contact_info)}
                                            </div>
                                        ` : ''}
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    ` : ''}
                    
                    ${selectedBundle.total_savings > 0 ? `
                        <div class="savings-card">
                            <div class="text-center text-success font-bold">
                                <div class="mb-2">
                                    <span data-i18n="bundle.totalSaving">Общая экономия:</span> <strong>${formatPrice(selectedBundle.total_savings)}</strong>
                                </div>
                                <div>
                                    <span data-i18n="bundle.payOnly">Вы платите только</span> <strong>${formatPrice(selectedBundle.main_service.price)}</strong> 
                                    <span data-i18n="bundle.instead">вместо</span> <strong>${formatPrice(selectedBundle.main_service.price + selectedBundle.total_savings)}</strong>
                                </div>
                            </div>
                        </div>
                    ` : ''}
                </div>
            `;

            applyTranslations(currentDict);

            // ✅ Кнопка "Objednať" теперь просто добавляет в корзину
            const orderBtn = document.getElementById("order-btn");
            if (orderBtn) {
                orderBtn.onclick = () => {
                    cart.push(selectedBundle);
                    saveCart();
                    updateCartCount();
                    renderCart();

                    if (typeof pushAddToCart === 'function') {
                        pushAddToCart(selectedBundle);
                    }

                    closeBundleModal();
                    showAlert(currentDict.cart?.added || "Produkt bol pridaný do košíka.", "success");
                };
            }

        } else {
            throw new Error(data.message || 'Chyba pri načítaní detailov');
        }
    } catch (error) {
        console.error('Bundle detail error:', error);
        body.innerHTML = `
            <div class="text-center text-red-600">
                <p>Chyba pri načítaní detailov balíka.</p>
                <button onclick="showBundleDetail(${bundleId})" class="btn btn-primary mt-3">
                    Skúsiť znovu
                </button>
            </div>
        `;
    }
}

// Close bundle modal
function closeBundleModal() {
    const modal = document.getElementById('bundle-modal');
    if (modal) {
        modal.classList.add('hidden');
        selectedBundle = null;
    }
}

// ================= DATALAYER HELPER =================

function pushViewItem(bundle) {
    if (!window.dataLayer) window.dataLayer = [];
    
    const coupon = sessionStorage.getItem('appliedCoupon') || null;
    const discount = parseFloat(sessionStorage.getItem('discountAmount')) || 0;
    
    const item = {
        item_id: String(bundle.id),
        item_name: getLocalized(bundle.name),
        item_category: 'Bundle',
        item_variant: currentLang || 'sk',
        price: parseFloat(bundle.price) || 0,
        quantity: 1
    };
    if (coupon) item.coupon = coupon;
    if (discount > 0) item.discount = discount;
    
    window.dataLayer.push({
        event: 'view_item',
        ecommerce: {
            currency: 'EUR',
            value: parseFloat(bundle.price) || 0,
            items: [item]
        }
    });
}

// Экспорт в глобал
window.currentBundles = currentBundles;
window.selectedBundle = selectedBundle;
window.renderBundles = renderBundles;
window.loadBundles = loadBundles;
window.showBundleDetail = showBundleDetail;
window.closeBundleModal = closeBundleModal;
window.pushViewItem = pushViewItem;