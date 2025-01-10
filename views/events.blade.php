@typography([
    'id' => 'mod-posts-' . $ID . '-label',
    'element' => $element ?? 'h2',
    'variant' => $variant ?? 'h2',
    'classList' => $classList ?? ['module-title']
])
    {!! $postTitle !!}
@endtypography
<div class="o-grid o-grid--events u-margin__top--4">
    @foreach ($events as $event)
        <div class="o-grid-12@md">
            @card([
                'link' => $event->guid,
                'classList' => ['has-event']
            ])
                @event([
                    'title' => $event->postTitle,
                    'title_variant' => 'h3',
                    'image' => $event->image ?? null,
                    'date' => $event->date,
                    'day' => $event->day,
                    'month' => $event->month,
                    'time' => $event->time ?? null,
                    'location' => $event->location ?? null,
                    'tags' => $event->categories ?? null
                ])
                @endevent
            @endcard
        </div>
    @endforeach
</div>
<div class="u-display--flex u-align-content--center u-margin__y--4">
    @button([
        'text' => $archive_title,
        'style' => 'filled',
        'color' => 'secondary',
        'href' => $archive_link,
        'classList' => ['u-flex-grow--1@xs', 'u-margin__x--auto'],
    ])
    @endbutton
</div>
