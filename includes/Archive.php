<?php
namespace AlingsasCustomisation\Includes;

use AlingsasCustomisation\Helpers\Events;

class Archive {
    public function __construct() {
        // Preprocess events on archive page
        add_filter('Municipio/Controller/Archive/getArchivePosts', function($posts) {
            if (is_post_type_archive('tribe_events')) {
                foreach ($posts as $key => $post) {
                    $posts[$key] = Events::parseEvent($post->id);
                }
            }

            return $posts;
        });

        add_filter('pre_get_posts', function(\WP_Query $query) {
            if (!is_admin() && $query->is_post_type_archive('tribe_events') && $query->is_main_query()) {
                $query->set('order', 'asc');
                $query->set('orderby', 'meta_value');
                $query->set('meta_key', '_EventStartDate');

                $meta_query = [];
                if (isset($_GET['from']) && !empty($_GET['from'])) {
                    $from = $_GET['from'];
                    $query->set('from', '');
                    $meta_query[] = [
                        'key' => '_EventStartDate',
                        'value' => $from,
                        'compare' => '>=',
                        'type' => 'DATETIME',
                    ];
                } else {
                    $meta_query[] = [
                        'key' => '_EventStartDate',
                        'value' => date('Y-m-d'),
                        'compare' => '>=',
                        'type' => 'DATETIME',
                    ];
                }

                if (isset($_GET['to']) && !empty($_GET['to'])) {
                    $to = $_GET['to'];
                    $query->set('t', '');
                    $meta_query[] = [
                        'key' => '_EventEndDate',
                        'value' => $to,
                        'compare' => '<=',
                        'type' => 'DATETIME',
                    ];
                }


                $query->set('meta_query', $meta_query);
            }

            return $query;
        });
    }
}