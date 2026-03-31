<?php

if (function_exists('acf_add_local_field_group')) {

    acf_add_local_field_group([
        'key' => 'group_67f2a8c1b3a01',
        'title' => __('News archive settings', 'municipio-customisation'),
        'fields' => [
            [
                'key' => 'field_67f2a8c1d4e00',
                'label' => __('Days before auto-archive', 'municipio-customisation'),
                'name' => 'news_archive_after_days',
                'aria-label' => '',
                'type' => 'number',
                'instructions' => __('Published news older than this many days are moved to the Archived status (daily).', 'municipio-customisation'),
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => [
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ],
                'default_value' => 90,
                'min' => 1,
                'max' => '',
                'placeholder' => '',
                'step' => 1,
                'prepend' => '',
                'append' => __('days', 'municipio-customisation'),
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'options_page',
                    'operator' => '==',
                    'value' => 'nyheter-archive-settings',
                ],
            ],
        ],
        'menu_order' => 0,
        'position' => 'acf_after_title',
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
        'hide_on_screen' => '',
        'active' => true,
        'description' => '',
        'show_in_rest' => 0,
        'acfe_display_title' => '',
        'acfe_autosync' => [
            'json',
        ],
        'acfe_form' => 0,
        'acfe_meta' => '',
        'acfe_note' => '',
    ]);
}
