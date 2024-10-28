<?php 

if (function_exists('acf_add_local_field_group')) {
    acf_add_local_field_group(array(
    'key' => 'group_67161fd718251',
    'title' => __('Kortutseende', 'municipio-customisation'),
    'fields' => array(
        0 => array(
            'key' => 'field_67161fd750750',
            'label' => __('Färg på huvud', 'municipio-customisation'),
            'name' => 'card_head_color',
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
                'winered' => __('Vinröd', 'municipio-customisation'),
            ),
            'default_value' => false,
            'return_format' => 'value',
            'multiple' => 0,
            'allow_custom' => 0,
            'placeholder' => '',
            'search_placeholder' => '',
            'allow_null' => 0,
            'ui' => 0,
            'ajax' => 0,
        ),
    ),
    'location' => array(
        0 => array(
            0 => array(
                'param' => 'post_type',
                'operator' => '==',
                'value' => 'mod-manualinput',
            ),
        ),
        1 => array(
            0 => array(
                'param' => 'block',
                'operator' => '==',
                'value' => 'acf/manualinput',
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