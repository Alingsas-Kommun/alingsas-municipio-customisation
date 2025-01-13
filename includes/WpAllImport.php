<?php

namespace AlingsasCustomisation\Includes;

class WpAllImport {
    public function __construct() {
        add_action('pmxi_saved_post', function($post_id, $xml_node, $is_update) {
            // Get the temporary custom field values
            $temp_action = get_post_meta($post_id, 'temp-unpublish-action', true);
            $temp_date = get_post_meta($post_id, 'temp-unpublish-date', true);
        
            // If the temporary values exist, move them to the correct fields
            if ($temp_action) {
                update_post_meta($post_id, 'unpublish-action', $temp_action);
                // Optionally delete the temporary field
                delete_post_meta($post_id, 'temp-unpublish-action');
            }
        
            if ($temp_date) {
                update_post_meta($post_id, 'unpublish-date', $temp_date);
                // Optionally delete the temporary field
                delete_post_meta($post_id, 'temp-unpublish-date');
            }
        }, 10, 3);
    }
}
