<?php

namespace AlingsasCustomisation\Includes;

use AlingsasCustomisation\Includes\Modules\ManualInput;

/**
 * Give Modularity modules inserted as Gutenberg blocks the same wrapper classes
 * as widget-area modules.
 *
 * Modularity runs `Modularity/Display/BeforeModule::classes` only for widget
 * modules (Display::displayModule). Blocks get a fixed wrapper
 * (`modularity-mod-<name> block-modularity-mod-<name>`) from
 * BlockManager::renderBlock, so every class this plugin adds through that
 * filter — manual input slider/search/arrow modifiers, inlay list appearance,
 * announcements — was missing on Gutenberg pages (e.g. the one-page template).
 *
 * The filter callbacks read settings with get_field($name, $ID). For blocks
 * those values live in the block's attributes, so they're exposed to ACF as
 * local meta under the block ID for the duration of the filter call.
 */
class BlockModules {
    public function __construct() {
        add_filter('render_block', [$this, 'addModuleClasses'], 10, 2);
    }

    /**
     * @param string $content Rendered block HTML.
     * @param array  $block   Parsed block.
     * @return string
     */
    public function addModuleClasses($content, $block) {
        $blockName = $block['blockName'] ?? '';
        if (!is_string($content) || !is_string($blockName) || !str_starts_with($blockName, 'acf/')) {
            return $content;
        }

        $module = substr($blockName, strlen('acf/'));
        $wrapperClasses = 'modularity-mod-' . $module . ' block-modularity-mod-' . $module;
        if (strpos($content, 'class="' . $wrapperClasses) === false) {
            return $content;
        }

        $data = $block['attrs']['data'] ?? [];
        if (!is_array($data) || empty($data) || !function_exists('acf_setup_meta')) {
            return $content;
        }

        $blockId = 'block_' . (function_exists('acf_get_block_id')
            ? acf_get_block_id($block['attrs'])
            : md5(wp_json_encode($block['attrs'])));

        acf_setup_meta($data, $blockId);
        $classes = (array) apply_filters('Modularity/Display/BeforeModule::classes', [], [], 'mod-' . $module, $blockId);
        acf_reset_meta($blockId);

        $classes = array_unique(array_filter(array_map('sanitize_html_class', $classes)));
        if (empty($classes)) {
            return $content;
        }

        $search = 'class="' . $wrapperClasses;
        $replace = $search . ' ' . implode(' ', $classes);
        $position = strpos($content, $search);
        $content = substr_replace($content, $replace, $position, strlen($search));

        // Mirror ManualInput::appendSearchForm(), which hangs off the
        // Modularity/Display/BeforeModule HTML filter that blocks also skip.
        if ($module === 'manualinput' && in_array('has-search', $classes, true)) {
            $openingTagEnd = strpos($content, '>', $position);
            if ($openingTagEnd !== false) {
                $content = substr_replace($content, ManualInput::searchFormMarkup(), $openingTagEnd + 1, 0);
            }
        }

        return $content;
    }
}
