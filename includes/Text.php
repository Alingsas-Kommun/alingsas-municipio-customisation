<?php

namespace AlingsasCustomisation\Includes;

class Text {
    public function __construct() {
        add_action('acf/include_fields', [$this, 'additional_settings']);

        add_filter('Modularity/Display/BeforeModule::classes', function ($classes, $args, $posttype, $ID) {
            if ($posttype === 'mod-text') {
                $appearance = get_field('appearance', $ID);
                if (!empty($appearance)) {
                    $classes[] = 'alingsas-appearance-' . $appearance;
                }
            }

            return $classes;
        }, 10, 4);
    }

    public function additional_settings() {
        if (! function_exists('acf_add_local_field_group')) {
            return;
        }

        acf_add_local_field_group(array(
            'key' => 'group_67064d0f1f69d',
            'title' => 'Utseende',
            'fields' => array(
                array(
                    'key' => 'field_67064d0f2f0b1',
                    'label' => 'Utseende',
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
                        'standard' => 'Standard',
                        'gray' => 'Grå',
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
                array(
                    array(
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'mod-text',
                    ),
                ),
                array(
                    array(
                        'param' => 'block',
                        'operator' => '==',
                        'value' => 'acf/text',
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
}
