function scrollToSection(sectionId) {
    const element = document.getElementById(sectionId);
    if (element) element.scrollIntoView({ behavior: 'smooth' });
}

function showLoading() {
    const loading = document.getElementById('loading');
    if (loading) loading.classList.remove('hidden');
}

function hideLoading() {
    const loading = document.getElementById('loading');
    if (loading) loading.classList.add('hidden');
}

function showError(message) {
    showAlert(message, 'error');
}

function showAlert(message, type = 'info') {
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.textContent = message;
    document.body.appendChild(alert);

    setTimeout(() => {
        if (alert.parentNode) {
            alert.remove();
        }
    }, 5000);
}

function formatPrice(price) {
    return new Intl.NumberFormat(currentLang, {
        style: 'currency',
        currency: 'EUR'
    }).format(price);
}

function escapeHtml(text) {
    if (!text) return '';
    const map = {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'};
    return text.replace(/[&<>"']/g, m => map[m]);
}

function debounce(func, wait) {
    let timeout;
    return function(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func(...args), wait);
    };
}

function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function isValidPhone(phone) {
    return /^[\+]?[(]?[\d\s\-\(\)]{9,}$/.test(phone.trim());
}

function clearFormErrors() {
    document.querySelectorAll('.error-message').forEach(el => el.textContent = '');
    document.querySelectorAll('.form-input.error').forEach(el => el.classList.remove('error'));
}

function showFieldError(fieldName, message) {
    const input = document.getElementById(fieldName);
    const error = document.getElementById(fieldName + '_error');
    if (input) input.classList.add('error');
    if (error) error.textContent = message;
}

// Экспортируем в глобал
window.scrollToSection = scrollToSection;
window.showError = showError;
window.showAlert = showAlert;
window.formatPrice = formatPrice;
window.escapeHtml = escapeHtml;
window.debounce = debounce;
window.isValidEmail = isValidEmail;
window.isValidPhone = isValidPhone;
window.clearFormErrors = clearFormErrors;
window.showFieldError = showFieldError;