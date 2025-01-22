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

        // Hide regular posts from admin menu
        add_action('admin_menu', function () {
            remove_menu_page('edit.php'); // Hides the "Posts" menu
        });

        // No HTML tags in news post object
        add_filter('Municipio/Helper/Post/postObject', function($postObject) {
            if ($postObject instanceof \WP_Post && $postObject->post_type === 'nyheter') {
                $postObject->post_excerpt = strip_tags($postObject->post_excerpt);

                if (isset($postObject->excerpt)) {
                    $postObject->excerpt = strip_tags($postObject->excerpt);
                }
                if (isset($postObject->excerpt_short)) {
                    $postObject->excerpt_short = strip_tags($postObject->excerpt_short);
                }
                if (isset($postObject->excerpt_shorter)) {
                    $postObject->excerpt_shorter = strip_tags($postObject->excerpt_shorter);
                }
            }
            
            return $postObject;
        });
    }
}
