<?php
/**
 * Autoload for Traffic.
 *
 * @package Bootstrap
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

spl_autoload_register(
	function ( $class ) {
		$classname = $class;
		$filepath  = __DIR__ . '/';
		if ( strpos( $classname, 'Traffic\\' ) === 0 ) {
			while ( strpos( $classname, '\\' ) !== false ) {
				$classname = substr( $classname, strpos( $classname, '\\' ) + 1, 1000 );
			}
			$filename = 'class-' . str_replace( '_', '-', strtolower( $classname ) ) . '.php';
			if ( strpos( $class, 'Traffic\System\\' ) === 0 ) {
				$filepath = TRAFFIC_INCLUDES_DIR . 'system/';
			}
			if ( strpos( $class, 'Traffic\Plugin\Feature\\' ) === 0 ) {
				$filepath = TRAFFIC_INCLUDES_DIR . 'features/';
			} elseif ( strpos( $class, 'Traffic\Plugin\\' ) === 0 ) {
				$filepath = TRAFFIC_INCLUDES_DIR . 'plugin/';
			}
			if ( strpos( $class, 'Traffic\Library\\' ) === 0 ) {
				$filepath = TRAFFIC_VENDOR_DIR;
			}
			if ( strpos( $filename, '-public' ) !== false ) {
				$filepath = TRAFFIC_PUBLIC_DIR;
			}
			if ( strpos( $filename, '-admin' ) !== false ) {
				$filepath = TRAFFIC_ADMIN_DIR;
			}
			$file = $filepath . $filename;
			if ( file_exists( $file ) ) {
				include_once $file;
			}
		}
	}
);
