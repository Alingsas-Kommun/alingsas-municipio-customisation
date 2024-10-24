<?php

namespace AlingsasCustomisation\Includes\ACF;

use ACF_Location;

class Modularity_Location extends ACF_Location {
    public function initialize() {
        $this->name = 'modularity_module';
        $this->label = 'Modularity-modul';
        $this->category = 'Municipio';
        $this->object_type = 'post';
    }

    public function get_values( $rule ) {
        $choices = [
            'yes' => 'Ja',
            'no' => 'Nej',
        ];

        return $choices;
    }

    public function match( $rule, $screen, $field_group ) {
        $result = strpos($screen['post_type'], 'mod-') === 0;
    
        if( $rule['operator'] === '!=' ) {
            return !$result;
        }
        return $result;
    }
}