<?php

namespace AlingsasCustomisation\Includes;

class CookieConsent
{
    private const MATOMO_CONTAINER_URL = 'https://matomo.michaelclaesson.se/js/container_ff6YHbXc.js';
    private const PRESSIDIUM_COOKIE_NAME = 'pressidium_cookie_consent';
    private const REK_AI_SCRIPT_HANDLE = 'modularity-recommend-stats';

    public function __construct()
    {
        add_action('wp_head', [$this, 'printMatomoTagManager'], -9);
        add_action('wp_enqueue_scripts', [$this, 'addRekAiConsentBootstrap'], 20);
        add_filter('script_loader_tag', [$this, 'addRekAiConsentAttributes'], 10, 3);
    }

    public function printMatomoTagManager(): void
    {
        if (is_admin()) {
            return;
        }

        $containerUrl = wp_json_encode(self::MATOMO_CONTAINER_URL);
        $cookieName = wp_json_encode(self::PRESSIDIUM_COOKIE_NAME);
        ?>
        <!-- Matomo Tag Manager -->
        <script data-pressidium-cc-no-block>
            (function() {
                var mtm = window._mtm = window._mtm || [];
                var paq = window._paq = window._paq || [];
                var pressidiumCookieName = <?php echo $cookieName; ?>;
                var matomoContainerUrl = <?php echo $containerUrl; ?>;
                var currentMatomoAnalyticsConsent = null;

                paq.push(['requireConsent']);

                function readCookie(name) {
                    var cookies = document.cookie ? document.cookie.split('; ') : [];
                    for (var i = 0; i < cookies.length; i++) {
                        var parts = cookies[i].split('=');
                        var cookieName = parts.shift();
                        if (cookieName === name) {
                            return parts.join('=');
                        }
                    }
                    return '';
                }

                function getStoredPressidiumCategories() {
                    var rawCookie = readCookie(pressidiumCookieName);
                    if (!rawCookie) {
                        return [];
                    }

                    var decodedCookie = rawCookie;
                    try {
                        decodedCookie = decodeURIComponent(rawCookie);
                    } catch (error) {
                        decodedCookie = rawCookie;
                    }

                    try {
                        var consent = JSON.parse(decodedCookie);
                        if (Array.isArray(consent.categories)) {
                            return consent.categories;
                        }
                        if (Array.isArray(consent.level)) {
                            return consent.level;
                        }
                    } catch (error) {
                        return [];
                    }

                    return [];
                }

                function allowedCategory(category) {
                    if (
                        window.pressidiumCookieConsent &&
                        typeof window.pressidiumCookieConsent.allowedCategory === 'function'
                    ) {
                        return window.pressidiumCookieConsent.allowedCategory(category);
                    }

                    return getStoredPressidiumCategories().indexOf(category) !== -1;
                }

                function currentConsentState(eventName) {
                    return {
                        event: eventName,
                        consent_analytics: allowedCategory('analytics'),
                        consent_targeting: allowedCategory('targeting'),
                        consent_preferences: allowedCategory('preferences')
                    };
                }

                function updateRekAiConsent(analyticsAllowed) {
                    window.__rekai = window.__rekai || {};
                    window.__rekai.consentaccepted = !!analyticsAllowed;
                    window.__rekai.blockSaveToSessionStorage = !analyticsAllowed;
                    window.__rekai.blockSaveToLocalStorage = !analyticsAllowed;
                    window.__rekai.blockSendBeacon = !analyticsAllowed;

                    if (
                        !analyticsAllowed &&
                        typeof window.__rekai.removeLocalAndSessionStorage === 'function'
                    ) {
                        window.__rekai.removeLocalAndSessionStorage();
                    }
                }

                function updateMatomoConsent(analyticsAllowed) {
                    if (analyticsAllowed) {
                        paq.push(['setConsentGiven']);
                        paq.push(['rememberConsentGiven']);
                        currentMatomoAnalyticsConsent = true;
                        return;
                    }

                    paq.push(['forgetConsentGiven']);
                    paq.push(['deleteCookies']);
                    currentMatomoAnalyticsConsent = false;
                }

                function waitForTrackerAndTrackPageView(maxAttempts) {
                    maxAttempts = maxAttempts || 25;
                    var attempts = 0;

                    function tryTrack() {
                        attempts++;
                        var matomoReady = typeof window.Matomo !== 'undefined';

                        if (matomoReady) {
                            // Use the tracker object directly — avoids _paq proxy queue ordering issues.
                            // getAsyncTrackers() returns all instances created by MTM/async code.
                            var trackers = [];
                            try {
                                if (typeof window.Matomo.getAsyncTrackers === 'function') {
                                    trackers = window.Matomo.getAsyncTrackers();
                                } else if (typeof window.Matomo.getAsyncTracker === 'function') {
                                    var t = window.Matomo.getAsyncTracker();
                                    if (t) trackers = [t];
                                }
                            } catch (e) {}

                            if (trackers.length > 0) {
                                trackers.forEach(function(tracker) {
                                    try {
                                        // setConsentGiven/rememberConsentGiven are already queued via _paq
                                        // in updateMatomoConsent. Only call trackPageView here to avoid
                                        // duplicate pings.
                                        tracker.setConsentGiven();
                                        tracker.rememberConsentGiven();
                                    } catch (e) {}
                                });
                                return;
                            }

                            // Fallback: no trackers found via API, go through _paq
                            paq.push(['trackPageView']);
                            return;
                        }

                        if (attempts < maxAttempts) {
                            window.setTimeout(tryTrack, 200);
                        }
                    }

                    tryTrack();
                }

                function pushConsentState(eventName) {
                    var consentState = currentConsentState(eventName);
                    var shouldTrackCurrentPageView =
                        eventName !== 'pressidium_consent_default' &&
                        consentState.consent_analytics &&
                        currentMatomoAnalyticsConsent === false;

                    console.log(
                        '[Consent] pushConsentState: event=' + eventName +
                        ' | analytics=' + consentState.consent_analytics +
                        ' | previousConsent=' + currentMatomoAnalyticsConsent +
                        ' | shouldTrackPageView=' + shouldTrackCurrentPageView
                    );

                    updateMatomoConsent(consentState.consent_analytics);
                    updateRekAiConsent(consentState.consent_analytics);
                    mtm.push(consentState);

                    window.dispatchEvent(new CustomEvent('alingsas-consent-updated', {
                        detail: consentState
                    }));

                    if (shouldTrackCurrentPageView) {
                        console.log('[Consent] Consent upgraded to analytics=true — waiting for Matomo tracker to load...');
                        waitForTrackerAndTrackPageView();
                    }
                }

                window.alingsasConsent = {
                    allowedCategory: allowedCategory,
                    current: currentConsentState,
                    push: pushConsentState,
                    updateMatomo: updateMatomoConsent,
                    updateRekAi: updateRekAiConsent
                };

                pushConsentState('pressidium_consent_default');

                mtm.push({
                    'mtm.startTime': (new Date().getTime()),
                    event: 'mtm.Start'
                });

                (function() {
                    var documentRef = document;
                    if (documentRef.querySelector('script[src="' + matomoContainerUrl + '"]')) {
                        return;
                    }

                    var tagManagerScript = documentRef.createElement('script');
                    var firstScript = documentRef.getElementsByTagName('script')[0];
                    tagManagerScript.async = true;
                    tagManagerScript.src = matomoContainerUrl;
                    firstScript.parentNode.insertBefore(tagManagerScript, firstScript);
                })();

                window.addEventListener('pressidium-cookie-consent-accepted', function() {
                    pushConsentState('pressidium_consent_update');
                });
                window.addEventListener('pressidium-cookie-consent-changed', function() {
                    pushConsentState('pressidium_consent_update');
                });
            })();
        </script>
        <!-- End Matomo Tag Manager -->
        <?php
    }

