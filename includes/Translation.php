<?php

namespace AlingsasCustomisation\Includes;

class Translation {
    public const transMapper = [
        'Home' => 'Hem',
    ];

    public function __construct() {
        // Translation mapper
        add_filter("gettext_municipio", function ($translation, $text) {
            if (isset(self::transMapper[$text])) {
                $translation = self::transMapper[$text];
            }

            return $translation;
        }, 10, 2);

        // Specific translation overrides
        add_filter("gettext_municipio", function ($translation, $text) {
            if (is_post_type_archive('event')) {
                if ($text === 'Choose a from date') {
                    $translation = __('From', 'municipio-customisation');
                }

                if ($text === 'Choose a to date') {
                    $translation = __('To', 'municipio-customisation');
                }
            }

            if (is_post_type_archive('lediga-jobb')) {
                if ($text === 'Title') {
                    $translation = __('Service', 'municipio-customisation');
                }
            }

            return $translation;
        }, 10, 2);
    }
}
