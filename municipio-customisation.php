<?php
/*
Plugin Name: Alingsås Municipio customisation
Description: Alingsås kommun's custom tweaks for Municipio
Version: 0.1.27
Author: Consid
Author URI: https://www.consid.se
Text Domain: municipio-customisation
*/

namespace AlingsasCustomisation;

class Plugin {

	public const VERSION = '0.1.27';

	public const PATH = __DIR__;

	public const VIEWPATH = __DIR__ . '/views/';

	public function __construct() {
		// Require helpers
		$helpers = glob( __DIR__ . '/helpers/*.php' );
		foreach ( $helpers as $helper ) {
			require_once $helper;
		}

		// Require custom components
		$components = glob( __DIR__ . '/components/*' );
		foreach ( $components as $component ) {
			$class = basename( $component );
			require_once $component . '/' . $class . '.php';
		}

		// Initiate files in includes
		$includes = array_merge( glob( __DIR__ . '/includes/*.php' ), glob( __DIR__ . '/includes/**/*.php' ) );
		foreach ( $includes as $class ) {
			require_once $class;
		}
		foreach ( $includes as $class ) {
			$path = str_replace( plugin_dir_path( __FILE__ ) . 'includes/', '', $class );
			$path = explode( '/', $path );
			array_pop( $path );

			$class_path = array_reduce( $path, fn ( $carry, $item ) => $carry . ucfirst( $item ) . '\\', '\\' );
			$classname  = '\\AlingsasCustomisation\\Includes' . $class_path . ucfirst( pathinfo( $class, PATHINFO_FILENAME ) );
			if ( class_exists( $classname ) ) {
				new $classname;
			}
		}
	}
}

new Plugin;
