<?php

namespace AlingsasCustomisation\Includes\Modules;

use AlingsasCustomisation\Plugin;

class Recommend {
    public function __construct() {
        // Remove small button class from template buttons
        add_filter('ComponentLibrary/Component/Button/Class', function($classes, $context) {
            if (in_array('module.recommend.button', $context)) {
                $classes = array_filter($classes, fn ($class) => $class !== 'c-button--sm');
                $classes[] = 'c-button--md';
            }

            return $classes;
        }, 10, 2);
    }
}
