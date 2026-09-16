<?php

/**
 * Manual Input module customisations: search, and Splide slider via Municipio's @slider.
 *
 * @package AlingsasCustomisation
 */

namespace AlingsasCustomisation\Includes\Modules;

use AlingsasCustomisation\Plugin;
use Modularity\Module\ManualInput\ManualInput as ModularityManualInput;

class ManualInput {
    /**
     * Display templates that can render as a Splide slider.
     *
     * @var array<int, string>
     */
    public const SLIDER_TEMPLATES = ['card', 'block', 'box', 'segment', 'news'];

    private const FIELD_SHOW_AS_SLIDER = 'field_68c91a16001ab';

    public function __construct() {
        add_filter('/Modularity/externalViewPath', [$this, 'addExternalViewPath']);
        add_filter('ComponentLibrary/ViewPaths', [$this, 'addOriginalViewPath']);
        add_filter('Modularity/Display/mod-manualinput/viewData', [$this, 'addSliderViewData']);
        add_filter('Modularity/Display/BeforeModule', [$this, 'appendSearchForm'], 10, 4);
        add_filter('Modularity/Display/BeforeModule::classes', [$this, 'addModuleClasses'], 10, 4);
    }

    /**
     * Prepend this plugin's Manual Input views so our base.blade.php wins.
     *
     * @param array $paths
     * @return array
     */
    public function addExternalViewPath(array $paths): array {
        $paths['mod-manualinput'] = Plugin::PATH . '/views/modules/manualinput';

        return $paths;
    }

    /**
     * Keep Modularity's original appearances/partials resolvable after swapping the view path.
     *
     * @param array $paths
     * @return array
     */
    public function addOriginalViewPath(array $paths): array {
        $originalViews = dirname((new \ReflectionClass(ModularityManualInput::class))->getFileName()) . '/views';
        if (!is_dir($originalViews)) {
            return $paths;
        }

        $paths[] = $originalViews;

        return $paths;
    }

    /**
     * Pass Splide/slider config into the Manual Input view.
     *
     * @param array $data
     * @return array
     */
    public function addSliderViewData(array $data): array {
        if (!$this->viewShouldUseSlider($data)) {
            $data['showAsSlider'] = false;
            return $data;
        }

        $data['showAsSlider'] = true;
        $data['sliderId'] = $this->getSliderDomId($data['ID'] ?? null);
        $data['slidesPerPage'] = $this->getSlidesPerPage($data['columns'] ?? '');
        $data['ariaLabels'] = (object) [
            'prev' => __('Previous slide', 'municipio-customisation'),
            'next' => __('Next slide', 'municipio-customisation'),
        ];

        if (empty($data['manualInputs']) || !is_array($data['manualInputs'])) {
            return $data;
        }

        foreach ($data['manualInputs'] as &$input) {
            if (!is_array($input)) {
                continue;
            }

            $input['columnSize'] = 'u-height--100';
        }

        return $data;
    }

    /**
     * Append the free-text search form used by accordion Manual Input modules.
     *
     * @param string $beforeModule
     * @param array  $args
     * @param string $postType
     * @param mixed  $ID
     * @return string
     */
    public function appendSearchForm($beforeModule, $args, $postType, $ID): string {
        if ($postType !== 'mod-manualinput') {
            return $beforeModule;
        }

        if (!preg_match('/\bclass="[^"]*\bhas-search\b[^"]*"/', $beforeModule)) {
            return $beforeModule;
        }

        $uniqid = uniqid();
        $beforeModule .= '
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

        return $beforeModule;
    }

    /**
     * Add modifier classes for search and slider.
     *
     * @param array  $classes
     * @param array  $args
     * @param string $postType
     * @param mixed  $ID
     * @return array
     */
    public function addModuleClasses($classes, $args, $postType, $ID): array {
        if ($postType !== 'mod-manualinput') {
            return $classes;
        }

        if (get_field('use_search', $ID)) {
            $classes[] = 'has-search';
        }

        if (get_field('show_as_slider', $ID)) {
            $classes[] = 'has-slider';
        }

        return $classes;
    }

    /**
     * @param array $data
     * @return bool
     */
    private function viewShouldUseSlider(array $data): bool {
        $displayAs = $this->getDisplayAsFromViewData($data);
        if (!in_array($displayAs, self::SLIDER_TEMPLATES, true)) {
            return false;
        }

        return $this->isSliderRequested($data);
    }

    /**
     * @param array $data
     * @return bool
     */
    private function isSliderRequested(array $data): bool {
        $candidates = [
            $data['showAsSlider'] ?? null,
            $data['show_as_slider'] ?? null,
            $data[self::FIELD_SHOW_AS_SLIDER] ?? null,
        ];

        foreach ($candidates as $value) {
            if ($this->isTruthy($value)) {
                return true;
            }
        }

        $id = $data['ID'] ?? null;
        if (!$id || !is_numeric($id)) {
            return false;
        }

        return $this->isTruthy(get_field('show_as_slider', $id));
    }

    /**
     * @param mixed $value
     * @return bool
     */
    private function isTruthy($value): bool {
        return $value === true || $value === 1 || $value === '1';
    }

    /**
     * @param array $data
     * @return string
     */
    private function getDisplayAsFromViewData(array $data): string {
        if (!empty($data['display_as']) && is_string($data['display_as'])) {
            return $data['display_as'];
        }

        $context = $data['context'][0] ?? '';
        if (!is_string($context) || $context === '') {
            return '';
        }

        return preg_replace('/^module\.manual-input\./', '', $context) ?: '';
    }

    /**
     * @param mixed $id
     * @return string
     */
    private function getSliderDomId($id): string {
        if (is_numeric($id)) {
            return (string) $id;
        }

        if (is_string($id) && $id !== '') {
            return preg_replace('/[^a-zA-Z0-9\-_]/', '', $id) ?: uniqid('manualinput-');
        }

        return uniqid('manualinput-');
    }

    /**
     * Map Manual Input column class (o-grid-4) to Splide perPage.
     *
     * @param string $columns
     * @return int
     */
    private function getSlidesPerPage(string $columns): int {
        if (!preg_match('/o-grid-(\d+)/', $columns, $matches)) {
            return 1;
        }

        $span = (int) $matches[1];
        if ($span < 1) {
            return 1;
        }

        return max(1, (int) (12 / $span));
    }
}
