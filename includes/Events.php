<?php
namespace AlingsasCustomisation\Includes;

use AlingsasCustomisation\Helpers\Events as EventHelper;

use ComponentLibrary\Integrations\Image\Image;
use Municipio\Integrations\Component\ImageResolver;
class Events {
    public function __construct() {
        add_filter('tribe_events_views_v2_should_hijack_page_template', function($hijack) {
            $hijack = false;

            return $hijack;
        }, -1);

        add_filter('tribe_events_views_v2_use_wp_template_hierarchy', '__return_true');

        add_filter('template_include', function($template) {
            if (is_singular('tribe_events')){
                $template = 'single-tribe-event';
            }

            return $template;
        }, 14);

        add_filter('Municipio/Template/tribe_events/single/viewData', function($data) {
            $thumbnail_id = get_post_thumbnail_id();
            $resolver = new ImageResolver;
            $image = new Image($thumbnail_id, [1920, 1080], $resolver);
            
            $data['featuredImage'] = $image;

            $data['event'] = EventHelper::parseEvent(get_the_ID());

            return $data;
        });
    }
}