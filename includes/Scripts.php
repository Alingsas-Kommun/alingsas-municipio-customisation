<?php

namespace AlingsasCustomisation\Includes;

use AlingsasCustomisation\Plugin;

class Scripts {
    public function __construct() {
        add_action('wp_enqueue_scripts', function () {
            $manifest_path = dirname(plugin_dir_path(__FILE__)) . '/dist/.vite/manifest.json';
            if (file_exists($manifest_path)) {
                $manifest = json_decode(file_get_contents($manifest_path));
                wp_enqueue_script('alingsas-script', dirname(plugin_dir_url(__FILE__)) . '/dist/' . $manifest->{'src/js/main.js'}->file, ['jquery'], Plugin::VERSION, ['in_footer' => true]);
            }
        });

        // Vite development
        if (wp_get_environment_type() === 'development') {
            add_action('wp_head', function () {
                echo '<script type="module" src="https://localhost:5173/@vite/client"></script>';
                echo '<script type="module" src="https://localhost:5173/src/js/main.js"></script>';
            });
        }
    }
}
