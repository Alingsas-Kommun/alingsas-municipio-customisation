<?php

namespace AlingsasCustomisation\Includes;

use AlingsasCustomisation\Plugin;

class Widgets {
    public function __construct() {
        /* Site banner */
        register_sidebar(array(
            'id'            => 'header-area-site-banner',
            'name'          => __('Site banner (top of page)', 'municipio-customisation'),
            'description'   => __('The area above the header', 'municipio-customisation'),
            'before_title'  => '<h2>',
            'after_title'   => '</h2>',
            'before_widget' => '<div id="%1$s" class="%2$s">',
            'after_widget'  => '</div>',
        ));
        
        // Add generic template path for Municipio
        // TODO: Remove after new widget area has been added
        add_filter('Municipio/viewPaths', function($paths) {
            $paths[] = Plugin::PATH . '/views/';

            return $paths;
        });
    }
}
