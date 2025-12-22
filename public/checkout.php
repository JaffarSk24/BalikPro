<?php
require_once __DIR__ . '/../vendor/autoload.php';
session_start();

$pageLang = '';
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($pageLang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title data-i18n="checkout.pageTitle">Objednávka - Balík PRO</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
    <style>
        /* Checkout specific styles */
        .co-list { list-style: none; padding: 0; margin: 1.5rem 0; }
        .co-item {
            display: flex; align-items: center; justify-content: space-between;
            background: rgba(17, 24, 39, 0.4); 
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-sm); 
            padding: 16px; margin-bottom: 12px;
        }
        .co-left { display: flex; flex-direction: column; gap: 4px; }
        .co-name { font-weight: 600; color: var(--text-primary); font-size: 1.1rem; }
        .co-bonus { color: var(--success); font-weight: 500; font-size: 0.9rem; }
        .co-price { color: var(--primary-color); font-weight: 700; }
        .cart-item-remove {
            background: rgba(220, 38, 38, 0.2); color: #fca5a5; 
            width: 32px; height: 32px; border-radius: 8px;
            border: 1px solid rgba(220, 38, 38, 0.3); 
            display: flex; align-items: center; justify-content: center; 
            cursor: pointer; transition: all 0.2s;
        }
        .cart-item-remove:hover { background: rgba(220, 38, 38, 0.4); color: white; }

        .co-scroll {
            max-height: 320px;
            overflow-y: auto;
            padding-right: 8px;
        }
        
        .co-scroll::-webkit-scrollbar { width: 6px; }
        .co-scroll::-webkit-scrollbar-track { background: rgba(255,255,255,0.05); border-radius: 4px; }
        .co-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.2); border-radius: 4px; }
        .co-scroll::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.3); }

        .consent-note {
            color: var(--text-secondary);
            font-size: 0.875rem;
            margin-top: 1rem;
        }
        .consent-note a { color: var(--primary-color); text-decoration: underline; }
        
        /* Layout Grid */
        .checkout-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; }
        @media (max-width: 768px) { .checkout-grid { grid-template-columns: 1fr; } }
        
        .cart-total-row {
            display: flex; justify-content: space-between; 
            font-size: 1.25rem; font-weight: 700; color: var(--text-primary);
            border-top: 1px solid var(--glass-border);
            padding-top: 1.5rem; margin-top: 1.5rem;
        }
         
        .w-full { width: 100%; }
        .mt-6 { margin-top: 1.5rem; }
        .mb-8 { margin-bottom: 2rem; }
        .text-center { text-align: center; }
    </style>
    </style>
    <script>
        // Наследуем язык, установленный на главной (из localStorage) и вешаем класс "загрузка i18n"
        document.documentElement.classList.add('i18n-loading');
        window.pageLang = localStorage.getItem('lang') || '';
        if (window.pageLang) {
            document.documentElement.setAttribute('lang', window.pageLang);
        }
    </script>
</head>
<body>

<?php
// Подключения из public/views
$viewsDir = __DIR__ . '/views';

// header.php (общая шапка)
$headerPath = $viewsDir . '/header.php';
if (file_exists($headerPath)) { include $headerPath; }

// header-extra.php (доп. секция для checkout)
$headerExtraPath = $viewsDir . '/header-extra.php';
if (file_exists($headerExtraPath)) { include $headerExtraPath; }

// body-extra.php будет подключён ниже, после основного контента
?>

    <div class="container checkout-layout">
        <header class="text-center mb-8">
            <h1 data-i18n="checkout.title">Objednávka</h1>
        </header>

        <main class="checkout-grid">
            <!-- Левая колонка: Корзина -->
            <section class="checkout-summary glass-panel">
                <h2 data-i18n="checkout.yourOrder">Váš balík</h2>
                <!-- Внутренний контейнер со скроллом ТОЛЬКО для списка пакетов -->
                <div class="co-scroll">
                    <ul id="cart-items-list" class="co-list">
                        <!-- Заполняется JS -->
                    </ul>
                </div>
                <div class="cart-total-row">
                    <span data-i18n="checkout.total">Celkom</span>
                    <span id="cart-total">€0.00</span>
                </div>
            </section>

            <!-- Правая колонка: Форма -->
            <section class="checkout-form glass-panel">
                <h2 data-i18n="checkout.contactDetails">Kontaktné údaje</h2>
                <form id="checkout-form" class="mt-6">
                    <div class="input-group">
                        <label for="name" class="form-label" data-i18n="checkout.fullName">Meno a priezvisko *</label>
                        <input type="text" id="name" name="name" required
                               class="form-input"
                               data-i18n-placeholder="checkout.namePlaceholder">
                    </div>
                    <div class="input-group">
                        <label for="email" class="form-label" data-i18n="checkout.email">Email *</label>
                        <input type="email" id="email" name="email" required
                               class="form-input"
                               data-i18n-placeholder="checkout.emailPlaceholder">
                    </div>
                    <div class="input-group">
                        <label for="phone" class="form-label" data-i18n="checkout.phone">Telefón</label>
                        <input type="tel" id="phone" name="phone"
                               class="form-input"
                               data-i18n-placeholder="checkout.phonePlaceholder">
                    </div>
                    <button type="submit" class="btn btn-primary w-full">
                        <span data-i18n="checkout.proceedToPayment">Prejsť na platbu</span>
                    </button>
                </form>

                <!-- Согласие на обработку данных -->
                <p class="consent-note" data-i18n="checkout.consentNotice"></p>
            </section>
        </main>

<?php
// body-extra.php (доп. контент внутри body)
$bodyExtraPath = $viewsDir . '/body-extra.php';
if (file_exists($bodyExtraPath)) { include $bodyExtraPath; }
?>

    </div>

<?php
// footer.php (общий подвал)
$footerPath = $viewsDir . '/footer.php';
if (file_exists($footerPath)) { include $footerPath; }
?>

    <!-- Порядок важен: i18n.js раньше checkout.js -->
    <script src="/assets/js/utils.js"></script>
    <script src="/assets/js/i18n.js"></script>
    <script src="/assets/js/cart.js"></script>
    <script src="/assets/js/checkout.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', async () => {
            // Инициализируем i18n до любого динамического рендера
            if (typeof initI18n === 'function') {
                await initI18n();
            }
            // Рендер корзины (создаёт элементы с data-i18n), потом повторная локализация
            if (typeof renderCheckoutCart === 'function') {
                renderCheckoutCart();
            }
        });
    </script>
</body>
</html>