<?php

namespace AlingsasCustomisation\Includes;

use AlingsasCustomisation\Plugin;

class Styles {
    public function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueueFrontendStyles']);
        add_filter('block_editor_settings_all', [$this, 'addEditorIframeStyles'], 20);
        add_filter('mce_css', [$this, 'addTinyMceStylesheet']);
    }

    /**
     * Enqueue compiled frontend CSS from the Vite manifest.
     */
    public function enqueueFrontendStyles(): void {
        $url = $this->getDistUrl('src/scss/main.scss');
        if ($url === null) {
            return;
        }

        wp_enqueue_style('alingsas-style', $url, null, Plugin::VERSION);
    }

    /**
     * Inject the same token sheet into the Gutenberg content iframe.
     *
     * @param array<string, mixed> $settings
     * @return array<string, mixed>
     */
    public function addEditorIframeStyles(array $settings): array {
        $css = $this->getDistCss('src/scss/editor.scss');
        if ($css === '') {
            return $settings;
        }

        $editorStyles = $settings['styles'] ?? [];
        if (!is_array($editorStyles)) {
            $editorStyles = [];
        }

        $editorStyles[] = ['css' => $css];
        $settings['styles'] = $editorStyles;

        return $settings;
    }

    /**
     * Load the token sheet in TinyMCE as content_css (iframe <link>), same as
     * Municipio's styleguide.css. Do not use content_style — TinyMCE 4 parses
     * that string and can leave mce_SELRES bookmarks in the HTML.
     *
     * @param string $mce_css Comma-separated stylesheet URLs.
     */
    public function addTinyMceStylesheet(string $mce_css): string {
        $url = $this->getDistUrl('src/scss/editor.scss');
        if ($url === null) {
            return $mce_css;
        }

        return trim($mce_css . ',' . $url, ' ,');
    }

    /**
     * @return string|null Absolute URL to a Vite-built asset, or null if missing.
     */
    private function getDistUrl(string $entry): ?string {
        $file = $this->getManifestFile($entry);
        if ($file === null) {
            return null;
        }

        return dirname(plugin_dir_url(__FILE__)) . '/dist/' . $file;
    }

    /**
     * @return string Compiled CSS contents, or empty string if missing.
     */
    private function getDistCss(string $entry): string {
        $file = $this->getManifestFile($entry);
        if ($file === null) {
            return '';
        }

        $path = dirname(plugin_dir_path(__FILE__)) . '/dist/' . $file;
        if (!is_readable($path)) {
            return '';
        }

        $css = file_get_contents($path);

        return is_string($css) ? $css : '';
    }

    /**
     * @return string|null Manifest-relative file path.
     */
    private function getManifestFile(string $entry): ?string {
        $manifestPath = dirname(plugin_dir_path(__FILE__)) . '/dist/manifest.json';
        if (!is_readable($manifestPath)) {
            return null;
        }

        $manifest = json_decode((string) file_get_contents($manifestPath));
        $file = $manifest->{$entry}->file ?? null;

        return is_string($file) && $file !== '' ? $file : null;
    }
}
