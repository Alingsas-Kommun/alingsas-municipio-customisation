<?php

namespace AlingsasCustomisation\Includes\Navigation;

/**
 * MenuFilter Class
 *
 * Handles custom filtering of navigation menu items in the Municipio theme.
 * Currently implements filtering of orphaned pages (pages without parent or children)
 * from the navigation menu.
 *
 * @package AlingsasCustomisation\Includes\Navigation
 * @since 0.1.27
 */
class MenuFilter {
    /**
     * Initialize the menu filter
     *
     * Hooks into the Municipio navigation filter to modify menu items.
     */
    public function __construct() {
        add_filter('Municipio/Navigation/Items', [$this, 'filterOrphanedPages'], 10, 2);
    }

    /**
     * Filter out orphaned pages from the navigation menu
     *
     * An orphaned page is defined as a page that:
     * 1. Has no parent (is at root level)
     * 2. Has no children (is a leaf node)
     * 
     * This filter helps maintain a clean hierarchical menu structure by hiding
     * pages that aren't properly integrated into the site's content hierarchy.
     *
     * @param array  $items      Array of menu items to be filtered
     * @param string $identifier The menu identifier from Municipio (unused in current implementation, but kept for filter signature compatibility)                         
     * 
     * @return array Filtered array of menu items with orphaned pages removed
     */
    public function filterOrphanedPages(array $items, string $identifier): array {
        if (empty($items)) {
            return $items;
        }

        return array_filter($items, [$this, 'shouldKeepMenuItem']);
    }

    /**
     * Determine if a menu item should be kept in the navigation
     *
     * @param array $item The menu item to evaluate
     * 
     * @return bool True if the item should be kept, false if it should be filtered out
     */
    private function shouldKeepMenuItem(array $item): bool {
        // Keep all non-page post types
        if (!isset($item['post_type']) || $item['post_type'] !== 'page') {
            return true;
        }

        // Keep if it has a parent (is not at root level)
        if (!empty($item['post_parent'])) {
            return true;
        }

        // Keep if it already has children in the menu structure
        if (is_array($item['children']) && !empty($item['children'])) {
            return true;
        }

        // Check for any published children using WordPress functions
        $pageId = isset($item['page_id']) ? $item['page_id'] : $item['id'];
        return $this->hasPublishedChildren($pageId);
    }

    /**
     * Check if a page has any published children
     *
     * @param int $pageId The ID of the page to check
     * 
     * @return bool True if the page has published children, false otherwise
     */
    private function hasPublishedChildren(int $pageId): bool {
        $children = get_pages([
            'child_of' => $pageId,
            'post_status' => 'publish'
        ]);

        return !empty($children);
    }
}
