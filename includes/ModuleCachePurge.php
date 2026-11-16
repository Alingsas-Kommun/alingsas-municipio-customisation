<?php

namespace AlingsasCustomisation\Includes;

/**
 * Nginx Helper purges a saved post's own URL and its real archives, but not
 * ordinary pages that list the post type through a Posts module, such as
 * /trafik-och-gator/driftinformation/. Those pages kept serving the cached
 * list until the page cache expired.
 *
 * When a post is published, updated, unpublished, trashed or deleted, this
 * purges every published page holding a Posts module that uses the post's
 * type as its data source.
 *
 * Not covered: Posts modules inserted as Gutenberg blocks or shortcodes
 * (finding them means scanning post_content on every save), and modules whose
 * data source is something other than "post type".
 */
class ModuleCachePurge
{
    private const MODULE_POST_TYPE = 'mod-posts';

    /** @var array<string, true> Post types already handled in this request. */
    private array $handledPostTypes = [];

    public function __construct()
    {
        add_action('transition_post_status', [$this, 'onStatusTransition'], 30, 3);
        add_action('before_delete_post', [$this, 'onDelete'], 10, 2);
    }

    /**
     * @param string   $newStatus
     * @param string   $oldStatus
     * @param \WP_Post $post
     */
    public function onStatusTransition($newStatus, $oldStatus, $post): void
    {
        if ($newStatus !== 'publish' && $oldStatus !== 'publish') {
            return;
        }

        $this->purgePagesListing($post);
    }

    /**
     * Covers permanent deletion that skips the trash.
     *
     * @param int      $postId
     * @param \WP_Post $post
     */
    public function onDelete($postId, $post): void
    {
        if (!$post instanceof \WP_Post || $post->post_status !== 'publish') {
            return;
        }

        $this->purgePagesListing($post);
    }

    private function purgePagesListing($post): void
    {
        if (!$post instanceof \WP_Post || isset($this->handledPostTypes[$post->post_type])) {
            return;
        }

        $purger = $this->getPurger();
        if ($purger === null) {
            return;
        }

        // Imports save many posts of one type in a single request; look up once.
        $this->handledPostTypes[$post->post_type] = true;

        foreach ($this->getPageIdsListing($post->post_type) as $pageId) {
            $url = get_permalink($pageId);
            if (is_string($url) && $url !== '') {
                $purger->purge_url($url, false);
            }
        }
    }

    /**
     * Nginx Helper's purger, or null when the plugin is inactive or purging is off.
     */
    private function getPurger(): ?object
    {
        global $nginx_purger, $nginx_helper_admin;

        if (!is_object($nginx_purger) || !method_exists($nginx_purger, 'purge_url')) {
            return null;
        }

        if (!is_object($nginx_helper_admin) || empty($nginx_helper_admin->options['enable_purge'])) {
            return null;
        }

        return $nginx_purger;
    }

    /**
     * Published pages with a Posts module that lists the given post type.
     *
     * Both lookups are bounded by the meta_key index. Modularity's own
     * ModuleManager::getModuleUsage() is avoided because it also scans every
     * post_content for shortcodes, once per module.
     *
     * @return int[]
     */
    private function getPageIdsListing(string $postType): array
    {
        global $wpdb;

        $moduleIds = $wpdb->get_col($wpdb->prepare(
            "SELECT module.ID
            FROM {$wpdb->posts} module
            INNER JOIN {$wpdb->postmeta} type
                ON type.post_id = module.ID
                AND type.meta_key = 'posts_data_post_type'
                AND type.meta_value = %s
            INNER JOIN {$wpdb->postmeta} source
                ON source.post_id = module.ID
                AND source.meta_key = 'posts_data_source'
                AND source.meta_value = 'posttype'
            WHERE module.post_type = %s
                AND module.post_status = 'publish'",
            $postType,
            self::MODULE_POST_TYPE
        ));

        if (empty($moduleIds)) {
            return [];
        }

        // Same serialized fragment Modularity matches in ModuleUsageById.
        $conditions = [];
        foreach ($moduleIds as $moduleId) {
            $moduleId     = (string) (int) $moduleId;
            $fragment     = sprintf('s:6:"postid";s:%d:"%s";', strlen($moduleId), $moduleId);
            $conditions[] = $wpdb->prepare('placement.meta_value LIKE %s', '%' . $wpdb->esc_like($fragment) . '%');
        }

        $pageIds = $wpdb->get_col(
            "SELECT DISTINCT page.ID
            FROM {$wpdb->postmeta} placement
            INNER JOIN {$wpdb->posts} page
                ON page.ID = placement.post_id
                AND page.post_status = 'publish'
            WHERE placement.meta_key = 'modularity-modules'
                AND (" . implode(' OR ', $conditions) . ')'
        );

        return array_map('intval', $pageIds);
    }
}
