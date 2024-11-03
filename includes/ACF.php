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
            'general-settings' => 'group_6718a5f9a58fb',
            'background-settings' => 'group_671638b4358a9',
            'card-settings' => 'group_67161fd718251',
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
