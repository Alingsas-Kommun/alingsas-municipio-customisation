<?php
namespace AlingsasCustomisation\Includes;

class Events {
    public function __construct() {
        add_filter('tribe_events_views_v2_should_hijack_page_template', function($hijack) {
            $hijack = false;

            return $hijack;
        }, -1);

        add_filter('tribe_events_views_v2_use_wp_template_hierarchy', '__return_true');
    }
}