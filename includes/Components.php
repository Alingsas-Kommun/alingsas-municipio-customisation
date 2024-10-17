<?php

namespace AlingsasCustomisation\Includes;

use AlingsasCustomisation\Plugin;

class Components {
    public function __construct() {
        add_filter('helsingborg-stad/blade/internalComponentsPath', function($paths) {
            $paths[] = Plugin::PATH . '/components/';

            return $paths;
        });

        add_filter('helsingborg-stad/blade/controllerPaths', function($paths) {
            $paths[] = Plugin::PATH . '/components/';

            return $paths;
        });

        add_filter('ComponentLibrary/ViewPaths', function($paths) {
            $paths[] = Plugin::PATH . '/components/';

            return $paths;
        });
    }
}
