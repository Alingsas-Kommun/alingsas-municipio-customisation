<?php
namespace AlingsasCustomisation\Includes\Components;

class Button {
    public function __construct() {
        add_filter('ComponentLibrary/Component/Button/Class', function($classes, $context) {
            if (in_array('module.recommend.button', $context)) {
                $classes[] = 'alingsas-recommended';
            }

            return $classes;
        }, 10, 2);
    }
}