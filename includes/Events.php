<?php

namespace AlingsasCustomisation\Includes;

use AlingsasCustomisation\Helpers\Events as EventHelper;

use EventManagerIntegration\EventArchive;

class Events {
    public function __construct() {
        // Add option to display post as events calendar
        add_action('acf/load_field/name=posts_display_as', function ($field) {
            if (!isset($_GET['post'])) {
                return $field;
            }

            $pid         = intval($_GET['post']);
            $source_type = get_field('posts_data_post_type', $pid);

            if ($source_type === 'event') {
                $field['choices']['ak-event'] = [
                    'image-select-repeater-label' => __('Event', 'municipio-customisation'),
                    'image-select-repeater-value' => 'ak-event',
                ];
            }

            return $field;
        });

        // Use events template to display tribe events
        add_filter('Modularity/Module/Posts/template', function ($template, $class, $data, $fields) {
            if (isset($fields['posts_display_as']) && $fields['posts_display_as'] === 'ak-event') {
                $template = 'events.blade.php';
            }

            return $template;
        }, 10, 4);

        // Preprocess events on archive page
        add_filter('Municipio/Template/event/archive/viewData', function($viewData) {
            if (is_post_type_archive('event')) {
                foreach ($viewData['posts'] as $key => $post) {
                    $viewData['posts'][$key] = EventHelper::parseEvent($post);
                }
            }

            return $viewData;
        });
        add_filter('Municipio/Controller/Archive/getArchivePosts', function ($posts) {
            if (is_post_type_archive('event')) {
                foreach ($posts as $key => $post) {
                    $posts[$key] = EventHelper::parseEvent($post);
                }
            }

            return $posts;
        });

        // Sort events properly
        add_action('wp', function () {
            if (!is_admin() && !is_post_type_archive('event')) {
                $eventArchive = new EventArchive;
                add_filter('pre_get_posts', function (\WP_Query $query) use ($eventArchive) {
                    if ($query->get('post_type') === 'event') {
                        add_filter('posts_fields', array($eventArchive, 'eventFilterSelect'));
                        add_filter('posts_join', array($eventArchive, 'eventFilterJoin'));
                        add_filter('posts_where', array($eventArchive, 'eventFilterWhere'), 10, 2);
                        add_filter('posts_groupby', array($eventArchive, 'eventFilterGroupBy'));
                        add_filter('posts_orderby', array($eventArchive, 'eventFilterOrderBy'));
                    }

                    return $query;
                });
            }
        });
    }
}
