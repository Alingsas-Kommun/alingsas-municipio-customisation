<?php

namespace AlingsasCustomisation\Includes;

use AlingsasCustomisation\Plugin;

use ComponentLibrary\Init as ComponentLibraryInit;
use AlingsasCustomisation\Helpers\Posts;
use TypesenseIndex\Search as TypesenseIndexSearch;

class Search {
    private array $postTypes;

    private string $searchTerm;

    private \WP_Query $wpquery;

    private \wpdb $wpdb;

    public function __construct() {
        $this->postTypes = [
            'page'             => __('Pages', 'municipio-customisation'),
            'nyheter'          => __('News', 'municipio-customisation'),
            'lediga-jobb'      => __('Job vacancies', 'municipio-customisation'),
            'event'            => __('Events', 'municipio-customisation'),
            'driftinformation' => __('Operating information', 'municipio-customisation')
        ];

        //add_action('template_redirect', [$this, 'setLocalVars']);
        //add_action('pre_get_posts', [$this, 'setPostTypesToSearch']);
        //add_action('custom_search_page', [$this, 'customSearchPage']);
    }

    public function setLocalVars() {
        global $wp_query;
        global $wpdb;

        $this->wpquery = $wp_query;
        $this->wpdb    = $wpdb;

        if (!is_admin() && $wp_query->is_main_query() && $wp_query->is_search) {
            $this->searchTerm = $wp_query->query['s'];
        }
    }

    public function setPostTypesToSearch(\WP_Query $query) {
        if ($query->is_main_query() && $query->is_search() && !$query->get('post_type') && !is_admin()) {
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
        $data['highlights']            = TypesenseIndexSearch::getHighlights();
        $data['searchTerm']            = $this->searchTerm;
        $data['searchTermUrl']         = urlencode($this->searchTerm);
        $data['searchType']            = $this->getSearchType();
        $data['currentPagePagination'] = $this->getCurrentPage();
        $data['showPagination']        = \Municipio\Helper\Archive::showPagination(false, $this->wpquery);
        $data['paginationList']        = \Municipio\Helper\Archive::getPagination(false, $this->wpquery);

        $countByType = $this->getResultCountByPostType();
        $countByType = array_map(function ($typeName) use ($countByType, $data) {
            $typeKey = array_flip($this->postTypes)[$typeName];

            return [
                'name'    => $typeName,
                'link'    => home_url("/?s={$data['searchTermUrl']}&type={$typeKey}"),
                'type_id' => $typeKey,
                'count'   => $countByType[$typeName]['count'],
                'active'  => $typeKey === $data['searchType'],
            ];
        }, array_keys($countByType));
        $data['countByType'] = $countByType;

        return $data;
    }

    private function getLang() {
        $domain = $_SERVER['HTTP_HOST'];

        $lang = new \stdClass;

        $lang->search      = __('Search', 'municipio');
        $lang->placeholder = __('What are you searching for?', 'municipio');
        $lang->hits        = _n('Hit', 'Hits', $this->getTotalHitCount(), 'municipio-customisation');
        $lang->allHits     = __('All hits', 'municipio-customisation');
        /* translators: 1 nr hits, 2 hits, 3 search string, 4 domain */
        $lang->found = __('%1$d %2$s for <b>"%3$s"</b> on %4$s', 'municipio-customisation');
        $lang->found = sprintf($lang->found, $this->getTotalHitCount(), lcfirst($lang->hits), $this->searchTerm, $domain);

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
        return TypesenseIndexSearch::getTotalHitCount();
    }

    private function getResultCountByPostType() {
        $facetCounts = TypesenseIndexSearch::getFacetCounts();
        $countByType = $facetCounts['post_type_name'];

        return $countByType;
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