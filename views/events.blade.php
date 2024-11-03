<div class="o-grid o-grid--events">
    @foreach ($events as $event)
        <div class="o-grid-12@md">
            @card([
                'link' => $event->guid,
                'classList' => ['has-event']
            ])
                @event([
                    'title' => $event->postTitle,
                    'image' => $event->image ?? null,
                    'date' => $event->date,
                    'day' => $event->day,
                    'month' => $event->month,
                    'time' => $event->time ?? null,
                    'location' => $event->location ?? null,
                    'tags' => $event->tags ?? null
                ])
                @endevent
            @endcard
        </div>
    @endforeach
</div>
<div class="u-display--flex u-align-content--center u-margin__y--4">
    @button([
        'text' => 'Se fler evenemang',
        'style' => 'filled',
        'color' => 'secondary',
        'href' => $archive_link,
        'classList' => ['u-flex-grow--1@xs', 'u-margin__x--auto'],
    ])
    @endbutton
</div>
