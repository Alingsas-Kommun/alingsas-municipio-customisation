<?php

namespace AlingsasCustomisation\Helpers;

use AlingsasCustomisation\Includes\AppearanceSettings;

class Appearance {
    public static function getColorValue($id) {
        // Is Municipio palette color
        if (strpos($id, 'municipio-') !== false) {
            return 'var(--color-' . str_replace('municipio-', '', $id) . ')';
        }

        // Is theme color
        if (strpos($id, 'theme-') !== false) {
            return 'var(--alingsas-' . $id . ')';
        }

        // Is theme color
        if (strpos($id, 'color-') !== false) {
            return 'var(--alingsas-' . $id . ')';
        }

        // Is standard color
        if (empty($id) || $id === '-') {
            return false;
        }

        // Is hex color
        if (strpos($id, '#') === 0) {
            return $id;
        }

        return 'var(--color-' . $id . ')';
    }

    public static function getThemeColorVars($theme_id) {
        $theme = self::getThemeSettings($theme_id);

        $vars = '';

        foreach ($theme as $key => $settings) {
            if (strpos($key, 'var_') === false) {
                continue;
            }

            $var_name = str_replace(['var_', '_'], ['', '-'], $key);
            $var_value = $settings['alingsas_color'] !== 'custom'
                ? self::getColorValue($settings['alingsas_color'])
                : $settings['custom_color'];

            if (!empty($var_value)) {
                $vars .= "--theme-{$var_name}: {$var_value};";
            }
        }

        return $vars;
    }

    public static function getThemeSettings($theme_id) {
        $themes = self::getThemes();

        foreach ($themes as $theme) {
            if ($theme['id'] === $theme_id) {
                return $theme;
            }
        }

        return false;
    }

    public static function getThemes() {
        return get_field(AppearanceSettings::FIELD_THEMES, 'options');
    }
}