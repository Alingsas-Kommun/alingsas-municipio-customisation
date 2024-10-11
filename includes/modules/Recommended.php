<?php

namespace AlingsasCustomisation\Includes\Modules;

class Recommended {
    public function __construct() {
        add_action('acf/include_fields', [$this, 'additional_settings']);

        add_filter('Modularity/Display/BeforeModule::classes', function ($classes, $args, $posttype, $ID) {
            if ($posttype === 'mod-recommend') {
                $type = get_field('page_type', $ID);
                if (!empty($type)) {
                    $classes[] = 'alingsas-' . $type . '-pages';
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
            'key' => 'group_6706354df3a68',
            'title' => 'Typ av länkar',
            'fields' => array(
                array(
                    'key' => 'field_6706354e6b324',
                    'label' => 'Typ av sidor',
                    'name' => 'page_type',
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
                        'popular' => 'Populära',
                        'related' => 'Relaterade',
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
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'mod-recommend',
                    ),
                ),
                array(
                    array(
                        'param' => 'block',
                        'operator' => '==',
                        'value' => 'acf/recommend',
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
