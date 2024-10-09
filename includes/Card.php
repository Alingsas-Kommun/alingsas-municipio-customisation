<?php
namespace AlingsasCustomisation\Includes;

class Card {
    public function __construct() {
        add_filter('ComponentLibrary/Component/Card/Class', function($classes, $context) {
            if (in_array('module.manual-input.accordion', $context)) {
                $classes[] = 'alingsas-faq';
            }

            return $classes;
        }, 10, 2);

        add_filter('ComponentLibrary/Component/Card/Class', function($classes, $context) {
            if (in_array('module.inlay.list', $context)) {
                $classes[] = 'alingsas-links';
            }

            if (in_array('module.files.list', $context)) {
                $classes[] = 'alingsas-attachments';
            }

            return $classes;
        }, 10, 2);
    }
}