    public function addRekAiConsentBootstrap(): void
    {
        $script = <<<'JS'
(function() {
    var analyticsAllowed = false;

    if (
        window.alingsasConsent &&
        typeof window.alingsasConsent.allowedCategory === 'function'
    ) {
        analyticsAllowed = window.alingsasConsent.allowedCategory('analytics');
    }

    window.__rekai = window.__rekai || {};
    window.__rekai.consentaccepted = !!analyticsAllowed;
    window.__rekai.blockSaveToSessionStorage = !analyticsAllowed;
    window.__rekai.blockSaveToLocalStorage = !analyticsAllowed;
    window.__rekai.blockSendBeacon = !analyticsAllowed;
})();
JS;

        wp_add_inline_script(self::REK_AI_SCRIPT_HANDLE, $script, 'before');
    }

    public function addRekAiConsentAttributes(string $tag, string $handle, string $src): string
    {
        if ($handle !== self::REK_AI_SCRIPT_HANDLE) {
            return $tag;
        }

        $attributes = '';
        if (strpos($tag, 'data-useconsent=') === false) {
            $attributes .= ' data-useconsent="true"';
        }
        if (strpos($tag, 'data-pressidium-cc-no-block') === false) {
            $attributes .= ' data-pressidium-cc-no-block';
        }

        if ($attributes === '') {
            return $tag;
        }

        return str_replace('<script ', '<script' . $attributes . ' ', $tag);
    }
}
