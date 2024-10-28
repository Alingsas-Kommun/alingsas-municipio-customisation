<?php

namespace AlingsasCustomisation\Includes;

class ExtraSettings {
    private const modulesWithBackgroundSettings = ['mod-text'];

    private const modulesWithCardSettings = ['mod-manualinput'];

    public function __construct() {
        // General settings
        add_filter('Modularity/Display/BeforeModule::classes', function ($classes, $args, $posttype, $ID) {
                $background_color = get_field('background_stripe_color', $ID);
                $no_top_margin = get_field('no_top_margin', $ID);
                $no_bottom_margin = get_field('no_bottom_margin', $ID);

                if (!empty($background_color) && $background_color !== 'none') {
                    $classes[] = 'has-background-stripe';
                    $classes[] = 'background-stripe-color-' . $background_color;
                }

                if ($no_top_margin) {
                    $classes[] = 'no-top-margin';
                }

                if ($no_bottom_margin) {
                    $classes[] = 'no-bottom-margin';
                }

            return $classes;
        }, 10, 4);

        // General
        add_filter('Modularity/Display/BeforeModule::classes', function ($classes, $args, $posttype, $ID) {
            if (in_array($posttype, self::modulesWithBackgroundSettings)) {
                $background_color = get_field('background_color', $ID);

                if (!empty($background_color) && $background_color !== 'standard') {
                    $classes[] = 'background-color-' . $background_color;
                }
            }

            return $classes;
        }, 10, 4);

        // Card
        add_filter('Modularity/Display/BeforeModule::classes', function ($classes, $args, $posttype, $ID) {
            if (in_array($posttype, self::modulesWithCardSettings)) {
                $card_header_color = get_field('card_head_color', $ID);

                if (!empty($card_header_color) && $card_header_color !== 'standard') {
                    $classes[] = 'card__header--bg-color-' . $card_header_color;
                    $classes[] = 'card__header---color-white';
                }
            }

            return $classes;
        }, 10, 4);
    }
}
