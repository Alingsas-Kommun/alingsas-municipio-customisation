<div class="{{ $class }}" {!! $attribute !!}>
    @isset($image)
        <div class="image">
            <img src="{{ $image }}" alt="{{ $title }}">
            @isset($day, $month)
                @typography(['element' => 'span', 'classList' => ['date']])
                    <span class="day">{{ $day }}</span>
                    <span class="month">{{ $month }}</span>
                @endtypography
            @endisset
        </div>
    @endisset
    <div class="content">
        @typography([
            'variant' => 'h2',
            'element' => 'h3'
        ])
            {{ $title }}
        @endtypography
        <div class="metadata">
            @isset($date)
                <div class="meta">
                    <span class="icon">
                        @icon(['icon' => 'calendar_month', 'size' => 'md'])
                        @endicon
                    </span>
                    <span class="value">
                        @typography(['element' => 'span', 'classList' => ['tag']])
                            {!! $date !!}
                        @endtypography
                    </span>
                </div>
            @endisset
            @isset($time)
                <div class="meta">
                    <span class="icon">
                        @icon(['icon' => 'schedule', 'size' => 'md'])
                        @endicon
                    </span>
                    <span class="value">
                        @typography(['element' => 'span', 'classList' => ['tag']])
                            {!! $time !!}
                        @endtypography
                    </span>
                </div>
            @endisset
            @isset($location)
                <div class="meta">
                    <span class="icon">
                        @icon(['icon' => 'location_on', 'size' => 'md'])
                        @endicon
                    </span>
                    <span class="value">
                        @typography(['element' => 'span', 'classList' => ['tag']])
                            {{ $location }}
                        @endtypography
                    </span>
                </div>
            @endisset
            @isset($tags)
                <div class="meta">
                    <span class="icon">
                        @icon(['icon' => 'sell', 'size' => 'md'])
                        @endicon
                    </span>
                    <span class="value">
                        @foreach ($tags as $tag)
                            @typography(['element' => 'span', 'classList' => ['tag']])
                                {{ $tag }}
                            @endtypography
                            @if (!$loop->last)
                                <span>, </span>
                            @endif
                        @endforeach
                    </span>
                </div>
            @endisset
        </div>
    </div>
</div>
