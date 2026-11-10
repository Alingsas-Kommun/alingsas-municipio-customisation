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
                $classes[] = 'background-stripe-color-' . sanitize_html_class((string) $ID);
                $classes[] = 'background-stripe-color-' . sanitize_html_class((string) $background_color);
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
            return $html . self::backgroundStripeStyle($ID);
        }, 10, 4);

        // Custom anchors
        add_filter('Modularity/Display/BeforeModule', function ($html, $args, $posttype, $ID) {
            $anchor = self::anchor($ID);

            if ($anchor !== '') {
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

    /**
     * Inline style painting the background stripe for one module. The named
     * palette colours have static CSS (extra-settings.scss); custom colours and
     * themes only get colour through this rule. Shared with Gutenberg blocks
     * (see BlockModules), where $ID is the block ID ("block_<hash>").
     *
     * @param int|string $ID Module post ID or block ID.
     */
    public static function backgroundStripeStyle($ID): string {
        $background_color = get_field('background_stripe_color', $ID);
        if (empty($background_color) || $background_color === 'none') {
            return '';
        }

        $color = AppearanceHelper::getColorValue($background_color);
        if (!$color) {
            return '';
        }

        return '<style>@layer theme {.background-stripe-color-' . sanitize_html_class((string) $ID) . '::before { background-color: ' . esc_attr($color) . '; }}</style>';
    }

    /**
     * Custom anchor ("module_id") set on a module, or '' when none.
     *
     * @param int|string $ID Module post ID or block ID.
     */
    public static function anchor($ID): string {
        $anchor = get_field('module_id', $ID);

        return is_string($anchor) ? trim($anchor) : '';
    }
}
