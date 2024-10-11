<?php

namespace AlingsasCustomisation\Includes\Modules;

class InlayList {
    public function __construct() {
        add_action('acf/include_fields', [$this, 'additional_settings']);

        add_filter('Modularity/Display/BeforeModule::classes', function ($classes, $args, $posttype, $ID) {
            if ($posttype === 'mod-inlaylist') {
                $appearance = get_field('appearance', $ID);

                if (!empty($appearance)) {
                    $classes[] = 'alingsas-appearance-' . $appearance;
                }

                if (!empty($appearance) && $appearance === 'buttons') {
                    $gray_background = get_field('gray_background', $ID);
                    if ($gray_background === true) {
                        $classes[] = 'alingsas-gray-background';
                    }
                }
            }

            return $classes;
        }, 10, 4);
    }

    public function additional_settings() {
        if (!function_exists('acf_add_local_field_group')) {
            return;
        }

        acf_add_local_field_group(array(
            'key' => 'group_67065157310c7',
            'title' => 'Inställningar',
            'fields' => array(
                array(
                    'key' => 'field_670651571749f',
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
                        'buttons' => 'Knappar',
                    ),
                    'default_value' => 'standard',
                    'return_format' => 'value',
                    'multiple' => 0,
                    'allow_custom' => 0,
                    'placeholder' => '',
                    'search_placeholder' => '',
                    'allow_null' => 0,
                    'ui' => 0,
                    'ajax' => 0,
                ),
                array(
                    'key' => 'field_67066fbfb2330',
                    'label' => 'Grå bakgrund',
                    'name' => 'gray_background',
                    'aria-label' => '',
                    'type' => 'true_false',
                    'instructions' => '',
                    'required' => 0,
                    'conditional_logic' => array(
                        array(
                            array(
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
                    'message' => 'Ja',
                    'default_value' => 0,
                    'ui' => 0,
                    'ui_on_text' => '',
                    'ui_off_text' => '',
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'mod-inlaylist',
                    ),
                ),
                array(
                    array(
                        'param' => 'block',
                        'operator' => '==',
                        'value' => 'all',
                    ),
                ),
                array(
                    array(
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
}
