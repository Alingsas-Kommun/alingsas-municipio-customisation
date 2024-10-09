<?php
/*
Plugin Name: Alingsås custom Municipio styling
Version: 1.0.0
Author: Consid Borås AB
*/

namespace AlingsasCustomisation;

class Bootstrap {

    private const VERSION = '1.0.0';

    public function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_style']);

        $includes = glob(__DIR__ . '/includes/*.php');
        foreach ($includes as $class) {
            require_once $class;
            
            $classname = '\\AlingsasCustomisation\\Includes\\' . pathinfo($class, PATHINFO_FILENAME);
            if (class_exists($classname)) {
                new $classname;
            }
        }
    }

    public function enqueue_style() {
        wp_enqueue_style('alingsas-style', plugin_dir_url(__FILE__) . 'dist/css/main.css', null, self::VERSION);
    }

}

new Bootstrap;