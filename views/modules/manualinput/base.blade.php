@if (!empty($showAsSlider))
    @include('manualinput-slider')
@else
    @includeWhen(empty($hideTitle) && !empty($postTitle), 'partials.post-title')
    @if (!empty($manualInputs))
        <div class="o-grid{{ !empty($stretch) ? ' o-grid--stretch' : '' }}{{ !empty($centerContent) ? ' has-centered-content' : '' }}{{ !empty($hideHeadingArrow) ? ' hide-heading-arrow' : '' }}">
            @foreach ($manualInputs as $input)
                @include('appearances.' . $input['view'])
            @endforeach
        </div>
    @endif
@endif
