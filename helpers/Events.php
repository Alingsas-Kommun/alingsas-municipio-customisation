<?php

namespace AlingsasCustomisation\Helpers;

use DateTime;
use WP_Term;

use ComponentLibrary\Integrations\Image\Image;
use Municipio\Integrations\Component\ImageResolver;

class Events {
    public static function parseEvent($event) {
        if ($event->postType !== 'event') {
            return $event;
        }

        $event->link = get_permalink($event->id);

        // Thumbnail
        $resolver = new ImageResolver;
        $image = new Image(get_post_thumbnail_id($event->id), [1280, 720], $resolver);
        $event->image = $image;

        // Start and end dates
        if (isset($event->startDate) && $event->endDate) {
            $startDate = new DateTime($event->startDate);
            $endDate = new DateTime($event->endDate);
        } else {
            $date = get_post_meta($event->id, 'occasions_complete', true);
            $startDate = new DateTime($date[0]['start_date']);
            $endDate = new DateTime($date[0]['end_date']);
        }

        $event->day = date('d', $startDate->getTimestamp());
        $event->month = wp_date('M', $startDate->getTimestamp());

        if ($startDate->format('Y-m-d') === $endDate->format('Y-m-d')) {
            $event->date = ucfirst(wp_date('l j F', $startDate->getTimestamp()));
            $event->time = $startDate->format('H:i') . ' &ndash; ' . $endDate->format('H:i');
        } else {
            $event->date = wp_date('j M \k\l. H:i', $startDate->getTimestamp()) . ' &ndash; ' . wp_date('j M \k\l. H:i', $endDate->getTimestamp());
        }

        // Location
        $location = get_post_meta($event->id, 'location', true);
        if (is_array($location)) {
            $event->location = $location['title'];
            //$event->location_full = tribe_get_address($pid) . ', ' . tribe_get_zip($pid) . ' ' . tribe_get_city($pid);
        }

        // Categories
        $terms = wp_get_post_terms($event->id, 'event_categories');
        if (sizeof($terms)) {
            $event->categories = array_map(fn(WP_Term $item) => ucfirst($item->name), $terms);
        }

        // Tags
        $terms = wp_get_post_terms($event->id, 'event_tags');
        if (sizeof($terms)) {
            $event->tags = array_map(fn(WP_Term $item) => ucfirst($item->name), $terms);
        }

        // Organizers
        /* $organizers = tribe_get_organizer_ids($pid);
        if (!empty($organizers)) {
            $organizers = array_map(function($oid) {
                return get_the_title($oid);
            }, $organizers);
            $organizers = implode(', ', $organizers);
            $event->organizer = $organizers;
        } */

        // Cost
        /* $cost = tribe_get_formatted_cost($pid);
        if (!empty($cost)) {
            $event->cost = $cost;
        } */

        return $event;
    }
}
