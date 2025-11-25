@if ($posts)
    <div class="o-grid">
        @foreach ($posts as $post)
            <div class="{{ $gridColumnClass }}">
                @card([
                    'link' => $post->permalink,
                    'image' => $post->imageContract ?? (!empty($post->images['thumbnail16:9']) ? $post->images['thumbnail16:9'] : null),
                    'heading' => $post->postTitle,
                    'classList' => ['t-archive-card', 'u-height--100', 'u-display--flex', 'u-level-2'],
                    'content' => \Municipio\Helper\Sanitize::sanitizeATags($post->excerptShort),
                    'tags' => $post->termsUnlinked,
                    'meta' => $displayReadingTime ? $post->readingTime : '',
                    'date' => [
                        'timestamp' => $post->getArchiveDateTimestamp(),
                        'format' => $post->getArchiveDateFormat()
                    ],
                    'dateBadge' => $post->getArchiveDateFormat() == 'date-badge',
                    'context' => ['archive', 'archive.list', 'archive.list.card'],
                    'containerAware' => true,
                    'hasPlaceholder' => $anyPostHasImage && empty($post->images['thumbnail16:9']['src'])
                ])
                @endcard
            </div>
        @endforeach
    </div>
@endif
