<?php

namespace Modularity\Module\Posts\TemplateController;

use AlingsasCustomisation\Helpers\Events;

class TribeEventsTemplate extends AbstractController {
    public function __construct(\Modularity\Module\Posts\Posts $module) {
        $posts = $module->getPosts();

        $events = [];
        foreach ($posts as $post) {
            $event = Events::parseEvent($post->id);

            if ($event) {
            $events[] = $event;
        }
        }

        $this->data['events'] = $events;

        $this->data['archive_link'] = get_post_type_archive_link('tribe_events');
    }
}
