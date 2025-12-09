<?php

namespace AlingsasCustomisation\Includes;

use AlingsasCustomisation\Plugin;

use ComponentLibrary\Init as ComponentLibraryInit;
use AlingsasCustomisation\Helpers\Posts;
use TypesenseIndex\Helper\Index as TypesenseClient;
use TypesenseIndex\Search as TypesenseSearch;

class Search {
    private array $postTypes;

    private string $searchTerm;

    private \WP_Query $wpquery;

    public function __construct() {
        add_action('template_redirect', [$this, 'setLocalVars']);
        add_action('pre_get_posts', [$this, 'setPostTypesToSearch']);
        add_action('custom_search_page', [$this, 'customSearchPage']);

        add_action('init', function() {
            $this->postTypes = [
                'page'             => __('Pages', 'municipio-customisation'),
                'nyheter'          => __('News', 'municipio-customisation'),
                'lediga-jobb'      => __('Jobs', 'municipio-customisation'),
                'event'            => __('Events', 'municipio-customisation'),
                'driftinformation' => __('Operating information', 'municipio-customisation')
            ];
        });

        add_filter('query_vars', function($vars) {
            $vars[] = 's';
            $vars[] = 'type';
            return $vars;
        });

        add_filter('TypesenseIndex/Index/CustomBoost', function(int|null $boostValue, \WP_Post $post) {
            if ($post->post_type === 'page' && $boostValue === null) {
                $boostValue = 1;
            }

            return $boostValue;
        }, 10, 2);
    }

    public function setLocalVars() {
        global $wp_query;

        $this->wpquery = $wp_query;

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
        if (!TypesenseClient::canConnect()) {
            return $this->getFallbackData();
        }

        $data = [];

        $data['resultCount']           = $this->wpquery->found_posts;
        $data['allHits']               = $this->getTotalHitCount();
        $data['posts']                 = $this->getPosts();
        $data['highlights']            = TypesenseSearch::getHighlights();
        $data['searchTerm']            = $this->searchTerm;
        $data['searchTermUrl']         = urlencode($this->searchTerm);
        $data['searchType']            = $this->getSearchType();
        $data['currentPagePagination'] = $this->getCurrentPage();
        
        $data['getPaginationComponentArguments'] = (new \Municipio\PostsList\ViewCallableProviders\Pagination\GetPaginationComponentArguments(
                $this->wpquery->max_num_pages,
                $this->getCurrentPage(),
                'paged',
            ))->getCallable();
       /*  $data['showPagination']        = \Municipio\Helper\Archive::showPagination(false, $this->wpquery);
        $data['paginationList']        = \Municipio\Helper\Archive::getPagination(false, $this->wpquery); */

        $countByType = $this->getResultCountByPostType();
        $countByType = array_map(function ($typeKey) use ($countByType, $data) {
            $typeName = $this->postTypes[$typeKey] ?? $typeKey;
            return [
                'name'    => $typeName,
                'link'    => home_url("/?s={$data['searchTermUrl']}&type={$typeKey}"),
                'type_id' => $typeKey,
                'count'   => $countByType[$typeKey]['count'],
                'active'  => $typeKey === $data['searchType'],
            ];
        }, array_keys($countByType));
        $data['countByType'] = $countByType;

        return $data;
    }

    private function getFallbackData()
    {
        $data = [];

        $data['resultCount'] = $this->wpquery->found_posts;
        $data['allHits'] = $this->getFallbackTotalHitCount();
        $data['posts'] = $this->getPosts();
        $data['searchTerm'] = $this->searchTerm;
        $data['searchTermUrl'] = urlencode($this->searchTerm);
        $data['searchType'] = $this->getSearchType();
        $data['currentPagePagination'] = $this->getCurrentPage();
        /* $data['showPagination'] = \Municipio\Helper\Archive::showPagination(false, $this->wpquery);
        $data['paginationList'] = \Municipio\Helper\Archive::getPagination(false, $this->wpquery); */

        $count = $this->getFallbackResultCountByPostType();
        $countByType = array_map(function ($typeKey) use ($count, $data) {
            return [
                'name' => $this->postTypes[$typeKey],
                'link' => home_url("/?s={$data['searchTermUrl']}&type={$typeKey}"),
                'type_id' => $typeKey,
                'count' => $count[$typeKey] ?? 0,
                'active' => $typeKey === $data['searchType'],
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
        if (!TypesenseClient::canConnect()) {
            return $this->getFallbackTotalHitCount();
        }

        return TypesenseSearch::getTotalHitCount();
    }

    private function getFallbackTotalHitCount() {
        global $wpdb;

        $postTypesStr = implode(',', array_map(fn($item) => "'{$item}'", array_keys($this->postTypes)));

        $sql = "SELECT COUNT(*)
                FROM {$wpdb->posts}
                WHERE post_type IN ({$postTypesStr})
                AND post_status = 'publish'
                AND post_password = ''
                AND (
                    post_title LIKE %s
                    OR post_excerpt LIKE %s
                    OR post_content LIKE %s
                )";

        $preparedStatement = $wpdb->prepare($sql, '%' . $this->searchTerm . '%', '%' . $this->searchTerm . '%', '%' . $this->searchTerm . '%');

        $count = $wpdb->get_var($preparedStatement);

        return intval($count);
    }

    private function getResultCountByPostType() {
        $facetCounts = TypesenseSearch::getFacetCounts();
        $countByType = $facetCounts['post_type'];

        return $countByType;
    }

    private function getFallbackResultCountByPostType()
    {
        global $wpdb;

        $postTypesStr = implode(',', array_map(fn($item) => "'{$item}'", array_keys($this->postTypes)));

        $sql = "SELECT COUNT(*) occurrences, post_type
                FROM {$wpdb->posts}
                WHERE post_type IN ({$postTypesStr})
                AND post_status = 'publish'
                AND post_password = ''
                AND (
                    post_title LIKE %s
                    OR post_excerpt LIKE %s
                    OR post_content LIKE %s
                )
                GROUP BY post_type";

        $preparedStatement = $wpdb->prepare($sql, '%' . $this->searchTerm . '%', '%' . $this->searchTerm . '%', '%' . $this->searchTerm . '%');

        $count = $wpdb->get_results($preparedStatement);

        if (is_array($count) && sizeof($count) > 0) {
            $count = array_combine(array_column($count, 'post_type'), array_map('intval', array_column($count, 'occurrences')));
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