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
     * 1. Has no parent in the menu (is at root level)
     * 2. Has no children in the menu or in the WordPress page tree
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

        return array_filter($items, fn(array $item): bool => $this->shouldKeepMenuItem($item, $items));
    }

    /**
     * Determine if a menu item should be kept in the navigation
     *
     * @param array $item     The menu item to evaluate
     * @param array $allItems All menu items in the current flat list
     *
     * @return bool True if the item should be kept, false if it should be filtered out
     */
    private function shouldKeepMenuItem(array $item, array $allItems): bool {
        // Keep all non-page post types
        if (!isset($item['post_type']) || $item['post_type'] !== 'page') {
            return true;
        }

        // Keep if it has a parent in the menu (is not at root level)
        if (!empty($item['post_parent'])) {
            return true;
        }

        // Keep if other menu items are nested under this item
        if ($this->hasMenuChildren($item, $allItems)) {
            return true;
        }

        // Check for any published children in the WordPress page tree
        $pageId = isset($item['page_id']) ? (int) $item['page_id'] : (int) $item['id'];
        return $this->hasPublishedChildren($pageId);
    }

    /**
     * Check if a menu item has child items in the flat menu list
     *
     * @param array $item     The menu item to check
     * @param array $allItems All menu items in the current flat list
     *
     * @return bool True if the item has menu children, false otherwise
     */
    private function hasMenuChildren(array $item, array $allItems): bool {
        $menuItemId = (int) ($item['id'] ?? 0);

        if ($menuItemId === 0) {
            return false;
        }

        foreach ($allItems as $otherItem) {
            if ((int) ($otherItem['post_parent'] ?? 0) === $menuItemId) {
                return true;
            }
        }

        return false;
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
