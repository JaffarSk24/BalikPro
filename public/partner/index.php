<?php
require_once __DIR__ . '/../../autoload.php';
?>
<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Partner prihlásenie - Balík PRO</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .partner-login {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #f8fafc 0%, #e0e7ff 100%);
            padding: 2rem;
        }
        
        .login-card {
            background: white;
            padding: 3rem 2rem;
            border-radius: 1rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .logo {
            font-size: 2rem;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 0.5rem;
        }
        
        .subtitle {
            color: #6b7280;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #374151;
        }
        
        .form-input {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 0.5rem;
            font-size: 1rem;
            transition: border-color 0.2s ease;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        .form-input.error {
            border-color: #dc2626;
        }
        
        .error-message {
            color: #dc2626;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
        
        .login-btn {
            width: 100%;
            padding: 1rem;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .login-btn:hover:not(:disabled) {
            background: #1d4ed8;
            transform: translateY(-1px);
        }
        
        .login-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .help-text {
            text-align: center;
            margin-top: 2rem;
            color: #6b7280;
            font-size: 0.875rem;
            line-height: 1.5;
        }
        
        .help-text a {
            color: #2563eb;
            text-decoration: none;
        }
        
        .help-text a:hover {
            text-decoration: underline;
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
        
        .alert {
            padding: 0.875rem 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            display: none;
            opacity: 1; /* Force opacity to 1 when displayed */
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
    </style>
</head>
<body>
    <div class="partner-login">
        <div class="login-card">
            <div class="login-header">
                <div class="logo">Balík PRO</div>
                <div class="subtitle">Partner prihlásenie</div>
            </div>
            
            <div id="alert" class="alert"></div>
            
            <form id="login-form">
                <div class="form-group">
                    <label class="form-label" for="partner_id">Partner ID</label>
                    <input type="number" id="partner_id" name="partner_id" class="form-input" 
                           placeholder="Zadajte vaše Partner ID" required>
                    <div class="error-message" id="partner_id_error"></div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="pin">PIN kód</label>
                    <input type="password" id="pin" name="pin" class="form-input" 
                           placeholder="Zadajte váš PIN kód" required maxlength="10">
                    <div class="error-message" id="pin_error"></div>
                </div>
                
                <button type="submit" id="login-btn" class="login-btn">
                    Prihlásiť sa
                </button>
            </form>
            
            <div class="help-text">
                Potrebujete pomoc s prihlásením?<br>
                <a href="mailto:support@balikpro.sk">Kontaktujte našu podporu</a>
                <br><br>
                <a href="/">← Späť na hlavnú stránku</a>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('login-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById('login-btn');
            const alert = document.getElementById('alert');
            const partnerId = document.getElementById('partner_id').value;
            const pin = document.getElementById('pin').value;
            
            // Clear previous errors
            clearFormErrors();
            hideAlert();
            
            // Validate form
            if (!partnerId || !pin) {
                showAlert('Vyplňte všetky polia', 'error');
                return;
            }
            
            if (partnerId < 1) {
                showFieldError('partner_id', 'Zadajte platné Partner ID');
                return;
            }
            
            if (pin.length < 4) {
                showFieldError('pin', 'PIN musí mať aspoň 4 znaky');
                return;
            }
            
            // Disable button and show loading
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<div class="spinner"></div>Prihlasuje...';
            
            try {
                const response = await fetch('/api/partner/auth', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        partner_id: parseInt(partnerId),
                        pin: pin
                    })
                });
                
                const data = await response.json();
                
                if (response.ok && data.success) {
                    // Store token and redirect to dashboard
                    localStorage.setItem('partner_token', data.data.token);
                    localStorage.setItem('partner_info', JSON.stringify(data.data.partner));
                    
                    showAlert('Prihlásenie úspešné! Presmerovávam...', 'success');
                    
                    setTimeout(() => {
                        window.location.href = '/partner/dashboard.php';
                    }, 1000);
                } else {
                    console.error('Login failed:', data); // Log full data
                    throw new Error(data.message || 'Nesprávne prihlasovacie údaje (Unknown Error)');
                }
            } catch (error) {
                console.error('Login catch error:', error);
                showAlert('Chyba: ' + error.message, 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Prihlásiť sa';
            }
        });
        
        function showAlert(message, type) {
            const alert = document.getElementById('alert');
            alert.className = 'alert alert-' + type;
            alert.textContent = message;
            alert.style.display = 'block';
        }
        
        function hideAlert() {
            const alert = document.getElementById('alert');
            alert.style.display = 'none';
        }
        
        function clearFormErrors() {
            const errorElements = document.querySelectorAll('.error-message');
            const inputElements = document.querySelectorAll('.form-input.error');
            
            errorElements.forEach(el => el.textContent = '');
            inputElements.forEach(el => el.classList.remove('error'));
        }
        
        function showFieldError(fieldName, message) {
            const input = document.getElementById(fieldName);
            const error = document.getElementById(fieldName + '_error');
            
            if (input) input.classList.add('error');
            if (error) error.textContent = message;
        }
        
        // Check if already logged in
        window.addEventListener('DOMContentLoaded', function() {
            const token = localStorage.getItem('partner_token');
            if (token) {
                // Verify token is still valid
                fetch('/api/partner/1/dashboard', {
                    headers: {
                        'Authorization': 'Bearer ' + token
                    }
                }).then(response => {
                    if (response.ok) {
                        window.location.href = '/partner/dashboard.php';
                    } else {
                        // Token is invalid, remove it
                        localStorage.removeItem('partner_token');
                        localStorage.removeItem('partner_info');
                    }
                }).catch(() => {
                    // Network error, continue with login page
                });
            }
        });
    </script>
</body>
</html>
