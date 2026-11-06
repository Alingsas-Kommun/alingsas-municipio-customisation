<?php

namespace AlingsasCustomisation\Includes;

use AlingsasCustomisation\Plugin;

/**
 * Replaces Municipio's WYSIWYG "Button" dialog (TinyMCE plugin mce_hbg_buttons).
 *
 * Municipio 6's dialog previews colours from styleguide defaults: it looks for
 * the Kirki inline styles (#kirki_inline_styles), which no longer exist, so the
 * swatches don't match the site's design tokens. It also offers a grey
 * "default" colour editors shouldn't use. This registers a native TinyMCE
 * dialog under the same plugin name, with only the two brand colours (named,
 * with a preview using the stored token colours). Inserted markup matches
 * Municipio's, so existing buttons and frontend styling are unaffected.
 */
class TinyMceButtons {
    private const PLUGIN_SLUG = 'mce_hbg_buttons';

    public function __construct() {
        add_filter('mce_external_plugins', [$this, 'replacePlugin'], 30);
        add_action('admin_head', [$this, 'printConfig']);
    }

    /**
     * @param array<string, string> $plugins
     * @return array<string, string>
     */
    public function replacePlugin($plugins) {
        if (!is_array($plugins) || !isset($plugins[self::PLUGIN_SLUG])) {
            return $plugins;
        }

        $plugins[self::PLUGIN_SLUG] = dirname(plugin_dir_url(__FILE__)) . '/assets/admin/mce-buttons.js?ver=' . Plugin::VERSION;

        return $plugins;
    }

    public function printConfig(): void {
        if (get_user_option('rich_editing') !== 'true') {
            return;
        }

        $config = [
            'colors' => $this->getColors(),
            'i18n' => [
                'toolbar' => __('Knapp', 'municipio-customisation'),
                'tooltip' => __('Lägg till knapp', 'municipio-customisation'),
                'title' => __('Lägg till knapp', 'municipio-customisation'),
                'text' => __('Knapptext', 'municipio-customisation'),
                'href' => __('Länk', 'municipio-customisation'),
                'color' => __('Färg', 'municipio-customisation'),
                'style' => __('Stil', 'municipio-customisation'),
                'filled' => __('Fylld', 'municipio-customisation'),
                'outlined' => __('Kontur', 'municipio-customisation'),
                'size' => __('Storlek', 'municipio-customisation'),
                'sizeMd' => __('Normal', 'municipio-customisation'),
                'sizeLg' => __('Stor', 'municipio-customisation'),
                'sizeSm' => __('Liten', 'municipio-customisation'),
                'newWindow' => __('Öppna länken i nytt fönster', 'municipio-customisation'),
                'preview' => __('Förhandsvisning', 'municipio-customisation'),
                'missing' => __('Fyll i knapptext och länk.', 'municipio-customisation'),
            ],
        ];

        echo '<script>window.alingsasMceButtons = ' . wp_json_encode($config) . ';</script>';
    }

    /**
     * Brand colours as rendered on the frontend: Municipio's button component
     * tokens first, then the global palette tokens, then the brand hex values.
     *
     * @return array<int, array{value: string, label: string, background: string, contrast: string}>
     */
    private function getColors(): array {
        $tokens = $this->getStoredTokens();
        $global = $tokens['token'] ?? [];
        $button = $tokens['component']['__general__']['button'] ?? [];

        $pick = static function (array ...$candidates): string {
            foreach ($candidates as [$source, $key]) {
                if (!empty($source[$key]) && is_string($source[$key])) {
                    return $source[$key];
                }
            }

            return '';
        };

        return [
            [
                'value' => 'primary',
                'label' => __('Mörkgrön', 'municipio-customisation'),
                'background' => $pick([$button, '--c-button--color--primary'], [$global, '--color--primary']) ?: '#236151',
                'contrast' => $pick([$button, '--c-button--color--primary-contrast'], [$global, '--color--primary-contrast']) ?: '#ffffff',
            ],
            [
                'value' => 'secondary',
                'label' => __('Ljusgrön', 'municipio-customisation'),
                'background' => $pick([$button, '--c-button--color--secondary'], [$global, '--color--secondary']) ?: '#cadfde',
                'contrast' => $pick([$button, '--c-button--color--secondary-contrast'], [$global, '--color--secondary-contrast']) ?: '#3c3939',
            ],
        ];
    }

    /**
     * Municipio's design-builder tokens (theme mod "tokens", JSON).
     *
     * @return array<string, mixed>
     */
    private function getStoredTokens(): array {
        $stored = get_theme_mod('tokens');
        if (!is_string($stored) || $stored === '') {
            return [];
        }

        $decoded = json_decode($stored, true);

        return is_array($decoded) ? $decoded : [];
    }
}
