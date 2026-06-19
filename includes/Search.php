<?php

namespace AlingsasCustomisation\Includes;

class Search {
    public function __construct() {
        add_filter( 'Municipio/viewData', function ( $data ) {
            if ( is_search() ) {
                $data['lang']->searchTitle = $data['lang']->searchResults;
            }
            return $data;
        } );
    }
}