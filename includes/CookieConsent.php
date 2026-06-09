<?php

namespace AlingsasCustomisation\Includes;

use AlingsasCustomisation\Plugin;

class CookieConsent
{
    private const CONSENT_SCRIPT_HANDLE = 'alingsas-cookie-consent';
    private const CONSENT_SCRIPT_ENTRY = 'src/js/cookie-consent.js';
    private const MATOMO_CONTAINER_URL = 'https://matomo.michaelclaesson.se/js/container_ff6YHbXc.js';
    private const PRESSIDIUM_COOKIE_NAME = 'pressidium_cookie_consent';
    private const REK_AI_SCRIPT_HANDLE = 'modularity-recommend-stats';

    public function __construct()
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueueConsentScript'], 1);
        add_action('wp_head', [$this, 'printConsentScript'], -9);
        add_action('wp_enqueue_scripts', [$this, 'addRekAiConsentBootstrap'], 20);
        add_filter('script_loader_tag', [$this, 'addRekAiConsentAttributes'], 10, 3);
    }

    public function enqueueConsentScript(): void
    {
        if (is_admin()) {
            return;
        }

        $scriptUrl = $this->getConsentScriptUrl();
        if ($scriptUrl === '') {
            return;
        }

        wp_register_script(self::CONSENT_SCRIPT_HANDLE, $scriptUrl, [], null, false);
        wp_add_inline_script(
            self::CONSENT_SCRIPT_HANDLE,
            'window.alingsasCookieConsentConfig = ' . wp_json_encode([
                'matomoContainerUrl' => self::MATOMO_CONTAINER_URL,
                'pressidiumCookieName' => self::PRESSIDIUM_COOKIE_NAME,
            ]) . ';',
            'before'
        );
        wp_enqueue_script(self::CONSENT_SCRIPT_HANDLE);
    }

    public function printConsentScript(): void
    {
        if (is_admin() || !wp_script_is(self::CONSENT_SCRIPT_HANDLE, 'enqueued')) {
            return;
        }

        wp_print_scripts([self::CONSENT_SCRIPT_HANDLE]);
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
        if ($handle === self::CONSENT_SCRIPT_HANDLE) {
            if (strpos($tag, 'data-pressidium-cc-no-block') !== false) {
                return $tag;
            }

            return str_replace('<script ', '<script data-pressidium-cc-no-block ', $tag);
        }

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

    private function getConsentScriptUrl(): string
    {
        if (wp_get_environment_type() === 'development') {
            return 'https://localhost:5173/' . self::CONSENT_SCRIPT_ENTRY;
        }

        $manifestPath = Plugin::PATH . '/dist/.vite/manifest.json';
        if (!file_exists($manifestPath)) {
            return '';
        }

        $manifest = json_decode(file_get_contents($manifestPath), true);
        if (!is_array($manifest) || empty($manifest[self::CONSENT_SCRIPT_ENTRY]['file'])) {
            return '';
        }

        return plugin_dir_url(Plugin::PATH . '/municipio-customisation.php') . 'dist/' . $manifest[self::CONSENT_SCRIPT_ENTRY]['file'];
    }
}
