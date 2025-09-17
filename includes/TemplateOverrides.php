<?php

namespace AlingsasCustomisation\Includes;

use AlingsasCustomisation\Plugin;

class TemplateOverrides {
	public function __construct() {
		$this->apiEventIntegration();
	}

	/** 
	 * Override Api Event Integration views
	 * @return void
	 */
	private function apiEventIntegration() {
		add_filter( 'Municipio/viewPaths', function ($paths) {
			$paths[] = Plugin::PATH . '/views/mod-event/';
			return $paths;
		}, 100, 1 );

	}
}
