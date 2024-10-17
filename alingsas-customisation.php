<?php
/*
Plugin Name: Alingsås custom Municipio styling
Version: 1.0.0
Author: Consid Borås AB
*/

namespace AlingsasCustomisation;

class Plugin {

    public const VERSION = '1.0.0';

    public const PATH = __DIR__;

    public function __construct() {
        require_once 'components/Event/Event.php';
        
        $includes = array_merge(glob(__DIR__ . '/includes/*.php'), glob(__DIR__ . '/includes/**/*.php'));
        foreach ($includes as $class) {
            require_once $class;

            $path = str_replace(plugin_dir_path(__FILE__) . 'includes/', '', $class);
            $path = explode('/', $path);
            array_pop($path);

            $class_path = array_reduce($path, fn ($carry, $item) => $carry . ucfirst($item) . '\\' , '\\');
            $classname = '\\AlingsasCustomisation\\Includes' . $class_path . ucfirst(pathinfo($class, PATHINFO_FILENAME));
            if (class_exists($classname)) {
                new $classname;
            }
        }
    }
}

new Plugin;
