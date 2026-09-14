<?php

namespace AlingsasCustomisation\Includes;

class Search {
    /**
     * Header collapsible search uses an unstable input id; bind by class instead.
     */
    private const HEADER_QUICK_SEARCH_SELECTOR = '.collapsible-search-form input[name="s"]';

    public function __construct() {
        add_filter( 'Municipio/viewData', function ( $data ) {
            if ( is_search() ) {
                $data['lang']->searchTitle = $data['lang']->searchResults;
            }
            return $data;
        } );

        add_filter('option_typesense_quick_search_selectors', [$this, 'ensureHeaderQuickSearchSelector']);
    }

    /**
     * Append a durable header-search selector so quick search is not hero-only.
     *
     * @param mixed $selectors
     * @return mixed
     */
    public function ensureHeaderQuickSearchSelector(mixed $selectors): mixed
    {
        if (!is_array($selectors)) {
            return $selectors;
        }

        foreach ($selectors as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            if (str_contains((string) ($entry['selector'] ?? ''), 'collapsible-search-form')) {
                return $selectors;
            }
        }

        $selectors[] = [
            'selector' => self::HEADER_QUICK_SEARCH_SELECTOR,
            'sibling' => false,
            'mobile_behavior' => 'overlay',
        ];

        return $selectors;
    }
}
