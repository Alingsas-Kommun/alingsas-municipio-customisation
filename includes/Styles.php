<?php

namespace AlingsasCustomisation\Includes;

use AlingsasCustomisation\Plugin;

class Styles {
    public function __construct() {
        add_action('wp_enqueue_scripts', function () {
            $manifest_path = dirname(plugin_dir_path(__FILE__)) . '/dist/css/manifest.json';
            if (file_exists($manifest_path)) {
                $manifest = json_decode(file_get_contents($manifest_path));
                wp_enqueue_style('alingsas-style', dirname(plugin_dir_url(__FILE__)) . '/dist/css/' . $manifest->{'main.css'}, null, Plugin::VERSION);
            } else {
                wp_enqueue_style('alingsas-style', dirname(plugin_dir_url(__FILE__)) . '/dist/css/main.css', null, Plugin::VERSION);
            }
        });
    }
}
