<?php

namespace AlingsasCustomisation\Includes;

use AlingsasCustomisation\Plugin;

class Styles {
    public function __construct() {
        add_action('wp_enqueue_scripts', function () {
            $manifest_path = dirname(plugin_dir_path(__FILE__)) . '/dist/.vite/manifest.json';
            if (file_exists($manifest_path)) {
                $manifest = json_decode(file_get_contents($manifest_path));
                wp_enqueue_style('alingsas-style', dirname(plugin_dir_url(__FILE__)) . '/dist/' . $manifest->{'src/scss/main.scss'}->file, null, Plugin::VERSION);
            }
        });

        add_action('admin_enqueue_scripts', function () {
            $admin_css = dirname(plugin_dir_url(__FILE__)) . '/src/css/admin.css';
            wp_enqueue_style('alingsas-admin-style', $admin_css, [], filemtime($admin_css));
        });
    }
}
