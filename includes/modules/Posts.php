<?php

namespace AlingsasCustomisation\Includes\Modules;

use AlingsasCustomisation\Plugin;

class Posts {
    public function __construct() {
        add_action('acf/load_field/name=posts_display_as', function ($field) {
            if (class_exists('Tribe__Events__Main')) {
                $field['choices']['tribe-events'] = 'Events Calendar';
            }

            return $field;
        });

        add_filter('Modularity/Module/Posts/template', function($template, $class, $data, $fields) {
            if (isset($fields['posts_display_as']) && $fields['posts_display_as'] === 'tribe-events') {
                $template = 'events.blade.php';
            }

            return $template;
        }, 10, 4);

        add_filter('Modularity/Module/posts/TemplatePath', function($paths) {
            $paths[] = Plugin::PATH . '/views/';

            return $paths;
        });

        add_filter('ComponentLibrary/ViewPaths', function($paths) {
            $paths[] = Plugin::PATH . '/views/';

            return $paths;
        });
    }
}
