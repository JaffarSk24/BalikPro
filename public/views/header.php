<?php
// Определяем, на главной ли мы, чтобы строить ссылки на якоря корректно
$reqPath = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
$isHome = ($reqPath === '/' || $reqPath === '/index.php');

function home_anchor(string $hash, bool $isHome): string {
    // На главной — просто "#id", на внутренних — "/#id"
    return $isHome ? $hash : '/'.$hash;
}
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Balík PRO</title>
<link rel="stylesheet" href="/assets/css/style.css">
<link rel="icon" type="image/x-icon" href="/assets/images/favicon.ico">
<!-- Header -->
<header class="header">
    <div class="container">
        <div class="header-content">
            <div class="logo">
                <a href="/" class="logo-link" aria-label="Balík PRO – Home">
                    <h1>Balík <span class="pro">PRO</span></h1>
                </a>
            </div>
            <nav class="nav">
                <a href="<?= home_anchor('#bundles', $isHome) ?>" class="nav-link" data-i18n="menu.bundles">Balíky</a>
                <a href="<?= home_anchor('#how-it-works', $isHome) ?>" class="nav-link" data-i18n="menu.how">Ako to funguje</a>
                <a href="<?= home_anchor('#partner', $isHome) ?>" class="nav-link" data-i18n="menu.partner">Partner</a>
            </nav>

            <!-- Right controls: Cart + Language -->
            <div class="header-controls">
                <!-- CART LINK -->
                <a href="/checkout.php" id="cart-link" class="nav-link cart-link" aria-haspopup="dialog" aria-controls="cart-modal">
                    <span data-i18n="cart.title">Košík</span>
                    <span id="cart-count">(0)</span>
                </a>

                <!-- LANGUAGE SWITCHER -->
                <div class="lang-switcher">
                    <button class="lang-btn" onclick="setLang('sk')" id="lang-sk">SK</button>
                    <button class="lang-btn" onclick="setLang('ru')" id="lang-ru">RU</button>
                    <button class="lang-btn" onclick="setLang('uk')" id="lang-uk">UA</button>
                </div>
            </div>
        </div>
    </div>
</header>