// Hijack anchor clicks and smooth-scroll to targets accounting for fixed headers
export default function anchorScroll() {
    const EXTRA_OFFSET = 25; // pixels

    function getOffsetHeight(selector) {
        const el = document.querySelector(selector);
        return el ? el.offsetHeight : 0;
    }

    function getTotalOffset() {
        let total = EXTRA_OFFSET;
        // #wpadminbar if present
        total += getOffsetHeight('#wpadminbar');
        // header.c-header
        total += getOffsetHeight('header.c-header');
        return total;
    }

    function scrollToElement(el, updateHash = true) {
        if (!el) return;

        const offset = getTotalOffset();
        const rect = el.getBoundingClientRect();
        const top = Math.max(0, rect.top + window.pageYOffset - offset);

        window.scrollTo({ top, behavior: 'smooth' });

        // Update the URL hash without jumping
        if (updateHash && el.id) {
            try {
                history.pushState && history.pushState(null, '', '#' + el.id);
            } catch (e) {
                // ignore
            }
        }
    }

    function findTarget(hash) {
        if (!hash) return null;
        const id = hash.replace(/^#/, '');
        if (!id) return null;
        // By id
        let el = document.getElementById(id);
        if (el) return el;
        // By name (anchors)
        el = document.querySelector(`[name="${CSS.escape ? CSS.escape(id) : id}"]`);
        if (el) return el;
        // As a last resort, look for an element with data-anchor attribute
        return document.querySelector(`[data-anchor="${CSS.escape ? CSS.escape(id) : id}"]`);
    }

    // Handle clicks on same-page anchors
    document.addEventListener('click', function (e) {
        const a = e.target.closest && e.target.closest('a[href^="#"]');
        if (!a) return;

        const href = a.getAttribute('href');
        if (!href || href === '#') return;

        // Only handle same-page anchors
        const url = new URL(href, window.location.href);
        if (url.pathname !== window.location.pathname || url.search !== window.location.search) return;

        const target = findTarget(url.hash);
        if (target) {
            e.preventDefault();
            scrollToElement(target, true);
        }
    }, false);

    // On load, if there's a hash, scroll to it after a short delay to allow layout
    if (window.location.hash) {
        // Wait for images/fonts/layout to settle
        window.addEventListener('load', function () {
            const t = findTarget(window.location.hash);
            if (t) setTimeout(() => scrollToElement(t, false), 50);
        });
        // Also attempt immediately in case assets already loaded
        setTimeout(() => {
            const t = findTarget(window.location.hash);
            if (t) scrollToElement(t, false);
        }, 250);
    }
}
