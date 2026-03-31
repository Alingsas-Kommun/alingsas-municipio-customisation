<?php

namespace AlingsasCustomisation\Includes\Cron;

use AlingsasCustomisation\Includes\NewsArchive;

/**
 * Moves published nyheter older than configured days to the "archived" post status.
 */
class News
{
    public const CRON_HOOK = 'alingsas_news_posts_archiver';

    public function __construct()
    {
        add_action('wp', [$this, 'scheduleCronJobs']);
        add_action(self::CRON_HOOK, [$this, 'maybeArchiveNewsPosts']);
    }

    public function scheduleCronJobs(): void
    {
        if (! wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', self::CRON_HOOK);
        }
    }

    public function maybeArchiveNewsPosts(): void
    {
        $days = NewsArchive::getArchiveAfterDays();
        $before = wp_date('Y-m-d H:i:s', strtotime('-' . $days . ' days'));

        $batch = 50;

        while (true) {
            $query = new \WP_Query([
                'post_type' => 'nyheter',
                'post_status' => 'publish',
                'posts_per_page' => $batch,
                'paged' => 1,
                'fields' => 'ids',
                'no_found_rows' => true,
                'date_query' => [
                    [
                        'before' => $before,
                        'inclusive' => true,
                        'column' => 'post_date',
                    ],
                ],
            ]);

            if (empty($query->posts)) {
                break;
            }

            foreach ($query->posts as $postId) {
                wp_update_post([
                    'ID' => (int) $postId,
                    'post_status' => NewsArchive::POST_STATUS,
                ]);
            }
        }
    }
}
