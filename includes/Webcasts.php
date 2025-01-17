<?php

namespace AlingsasCustomisation\Includes;

class Webcasts {
    public function __construct() {
        add_filter('the_content', [$this, 'filterContent']);
        add_filter('comments_open', [$this, 'filterCommentsOpen'], 10, 2);
    }

    /**
     * Filter the content to include the webcast iframe if the post is a webcast.
     *
     * @param string $content The post content.
     * @return string The filtered post content.
     */
    public function filterContent($content) {
        if (is_singular('webcast')) {
            $webcast_url = get_field('webcast_url');

            if ($webcast_url) {
                $iframe = sprintf(
                    '<iframe src="%s" style="height: 1100px; width: 970px; max-width: 100%%;" frameborder="0" scrolling="no" allowfullscreen=""></iframe>',
                    esc_url($webcast_url)
                );

                $content .= $iframe;
            }
        }

        return $content;
    }

    /**
     * Filter whether comments are open for a given post.
     *
     * @param bool $open Whether the current post is open for comments.
     * @param int $post_id The post ID.
     * @return bool Whether comments are open for the given post.
     */
    public function filterCommentsOpen($open, $post_id) {
        $post_type = get_post_type($post_id);

        if ($post_type === 'webcast') {
            return false;
        }

        return $open;
    }
}
