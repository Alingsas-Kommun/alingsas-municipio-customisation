<?php

namespace AlingsasCustomisation\Includes;

class Formats {
    public function __construct() {
        add_filter('Municipio\WpEditor\AvailableFormats', function ($formats) {
            $formats['Alingsås'] = ['preamble' => [
                'title' => 'Preamble',
                'block' => 'p',
                'classes' => 'preamble',
            ]];

            return $formats;
        });
    }
}
