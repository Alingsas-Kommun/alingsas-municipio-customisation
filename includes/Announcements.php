<?php

namespace AlingsasCustomisation\Includes;

class Announcements {

    public function __construct() {
        // Validate that either link or content is filled in
        add_filter('acf/validate_save_post', function () {
            if (get_post_type($_POST['post_ID'] ?? null) !== 'anslagstavla') {
                return;
            }

            $link = $_POST['acf']['field_6793527ca006c'] ?? '';
            $content = $_POST['acf']['field_67a22fbb01482'] ?? '';

            if (empty($link) && empty($content)) {
                acf_add_validation_error('', __('Either "Link to PDF/protocol" or "Content" must be filled in.', 'municipio-customisation'));
            }
        });

        // Change announcement links to use portal link instead
        add_filter('post_type_link', function ($post_link, $post) {
            if (is_admin()) {
                return $post_link;
            }

            if ($post->post_type === 'anslagstavla') {
                $post_content = get_field('content', $post->ID);
                if (!empty($post_content)) {
                    return $post_link;
                }

                $post_link = get_field('link', $post->ID);
            }

            return $post_link;
        }, 10, 2);

        // Change content if on a singular page
        add_action('template_redirect', function () {
            if (is_singular('anslagstavla')) {
                add_filter('the_content', function ($content) {
                    $content = get_field('content', get_queried_object_id());
                    $link = get_field('link', get_queried_object_id());

                    if (!empty($link)) {
                        $content .= '<p><a href="' . $link . '">' . __('Download protocol', 'municipio-customisation') . '</a></p>';
                    }

                    return $content;
                });
            }
        });

        // Add date and archive date as preamble
        add_filter('Municipio/Helper/Post/postObject', function ($postObject) {
            if ($postObject instanceof \WP_Post && $postObject->post_type === 'anslagstavla') {
                $meeting_date = get_field('meeting_date', $postObject->ID);
                $archive_date = get_field('archive_date', $postObject->ID);

                $excerpt = [];

                if (!empty($meeting_date)) {
                    $excerpt[] = __('Grant date:', 'municipio-customisation') . ' ' . $meeting_date;
                }

                if (!empty($archive_date)) {
                    $excerpt[] = __('To be archived:', 'municipio-customisation') . ' ' . $archive_date;
                }

                $excerpt = implode('<br>', $excerpt);

                $postObject->post_excerpt = $excerpt;
                $postObject->excerpt = $excerpt;
                $postObject->excerpt_short = $excerpt;
                $postObject->excerpt_shorter = $excerpt;
            }

            return $postObject;
        });

        // Add class when we're showing anslagstavla posts
        add_filter('Modularity/Display/BeforeModule::classes', function ($classes, $args, $post_type, $ID) {
            if ($post_type !== 'mod-posts') {
                return $classes;
            }

            if (get_field('posts_data_post_type', $ID) !== 'anslagstavla') {
                return $classes;
            }

            $classes[] = 'announcements';

            return $classes;
        }, 10, 4);

        // Remove archived announcements if not specifically on archive archive
        if (!is_admin()) {
            add_filter('pre_get_posts', function ($query) {
                if (!is_post_type_archive('anslagstavla')) {
                    if ($query->get('post_type') === 'anslagstavla') {
                        $query->set('meta_query', [
                            'relation' => 'OR',
                            [
                                'key' => 'archived',
                                'compare' => 'NOT EXISTS',
                            ],
                            [
                                'key' => 'archived',
                                'value' => 0,
                                'compare' => '='
                            ]
                        ]);
                    }
                }

                return $query;
            });
        }
    }
}
