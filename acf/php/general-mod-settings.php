<?php 


if (function_exists('acf_add_local_field_group')) {

    acf_add_local_field_group(array(
    'key' => 'group_6718a5f9a58fb',
    'title' => __('Extrainställningar', 'municipio-customisation'),
    'fields' => array(
        0 => array(
            'key' => 'field_6718a5f939289',
            'label' => __('Bakgrundsremsa', 'municipio-customisation'),
            'name' => 'background_stripe_color',
            'aria-label' => '',
            'type' => 'select',
            'instructions' => '',
            'required' => 0,
            'conditional_logic' => 0,
            'wrapper' => array(
                'width' => '',
                'class' => '',
                'id' => '',
            ),
            'choices' => array(
                'none' => __('Ingen', 'municipio-customisation'),
                'municipio-primary' => __('Primärfärg', 'municipio-customisation'),
                'municipio-primary-light' => __('Primärfärg (ljus)', 'municipio-customisation'),
                'municipio-primary-dark' => __('Primärfärg (mörk)', 'municipio-customisation'),
                'municipio-secondary' => __('Sekundärfärg', 'municipio-customisation'),
                'municipio-secondary-light' => __('Sekundärfärg (ljus)', 'municipio-customisation'),
                'municipio-secondary-dark' => __('Sekundärfärg (mörk)', 'municipio-customisation'),
            ),
            'default_value' => __('none', 'municipio-customisation'),
            'return_format' => 'value',
            'multiple' => 0,
            'allow_null' => 0,
            'ui' => 0,
            'ajax' => 0,
            'placeholder' => '',
            'allow_custom' => 0,
            'search_placeholder' => '',
        ),
        1 => array(
            'key' => 'field_671f5ab73d689',
            'label' => __('Ingen marginal upptill', 'municipio-customisation'),
            'name' => 'no_top_margin',
            'aria-label' => '',
            'type' => 'true_false',
            'instructions' => '',
            'required' => 0,
            'conditional_logic' => 0,
            'wrapper' => array(
                'width' => '',
                'class' => '',
                'id' => '',
            ),
            'message' => '',
            'default_value' => 0,
            'ui' => 0,
            'ui_on_text' => '',
            'ui_off_text' => '',
        ),
        2 => array(
            'key' => 'field_671f5ac63d68a',
            'label' => __('Ingen marginal nedtill', 'municipio-customisation'),
            'name' => 'no_bottom_margin',
            'aria-label' => '',
            'type' => 'true_false',
            'instructions' => '',
            'required' => 0,
            'conditional_logic' => 0,
            'wrapper' => array(
                'width' => '',
                'class' => '',
                'id' => '',
            ),
            'message' => '',
            'default_value' => 0,
            'ui' => 0,
            'ui_on_text' => '',
            'ui_off_text' => '',
        ),
    ),
    'location' => array(
        0 => array(
            0 => array(
                'param' => 'modularity_module',
                'operator' => '==',
                'value' => 'yes',
            ),
            1 => array(
                'param' => 'post_type',
                'operator' => '!=',
                'value' => 'mod-shape-divider',
            ),
        ),
    ),
    'menu_order' => 0,
    'position' => 'side',
    'style' => 'default',
    'label_placement' => 'left',
    'instruction_placement' => 'label',
    'hide_on_screen' => '',
    'active' => true,
    'description' => '',
    'show_in_rest' => 0,
    'acfe_display_title' => '',
    'acfe_autosync' => array(
        0 => 'json',
    ),
    'acfe_form' => 0,
    'acfe_meta' => '',
    'acfe_note' => '',
));

}