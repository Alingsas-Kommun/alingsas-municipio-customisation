<?php

namespace AlingsasCustomisation\Includes;

/**
 * Custom post status "archived" for news (nyheter) and settings for auto-archive.
 */
class NewsArchive
{
    public const POST_STATUS = 'archived';

    public const OPTIONS_PAGE_SLUG = 'nyheter-archive-settings';

    public const FIELD_DAYS = 'news_archive_after_days';

    public function __construct()
    {
        add_action('init', [$this, 'registerPostStatus']);
        add_action('acf/init', [$this, 'registerOptionsPage'], 5);
        add_action('pre_get_posts', [$this, 'limitFrontEndArchiveToPublished'], 10, 1);
    }

    public function registerPostStatus(): void
    {
        register_post_status(self::POST_STATUS, [
            'label' => _x('Archived', 'post status label', 'municipio-customisation'),
            'public' => true,
            'exclude_from_search' => true,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'internal' => false,
            'label_count' => _n_noop(
                'Archived <span class="count">(%s)</span>',
                'Archived <span class="count">(%s)</span>',
                'municipio-customisation',
            ),
        ]);
    }

    public function registerOptionsPage(): void
    {
        if (! function_exists('acf_add_options_sub_page')) {
            return;
        }

        acf_add_options_sub_page([
            'page_title' => __('News archive settings', 'municipio-customisation'),
            'menu_title' => __('Settings', 'municipio-customisation'),
            'parent_slug' => 'edit.php?post_type=nyheter',
            'menu_slug' => self::OPTIONS_PAGE_SLUG,
            'capability' => 'edit_posts',
        ]);
    }

    /**
     * Keep front-end queries for nyheter to published posts only (excludes archived status in listings/modules).
     */
    public function limitFrontEndArchiveToPublished(\WP_Query $query): void
    {
        if (is_admin()) {
            return;
        }
        // Do not force publish status on singular views;
        // archived news should still be reachable via direct permalink.
        if ($query->is_singular()) {
            return;
        }

        if (defined('REST_REQUEST') && REST_REQUEST) {
            return;
        }

        if (defined('WP_CLI') && constant('WP_CLI')) {
            return;
        }

        if (defined('DOING_CRON') && DOING_CRON) {
            return;
        }

        $postType = $query->get('post_type');
        $isNyheter = $postType === 'nyheter'
            || (is_array($postType) && in_array('nyheter', $postType, true));

        if (! $isNyheter) {
            return;
        }

        $status = $query->get('post_status');
        if ($status === self::POST_STATUS || $status === 'any') {
            return;
        }

        if (empty($status) || $status === 'publish') {
            $query->set('post_status', 'publish');
        }
    }

    /**
     * Days before auto-archive; default 90. Stored on ACF options page for nyheter.
     */
    public static function getArchiveAfterDays(): int
    {
        $days = 90;
        if (function_exists('get_field')) {
            $value = get_field(self::FIELD_DAYS, self::OPTIONS_PAGE_SLUG);
            if ($value !== null && $value !== '' && is_numeric($value)) {
                $days = (int) $value;
            }
        }

        return max(1, $days);
    }
}
