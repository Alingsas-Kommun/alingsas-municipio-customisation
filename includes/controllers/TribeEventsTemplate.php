<?php

namespace Modularity\Module\Posts\TemplateController;

use DateTime;
use WP_Term;

class TribeEventsTemplate extends AbstractController {
    public function __construct(\Modularity\Module\Posts\Posts $module) {
        $posts = $module->getPosts();

        $events = [];
        foreach ($posts as $post) {
            $event = new \stdClass;

            $event->title = $post->postTitle;
            $event->link = get_permalink($post->id);

            $thumbnail = wp_get_attachment_image_src(get_post_thumbnail_id($post->id));
            if (is_array($thumbnail) && sizeof($thumbnail) > 0) {
                $event->image = $thumbnail[0];
            }
            
            $start = get_post_meta($post->id, '_EventStartDate', true);
            $end = get_post_meta($post->id, '_EventEndDate', true);

            $startDate = new DateTime($start);
            $endDate = new DateTime($end);

            $event->day = date('d', $startDate->getTimestamp());
            $event->month = wp_date('M', $startDate->getTimestamp());

            if ($startDate->format('Y-m-d') === $endDate->format('Y-m-d')) {
                $event->date = ucfirst(wp_date('l j F', $startDate->getTimestamp()));
                $event->time = $startDate->format('H:i') . ' &ndash; ' . $endDate->format('H:i');
            } else {
                $event->date = wp_date('j M \k\l. H:i', $startDate->getTimestamp()) . ' &ndash; ' . wp_date('j M \k\l. H:i', $endDate->getTimestamp());
            }

            $terms = wp_get_post_terms($post->id);
            if (sizeof($terms)) {
                $event->tags = array_map(fn (WP_Term $item) => $item->name, $terms);
            }

            $events[] = $event;
        }

        $this->data['events'] = $events;
    }
}
