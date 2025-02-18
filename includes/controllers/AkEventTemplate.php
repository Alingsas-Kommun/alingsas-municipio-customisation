<?php

namespace Modularity\Module\Posts\TemplateController;

use AlingsasCustomisation\Helpers\Events;

class AkEventTemplate extends AbstractController {
    public function __construct(\Modularity\Module\Posts\Posts $module) {
        $posts = $module->getPostsHelper->getPostsAndPaginationData($module->fields);
        $posts = $posts['posts'];

        $events = [];
        foreach ($posts as $post) {
            $post = \Municipio\Helper\Post::preparePostObjectArchive($post);
            $event = Events::parseEvent($post);

            if ($event) {
                $events[] = $event;
            }
        }

        $this->data['events'] = $events;

        $this->data['archive_title'] = __('More events', 'municipio-customisation');
        $this->data['archive_link'] = get_post_type_archive_link('event');
    }
}
