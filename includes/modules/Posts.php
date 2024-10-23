<?php

namespace AlingsasCustomisation\Includes\Modules;

use AlingsasCustomisation\Plugin;

class Posts {
    public function __construct() {
        // Extra classes
        add_filter('Modularity/Display/BeforeModule::classes', function ($classes, $args, $posttype, $ID) {
            if ($posttype === 'mod-posts') {
                $background_color = get_field('background_color', $ID);

                if (!empty($background_color) && $background_color !== 'none') {
                    $overlap = get_field('overlap_top_bottom', $ID);
                    $tilted_bottom = get_field('tilted_bottom', $ID);

                    $classes[] = 'modularity-mod--has-background-color';
                    $classes[] = 'modularity-mod--background-color-' . $background_color;

                    if ($overlap) {
                        $classes[] = 'modularity-mod--background-overlap';
                    }

                    if ($tilted_bottom) {
                        $classes[] = 'modularity-mod--background-tilted';
                    }
                }
            }

            return $classes;
        }, 10, 4);

        // Add option to display post as events calendar
        add_action('acf/load_field/name=posts_display_as', function ($field) {
            if (class_exists('Tribe__Events__Main')) {
                $field['choices']['tribe-events'] = 'Events Calendar';
            }

            return $field;
        });

        // Use events template to display tribe events
        add_filter('Modularity/Module/Posts/template', function($template, $class, $data, $fields) {
            if (isset($fields['posts_display_as']) && $fields['posts_display_as'] === 'tribe-events') {
                $template = 'events.blade.php';
            }

            return $template;
        }, 10, 4);

        // Search for templates in views path (for Municipio)
        add_filter('Modularity/Module/posts/TemplatePath', function($paths) {
            $paths[] = Plugin::PATH . '/views/';

            return $paths;
        });

        // Search for views in views path (for blade renderer)
        add_filter('ComponentLibrary/ViewPaths', function($paths) {
            $paths[] = Plugin::PATH . '/views/';

            return $paths;
        });
    }
}
