<?php

namespace AlingsasCustomisation\Includes;

use AlingsasCustomisation\Plugin;

class Config {
    public function __construct() {
        // Remove print button
        add_filter('Municipio/Accessibility/Items', function ($items) {
            unset($items['print']);

            return $items;
        });

        // Remove small button class from header buttons
        add_filter('ComponentLibrary/Component/Button/Class', function($classes, $context) {
            if (in_array('component.nav.button', $context)) {
                $classes = array_filter($classes, fn ($class) => $class !== 'c-button--sm');
                $classes[] = 'c-button--md';
            }

            return $classes;
        }, 10, 2);

        // Add template path for posts module
        add_filter('Modularity/Module/posts/TemplatePath', function($paths) {
            $paths[] = Plugin::PATH . '/views/';

            return $paths;
        });

        // Load textdomain
        load_plugin_textdomain('municipio-customisation', false, basename(Plugin::PATH) . '/languages/');
    }
}
