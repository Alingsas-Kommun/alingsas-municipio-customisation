<?php

namespace AlingsasCustomisation\Includes;

use AlingsasCustomisation\Helpers\Appearance as AppearanceHelper;

class ExtraSettings {
    public const FIELD_BACKGROUND_STRIPE_COLOR = 'field_6718a5f939289';

    private array $custom_anchors = [];

    public function __construct() {
        // General settings
        add_filter('Modularity/Display/BeforeModule::classes', function ($classes, $args, $posttype, $ID) {
            $background_color = get_field('background_stripe_color', $ID);
            $no_top_margin = get_field('no_top_margin', $ID);
            $no_bottom_margin = get_field('no_bottom_margin', $ID);

            if (!empty($background_color) && $background_color !== 'none') {
                $classes[] = 'has-background-stripe';
                $classes[] = 'background-stripe-color-' . $ID;
            }

            if ($no_top_margin) {
                $classes[] = 'no-top-margin';
            }

            if ($no_bottom_margin) {
                $classes[] = 'no-bottom-margin';
            }

            return $classes;
        }, 10, 4);
        
        // Output background stripe styles
        add_filter('Modularity/Display/AfterModule', function ($html, $args, $posttype, $ID) {
            $background_color = get_field('background_stripe_color', $ID);

            if (!empty($background_color) && $background_color !== 'none') {
                $html .= '
                <style>
                .background-stripe-color-' . $ID . '::before {
                    background-color: ' . AppearanceHelper::getColorValue($background_color) . ';
                }
                </style>
                ';
            }

            return $html;
        }, 10, 4);

        // Custom anchors
        add_filter('Modularity/Display/BeforeModule', function ($html, $args, $posttype, $ID) {
            $anchor = get_field('module_id', $ID);

            if (!empty($anchor)) {
                $html .= '<div id="' . esc_attr($anchor) . '">';
                $this->custom_anchors[$ID] = $anchor;
            }

            return $html;
        }, 10, 4);
        add_filter('Modularity/Display/AfterModule', function ($html, $args, $posttype, $ID) {
            if (isset($this->custom_anchors[$ID])) {
                $html .= '</div> <!-- #'. esc_attr($this->custom_anchors[$ID]) .' -->';
            }

            return $html;
        }, 10, 4);

        // Hide breadcrumbs if set on single page
        add_filter('template_redirect', function () {
            if (is_singular() && get_field('hide_breadcrumbs')) {
                add_filter('Municipio/Partials/Navigation/HelperNavBeforeContent', '__return_false');
            }
        });
    }
}
