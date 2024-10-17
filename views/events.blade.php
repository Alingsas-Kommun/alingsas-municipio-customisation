<div class="o-grid o-grid--events">
    @foreach ($events as $event)
        <div class="o-grid-12@md">
            @card([
                'link' => $event->link,
                'classList' => ['has-event']
            ])
                @event([
                    'title' => $event->title,
                    'image' => $event->image ?? null,
                    'date' => $event->date,
                    'day' => $event->day,
                    'month' => $event->month,
                    'time' => $event->time ?? null,
                    'location' => 'Plats',
                    'tags' => $event->tags ?? null
                ])
                @endevent
            @endcard
        </div>
    @endforeach
</div>
