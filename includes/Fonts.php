<?php

namespace AlingsasCustomisation\Includes;

/**
 * Print native WordPress font-library faces on the front end.
 *
 * Municipio 7 stores Inter in wp_font_family / wp_font_face and Design Builder
 * sets --font-family-base, but WP_Font_Face_Resolver does not emit @font-face
 * because those families never merge into theme.json. This adapter prints the
 * already-installed library fonts (no second catalogue).
 */
class Fonts
{
    public function __construct()
    {
        add_action('wp_head', [$this, 'printFontFaces'], 5);
    }

    /**
     * Print @font-face rules for Design Builder families that exist in the library.
     */
    public function printFontFaces(): void
    {
        if (!function_exists('wp_print_font_faces')) {
            return;
        }

        $fonts = $this->getFontsForPrint();
        if ($fonts === []) {
            return;
        }

        wp_print_font_faces($fonts);
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    private function getFontsForPrint(): array
    {
        $wanted = $this->getWantedFamilyNames();
        $families = get_posts([
            'post_type' => 'wp_font_family',
            'post_status' => 'publish',
            'numberposts' => -1,
        ]);

        $fonts = [];

        foreach ($families as $familyPost) {
            $familyData = json_decode((string) $familyPost->post_content, true);
            $familyName = is_array($familyData) && !empty($familyData['fontFamily'])
                ? (string) $familyData['fontFamily']
                : $familyPost->post_title;
            $normalized = $this->normalizeFamilyName($familyName);

            if ($wanted !== [] && !in_array($normalized, $wanted, true)) {
                continue;
            }

            $faces = get_posts([
                'post_type' => 'wp_font_face',
                'post_status' => 'publish',
                'post_parent' => $familyPost->ID,
                'numberposts' => -1,
            ]);

            $prepared = [];
            foreach ($faces as $facePost) {
                $face = $this->prepareFontFace(json_decode((string) $facePost->post_content, true), $familyName);
                if ($face !== null && $this->isUsefulVariant($face)) {
                    $prepared[] = $face;
                }
            }

            if ($prepared !== []) {
                $fonts[$familyName] = $prepared;
            }
        }

        return $fonts;
    }

    /**
     * @return list<string>
     */
    private function getWantedFamilyNames(): array
    {
        $raw = get_theme_mod('tokens', '');
        $decoded = is_string($raw) ? json_decode($raw, true) : [];
        $tokens = is_array($decoded['token'] ?? null) ? $decoded['token'] : [];
        $wanted = [];

        foreach (['--font-family-base', '--font-family-heading'] as $key) {
            if (!empty($tokens[$key]) && is_string($tokens[$key])) {
                $wanted[] = $this->normalizeFamilyName($tokens[$key]);
            }
        }

        return array_values(array_unique(array_filter($wanted)));
    }

    /**
     * @param mixed $data
     * @return array<string, mixed>|null
     */
    private function prepareFontFace(mixed $data, string $familyName): ?array
    {
        if (!is_array($data)) {
            return null;
        }

        $src = $data['src'] ?? [];
        if (is_string($src)) {
            $src = [$src];
        }
        if (!is_array($src) || $src === []) {
            return null;
        }

        $localized = [];
        foreach ($src as $url) {
            if (!is_string($url) || $url === '') {
                continue;
            }
            $localized[] = $this->localizeSrc($url);
        }

        if ($localized === []) {
            return null;
        }

        $cssFamily = trim(explode(',', $familyName)[0], " \t\n\r\0\x0B\"'");

        return [
            'font-family' => $cssFamily !== '' ? $cssFamily : 'Inter',
            'font-style' => (string) ($data['fontStyle'] ?? $data['font-style'] ?? 'normal'),
            'font-weight' => (string) ($data['fontWeight'] ?? $data['font-weight'] ?? '400'),
            'font-display' => (string) ($data['fontDisplay'] ?? $data['font-display'] ?? 'swap'),
            'src' => $localized,
        ];
    }

    private function localizeSrc(string $src): string
    {
        $filename = basename(wp_parse_url($src, PHP_URL_PATH) ?: $src);
        if ($filename === '') {
            return $src;
        }

        $candidates = [
            WP_CONTENT_DIR . '/uploads/fonts/' . $filename,
            WP_CONTENT_DIR . '/uploads/networks/1/fonts/' . $filename,
        ];

        foreach ($candidates as $path) {
            if (!is_file($path)) {
                continue;
            }

            $publicDir = WP_CONTENT_DIR . '/fonts/library';
            if (!is_dir($publicDir) && !wp_mkdir_p($publicDir)) {
                return $src;
            }

            $publicPath = $publicDir . '/' . $filename;
            if (!is_file($publicPath) && !copy($path, $publicPath)) {
                return $src;
            }

            return '/wp-content/fonts/library/' . $filename;
        }

        return $src;
    }

    /**
     * @param array<string, mixed> $face
     */
    private function isUsefulVariant(array $face): bool
    {
        $weight = preg_replace('/[^0-9]/', '', (string) $face['font-weight']) ?: '400';

        return in_array($weight, ['400', '500', '600', '700'], true);
    }

    private function normalizeFamilyName(string $value): string
    {
        $first = trim(explode(',', $value)[0]);
        return strtolower(trim($first, " \t\n\r\0\x0B\"'"));
    }
}
