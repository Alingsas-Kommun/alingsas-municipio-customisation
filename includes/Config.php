<?php

namespace AlingsasCustomisation\Includes;

class Config {
    public function __construct() {
        // Remove print button
        add_filter('Municipio/Accessibility/Items', function ($items) {
            unset($items['print']);

            return $items;
        });
    }
}
