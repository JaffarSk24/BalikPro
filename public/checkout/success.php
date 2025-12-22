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

// было: $order['status'] === 'paid' (колонки status нет)
// стало: payment_status (enum: pending/paid/failed/cancelled)
$paymentStatus = $order['payment_status'] ?? null;
$isPaid = ($paymentStatus === 'paid');

$orderNumber   = $order['order_number'] ?? null;
$orderLang     = strtoupper($order['lang'] ?? 'SK');
$customerEmail = $order['customer_email'] ?? '';
$customerPhone = $order['customer_phone'] ?? '';

if (session_status() === PHP_SESSION_NONE) { @session_start(); }
if ($isPaid && isset($_SESSION['orderNumber'])) {
    unset($_SESSION['orderNumber']);
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($order['lang'] ?? 'sk') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title data-i18n="success.pageTitle">Platba úspešná - Balík PRO</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <script>
        window.orderNumber = "<?= htmlspecialchars($orderNumber ?? '') ?>";
        window.customerEmail = "<?= htmlspecialchars($customerEmail ?? '') ?>";
    </script>
</head>
<body>

<?php
$viewsDir = __DIR__ . '/views';

$headerPath = $viewsDir . '/header.php';
if (file_exists($headerPath)) { include $headerPath; }

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
            <?php if ($isPaid): ?>
                <div style="width:80px; height:80px; background:#10b981; border-radius:50%; margin:0 auto 2rem; display:flex; align-items:center; justify-content:center; font-size:2rem;">
                    ✅
                </div>

                <h2 data-i18n="success.paidTitle" style="color:#1f2937; margin-bottom:1rem;">Platba bola úspešná!</h2>

                <p style="color:#6b7280; margin-bottom:2rem;">
                    <span data-i18n="success.thanks">Ďakujeme za vašu objednávku {orderNumber}.</span><br>
                    <span data-i18n="success.sentTo">Kupóny vám pošleme na email {email}.</span>
                </p>

                <div style="background:#f0f9ff; border:1px solid #0ea5e9; padding:1.5rem; border-radius:.5rem; margin-bottom:2rem; text-align:left;">
                    <h3 data-i18n="success.nextTitle" style="color:#0c4a6e; margin-bottom:1rem;">Čo bude nasledovať?</h3>
                    <ul style="color:#075985; line-height:1.6;">
                        <li data-i18n="success.next1">📧 Kupóny dostanete na email do 5 minút</li>
                        <li data-i18n="success.next2">📱 V emaili nájdete PDF s QR kódmi</li>
                        <li data-i18n="success.next3">🏪 Navštívte partnerov a aktivujte kupóny</li>
                        <li data-i18n="success.next4">🎉 Užite si vaše služby!</li>
                    </ul>
                </div>
            <?php else: ?>
                <div style="width:80px; height:80px; background:#f59e0b; border-radius:50%; margin:0 auto 2rem; display:flex; align-items:center; justify-content:center; font-size:2rem;">
                    ⏳
                </div>

                <h2 data-i18n="success.processingTitle" style="color:#1f2937; margin-bottom:1rem;">Spracovávame vašu platbu...</h2>

                <p data-i18n="success.processingDesc" style="color:#6b7280; margin-bottom:2rem;">
                    Vaša objednávka je v procese spracovania.<br>
                    Prosím, počkajte chvíľu alebo obnovte stránku.
                </p>
            <?php endif; ?>

            <div style="display:flex; gap:1rem; justify-content:center; flex-wrap:wrap;">
                <a href="/" class="btn btn-primary" data-i18n="common.backHome">Späť na hlavnú stránku</a>
                <a href="/partner/" class="btn btn-secondary" data-i18n="common.partnerLogin">Partner prihlásenie</a>
            </div>
        </div>
    </div>

<?php
$bodyExtraPath = $viewsDir . '/body-extra.php';
if (file_exists($bodyExtraPath)) { include $bodyExtraPath; }
?>

<?php if ($isPaid && $order): ?>
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
                $item = [
                    'item_id'       => (string)$ci['main_service_id'],
                    'item_name'     => $row['title'],
                    'item_category' => 'Main Service',
                    'item_variant'  => $orderLang,
                    'price'         => (float)$row['price'],
                    'quantity'      => 1
                ];
                if ($couponCode) $item['coupon'] = $couponCode;
                if ($discountAmount > 0) $item['discount'] = $discountAmount;
                $itemsForDl[] = $item;
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

    $paymentType = $order['payment_provider'] ?? 'unknown';
?>
<script>
window.dataLayer = window.dataLayer || [];
window.dataLayer.push({ ecommerce: null });
window.dataLayer.push({
    event: "purchase",
    ecommerce: {
        transaction_id: "<?= htmlspecialchars($orderNumber) ?>",
        affiliation: "Balik.PRO <?= $orderLang ?>",
        currency: "EUR",
        value: <?= (float)$order['total_amount'] ?>,
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