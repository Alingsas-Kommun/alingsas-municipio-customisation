@php
    $customIcon = is_string($menuItem['customIcon'] ?? null) ? $menuItem['customIcon'] : '';
    $headingLabel = trim(implode(' ', array_filter([
        $menuItem['label'] ?? '',
        $menuItem['lastWord'] ?? '',
    ], 'strlen')));
@endphp

@if($customIcon !== '' && $headingLabel !== '')
    @element([
        'classList' => [
            'mod-menu__heading-row',
        ]
    ])
        @icon([
            'icon' => $customIcon,
            'size' => 'lg',
            'classList' => [
                'mod-menu__heading-icon',
                'u-color__text--primary',
            ]
        ])
        @endicon
        @link([
            'href' => $menuItem['href'] ?? '#',
            'classList' => [
                'mod-menu__heading-item',
            ]
        ])
            @typography([
                'element' => 'h2',
                'variant' => 'h5',
                'classList' => [
                    'mod-menu__heading-label',
                    'u-margin__y--0',
                    'u-color__text--primary'
                ]
            ])
                {{ $headingLabel }}
            @endtypography
        @endlink
    @endelement
@elseif(!empty($menuItem['label']) || !empty($menuItem['lastWord']))
    @link([
        'href' => $menuItem['href'] ?? '#',
        'classList' => [
            'mod-menu__heading-item',
        ]
    ])
        @typography([
            'element' => 'h2',
            'variant' => 'h5',
            'classList' => [
                'mod-menu__heading-label',
                'u-margin__y--0',
                'u-color__text--primary'
            ]
        ])
            @if (!empty($menuItem['label']))
            {{ $menuItem['label'] }}
            @endif
            @element([
                'componentElement' => 'span',
                'classList' => [
                    'mod-menu__heading-icon'
                ]
            ])
                @if (!empty($menuItem['lastWord']))
                {{ $menuItem['lastWord'] }}
                @endif
                @icon([
                    'icon' => 'arrow_forward',
                    'size' => 'md',
                    'attributeList' => [
                        'style' => 'vertical-align: middle;'
                    ]
                ])
                @endicon
            @endelement
        @endtypography
    @endlink
@endif
