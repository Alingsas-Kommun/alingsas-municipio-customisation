<?php 

if (function_exists('acf_add_local_field_group')) {
    acf_add_local_field_group(array(
    'key' => 'group_67065157310c7',
    'title' => __('Inställningar lista', 'municipio-customisation'),
    'fields' => array(
        0 => array(
            'key' => 'field_670651571749f',
            'label' => __('Appearance', 'municipio-customisation'),
            'name' => 'appearance',
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
                'standard' => __('Standard', 'municipio-customisation'),
                'buttons' => __('Buttons', 'municipio-customisation'),
            ),
            'default_value' => __('standard', 'municipio-customisation'),
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
            'key' => 'field_67066fbfb2330',
            'label' => __('Background', 'municipio-customisation'),
            'name' => 'background_color',
            'aria-label' => '',
            'type' => 'true_false',
            'instructions' => '',
            'required' => 0,
            'conditional_logic' => array(
                0 => array(
                    0 => array(
                        'field' => 'field_670651571749f',
                        'operator' => '==',
                        'value' => 'buttons',
                    ),
                ),
            ),
            'wrapper' => array(
                'width' => '',
                'class' => '',
                'id' => '',
            ),
            'message' => __('Yes', 'municipio-customisation'),
            'default_value' => 0,
            'ui' => 0,
            'ui_on_text' => '',
            'ui_off_text' => '',
        ),
    ),
    'location' => array(
        0 => array(
            0 => array(
                'param' => 'post_type',
                'operator' => '==',
                'value' => 'mod-inlaylist',
            ),
        ),
        1 => array(
            0 => array(
                'param' => 'block',
                'operator' => '==',
                'value' => 'all',
            ),
        ),
        2 => array(
            0 => array(
                'param' => 'block',
                'operator' => '==',
                'value' => 'all',
            ),
        ),
        3 => array(
            0 => array(
                'param' => 'block',
                'operator' => '==',
                'value' => 'all',
            ),
        ),
        4 => array(
            0 => array(
                'param' => 'block',
                'operator' => '==',
                'value' => 'acf/inlaylist',
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