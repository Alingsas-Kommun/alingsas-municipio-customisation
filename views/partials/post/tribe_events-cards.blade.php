@if ($posts)
    <div class="o-grid">
        @foreach ($posts as $post)
            <div class="{{ $gridColumnClass }}">
                @card([
                    'link' => $post->link,
                    'classList' => ['has-event']
                ])
                    @event([
                        'title' => $post->title,
                        'image' => $post->image ?? null,
                        'date' => $post->date,
                        'day' => $post->day,
                        'month' => $post->month,
                        'time' => $post->time ?? null,
                        'location' => $post->location ?? null,
                        'tags' => $post->tags ?? null
                    ])
                    @endevent
                @endcard
            </div>
        @endforeach
    </div>
@endif