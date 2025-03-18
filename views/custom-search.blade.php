<div class="ak-search">
    @form([
    'method' => 'GET',
    'action' => home_url('/'),
    'classList' => ['u-width--100']
    ])
    @group(['direction' => 'horizontal', 'classList' => ['u-width--100']])
        @field([
            'type' => 'text',
            'name' => 's',
            'value' => $searchTerm,
            'required' => false,
            'radius' => 'sm',
            'label' => $lang->placeholder,
            'hideLabel' => true,
            'icon' => ['icon' => ''],
            'classList' => ['u-flex-grow--1', 'u-box-shadow--1']
        ])
        @endfield

        @button([
            'classList' => ['c-button--no-disabled-color'],
            'style' => 'primary',
            'type' => 'submit',
            'icon' => 'search',
            'reversePositions' => true,
            'text' => $lang->search,
            'attributeList' => [
                'aria-label' => $lang->search,
            ],
        ])
        @endbutton
    @endgroup
    @endform

    <div class="search-result-count">
        @typography(['variant' => 'meta', 'element' => 'span'])
            {!! $lang->found !!}
        @endtypography
    </div>

    <div class="search-by-type">
        <ul>
            <li>
                @button([
                    'text' => $lang->allHits . " ({$allHits})",
                    'href' => "/?s={$searchTermUrl}",
                    'color' => 'primary',
                    'style' => $searchType === 'all-hits' ? 'filled' : 'outlined',
                    'size' => 'sm',
                ])
                @endbutton
            </li>
            @foreach ($countByType as $type)
                <li>
                    @button([
                        'text' => "{$type['name']} ({$type['count']})",
                        'href' => $type['link'],
                        'color' => 'primary',
                        'style' => $type['active'] ? 'filled' : 'outlined',
                        'size' => 'sm',
                    ])
                    @endbutton
                </li>
            @endforeach
        </ul>
    </div>

    @if ($resultCount)
        <section class="t-searchresult">
            @foreach ($posts as $post)
                @card([
                    'heading' => isset($highlights[$post->id]['post_title'])
                        ? $highlights[$post->id]['post_title']
                        : $post->postTitleFiltered,
                    'link' => $post->permalink,
                    'classList' => ['u-margin__top--4']
                ])
                    @slot('content')
                        <div class="text">
                            @if (isset($highlights[$post->id]['post_excerpt']))
                                <p>{!! $highlights[$post->id]['post_excerpt'] !!}</p>
                            @elseif (isset($highlights[$post->id]['content']))
                                <p>{!! $highlights[$post->id]['content'] !!}</p>
                            @elseif (!empty($post->excerpt))
                                <p>
                                    {{ strip_tags($post->excerpt) }}
                                </p>
                            @endif
                        </div>
                        <div class="breadcrumbs" style="font-weight:bold;margin-top:1rem;">
                            {{ implode(' > ', array_map(fn($crumb) => $crumb['title'], $post->breadcrumbs)) }}
                        </div>
                        <p>Senast ändrad: {{ $post->postModifiedGmt }} </p>
                    @endslot
                @endcard
            @endforeach

        </section>

        <section class="t-searchpagination u-mt-0 u-margin__top--6 u-margin__bottom--8">

            @if ($showPagination)
                @pagination([
                    'list' => $paginationList,
                    'classList' => ['u-display--flex', 'u-justify-content--center'],
                    'current' => $currentPagePagination,
                    'linkPrefix' => 'paged'
                ])
                @endpagination
            @endif

        </section>

    @endif
</div>
