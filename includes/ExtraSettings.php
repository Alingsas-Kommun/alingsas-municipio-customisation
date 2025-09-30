<?php

namespace AlingsasCustomisation\Includes;

use AlingsasCustomisation\Helpers\Appearance as AppearanceHelper;

class ExtraSettings {
    public const FIELD_BACKGROUND_STRIPE_COLOR = 'field_6718a5f939289';

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

        add_filter('template_redirect', function () {
            // Hide breadcrumbs if set on single page
            if (is_singular() && get_field('hide_breadcrumbs')) {
                add_filter('Municipio/Partials/Navigation/HelperNavBeforeContent', '__return_false');
            }

            if (is_singular() && get_field('hide_title')) {

                // Make sure decorator class is available (in case Composer autoload not active for plugin src)
                if (!class_exists('\\AlingsasCustomisation\\Decorators\\HideTitlePostObject')) {
                    require_once __DIR__ . '/../src/Decorators/HideTitlePostObject.php';
                }

                // Decorate the PostObject so getTitle() returns empty, hiding the H1 in templates
                add_filter('Municipio/DecoratePostObject', function ($postObject) {
                    if (method_exists($postObject, 'getId')) {
                        $postId = $postObject->getId();
                        if ($postId && get_field('hide_title', $postId)) {
                            $postObject = new \AlingsasCustomisation\Decorators\HideTitlePostObject($postObject, true);
                        }
                    }
                    return $postObject;
                }, 20);
            }
        });
    }
}
