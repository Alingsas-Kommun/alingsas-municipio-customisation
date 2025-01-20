<?php

namespace AlingsasCustomisation\Includes\Modules;

class InlayList {
    public function __construct() {
        add_filter('Modularity/Display/BeforeModule::classes', function ($classes, $args, $posttype, $ID) {
            if ($posttype === 'mod-inlaylist') {
                $appearance = get_field('appearance', $ID);

                if (!empty($appearance)) {
                    $classes[] = 'modularity-mod-inlaylist--appearance-' . $appearance;
                }

                if (!empty($appearance) && $appearance === 'buttons') {
                    $background_color = get_field('background_color', $ID);
                    if ($background_color) {
                        $classes[] = 'modularity-mod-inlaylist--background-color';
                    }
                }
            }

            return $classes;
        }, 10, 4);
    }
}
