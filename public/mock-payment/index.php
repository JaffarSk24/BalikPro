<?php
require_once __DIR__ . '/../../autoload.php';

use BalikPro\Models\Order;
use BalikPro\Utils\Logger;

$orderId = (int)($_GET['order_id'] ?? 0);
if (!$orderId) {
    header('Location: /?error=invalid_order');
    exit;
}

$orderModel = new Order();
$order = $orderModel->findById($orderId);

if (!$order) {
    header('Location: /?error=order_not_found');
    exit;
}

$logger = new Logger('mock_payment.log');
$logger->info("Mock payment page accessed", ['order_id' => $orderId]);
?>
<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simulovaná platba - Balík PRO</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8fafc;
            color: #1f2937;
            padding: 2rem;
        }
        
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .logo {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .content {
            padding: 2rem;
        }
        
        .order-info {
            background: #f3f4f6;
            padding: 1.5rem;
            border-radius: 0.5rem;
            margin-bottom: 2rem;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.75rem;
        }
        
        .info-row:last-child {
            margin-bottom: 0;
            font-size: 1.25rem;
            font-weight: bold;
            color: #2563eb;
            border-top: 1px solid #e5e7eb;
            padding-top: 0.75rem;
        }
        
        .payment-methods {
            margin-bottom: 2rem;
        }
        
        .payment-method {
            border: 2px solid #e5e7eb;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .payment-method:hover {
            border-color: #2563eb;
        }
        
        .payment-method.selected {
            border-color: #2563eb;
            background: #dbeafe;
        }
        
        .method-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.5rem;
        }
        
        .method-icon {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
        }
        
        .method-title {
            font-weight: 600;
        }
        
        .method-description {
            color: #6b7280;
            font-size: 0.875rem;
            margin-left: 2rem;
        }
        
        .payment-buttons {
            display: flex;
            gap: 1rem;
        }
        
        .btn {
            padding: 0.875rem 2rem;
            border: none;
            border-radius: 0.5rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            flex: 1;
        }
        
        .btn-primary {
            background: #2563eb;
            color: white;
        }
        
        .btn-primary:hover:not(:disabled) {
            background: #1d4ed8;
        }
        
        .btn-secondary {
            background: #dc2626;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #b91c1c;
        }
        
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .notice {
            background: #fef3c7;
            border: 1px solid #fcd34d;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 2rem;
        }
        
        .notice-title {
            font-weight: 600;
            color: #92400e;
            margin-bottom: 0.5rem;
        }
        
        .notice-text {
            color: #78350f;
            font-size: 0.875rem;
        }
        
        .spinner {
            width: 20px;
            height: 20px;
            border: 2px solid transparent;
            border-top: 2px solid currentColor;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 0.5rem;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">Balík PRO</div>
            <div>Simulovaná platba</div>
        </div>
        
        <div class="content">
            <div class="notice">
                <div class="notice-title">🧪 Vývojárska poznámka</div>
                <div class="notice-text">
                    Toto je simulovaná platobná brána pre účely testovania MVP. 
                    Skutočná integrácia s Revolut Pay bude implementovaná v produkčnej verzii.
                </div>
            </div>
            
            <div class="order-info">
                <h3 style="margin-bottom: 1rem;">Detaily objednávky</h3>
                <div class="info-row">
                    <span>Číslo objednávky:</span>
                    <span><strong><?= htmlspecialchars($order['order_number']) ?></strong></span>
                </div>
                <div class="info-row">
                    <span>Zákazník:</span>
                    <span><?= htmlspecialchars($order['customer_name']) ?></span>
                </div>
                <div class="info-row">
                    <span>Email:</span>
                    <span><?= htmlspecialchars($order['customer_email']) ?></span>
                </div>
                <div class="info-row">
                    <span>Celkom k úhrade:</span>
                    <span><?= number_format($order['total_amount'], 2, ',', ' ') ?> €</span>
                </div>
            </div>
            
            <div class="payment-methods">
                <h3 style="margin-bottom: 1rem;">Vyberte spôsob platby</h3>
                
                <div class="payment-method selected" onclick="selectPaymentMethod('card')">
                    <div class="method-header">
                        <div class="method-icon" style="background: #2563eb; color: white;">💳</div>
                        <div class="method-title">Platobná karta</div>
                    </div>
                    <div class="method-description">
                        Visa, Mastercard, American Express
                    </div>
                </div>
                
                <div class="payment-method" onclick="selectPaymentMethod('revolut')">
                    <div class="method-header">
                        <div class="method-icon" style="background: #000; color: white;">R</div>
                        <div class="method-title">Revolut Pay</div>
                    </div>
                    <div class="method-description">
                        Rýchla a bezpečná platba cez Revolut
                    </div>
                </div>
                
                <div class="payment-method" onclick="selectPaymentMethod('apple')">
                    <div class="method-header">
                        <div class="method-icon" style="background: #000; color: white;">🍎</div>
                        <div class="method-title">Apple Pay</div>
                    </div>
                    <div class="method-description">
                        Platba pomocou Apple Pay
                    </div>
                </div>
                
                <div class="payment-method" onclick="selectPaymentMethod('google')">
                    <div class="method-header">
                        <div class="method-icon" style="background: #4285f4; color: white;">G</div>
                        <div class="method-title">Google Pay</div>
                    </div>
                    <div class="method-description">
                        Platba pomocou Google Pay
                    </div>
                </div>
            </div>
            
            <div class="payment-buttons">
                <button id="fail-btn" class="btn btn-secondary" onclick="simulatePayment('failed')">
                    Simulovať neúspešnú platbu
                </button>
                <button id="success-btn" class="btn btn-primary" onclick="simulatePayment('paid')">
                    Simulovať úspešnú platbu
                </button>
            </div>
        </div>
    </div>

    <script>
        let selectedMethod = 'card';
        
        function selectPaymentMethod(method) {
            selectedMethod = method;
            
            // Update UI
            document.querySelectorAll('.payment-method').forEach(el => {
                el.classList.remove('selected');
            });
            
            event.target.closest('.payment-method').classList.add('selected');
        }
        
        async function simulatePayment(status) {
            const successBtn = document.getElementById('success-btn');
            const failBtn = document.getElementById('fail-btn');
            
            // Disable buttons and show loading
            successBtn.disabled = true;
            failBtn.disabled = true;
            
            const activeBtn = status === 'paid' ? successBtn : failBtn;
            const originalText = activeBtn.textContent;
            
            activeBtn.innerHTML = '<div class="spinner"></div>Spracovávam platbu...';
            
            try {
                // Simulate payment processing delay
                await new Promise(resolve => setTimeout(resolve, 2000));
                
                // Send webhook to simulate payment provider notification
                const webhookPayload = {
                    payment_id: 'mock_payment_' + Date.now(),
                    order_id: <?= $orderId ?>,
                    status: status,
                    amount: <?= $order['total_amount'] ?>,
                    currency: 'EUR',
                    payment_method: selectedMethod,
                    timestamp: new Date().toISOString()
                };
                
                const response = await fetch('/webhook.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(webhookPayload)
                });
                
                if (response.ok) {
                    // Redirect based on status
                    if (status === 'paid') {
                        window.location.href = '/checkout/success.php?order_id=<?= $orderId ?>';
                    } else {
                        window.location.href = '/checkout/failure.php?order_id=<?= $orderId ?>';
                    }
                } else {
                    throw new Error('Webhook failed');
                }
                
            } catch (error) {
                console.error('Payment simulation error:', error);
                alert('Chyba pri simulácii platby. Skúste to znovu.');
                
                // Reset buttons
                successBtn.disabled = false;
                failBtn.disabled = false;
                activeBtn.textContent = originalText;
            }
        }
    </script>
</body>
</html>
