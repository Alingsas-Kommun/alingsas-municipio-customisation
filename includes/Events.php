<?php
namespace AlingsasCustomisation\Includes;

use AlingsasCustomisation\Helpers\Events as EventHelper;

class Events {
    public function __construct() {
        // Add option to display post as events calendar
        add_action('acf/load_field/name=posts_display_as', function ($field) {
            if (!isset($_GET['post'])) {
                return $field;
            }

            $pid = intval($_GET['post']);
            $source_type = get_field('posts_data_post_type', $pid);

            if ($source_type === 'event') {
                $field['choices']['ak-event'] = 'Event';
            }

            return $field;
        });

        // Use events template to display tribe events
        add_filter('Modularity/Module/Posts/template', function($template, $class, $data, $fields) {
            if (isset($fields['posts_display_as']) && $fields['posts_display_as'] === 'ak-event') {
                $template = 'events.blade.php';
            }

            return $template;
        }, 10, 4);

        // Preprocess events on archive page
        add_filter('Municipio/Controller/Archive/getArchivePosts', function($posts) {
            if (is_post_type_archive('event')) {
                foreach ($posts as $key => $post) {
                    $posts[$key] = EventHelper::parseEvent($post);
                }
            }

            return $posts;
        });

        /* add_filter('Municipio/Template/tribe_events/single/viewData', function($data) {
            $thumbnail_id = get_post_thumbnail_id();
            $resolver = new ImageResolver;
            $image = new Image($thumbnail_id, [1920, 1080], $resolver);
            
            $data['featuredImage'] = $image;

            $data['event'] = EventHelper::parseEvent(get_the_ID());

            return $data;
        }); */
    }
}