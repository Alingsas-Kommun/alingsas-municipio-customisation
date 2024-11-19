<?php

namespace AlingsasCustomisation\Helpers;

class Posts {
	public static function getBreadcrumbs(\WP_Post $post, callable $filterSort) {
		if (! $post instanceof \WP_Post) {
			return [];
		}

		$postTypeObj = get_post_type_object($post->post_type);
		if ($postTypeObj) {
			$breadcrumbs[] = [
				'title' => $postTypeObj->labels->name,
				'url'   => get_post_type_archive_link($post->post_type)
			];
		}

		if ($post->post_type === 'post') {
			$categories = get_the_category($post->ID);
			if (! empty($categories)) {
				$category      = $categories[0];
				$breadcrumbs[] = [
					'title' => $category->name,
					'url'   => get_category_link($category->term_id)
				];
			}
		}

		if (is_post_type_hierarchical($post->post_type)) {
			$ancestors = get_post_ancestors($post->ID);
			foreach (array_reverse($ancestors) as $ancestor) {
				$breadcrumbs[] = [
					'title' => get_the_title($ancestor),
					'url'   => get_permalink($ancestor)
				];
			}
		}

		$breadcrumbs[] = [
			'title' => get_the_title($post->ID),
			'url'   => get_permalink($post->ID)
		];


		return $filterSort($breadcrumbs);
	}
}
