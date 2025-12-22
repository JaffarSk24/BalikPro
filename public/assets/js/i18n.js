let currentLang = 'sk';
let currentDict = {};

function getLocalized(field) {
    if (!field) return '';
    if (typeof field === 'string') return field;
    return field[currentLang] || field['sk'] || field['ru'] || field['uk'] || '';
}

function detectUserLang() {
    const langs = navigator.languages || [navigator.language || navigator.userLanguage];
    for (const lang of langs) {
        if (lang.toLowerCase().startsWith('ru')) return 'ru';
        if (lang.toLowerCase().startsWith('uk')) return 'uk';
        if (lang.toLowerCase().startsWith('sk')) return 'sk';
    }
    return 'sk';
}

async function loadLang(lang) {
    const res = await fetch(`/assets/i18n/${lang}.json`);
    return res.json();
}

function resolveKey(dict, key) {
    let value = dict;
    key.split('.').forEach(k => value = value ? value[k] : null);
    return value;
}

function interpolate(str) {
    const s = String(str);
    const year = new Date().getFullYear().toString();
    // безопасная замена всех вхождений без RegExp
    return Object.entries({
        '{orderNumber}': (window.orderNumber ?? ''),
        '{email}': (window.customerEmail ?? ''),
        '{year}': year,
        '{Y}': year
    }).reduce((acc, [k, v]) => acc.split(k).join(v), s);
}

function applyTranslations(dict) {
    // стандартные тексты
    document.querySelectorAll('[data-i18n]').forEach(el => {
        const key = el.getAttribute('data-i18n');
        const value = resolveKey(dict, key);
        if (value) el.innerHTML = interpolate(value);
    });

    // placeholder
    document.querySelectorAll('[data-i18n-placeholder]').forEach(el => {
        const key = el.getAttribute('data-i18n-placeholder');
        const value = resolveKey(dict, key);
        if (value) el.setAttribute('placeholder', interpolate(value));
    });

    // title
    document.querySelectorAll('[data-i18n-title]').forEach(el => {
        const key = el.getAttribute('data-i18n-title');
        const value = resolveKey(dict, key);
        if (value) el.setAttribute('title', interpolate(value));
    });

    // alt
    document.querySelectorAll('[data-i18n-alt]').forEach(el => {
        const key = el.getAttribute('data-i18n-alt');
        const value = resolveKey(dict, key);
        if (value) el.setAttribute('alt', interpolate(value));
    });
}

async function setLang(lang) {
    currentLang = lang;
    localStorage.setItem('lang', lang);
    currentDict = await loadLang(lang);

    applyTranslations(currentDict);
    highlightActiveLang();

    await loadBundles();
    renderCart();

    if (typeof renderCheckoutCart === 'function') {
        renderCheckoutCart();
    }

    if (window.selectedBundle) {
        showBundleDetail(window.selectedBundle.id);
    }

    document.documentElement.setAttribute("lang", currentLang);
}

function highlightActiveLang() {
    document.querySelectorAll('.lang-btn').forEach(btn => btn.classList.remove('active'));
    const activeBtn = document.getElementById(`lang-${currentLang}`);
    if (activeBtn) activeBtn.classList.add('active');
}

async function initI18n() {
    const saved = localStorage.getItem('lang');
    currentLang = saved || window.pageLang || detectUserLang();
    if (!saved) localStorage.setItem('lang', currentLang);

    currentDict = await loadLang(currentLang);
    applyTranslations(currentDict);
    highlightActiveLang();
    await loadBundles();
    renderCart();

    if (typeof renderCheckoutCart === 'function') {
        renderCheckoutCart();
    }

    if (window.selectedBundle) {
        showBundleDetail(window.selectedBundle.id);
    }

    document.documentElement.setAttribute("lang", currentLang);
    // снимаем «прелоадер» текста, чтобы не мигало словацким
    document.documentElement.classList.remove('i18n-loading');
}

// Экспорт глобально
window.getLocalized = getLocalized;
window.setLang = setLang;
window.highlightActiveLang = highlightActiveLang;
window.initI18n = initI18n;
window.applyTranslations = applyTranslations;