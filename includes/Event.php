<?php

// use EventManagerIntegration\PostTypes\Events as ApiEventIntEvents;
namespace AlingsasCustomisation\Includes;

class Event extends \EventManagerIntegration\PostTypes\Events {

	public function __construct() {
		add_filter( 'Municipio/Template/event/single/viewData', [ $this, 'singleViewData' ], 5, 1 );
	}

	public function singleViewData( $data ) {
		global $post;

		$terms               = [];
		$terms['categories'] = get_the_terms( $post->ID, 'event_categories' );
		$terms['tags']       = get_the_terms( $post->ID, 'event_tags' );
		$terms['groups']     = get_the_terms( $post->ID, 'event_groups' );

		foreach ( $terms as $termObjects ) {
			if ( is_array( $termObjects ) ) {
				foreach ( $termObjects as $term ) {
					if ( is_a( $term, 'WP_Term' ) ) {
						$term->url = $this->buildTermLink( $term, $term->taxonomy );
					}
				}
			}
		}

		$data['terms'] = $terms;

		return $data;
	}

	private function buildTermLink( $term, $taxonomy ) {
		$archiveUrl = get_post_type_archive_link( parent::$postTypeSlug );
		if ( ! $archiveUrl ) {
			return '';
		}

		$queryParams = [];
		if ( ! empty( $_GET['from'] ) ) {
			$queryParams['from'] = sanitize_text_field( $_GET['from'] );
		}
		if ( ! empty( $_GET['to'] ) ) {
			$queryParams['to'] = sanitize_text_field( $_GET['to'] );
		}

		$queryParams[ $taxonomy . '[]' ] = $term->slug;

		return add_query_arg( $queryParams, $archiveUrl );
	}
}

