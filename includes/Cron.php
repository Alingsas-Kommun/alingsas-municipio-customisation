<?php

namespace AlingsasCustomisation\Includes;

use DateTime;

class Cron {
    public function __construct() {
        // Schedule cron jobs
        add_action('wp', [$this, 'schedule_cron_jobs']);

        // Listeners for cron jobs
        add_action('alingsas_announcement_posts_archiver', [$this, 'maybe_archive_announcement_posts']);
    }

    public function schedule_cron_jobs() {

        if (!wp_next_scheduled('alingsas_announcement_posts_archiver')) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'hourly', 'alingsas_announcement_posts_archiver');
        }
    }

    public function maybe_archive_announcement_posts() {
        $args = [
            'post_type' => 'anslagstavla',
            'posts_per_page' => -1,
            'meta_query' => [
                'relation' => 'OR',
                [
                    'key' => 'archived',
                    'compare' => 'NOT EXISTS',
                ],
                [
                    'key' => 'archived',
                    'value' => 0,
                    'compare' => '='
                ]
            ]
        ];
        $query = new \WP_Query($args);

        $today = strtotime('today');
        foreach ($query->posts as $post) {
            $archive_date = get_field('archive_date', $post->ID);
            
            if (empty($archive_date)) {
                continue;
            }
            $archive_timestamp = strtotime($archive_date);
            
            if ($archive_timestamp > $today) {
                continue;
            }

            update_field('archived', 1, $post->ID);
            update_field('archive_date', null, $post->ID);

            wp_update_post([
                'ID' => $post->ID,
                'post_content' => uniqid()
            ]);
        }
    }
}
