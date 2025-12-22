<!-- Hero Section -->
<section class="hero">
    <div class="container">
        <div class="hero-content">
            <h1 class="hero-title" data-i18n="hero.title">
                Výhodné balíky služieb<br> priamo na <span class="highlight text-gradient">Slovensku</span>
            </h1>
            <p class="hero-description" data-i18n="hero.desc">
                Objavte našu ponuku balíkov služieb s úžasnými úsporami. 
                Jedna hlavná služba + bonus služby od overených partnerov.
            </p>
            <div class="hero-actions">
                <button class="btn btn-primary" onclick="scrollToSection('bundles')" data-i18n="hero.btnBundles">
                    Pozrieť balíky
                </button>
                <button class="btn btn-secondary" onclick="scrollToSection('how-it-works')" data-i18n="hero.btnHow">
                    Ako to funguje
                </button>
            </div>
        </div>
    </div>
</section>

<!-- Loading Indicator -->
<div id="loading" class="loading hidden">
    <div class="loading-content">
        <div class="spinner"></div>
        <p data-i18n="loading">Načítavam balíky...</p>
    </div>
</div>

<!-- Bundles Section -->
<section id="bundles" class="bundles-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title" data-i18n="bundles.title">Dostupné balíky</h2>
            <p class="section-description" data-i18n="bundles.desc">
                Vyberte si z našej ponuky výhodných balíkov služieb
            </p>
        </div>

        <!-- Search and Filters -->
        <div class="filters">
            <div class="search-box">
                <input type="text" id="search-input" placeholder="Hľadať balíky..." class="search-input">
                <button id="search-btn" class="search-btn">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Bundles Grid -->
        <div id="bundles-grid" class="bundles-grid"></div>

        <!-- No Results Message -->
        <div id="no-results" class="no-results hidden">
            <div class="no-results-content">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <h3 data-i18n="bundles.noResults">Žiadne výsledky</h3>
                <p data-i18n="bundles.noResultsDesc">Nenašli sme žiadne balíky podľa vašich kritérií.</p>
            </div>
        </div>
    </div>
</section>

<!-- How It Works Section -->
<section id="how-it-works" class="how-it-works">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title" data-i18n="how.title">Ako to funguje</h2>
            <p class="section-description" data-i18n="how.desc">Jednoduchý proces v 4 krokoch</p>
        </div>

        <div class="steps">
            <div class="step glass-panel">
                <div class="step-icon"><span class="step-number">1</span></div>
                <h3 class="step-title" data-i18n="how.step1Title">Vyberte balík</h3>
                <p class="step-description" data-i18n="how.step1Desc">
                    Prejdite si našu ponuku a vyberte balík služieb, ktorý vám vyhovuje.
                </p>
            </div>

            <div class="step glass-panel">
                <div class="step-icon"><span class="step-number">2</span></div>
                <h3 class="step-title" data-i18n="how.step2Title">Objednajte online</h3>
                <p class="step-description" data-i18n="how.step2Desc">
                    Vyplňte svoje údaje a zaplaťte bezpečne online kartou alebo cez Revolut Pay.
                </p>
            </div>

            <div class="step glass-panel">
                <div class="step-icon"><span class="step-number">3</span></div>
                <h3 class="step-title" data-i18n="how.step3Title">Dostanete kupóny</h3>
                <p class="step-description" data-i18n="how.step3Desc">
                    Na email vám pošleme PDF s kupónmi a QR kódmi pre všetky služby.
                </p>
            </div>

            <div class="step glass-panel">
                <div class="step-icon"><span class="step-number">4</span></div>
                <h3 class="step-title" data-i18n="how.step4Title">Využite služby</h3>
                <p class="step-description" data-i18n="how.step4Desc">
                    Navštívte partnerov a aktivujte kupóny jednoduchým naskenovaním QR kódu.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Bundle Detail Modal -->
<div id="bundle-modal" class="modal hidden">
    <div class="modal-overlay" onclick="closeBundleModal()"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modal-title" class="modal-title" data-i18n="modal.title">Detail balíka</h2>
            <button class="modal-close" onclick="closeBundleModal()">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
      
        <div id="modal-body" class="modal-body"></div>
      
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeBundleModal()" data-i18n="modal.cancel">Zrušiť</button>
            <button id="order-btn" class="btn btn-primary">
                <span data-i18n="modal.order">Objednať za</span> <span id="modal-price"></span>
            </button>
        </div>
    </div>
</div>

<!-- Cart Modal -->
<div id="cart-modal" class="modal hidden">
  <div class="modal-overlay" onclick="closeCart()"></div>
  <div class="modal-content">
    <div class="modal-header">
      <h2 id="cart-title" data-i18n="cart.title">Košík</h2>
      <button class="modal-close" onclick="closeCart()">×</button>
    </div>
    <div class="modal-body">
      <ul id="cart-items"></ul>
      <p id="cart-empty" data-i18n="products.empty" style="display:none;">Košík je prázdny</p>
      <p id="cart-total"></p>
    </div>
    <div class="modal-footer">
      <button id="clear-cart" class="btn btn-secondary" data-i18n="cart.clear">Vyprázdniť košík</button>
      <a href="/checkout.php" id="checkout-btn" class="btn btn-primary" data-i18n="cart.checkout">Objednať</a>
    </div>
  </div>
</div>

<!-- ✅ Toast / Alert Container должен быть глобальным -->
<div id="alert-container"></div>