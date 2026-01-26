<?php

namespace Modularity\Module\Posts\TemplateController;

use AlingsasCustomisation\Helpers\Events;

class AkEventTemplate extends AbstractController {
    public function __construct(\Modularity\Module\Posts\Posts $module) {
        $posts = $module->getPostsHelper->getPosts($module->fields);
        $posts = $posts->getPosts();
        $perPage = $module->fields['posts_count'];
        
        $priorityEvents = [];
        $longRunningEvents = [];
        
        foreach ($posts as $post) {
            $post = \Municipio\Helper\Post::preparePostObject($post);
            $event = Events::parseEvent($post);

            if (!$event) {
                continue;
            }

            if ($this->shouldShowOnFrontpage($post)) {
                $priorityEvents[] = $event;
            } else {
                $longRunningEvents[] = $event;
            }
        }

        // If we have enough priority events, use them
        if (count($priorityEvents) >= $perPage) {
            $events = array_slice($priorityEvents, 0, $perPage);
        } else {
            // Not enough priority events, fill up with long-running events
            $events = $priorityEvents;
            $remaining = $perPage - count($priorityEvents);
            
            if ($remaining > 0 && !empty($longRunningEvents)) {
                $fillEvents = array_slice($longRunningEvents, 0, $remaining);
                $events = array_merge($events, $fillEvents);
            }
        }

        $this->data['events'] = $events;
        $this->data['archive_title'] = __('More events', 'municipio-customisation');
        $this->data['archive_link'] = get_post_type_archive_link('event');
    }

    /**
     * Determine if an event should be displayed on the frontpage.
     * Long events (>7 days) are only shown at the start or end of their period.
     */
    private function shouldShowOnFrontpage(object $post): bool
    {
        $today = strtotime('today');
        $start = strtotime($post->startDate ?? '');
        $end = strtotime($post->endDate ?? '');

        if (!$start || !$end) {
            return true;
        }

        $durationDays = ($end - $start) / DAY_IN_SECONDS;
        $gracePeriodDays = 3;

        if ($durationDays <= 7) {
            return true;
        }

        $isNearStart = $today <= ($start + ($gracePeriodDays * DAY_IN_SECONDS));
        $isNearEnd = $today >= ($end - ($gracePeriodDays * DAY_IN_SECONDS));

        return $isNearStart || $isNearEnd;
    }
}