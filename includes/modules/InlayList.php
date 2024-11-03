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
                    $gray_background = get_field('gray_background', $ID);
                    if ($gray_background === true) {
                        $classes[] = 'modularity-mod-inlaylist--gray-background';
                    }
                }
            }

            return $classes;
        }, 10, 4);
    }
}
