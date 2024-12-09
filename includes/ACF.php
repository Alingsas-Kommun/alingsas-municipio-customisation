<?php

namespace AlingsasCustomisation\Includes;

use AlingsasCustomisation\Plugin;

class ACF {
    public function __construct() {
        add_action('acf/init', [$this, 'custom_location_types']);

        $acfExportManager = new \AcfExportManager\AcfExportManager();
        $acfExportManager->setTextdomain('municipio-customisation');
        $acfExportManager->setExportFolder(Plugin::PATH . '/acf/');
        $acfExportManager->autoExport(array(
            'appearance-settings' => 'group_673db0fb7c0f8',
            'card-settings' => 'group_67161fd718251',
            'clone-fields-settings' => 'group_6751b8151858a',
            'page-settings' => 'group_673dd0baaff48',
            'general-mod-settings' => 'group_6718a5f9a58fb',
            'mod-inlay-settings' => 'group_67065157310c7',
            'mod-manualinput-settings' => 'group_672336ea7ed1a',
        ));
        $acfExportManager->import();
    }

    public function custom_location_types() {
        if (function_exists('acf_register_location_type')) {
            acf_register_location_type('AlingsasCustomisation\Includes\ACF\Modularity_Location');
        }
    }
}
