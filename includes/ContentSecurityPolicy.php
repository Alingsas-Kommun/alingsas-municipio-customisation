<?php

namespace AlingsasCustomisation\Includes;

class ContentSecurityPolicy {
    public function __construct() {
        add_filter('WpSecurity/Csp', [$this, 'allowDataUriConnectSrc'], 10, 1);
    }

    /**
     * Allow data: URIs in connect-src so that Font Awesome SVG icons
     * loaded as inline data URIs are not blocked by the CSP.
     *
     * @param array $csp
     * @return array
     */
    public function allowDataUriConnectSrc(array $csp): array {
        $csp['connect-src'] ??= [];
        if (!in_array('data:', $csp['connect-src'], true)) {
            $csp['connect-src'][] = 'data:';
        }
        return $csp;
    }
}
