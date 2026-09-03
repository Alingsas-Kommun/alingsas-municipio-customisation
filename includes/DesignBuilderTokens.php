<?php

namespace AlingsasCustomisation\Includes;

/**
 * Keep Styleguide v3 Design Builder tokens readable and save-safe.
 *
 * Municipio 7 emits theme_mod('tokens') in @layer theme. A Kirki-era import
 * left --color--primary and --color--primary-contrast on the same hex, which
 * makes body chrome look unstyled. V41 also stores some component tokens as
 * JSON numbers; Design Builder drops non-strings on first save. Repair at
 * runtime and persist.
 */
class DesignBuilderTokens
{
    private const OPTION_REPAIRED = 'alingsas_design_builder_tokens_repaired';

    public function __construct()
    {
        add_action('init', [$this, 'maybePersistRepairedTokens'], 6);
        add_filter('theme_mod_tokens', [$this, 'filterThemeModTokens']);
        add_filter('Municipio/Styleguide/Customize/OverrideState', [$this, 'filterOverrideState']);
    }

    /**
     * Persist a contrast-safe, stringified copy so Design Builder matches the front end.
     */
    public function maybePersistRepairedTokens(): void
    {
        $decoded = $this->decodeTokens($this->getUnfilteredTokensRaw());
        $repaired = $this->repairTokens($decoded);

        if ($repaired === $decoded) {
            update_option(self::OPTION_REPAIRED, '1', false);
            return;
        }

        set_theme_mod('tokens', wp_json_encode($repaired));
        update_option(self::OPTION_REPAIRED, '1', false);
    }

    /**
     * @param mixed $value Raw theme_mod JSON.
     */
    public function filterThemeModTokens(mixed $value): string
    {
        $decoded = $this->decodeTokens($value);
        $repaired = $this->repairTokens($decoded);

        return wp_json_encode($repaired) ?: '{"token":{},"component":{}}';
    }

    /**
     * Design Builder reads OverrideState after json_decode; stringify numbers here too.
     *
     * @param mixed $stored
     * @return array{token: array<string, mixed>, component: array<string, mixed>}
     */
    public function filterOverrideState(mixed $stored): array
    {
        return $this->repairTokens($this->decodeTokens($stored));
    }

    /**
     * @param mixed $value
     * @return array{token: array<string, mixed>, component: array<string, mixed>}
     */
    private function decodeTokens(mixed $value): array
    {
        $empty = ['token' => [], 'component' => []];

        if (is_array($value)) {
            return [
                'token' => is_array($value['token'] ?? null) ? $value['token'] : [],
                'component' => is_array($value['component'] ?? null) ? $value['component'] : [],
            ];
        }

        if (!is_string($value) || $value === '') {
            return $empty;
        }

        $decoded = json_decode($value, true);
        if (!is_array($decoded)) {
            return $empty;
        }

        return [
            'token' => is_array($decoded['token'] ?? null) ? $decoded['token'] : [],
            'component' => is_array($decoded['component'] ?? null) ? $decoded['component'] : [],
        ];
    }

    /**
     * @param array{token: array<string, mixed>, component: array<string, mixed>} $tokens
     * @return array{token: array<string, mixed>, component: array<string, mixed>}
     */
    private function repairTokens(array $tokens): array
    {
        $pairs = [
            '--color--primary' => '--color--primary-contrast',
            '--color--secondary' => '--color--secondary-contrast',
            '--color--background' => '--color--background-contrast',
        ];

        foreach ($pairs as $colorKey => $contrastKey) {
            $color = $tokens['token'][$colorKey] ?? null;
            if (!is_string($color) || $color === '') {
                continue;
            }

            $contrast = $tokens['token'][$contrastKey] ?? null;
            if (!is_string($contrast) || $this->normalizeHex($color) === $this->normalizeHex($contrast)) {
                $tokens['token'][$contrastKey] = $this->contrastColor($color);
            }
        }

        $tokens['token'] = $this->stringifyNumericValues($tokens['token']);
        $tokens['component'] = $this->stringifyNumericValues($tokens['component']);
        $tokens = $this->ensurePillButtonRadius($tokens);

        return $tokens;
    }

    /**
     * Design Builder has no button-shape control and omits this token from the
     * save payload. Re-apply the migrated pill radius (Kirki button_shape).
     *
     * @param array{token: array<string, mixed>, component: array<string, mixed>} $tokens
     * @return array{token: array<string, mixed>, component: array<string, mixed>}
     */
    private function ensurePillButtonRadius(array $tokens): array
    {
        if (!isset($tokens['component']['__general__']) || !is_array($tokens['component']['__general__'])) {
            $tokens['component']['__general__'] = [];
        }

        if (!isset($tokens['component']['__general__']['button']) || !is_array($tokens['component']['__general__']['button'])) {
            $tokens['component']['__general__']['button'] = [];
        }

        $tokens['component']['__general__']['button']['--c-button--border-radius'] = '4';

        return $tokens;
    }

    /**
     * Styleguide Design Builder keeps only string overrides. V41 stores pill radius
     * and several other tokens as JSON numbers, which are dropped on first save.
     *
     * @param array<string, mixed> $values
     * @return array<string, mixed>
     */
    private function stringifyNumericValues(array $values): array
    {
        foreach ($values as $key => $value) {
            if (is_array($value)) {
                $values[$key] = $this->stringifyNumericValues($value);
                continue;
            }

            if (is_int($value) || is_float($value)) {
                $values[$key] = (string) $value;
            }
        }

        return $values;
    }

    /**
     * Raw theme_mod JSON, bypassing theme_mod_tokens so persist can see numbers.
     */
    private function getUnfilteredTokensRaw(): mixed
    {
        $mods = get_option('theme_mods_' . get_stylesheet());

        return is_array($mods) ? ($mods['tokens'] ?? '') : '';
    }

    private function normalizeHex(string $value): string
    {
        $value = strtolower(trim($value));
        if (!str_starts_with($value, '#')) {
            return $value;
        }

        $hex = ltrim($value, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        return '#' . $hex;
    }

    private function contrastColor(string $hex): string
    {
        $normalized = $this->normalizeHex($hex);
        if (!str_starts_with($normalized, '#') || strlen($normalized) !== 7) {
            return '#ffffff';
        }

        $r = hexdec(substr($normalized, 1, 2)) / 255;
        $g = hexdec(substr($normalized, 3, 2)) / 255;
        $b = hexdec(substr($normalized, 5, 2)) / 255;
        $linear = static function (float $channel): float {
            return $channel <= 0.03928 ? $channel / 12.92 : (($channel + 0.055) / 1.055) ** 2.4;
        };
        $luminance = 0.2126 * $linear($r) + 0.7152 * $linear($g) + 0.0722 * $linear($b);

        return $luminance > 0.4 ? '#000000' : '#ffffff';
    }
}
