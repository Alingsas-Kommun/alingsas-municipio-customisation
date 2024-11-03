<?php

namespace AlingsasCustomisation\Includes;

use AlingsasCustomisation\Plugin;

class Scripts {
    public function __construct() {
        add_action('wp_enqueue_scripts', function () {
            $manifest_path = dirname(plugin_dir_path(__FILE__)) . '/dist/js/manifest.json';
            if (file_exists($manifest_path)) {
                $manifest = json_decode(file_get_contents($manifest_path));
                wp_enqueue_script('alingsas-script', dirname(plugin_dir_url(__FILE__)) . '/dist/js/' . $manifest->{'main.js'}, ['jquery'], Plugin::VERSION, ['in_footer' => true]);
            } else {
                wp_enqueue_script('alingsas-script', dirname(plugin_dir_url(__FILE__)) . '/dist/js/main.js', ['jquery'], Plugin::VERSION, ['in_footer' => true]);
            }
        });
    }
}
