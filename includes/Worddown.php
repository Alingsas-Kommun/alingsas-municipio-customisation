<?php

namespace AlingsasCustomisation\Includes;

use AlingsasCustomisation\Plugin;

class Worddown {
    public function __construct() {
        add_filter('worddown_custom_metadata', [$this, 'addTypesenseMetadata'], 10, 3);
    }

    /**
     * Append Typesense search metadata to Worddown's YAML front matter.
     *
     * @param array $meta    Front matter array assembled by Worddown.
     * @param int   $postId  Post ID being exported.
     * @param mixed $context Worddown\Export\PostContext (read-only).
     * @return array
     */
    public function addTypesenseMetadata(array $meta, int $postId, $context): array
    {
        $meta['typesense_exclude'] = get_post_meta($postId, '_typesense_exclude', true) === '1';

        if (get_post_type($postId) === 'page') {
            $meta['typesense_exclude_as_section'] = get_post_meta($postId, '_typesense_exclude_as_section', true) === '1';
        }

        $extraTerms = (string) get_post_meta($postId, '_typesense_extra_terms', true);
        if ($extraTerms !== '') {
            $meta['typesense_extra_terms'] = $extraTerms;
        }

        return $meta;
    }
}
