<?php

namespace AlingsasCustomisation\Includes\Modules;

class Posts {
    /**
     * Legacy Modularity display slugs and the template they should resolve to.
     *
     * @var array<string, string>
     */
    private const DEPRECATED_DISPLAY_AS = [
        'items' => 'index',
    ];

    public function __construct() {
        add_filter('acf/load_value/name=posts_display_as', [$this, 'replaceDeprecatedDisplayAs'], 10, 3);
    }

    /**
     * Map legacy `posts_display_as` values to templates Modularity 7 accepts.
     *
     * Modularity 7 validates the display slug before applying its own deprecated-template
     * mapping, so modules still storing `items` (Municipio 6 era) fall back to List instead
     * of Card. Rewriting the loaded value keeps the production Card rendering and makes the
     * editor show the stored value as Card.
     *
     * @param mixed      $value  Stored field value
     * @param int|string $postId Module post ID
     * @param array      $field  ACF field definition
     * @return mixed
     */
    public function replaceDeprecatedDisplayAs($value, $postId, array $field) {
        if (!is_string($value)) {
            return $value;
        }

        return self::DEPRECATED_DISPLAY_AS[$value] ?? $value;
    }
}
