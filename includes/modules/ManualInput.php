<?php

namespace AlingsasCustomisation\Includes\Modules;

class ManualInput {
    public function __construct() {
        add_filter('Modularity/Display/BeforeModule', function ($beforeModule, $args, $post_type, $ID) {
            if ($post_type === 'mod-manualinput' && preg_match('/\bclass="[^"]*\bhas-search\b[^"]*"/', $beforeModule)) {
                $uniqid = uniqid();
                $html = '
                    <form>
                        <div class="c-field u-width--100 c-field--search c-field--md c-field--radius-md c-field--bg-transparent c-field--text-align-left">
                            <label id="label_' . $uniqid . '" for="input_' . $uniqid . '" class="c-field__label">Sök innehåll</label>
                            <div class="c-field__inner c-field__inner--search">
                                <input id="input_' . $uniqid . '" aria-labelledby="label_' . $uniqid . '" type="search" placeholder="Sök.." autocomplete="on">
                                <div class="c-field_focus-styler u-level-top"></div>
                            </div>
                        </div>
                    </form>
                ';
                $beforeModule .= $html;
            }
            return $beforeModule;
        }, 10, 4);

        add_filter('Modularity/Module/ManualInput/Template', function ($templateName) {
            //var_dump($templateName);
            return $templateName;
        });

        add_filter('Modularity/Display/BeforeModule::classes', function ($classes, $args, $posttype, $ID) {
            if ($posttype === 'mod-manualinput') {
                $search = get_field('use_search', $ID);

                if ($search) {
                    $classes[] = 'has-search';
                }
            }

            return $classes;
        }, 10, 4);
    }
}
