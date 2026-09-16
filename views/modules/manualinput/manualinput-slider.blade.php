@includeWhen(empty($hideTitle) && !empty($postTitle), 'partials.post-title')

@if (!empty($manualInputs))
    <div class="o-grid u-margin__bottom--4">
        <div class="o-grid-12@sm o-grid-8@md"></div>
        <div class="o-grid-12@sm o-grid-4@md u-display--flex u-align-items--end u-justify-content--end">
            <div class="c-slider__arrows" id="slider_{{ $sliderId }}">
                @button([
                    'icon' => 'keyboard_arrow_left',
                    'style' => 'filled',
                    'color' => 'primary',
                    'ariaLabel' => $ariaLabels->prev ?? __('Previous slide', 'municipio-customisation'),
                    'attributeList' => [
                        'data-js-slider-prev' => true
                    ]
                ])
                @endbutton
                @button([
                    'icon' => 'keyboard_arrow_right',
                    'style' => 'filled',
                    'color' => 'primary',
                    'ariaLabel' => $ariaLabels->next ?? __('Next slide', 'municipio-customisation'),
                    'attributeList' => [
                        'data-js-slider-next' => true
                    ]
                ])
                @endbutton
            </div>
        </div>
    </div>

    @slider([
        'classList' => ['c-slider--post'],
        'showStepper' => false,
        'autoSlide' => false,
        'repeatSlide' => true,
        'customButtons' => 'slider_' . $sliderId,
        'containerAware' => true,
        'attributeList' => [
            'data-slides-per-page' => $slidesPerPage ?? 1,
            'data-slides-per-move' => $slidesPerPage ?? 1,
        ]
    ])
        @foreach ($manualInputs as $input)
            @slider__item([
                'classList' => ['c-slider__item--post']
            ])
                @include('appearances.' . $input['view'])
            @endslider__item
        @endforeach
    @endslider
@endif
