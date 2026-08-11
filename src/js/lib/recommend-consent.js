// Hide RekAI preload buttons in the Recommend module when analytics cookies
// are declined, since the RekAI script (which replaces them) is blocked by
// Pressidium Cookie Consent and they would otherwise be stuck showing "loading".
export default function recommendConsent() {
    const PRELOADER_SELECTOR = '.modularity-mod-recommend .u-preloader';

    function analyticsAllowed() {
        const consent = window.pressidiumCookieConsent;
        if (!consent || typeof consent.allowedCategory !== 'function') return true;
        return !!consent.allowedCategory('analytics');
    }

    function syncPreloaders() {
        const allowed = analyticsAllowed();
        document.querySelectorAll(PRELOADER_SELECTOR).forEach((el) => {
            el.style.display = allowed ? '' : 'none';
        });
    }

    syncPreloaders();

    ['pressidium-cookie-consent-accepted', 'pressidium-cookie-consent-changed'].forEach((eventName) => {
        window.addEventListener(eventName, syncPreloaders);
    });
}
