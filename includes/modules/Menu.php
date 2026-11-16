<?php

namespace AlingsasCustomisation\Includes\Modules;

use AlingsasCustomisation\Plugin;
use Modularity\Module\Menu\Menu as ModularityMenu;

/**
 * Start-page menu listings keep Helsingborg's dropdown, and put the menu
 * item's own icon back beside the heading. Municipio 7 always draws
 * arrow_forward there, which replaced the icons editors set on the menu.
 */
class Menu {
    public function __construct() {
        add_filter('/Modularity/externalViewPath', [$this, 'addExternalViewPath']);
        add_filter('ComponentLibrary/ViewPaths', [$this, 'addOriginalViewPath']);
        add_filter('Modularity/Display/mod-menu/viewData', [$this, 'addHeadingIcons']);
    }

    /**
     * Prefer this plugin's menu views, so the heading partial can use the
     * stored icon.
     *
     * @param array<string, string> $paths
     * @return array<string, string>
     */
    public function addExternalViewPath(array $paths): array {
        $paths['mod-menu'] = Plugin::PATH . '/views/modules/menu';

        return $paths;
    }

    /**
     * The external path replaces Modularity's view directory. Keep the
     * original menu views resolvable for every partial this plugin does not
     * override.
     *
     * @param array<int, string> $paths
     * @return array<int, string>
     */
    public function addOriginalViewPath(array $paths): array {
        if (!class_exists(ModularityMenu::class)) {
            return $paths;
        }

        $originalViews = dirname((new \ReflectionClass(ModularityMenu::class))->getFileName()) . '/views';
        if (!is_dir($originalViews)) {
            return $paths;
        }

        $paths[] = $originalViews;

        return $paths;
    }

    /**
     * Attach the menu item icon name so the heading partial can render it.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function addHeadingIcons(array $data): array {
        if (empty($data['menu']['items']) || !is_array($data['menu']['items'])) {
            return $data;
        }

        foreach ($data['menu']['items'] as &$item) {
            if (!is_array($item)) {
                continue;
            }

            $item['customIcon'] = $this->iconName($item);
        }
        unset($item);

        return $data;
    }

    /**
     * @param array<string, mixed> $item
     */
    private function iconName(array $item): string {
        $icon = $item['icon']['icon'] ?? '';

        if (is_array($icon)) {
            $icon = $icon['material_icon'] ?? '';
        }

        if (!is_string($icon)) {
            return '';
        }

        $icon = trim($icon);

        return $icon === 'arrow_forward' ? '' : $icon;
    }
}
