<?php
/**
 * Plugin Name:       VilloCity Suite
 * Plugin URI:        https://github.com/brockett-creative/villocity-suite
 * Description:       Modular community-site toolkit from Brockett Creative Group. Turn individual modules on or off per site.
 * Version:           3.0.0-dev
 * Requires at least: 6.5
 * Requires PHP:      8.1
 * Author:            Brockett Creative Group, Inc.
 * Author URI:        https://brockettcreative.com
 * License:           Proprietary
 * Text Domain:       villocity-suite
 * Domain Path:       /languages
 * Update URI:        https://github.com/brockett-creative/villocity-suite
 *
 * @package VilloCity
 */

defined( 'ABSPATH' ) || exit;

// Keep this in sync with the "Version:" header above. CI refuses to release if they differ.
define( 'VILLOCITY_VERSION', '3.0.0-dev' );
define( 'VILLOCITY_FILE', __FILE__ );
define( 'VILLOCITY_DIR', plugin_dir_path( __FILE__ ) );
define( 'VILLOCITY_URL', plugin_dir_url( __FILE__ ) );

/*
 * PSR-4 style autoloader for the plugin's own classes.
 *
 *   BCG\VilloCity\Foo\Bar            -> src/Foo/Bar.php
 *   BCG\VilloCity\Modules\Baz\Thing  -> modules/Baz/Thing.php
 *
 * No Composer autoloader is needed at runtime; Composer is only used for dev tools
 * and to fetch the update-checker library into vendor/.
 */
spl_autoload_register(
	static function ( string $class_name ): void {
		$prefix = 'BCG\\VilloCity\\';
		if ( 0 !== strpos( $class_name, $prefix ) ) {
			return;
		}

		$relative = substr( $class_name, strlen( $prefix ) );

		if ( 0 === strpos( $relative, 'Modules\\' ) ) {
			$file = VILLOCITY_DIR . 'modules/' . str_replace( '\\', '/', substr( $relative, strlen( 'Modules\\' ) ) ) . '.php';
		} else {
			$file = VILLOCITY_DIR . 'src/' . str_replace( '\\', '/', $relative ) . '.php';
		}

		if ( is_readable( $file ) ) {
			require $file;
		}
	}
);

register_activation_hook( __FILE__, array( \BCG\VilloCity\Plugin::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( \BCG\VilloCity\Plugin::class, 'deactivate' ) );

add_action( 'plugins_loaded', array( \BCG\VilloCity\Plugin::instance(), 'boot' ) );
