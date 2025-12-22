<?php
require_once __DIR__ . '/../../autoload.php';

use BalikPro\Models\Order;
use BalikPro\Utils\Database;

$orderId = (int)($_GET['order_id'] ?? 0);
$order = null;

if ($orderId) {
    $orderModel = new Order();
    $order = $orderModel->findById($orderId);
}

$orderNumber = $order['order_number'] ?? null;
$orderLang = strtoupper($order['lang'] ?? 'SK');
$customerEmail = $order['customer_email'] ?? '';
$customerPhone = $order['customer_phone'] ?? '';
$paymentType = $order['payment_provider'] ?? 'unknown';
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($order['lang'] ?? 'sk') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title data-i18n="failure.pageTitle">Platba neúspešná - Balík PRO</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <script>
        // Прокидываем значения
        window.orderNumber = "<?= htmlspecialchars($orderNumber ?? '') ?>";
        window.customerEmail = "<?= htmlspecialchars($customerEmail ?? '') ?>";
    </script>
</head>
<body>

<?php
// Подключения из public/views
$viewsDir = __DIR__ . '/views';

// header.php (общая шапка)
$headerPath = $viewsDir . '/header.php';
if (file_exists($headerPath)) { include $headerPath; }

// header-extra.php (доп. секция для страниц)
$headerExtraPath = $viewsDir . '/header-extra.php';
if (file_exists($headerExtraPath)) { include $headerExtraPath; }
?>

    <div class="container" style="max-width: 900px; margin: 2.5rem auto; padding: 0 1rem;">
        <header style="text-align:center; margin: 1rem 0 1.5rem;">
            <h1 style="font-size: 2rem; font-weight: 800; color:#111827;">
                <span data-i18n="checkout.title">Objednávka</span>:
                <span id="order-number"><?= htmlspecialchars($orderNumber ?? '') ?></span>
            </h1>
        </header>

        <div class="card" style="max-width: 700px; margin: 0 auto 3rem; text-align:center; padding:3rem 2rem; background:white; border-radius:1rem; box-shadow:0 4px 6px rgba(0,0,0,0.1);">
            <div style="width:80px; height:80px; background:#dc2626; border-radius:50%; margin:0 auto 2rem; display:flex; align-items:center; justify-content:center; font-size:2rem; color:white;">
                ❌
            </div>

            <h2 data-i18n="failure.title" style="color:#1f2937; margin-bottom:1rem;">Platba nebola úspešná</h2>

            <p data-i18n="failure.desc" style="color:#6b7280; margin-bottom:2rem;">
                Bohužiaľ, pri spracovaní vašej platby došlo k chybe.<br>
                Objednávka nebola dokončená a žiadna suma nebola stiahnutá z vašej karty.
            </p>

            <div style="background:#fef2f2; border:1px solid #fca5a5; padding:1.5rem; border-radius:.5rem; margin-bottom:2rem; text-align:left;">
                <h3 data-i18n="failure.causesTitle" style="color:#991b1b; margin-bottom:1rem;">Možné príčiny:</h3>
                <ul style="color:#7f1d1d; line-height:1.6;">
                    <li data-i18n="failure.causeInsufficientFunds">Nedostatок prostriedkov na karte</li>
                    <li data-i18n="failure.causeInvalidCard">Nesprávne údaje platobnej karty</li>
                    <li data-i18n="failure.causeBlockedOrExpired">Karta je zablokovaná alebo expirovaná</li>
                    <li data-i18n="failure.causeTemporaryError">Dočasná chyba platobného systému</li>
                </ul>
            </div>

            <?php if ($order): ?>
                <p style="color:#6b7280; margin-bottom:2rem;" data-i18n="failure.orderAvailable">
                    Objednávka <strong><?= htmlspecialchars($order['order_number']) ?></strong> zostáva k dispozícii na opätovné zaplatenie.
                </p>
            <?php endif; ?>

            <div style="display:flex; gap:1rem; justify-content:center; flex-wrap:wrap;">
                <a href="/" class="btn btn-primary" data-i18n="common.backHome">Späť na hlavnú stránku</a>
                <?php if ($orderId): ?>
                    <a href="/mock-payment/?order_id=<?= $orderId ?>" class="btn btn-secondary" data-i18n="common.tryAgain">Skúsiť znovu</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

