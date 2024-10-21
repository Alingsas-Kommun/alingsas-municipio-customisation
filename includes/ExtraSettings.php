<?php

namespace AlingsasCustomisation\Includes;

class ExtraSettings {
    private const modulesWithGeneralSettings = ['mod-text'];

    private const modulesWithCardSettings = ['mod-manualinput'];

    public function __construct() {
        // Settings
        add_action('acf/include_fields', [$this, 'additional_settings']);

        // General
        add_filter('Modularity/Display/BeforeModule::classes', function ($classes, $args, $posttype, $ID) {
            if (in_array($posttype, self::modulesWithGeneralSettings)) {
                $background_color = get_field('background_color', $ID);

                if (!empty($background_color) && $background_color !== 'standard') {
                    $classes[] = 'background-color-' . $background_color;
                }
            }

            return $classes;
        }, 10, 4);

        // Card
        add_filter('Modularity/Display/BeforeModule::classes', function ($classes, $args, $posttype, $ID) {
            if (in_array($posttype, self::modulesWithCardSettings)) {
                $card_header_color = get_field('card_head_color', $ID);

                if (!empty($card_header_color) && $card_header_color !== 'standard') {
                    $classes[] = 'card__header--bg-color-' . $card_header_color;
                    $classes[] = 'card__header---color-white';
                }
            }

            return $classes;
        }, 10, 4);
    }

    public function additional_settings() {
        if (!function_exists('acf_add_local_field_group')) {
            return;
        }

        // General
        acf_add_local_field_group(array(
            'key' => 'group_671638b4358a9',
            'title' => 'Utseende',
            'fields' => array(
                array(
                    'key' => 'field_671638b487dbd',
                    'label' => 'Bakgrundsfärg',
                    'name' => 'background_color',
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

        // Card
        acf_add_local_field_group(array(
            'key' => 'group_67161fd718251',
            'title' => 'Kortutseende',
            'fields' => array(
                array(
                    'key' => 'field_67161fd750750',
                    'label' => 'Färg på huvud',
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
                        'standard' => 'Standard',
                        'winered' => 'Vinröd',
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
                        'value' => 'mod-manualinput',
                    ),
                ),
                array(
                    array(
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
}
