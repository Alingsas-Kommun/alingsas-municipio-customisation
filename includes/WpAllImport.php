<?php

namespace AlingsasCustomisation\Includes;

class WpAllImport {
    public function __construct() {
        add_action('pmxi_saved_post', [$this, 'handleSavedPost'], 10, 3);
    }

    /**
     * Handle the 'pmxi_saved_post' action to move temporary custom field values to the correct fields.
     *
     * @param int $post_id The post ID.
     * @param object $xml_node The XML node.
     * @param bool $is_update Whether this is an update.
     * @return void
     */
    public function handleSavedPost($post_id, $xml_node, $is_update) {
        $temp_action = get_post_meta($post_id, 'temp-unpublish-action', true);
        $temp_date = get_post_meta($post_id, 'temp-unpublish-date', true);

        if ($temp_action) {
            update_post_meta($post_id, 'unpublish-action', $temp_action);
            delete_post_meta($post_id, 'temp-unpublish-action');
        }

        if ($temp_date) {
            update_post_meta($post_id, 'unpublish-date', $temp_date);
            delete_post_meta($post_id, 'temp-unpublish-date');
        }
    }
}