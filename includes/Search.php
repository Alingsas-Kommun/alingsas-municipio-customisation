<?php

namespace AlingsasCustomisation\Includes;

use AlingsasCustomisation\Plugin;

use ComponentLibrary\Init as ComponentLibraryInit;
use AlingsasCustomisation\Helpers\Posts;

class Search {
	private array $postTypes;

	private string $searchTerm;

	private \WP_Query $wpquery;

	private \wpdb $wpdb;

	public function __construct() {
		$this->postTypes = [
			'page'             => __('Pages', 'municipio-customisation'),
			'nyheter'          => __('News', 'municipio-customisation'),
			'jobb'             => __('Jobs', 'municipio-customisation'),
			'event'            => __('Events', 'municipio-customisation'),
			'driftinformation' => __('Operating information', 'municipio-customisation')
		];

		add_action('template_redirect', [$this, 'setLocalVars']);
		add_action('pre_get_posts', [$this, 'setPostTypesToSearch']);
		add_action('custom_search_page', [$this, 'customSearchPage']);
	}

	public function setLocalVars() {
		global $wp_query;
		global $wpdb;

		$this->searchTerm = get_search_query();

		$this->wpquery = $wp_query;
		$this->wpdb    = $wpdb;
	}

	public function setPostTypesToSearch(\WP_Query $query) {
		if ($query->is_main_query() && $query->is_search() && ! is_admin()) {
			$query->set('post_type', array_keys($this->postTypes));
			$query->set('posts_per_page', 10);
			$query->set('paged', $this->getCurrentPage());

			$searchType = $this->getSearchType();
			if ($searchType !== 'all-hits' && in_array($searchType, array_keys($this->postTypes))) {
				$query->set('post_type', $searchType);
			}
		}
	}

	public function customSearchPage() {
		$markup           = "";
		$componentLibrary = new ComponentLibraryInit([]);
		$bladeEngine      = $componentLibrary->getEngine();

		$data         = $this->getData();
		$data['lang'] = $this->getLang();

		try {
			$markup = $bladeEngine->makeView('custom-search', $data, [], Plugin::VIEWPATH)->render();
		} catch (\Throwable $e) {
			$markup .= '<pre style="border: 3px solid #f00; padding: 10px;">';
			$markup .= '<strong>' . $e->getMessage() . '</strong>';
			$markup .= '<hr style="background: #000; outline: none; border:none; display: block; height: 1px;"/>';
			$markup .= $e->getTraceAsString();
			$markup .= '</pre>';
		}

		echo $markup;
	}

	private function getData() {
		$data = [];

		$data['resultCount']           = $this->wpquery->found_posts;
		$data['allHits']               = $this->getTotalHitCount();
		$data['posts']                 = $this->getPosts();
		$data['searchTerm']            = $this->searchTerm;
		$data['searchTermUrl']         = urlencode($this->searchTerm);
		$data['searchType']            = $this->getSearchType();
		$data['currentPagePagination'] = $this->getCurrentPage();
		$data['showPagination']        = \Municipio\Helper\Archive::showPagination(false, $this->wpquery);
		$data['paginationList']        = \Municipio\Helper\Archive::getPagination(false, $this->wpquery);

		$count               = $this->getResultCountByPostType();
		$countByType         = array_map(function ($typeKey) use ($count, $data) {
			return [
				'name'    => $this->postTypes[$typeKey],
				'link'    => home_url("/?s={$data['searchTermUrl']}&type={$typeKey}"),
				'type_id' => $typeKey,
				'count'   => $count[$typeKey] ?? 0,
				'active'  => $typeKey === $data['searchType'],
			];
		}, array_keys($this->postTypes));
		$data['countByType'] = $countByType;

		return $data;
	}

	private function getLang() {
		$domain = $_SERVER['HTTP_HOST'];

		$lang = new \stdClass;

		$lang->search      = __('Search', 'municipio');
		$lang->placeholder = __('What are you searching for?', 'municipio');
		$lang->hits        = _n('Hit', 'Hits', $this->wpquery->found_posts, 'municipio-customisation');
		$lang->allHits     = __('All hits', 'municipio-customisation');
		/* translators: 1 nr hits, 2 hits, 3 search string, 4 domain */
		$lang->found = __('%1$d %2$s for <b>"%3$s"</b> on %4$s', 'municipio-customisation');
		$lang->found = sprintf($lang->found, $this->wpquery->found_posts, lcfirst($lang->hits), $this->searchTerm, $domain);

		return $lang;
	}

	private function getSearchType() {
		return isset($_GET['type']) ? trim($_GET['type']) : 'all-hits';
	}

	private function getPosts() {
		$posts = $this->wpquery->posts;
		foreach ($posts as $postKey => $post) {
			$posts[$postKey] = \Municipio\Helper\Post::preparePostObject($post);

			$breadcrumbs = Posts::getBreadcrumbs($post, function ($breadcrumbs) {
				$filtered = array_filter($breadcrumbs, function ($item) {
					return $item['title'] !== 'Sidor';
				});
				return count($filtered) <= 1 ? [] : $filtered;
			});

			$posts[$postKey]->breadcrumbs = $breadcrumbs;
		}

		$posts = apply_filters('Municipio/Controller/Search/prepareSearchResultObject', $posts);

		return $posts;
	}

	private function getTotalHitCount() {
		$postTypes = implode(',', array_map(fn($item) => "'{$item}'", array_keys($this->postTypes)));

		$sql = "SELECT COUNT(*)
                FROM {$this->wpdb->posts}
                WHERE post_type IN ({$postTypes})
                AND post_status = 'publish'
                AND post_password = ''
                AND (
                    post_title LIKE %s 
                    OR post_excerpt LIKE %s 
                    OR post_content LIKE %s 
                )";

		$preparedStatement = $this->wpdb->prepare(
			$sql,
			'%' . $this->searchTerm . '%',
			'%' . $this->searchTerm . '%',
			'%' . $this->searchTerm . '%'
		);

		$count = $this->wpdb->get_var($preparedStatement);

		return intval($count);
	}

	private function getResultCountByPostType() {
		$postTypes = implode(',', array_map(fn($item) => "'{$item}'", array_keys($this->postTypes)));

		$sql = "SELECT COUNT(*) occurrences, post_type
                FROM {$this->wpdb->posts}
                WHERE post_type IN ({$postTypes})
                AND post_status = 'publish'
                AND post_password = ''
                AND (
                    post_title LIKE %s 
                    OR post_excerpt LIKE %s 
                    OR post_content LIKE %s 
                )
                GROUP BY post_type";

		$preparedStatement = $this->wpdb->prepare(
			$sql,
			'%' . $this->searchTerm . '%',
			'%' . $this->searchTerm . '%',
			'%' . $this->searchTerm . '%'
		);

		$count = $this->wpdb->get_results($preparedStatement);

		if (is_array($count) && sizeof($count) > 0) {
			$count = array_combine(
				array_column($count, 'post_type'),
				array_map('intval', array_column($count, 'occurrences'))
			);
		} else {
			$count = [];
		}

		return $count;
	}

	private function getCurrentPage() {
		$paged = isset($_GET['paged'])
			? (intval($_GET['paged']) > 0
				? intval($_GET['paged'])
				: 1)
			: 1;

		return $paged;
	}
}
