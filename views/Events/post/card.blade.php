@card([
    'link' => $post->link,
    'classList' => ['has-event']
])
    @event([
        'title' => $post->postTitle,
        'image' => $post->image ?? null,
        'date' => $post->date,
        'day' => $post->day,
        'month' => $post->month,
        'time' => $post->time ?? null,
        'location' => $post->location ?? null,
        'tags' => $post->categories ?? null
    ])
    @endevent
@endcard