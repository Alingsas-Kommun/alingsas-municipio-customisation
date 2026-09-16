@includeWhen(empty($hideTitle) && !empty($postTitle), 'partials.post-title')

@if (!empty($manualInputs))
    <div class="o-manualinput-slider{{ !empty($centerContent) ? ' has-centered-content' : '' }}{{ !empty($hideHeadingArrow) ? ' hide-heading-arrow' : '' }}">
        <div class="c-slider__arrows" id="slider_{{ $sliderId }}">
            @button([
                'icon' => 'keyboard_arrow_left',
                'style' => 'filled',
                'color' => 'primary',
                'classList' => ['c-slider__arrow', 'c-slider__arrow--prev'],
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
                'classList' => ['c-slider__arrow', 'c-slider__arrow--next'],
                'ariaLabel' => $ariaLabels->next ?? __('Next slide', 'municipio-customisation'),
                'attributeList' => [
                    'data-js-slider-next' => true
                ]
            ])
            @endbutton
        </div>

        @slider([
            'classList' => ['c-slider--post', 'c-slider--has-stepper'],
            'showStepper' => true,
            'autoSlide' => false,
            'repeatSlide' => false,
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
    </div>
@endif
