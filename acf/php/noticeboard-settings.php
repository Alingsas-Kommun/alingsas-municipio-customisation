<?php 

if (function_exists('acf_add_local_field_group')) {
    acf_add_local_field_group(array(
    'key' => 'group_6793527c520e2',
    'title' => __('Anslagsinformation', 'municipio-customisation'),
    'fields' => array(
        0 => array(
            'key' => 'field_6793527ca006c',
            'label' => __('Länk till PDF/protokoll', 'municipio-customisation'),
            'name' => 'link',
            'aria-label' => '',
            'type' => 'url',
            'instructions' => '',
            'required' => 1,
            'conditional_logic' => 0,
            'wrapper' => array(
                'width' => '',
                'class' => '',
                'id' => '',
            ),
            'default_value' => '',
            'placeholder' => '',
        ),
        1 => array(
            'key' => 'field_679352f7a006d',
            'label' => __('Anslagsdatum', 'municipio-customisation'),
            'name' => 'meeting_date',
            'aria-label' => '',
            'type' => 'date_picker',
            'instructions' => '',
            'required' => 0,
            'conditional_logic' => 0,
            'wrapper' => array(
                'width' => '',
                'class' => '',
                'id' => '',
            ),
            'display_format' => 'Y-m-d',
            'return_format' => 'Y-m-d',
            'first_day' => 1,
            'allow_in_bindings' => 1,
        ),
        2 => array(
            'key' => 'field_67a22fbb01482',
            'label' => __('Innehåll', 'municipio-customisation'),
            'name' => 'content',
            'aria-label' => '',
            'type' => 'wysiwyg',
            'instructions' => '',
            'required' => 0,
            'conditional_logic' => 0,
            'wrapper' => array(
                'width' => '',
                'class' => '',
                'id' => '',
            ),
            'default_value' => '',
            'tabs' => 'all',
            'toolbar' => 'full',
            'media_upload' => 1,
            'delay' => 0,
        ),
        3 => array(
            'key' => 'field_6793531da006e',
            'label' => __('Tas ner', 'municipio-customisation'),
            'name' => 'archive_date',
            'aria-label' => '',
            'type' => 'date_picker',
            'instructions' => '',
            'required' => 0,
            'conditional_logic' => 0,
            'wrapper' => array(
                'width' => '',
                'class' => '',
                'id' => '',
            ),
            'display_format' => 'Y-m-d',
            'return_format' => 'Y-m-d',
            'first_day' => 1,
        ),
        4 => array(
            'key' => 'field_67935342a006f',
            'label' => __('Anslagstyp', 'municipio-customisation'),
            'name' => 'announcement_type',
            'aria-label' => '',
            'type' => 'taxonomy',
            'instructions' => '',
            'required' => 0,
            'conditional_logic' => 0,
            'wrapper' => array(
                'width' => '',
                'class' => '',
                'id' => '',
            ),
            'taxonomy' => 'anslagstyp',
            'add_term' => 0,
            'save_terms' => 1,
            'load_terms' => 1,
            'return_format' => 'id',
            'field_type' => 'radio',
            'allow_null' => 0,
            'acfe_bidirectional' => array(
                'acfe_bidirectional_enabled' => '0',
            ),
            'bidirectional' => 0,
            'multiple' => 0,
            'bidirectional_target' => array(
            ),
        ),
        5 => array(
            'key' => 'field_6793772e2708e',
            'label' => __('Arkiverad', 'municipio-customisation'),
            'name' => 'archived',
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
            'message' => __('Ja', 'municipio-customisation'),
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
                'value' => 'anslagstavla',
            ),
        ),
    ),
    'menu_order' => 0,
    'position' => 'normal',
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