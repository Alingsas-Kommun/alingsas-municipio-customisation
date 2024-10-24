<?php

namespace AlingsasCustomisation\Includes;

class ACF {
    public function __construct() {
        add_action('acf/init', [$this, 'custom_location_types']);
    }

    public function custom_location_types() {
        if(function_exists('acf_register_location_type')) {
            acf_register_location_type('AlingsasCustomisation\Includes\ACF\Modularity_Location');
        }
    }
}