<?php
// body-extra.php (доп. контент внутри body)
$bodyExtraPath = $viewsDir . '/body-extra.php';
if (file_exists($bodyExtraPath)) { include $bodyExtraPath; }
?>

<?php if ($order): ?>
<?php
    $cartItems = $order['cart'] ? json_decode($order['cart'], true) : [];
    $pdo = Database::getInstance()->getConnection();
    $itemsForDl = [];

    $couponCode = $order['coupon_code'] ?? null;
    $discountAmount = isset($order['discount_amount']) ? (float)$order['discount_amount'] : 0;

    foreach ($cartItems as $ci) {
        if (!empty($ci['main_service_id'])) {
            $stmt = $pdo->prepare("SELECT title, price FROM services WHERE id = ?");
            $stmt->execute([$ci['main_service_id']]);
            if ($row = $stmt->fetch()) {
                $itemsForDl[] = [
                    'item_id'       => (string)$ci['main_service_id'],
                    'item_name'     => $row['title'],
                    'item_category' => 'Main Service',
                    'item_variant'  => $orderLang,
                    'price'         => (float)$row['price'],
                    'quantity'      => 1
                ];
            }
        }
        if (!empty($ci['bonus_service_ids']) && is_array($ci['bonus_service_ids'])) {
            $in  = str_repeat('?,', count($ci['bonus_service_ids']) - 1) . '?';
            $stmt = $pdo->prepare("SELECT id, title FROM services WHERE id IN ($in)");
            $stmt->execute($ci['bonus_service_ids']);
            foreach ($stmt->fetchAll() as $row) {
                $itemsForDl[] = [
                    'item_id'       => (string)$row['id'],
                    'item_name'     => $row['title'],
                    'item_category' => 'Bonus Service',
                    'item_variant'  => $orderLang,
                    'price'         => 0.00,
                    'quantity'      => 1
                ];
            }
        }
    }

    $hashedEmail = hash('sha256', strtolower(trim($customerEmail)));
    $hashedPhone = '';
    if (!empty($customerPhone)) {
        $cleanPhone = preg_replace('/\D/', '', $customerPhone);
        if ($cleanPhone) {
            $hashedPhone = hash('sha256', $cleanPhone);
        }
    }
?>
<script>
window.dataLayer = window.dataLayer || [];
window.dataLayer.push({ ecommerce: null });
window.dataLayer.push({
    event: "purchase_failed",
    ecommerce: {
        transaction_id: "<?= htmlspecialchars($orderNumber) ?>",
        affiliation: "Balik.PRO <?= $orderLang ?>",
        currency: "EUR",
        value: 0,
        <?php if ($couponCode): ?>coupon: "<?= htmlspecialchars($couponCode) ?>",<?php endif; ?>
        <?php if ($discountAmount > 0): ?>discount: <?= $discountAmount ?>,<?php endif; ?>
        payment_type: "<?= htmlspecialchars($paymentType) ?>",
        items: <?= json_encode($itemsForDl, JSON_UNESCAPED_UNICODE) ?>
    },
    user_data: {
        email_sha256: "<?= $hashedEmail ?>",
        <?php if ($hashedPhone): ?>phone_sha256: "<?= $hashedPhone ?>"<?php endif; ?>
    }
});
</script>
<?php endif; ?>

<?php
// footer.php (общий подвал)
$footerPath = $viewsDir . '/footer.php';
if (file_exists($footerPath)) { include $footerPath; }
?>

<script src="/assets/js/i18n.js"></script>
<script>
  if (typeof applyTranslations === 'function') {
      try { applyTranslations(window.currentDict || {}); } catch(e) {}
  }
  (function replaceTokens(){
      var ord = window.orderNumber || '';
      var em  = window.customerEmail || '';
      var replaceIn = function(el){
          if (!el) return;
          var html = el.innerHTML || '';
          if (!html) return;
          if (ord) html = html.split('{orderNumber}').join(ord);
          if (em)  html = html.split('{email}').join(em);
          el.innerHTML = html;
      };
      document.querySelectorAll('[data-i18n]').forEach(replaceIn);
  })();
</script>
</body>
</html>