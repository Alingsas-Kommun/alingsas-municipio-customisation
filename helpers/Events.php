<?php

namespace AlingsasCustomisation\Helpers;

use DateTime;
use WP_Post;
use WP_Term;

class Events {
    public static function parseEvent(int $pid) {
        $event = new \stdClass;

        $post = get_post($pid);

        if (!$post instanceof WP_Post || $post->post_type !== 'tribe_events') {
            return false;
        }

        $event->title = $post->post_title;
        $event->link = get_permalink($post->ID);

        // Thumbnail
        $thumbnail = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), 'large');
        if (is_array($thumbnail) && sizeof($thumbnail) > 0) {
            $event->image = $thumbnail[0];
        }

        // Start and end dates
        $start = get_post_meta($post->ID, '_EventStartDate', true);
        $end = get_post_meta($post->ID, '_EventEndDate', true);

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

        // Tags
        $terms = wp_get_post_terms($post->ID);
        if (sizeof($terms)) {
            $event->tags = array_map(fn(WP_Term $item) => $item->name, $terms);
        }

        // Location
        $location_id = get_post_meta($post->ID, '_EventVenueID', true);
        if (!empty($location_id)) {
            $event->location = get_post($location_id)->post_title;
        }

        // Organizers
        $organizers = tribe_get_organizer_ids($pid);
        if (!empty($organizers)) {
            $organizers = array_map(function($oid) {
                return get_the_title($oid);
            }, $organizers);
            $organizers = implode(', ', $organizers);
            $event->organizer = $organizers;
        }

        // Cost
        $cost = tribe_get_formatted_cost($pid);
        if (!empty($cost)) {
            $event->cost = $cost;
        }

        return $event;
    }
}
