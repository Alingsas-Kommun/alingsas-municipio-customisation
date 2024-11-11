<?php

namespace AlingsasCustomisation\Includes;

use AlingsasCustomisation\Plugin;

class Components {
    public function __construct() {
        // Add support for additional components path
        add_filter('helsingborg-stad/blade/internalComponentsPath', function($paths) {
            $paths[] = Plugin::PATH . '/components/';

            return $paths;
        });

        // Add support for additional components controller path
        add_filter('helsingborg-stad/blade/controllerPaths', function($paths) {
            $paths[] = Plugin::PATH . '/components/';

            return $paths;
        });

        // Add support for additional components' views path
        add_filter('ComponentLibrary/ViewPaths', function($paths) {
            $paths[] = Plugin::PATH . '/components/';
            $paths[] = Plugin::PATH . '/views/';

            return $paths;
        });
    }
}
