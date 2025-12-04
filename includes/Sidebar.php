<?php

namespace AlingsasCustomisation\Includes;

use AcfService\Implementations\NativeAcfService;

class Sidebar
{
    public function __construct()
    {
        // Make sure that right sidebar is always active on singular items
        // unless we have specifically deactivated it
        add_filter('is_active_sidebar', function ($isActiveSidebar, $sidebar) {
            $acfService = new NativeAcfService();
            $hideRightSidebar = $acfService->getField('hide_right_sidebar');
            $pageTemplate = get_page_template_slug();

            if ($hideRightSidebar && $sidebar === 'right-sidebar') {
                return false;
            }

            if (is_singular() && $sidebar === 'right-sidebar' && empty($pageTemplate)) {
                return true;
            }
            return $isActiveSidebar;
        }, 15, 2);
    }
}
