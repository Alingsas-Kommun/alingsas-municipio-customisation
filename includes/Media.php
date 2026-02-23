<?php
namespace AlingsasCustomisation\Includes;

class Media {
    const META_KEY = '_marked_unused';
    const META_VALUE = '1';
    
    public function __construct() {
        // Add custom dropdown filter to media library
        add_action('restrict_manage_posts', [$this, 'addUnusedImagesDropdown']);
        
        // Filter media library query for list view
        add_action('pre_get_posts', [$this, 'filterMediaLibraryQuery']);
        
        // Filter media library query for grid view (AJAX)
        add_filter('ajax_query_attachments_args', [$this, 'filterMediaGridQuery']);
    }
    
    /**
     * Add dropdown filter to the media library toolbar
     *
     * @param string $post_type Current post type
     */
    public function addUnusedImagesDropdown($post_type) {
        // Only show on media library page
        if ($post_type !== 'attachment') {
            return;
        }
        
        // Get current filter value
        $current = isset($_GET['marked_unused']) ? $_GET['marked_unused'] : '';
        
        // Count images marked as unused
        $args = [
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => [
                [
                    'key' => self::META_KEY,
                    'value' => self::META_VALUE,
                    'compare' => '='
                ]
            ]
        ];
        
        $query = new \WP_Query($args);
        $count = $query->found_posts;
        
        // Output checkbox
        ?>
        <label for="filter-by-marked-unused" style="margin: 0 8px;">
            <input
                type="checkbox"
                name="marked_unused"
                id="filter-by-marked-unused"
                value="1"
                <?php checked($current, '1'); ?>
            />
            <?php printf(__('Show only unused (%d)', 'municipio-customisation'), $count); ?>
        </label>
        <?php
    }
    
    /**
     * Filter the media library query for list view
     *
     * @param \WP_Query $query
     */
    public function filterMediaLibraryQuery($query) {
        global $pagenow;
        
        // Only apply to media library in admin
        if (!is_admin() || $pagenow !== 'upload.php') {
            return;
        }
        
        // Only apply when our filter is active
        if (!isset($_GET['marked_unused']) || $_GET['marked_unused'] === '') {
            return;
        }
        
        // Only for attachment queries
        if ($query->get('post_type') !== 'attachment') {
            return;
        }
        
        // Add meta query to filter marked/unmarked images
        $meta_query = $query->get('meta_query') ?: [];
        
        if ($_GET['marked_unused'] === '1') {
            // Show only marked unused images
            $meta_query[] = [
                'key' => self::META_KEY,
                'value' => self::META_VALUE,
                'compare' => '='
            ];
        }
        
        $query->set('meta_query', $meta_query);
    }
    
    /**
     * Filter the media library query for grid view (AJAX)
     *
     * @param array $query Query arguments
     * @return array Modified query arguments
     */
    public function filterMediaGridQuery($query) {
        // Check if the marked_unused filter is active
        if (isset($_REQUEST['query']['marked_unused']) && $_REQUEST['query']['marked_unused'] !== '') {
            $query['meta_query'] = isset($query['meta_query']) ? $query['meta_query'] : [];
            
            if ($_REQUEST['query']['marked_unused'] === '1') {
                // Show only marked unused images
                $query['meta_query'][] = [
                    'key' => self::META_KEY,
                    'value' => self::META_VALUE,
                    'compare' => '='
                ];
            }
        }
        
        return $query;
    }
}