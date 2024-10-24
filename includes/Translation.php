<?php

namespace AlingsasCustomisation\Includes;

class Translation {
    public const transMapper = [
        'Home' => 'Hem',
    ];

    public function __construct() {
        add_filter("gettext_municipio", function($translation, $text) {
            if (isset(self::transMapper[$text])) {
                $translation = self::transMapper[$text];
            }

            return $translation;
        }, 10, 2);
    }
}