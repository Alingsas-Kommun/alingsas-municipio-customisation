<?php

namespace AlingsasCustomisation\Includes;

use AlingsasCustomisation\Plugin;

class TemplateOverrides {
    public function __construct() {
        add_filter('Municipio/viewPaths', function ($paths) {
            $paths[] = Plugin::PATH . '/views/';

            if (is_post_type_archive('event') || is_singular('event')) {
                $paths[] = Plugin::PATH . '/views/Events/';
            }

            return $paths;
        }, 100, 1);

        add_filter('Modularity/Module/TemplatePath', function ($paths) {
            $paths[] = Plugin::PATH . '/views/';

            return $paths;
        }, 5, 1);
    }
}
