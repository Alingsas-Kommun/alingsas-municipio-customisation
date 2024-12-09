<?php

namespace AlingsasCustomisation\Includes;

use AlingsasCustomisation\Includes\ExtraSettings;
use AlingsasCustomisation\Helpers\Appearance as AppearanceHelper;

class AppearanceSettings {
    public const FIELD_CUSTOM_COLORS = 'field_673db188c0196';

    public const FIELD_CUSTOM_COLORS_ID = 'field_673dc384fb40f';

    public const FIELD_CUSTOM_COLORS_NAME = 'field_673dc384fb40f';

    public const FIELD_THEMES = 'field_673db58fbaeef';

    public const FIELD_THEME_ID = 'field_673f13830a0c2';

    public const FIELD_THEME_NAME = 'field_673dc12fa4c63';

    public const FIELD_THEME_COLOR = 'field_673db6b8e0e37';

    public const FIELD_PAGE_THEME = 'field_673dd0bbc9739';

    public const FIELD_THEME_PATHS = 'field_67448dbe1ba14';

    public const FIELD_PATH_THEME = 'field_67448ddb1ba16';

    public const FIELD_COLOR = 'alingsas_color';

    public function __construct() {
        // Add options page
        add_action('acf/init', function () {
            acf_add_options_sub_page(array(
                'page_title'  => 'Alingsås',
                'menu_title'  => 'Alingsås',
                'parent_slug' => 'themes.php',
            ));
        });

        // Load available theme colors from custom colors
        add_filter('acf/load_field/key=' . self::FIELD_THEME_COLOR, function ($field) {
            if ($this->isEditingFieldGroup()) {
                return $field;
            }

            $colors = get_field(self::FIELD_CUSTOM_COLORS, 'options');
            foreach ($colors as $color) {
                $field['choices'][$color['id']] = $color['name'];
            }

            return $field;
        });

        // Load available themes for page
        add_filter('acf/load_field/key=' . self::FIELD_PAGE_THEME, function ($field) {
            if ($this->isEditingFieldGroup()) {
                return $field;
            }

            $field['choices']['-'] = __('Standard', 'municipio-customisation');

            $themes = get_field(self::FIELD_THEMES, 'options');
            foreach ($themes as $theme) {
                $field['choices'][$theme['id']] = $theme['name'];
            }

            return $field;
        });

        // Load available themes for path
        add_filter('acf/load_field/key=' . self::FIELD_PATH_THEME, function ($field) {
            if ($this->isEditingFieldGroup()) {
                return $field;
            }

            $themes = get_field(self::FIELD_THEMES, 'options');
            foreach ($themes as $theme) {
                $field['choices'][$theme['id']] = $theme['name'];
            }

            return $field;
        });

        // Add custom colors to background stripe settings
        add_filter('acf/load_field/key=' . ExtraSettings::FIELD_BACKGROUND_STRIPE_COLOR, function ($field) {
            if ($this->isEditingFieldGroup()) {
                return $field;
            }

            $field['choices']['page-theme'] = __('According to theme', 'municipio-customisation');

            $themes = get_field(self::FIELD_THEMES, 'options');
            foreach ($themes as $theme) {
                $field['choices']['theme-' . $theme['id']] = sprintf(__('Theme: %s', 'municipio-customisation'), $theme['name']);
            }

            return $field;
        });

        // Add colors to theme component color selector
        add_filter('acf/load_field/name=' . self::FIELD_COLOR, function ($field) {
            if ($this->isEditingFieldGroup()) {
                return $field;
            }

            $field['choices']['-'] = __('Standard', 'municipio-customisation');

            $colors = get_field(self::FIELD_CUSTOM_COLORS, 'options');
            foreach ($colors as $color) {
                $field['choices']['color-' . $color['id']] = $color['name'];
            }

            $field['choices']['page-theme'] = __('Theme color', 'municipio-customisation');
            $field['choices']['custom'] = __('Custom color', 'municipio-customisation');

            return $field;
        });

        // Hide color ID field from admin
        add_filter('acf/load_field/key=' . self::FIELD_CUSTOM_COLORS_ID, [$this, 'hideField']);

        // Hide theme ID field from admin
        add_filter('acf/load_field/key=' . self::FIELD_THEME_ID, [$this, 'hideField']);

        // Hide title for color settings clone field
        add_filter('acf/load_field/key=field_6751b8156d755', function($field) {
            if (!is_admin()) {
                return $field;
            }

            $screen = get_current_screen();
            if ($screen->id === 'acf-field-group') {
                return $field;
            }

            $field['label'] = '';

            return $field;
        });

        // Give each custom color a unique ID
        add_filter('acf/save_post', function ($post_id) {
            $screen = get_current_screen();

            if ($screen->id !== 'appearance_page_acf-options-alingsas') {
                return;
            }

            if (!isset($_POST['acf'][self::FIELD_CUSTOM_COLORS])) {
                return;
            }

            foreach ($_POST['acf'][self::FIELD_CUSTOM_COLORS] as &$custom_color) {
                if (empty($custom_color[self::FIELD_CUSTOM_COLORS_ID])) {
                    $custom_color[self::FIELD_CUSTOM_COLORS_ID] = $this->generateUniqueId($custom_color[self::FIELD_CUSTOM_COLORS_NAME]);
                }
            }
        }, 5);

        // Give each theme a unique ID
        add_filter('acf/save_post', function ($post_id) {
            $screen = get_current_screen();

            if ($screen->id !== 'appearance_page_acf-options-alingsas') {
                return;
            }

            if (!isset($_POST['acf'][self::FIELD_THEMES])) {
                return;
            }

            foreach ($_POST['acf'][self::FIELD_THEMES] as &$custom_color) {
                if (empty($custom_color[self::FIELD_THEME_ID])) {
                    $custom_color[self::FIELD_THEME_ID] = $this->generateUniqueId($custom_color[self::FIELD_THEME_NAME]);
                }
            }
        }, 5);

        // Output CSS variables of colors in header
        add_action('wp_head', function () {
            $css_vars = '';

            $colors = get_field(self::FIELD_CUSTOM_COLORS, 'options');
            $themes = get_field(self::FIELD_THEMES, 'options');

            foreach ($colors as $color) {
                $css_vars .= "--alingsas-color-{$color['id']}: {$color['color']};";
            }

            foreach ($themes as $theme) {
                $css_vars .= "--alingsas-theme-{$theme['id']}: var(--alingsas-color-{$theme['theme_color']});";
            }

            echo '<style>:root {' . $css_vars . '}</style>';
        });

        // Output theme colors if page or URL has it
        add_action('wp_head', function () {
            $theme = null;
            $css_vars = '';

            $theme = get_field(self::FIELD_PAGE_THEME);
            if (empty($theme) || $theme === '-') {
                $theme = AppearanceHelper::getThemeForUrl($_SERVER['REQUEST_URI']);
            }

            if (!empty($theme) && $theme !== '-') {
                $css_vars .= AppearanceHelper::getThemeVar($theme);
                $css_vars .= AppearanceHelper::getThemeColorVars($theme);
            }

            if (!empty($css_vars)) {
                echo '<style>:root {' . $css_vars . '}</style>';
            }
        });
    }

    public function hideField($field) {
        if ($this->isEditingFieldGroup()) {
            return $field;
        }

        $field['wrapper']['class'] = 'hidden';

        return $field;
    }

    private function isEditingFieldGroup() {
        if (is_admin() && function_exists('get_current_screen') && get_current_screen()->id === 'acf-field-group') {
            return true;
        }

        return false;
    }

    private function generateUniqueId($base_string = '') {
        return md5($base_string . '_' . uniqid());
    }
}
