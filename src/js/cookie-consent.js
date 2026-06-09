(function() {
    var config = window.alingsasCookieConsentConfig || {};
    var mtm = window._mtm = window._mtm || [];
    var paq = window._paq = window._paq || [];
    var pressidiumCookieName = config.pressidiumCookieName || 'pressidium_cookie_consent';
    var matomoContainerUrl = config.matomoContainerUrl || '';
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
                var trackers = [];
                try {
                    if (typeof window.Matomo.getAsyncTrackers === 'function') {
                        trackers = window.Matomo.getAsyncTrackers();
                    } else if (typeof window.Matomo.getAsyncTracker === 'function') {
                        var tracker = window.Matomo.getAsyncTracker();
                        if (tracker) {
                            trackers = [tracker];
                        }
                    }
                } catch (error) {
                    trackers = [];
                }

                if (trackers.length > 0) {
                    trackers.forEach(function(tracker) {
                        try {
                            tracker.setConsentGiven();
                            tracker.rememberConsentGiven();
                        } catch (error) {}
                    });
                    return;
                }

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

        updateMatomoConsent(consentState.consent_analytics);
        updateRekAiConsent(consentState.consent_analytics);
        mtm.push(consentState);

        window.dispatchEvent(new CustomEvent('alingsas-consent-updated', {
            detail: consentState
        }));

        if (shouldTrackCurrentPageView) {
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
        if (!matomoContainerUrl || document.querySelector('script[src="' + matomoContainerUrl + '"]')) {
            return;
        }

        var tagManagerScript = document.createElement('script');
        var firstScript = document.getElementsByTagName('script')[0];
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
