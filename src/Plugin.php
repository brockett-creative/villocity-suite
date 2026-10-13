<?php
/**
 * Plugin bootstrap.
 *
 * @package VilloCity
 */

namespace BCG\VilloCity;

use BCG\VilloCity\Admin\AdminMenu;
use BCG\VilloCity\Admin\ModulesPage;

defined( 'ABSPATH' ) || exit;

/**
 * Wires the core services together. Holds no feature logic of its own:
 * every feature lives in a module under modules/.
 */
final class Plugin {

	/**
	 * Option that records the schema/data version, used for one-time upgrades.
	 */
	public const DB_VERSION_OPTION = 'villocity_db_version';

	/**
	 * Bump when an upgrade routine is added to maybe_upgrade().
	 */
	public const DB_VERSION = 1;

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * Module registry.
	 *
	 * @var ModuleRegistry
	 */
	private ModuleRegistry $modules;

	/**
	 * Get the shared instance.
	 */
	public static function instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor. Use instance().
	 */
	private function __construct() {
		$this->modules = new ModuleRegistry( require VILLOCITY_DIR . 'config/modules.php' );
	}

	/**
	 * Access the module registry (e.g. from admin screens).
	 */
	public function modules(): ModuleRegistry {
		return $this->modules;
	}

	/**
	 * Runs on plugins_loaded.
	 */
	public function boot(): void {
		add_action( 'init', array( $this, 'load_textdomain' ), 0 );

		$this->maybe_upgrade();

		( new Updater() )->register();
		( new Branding() )->register();

		if ( is_admin() ) {
			( new AdminMenu() )->register();
			( new ModulesPage( $this->modules ) )->register();
		}

		$this->modules->boot_enabled();
	}

	/**
	 * Load translations (on init, as WordPress 6.7+ expects).
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain( 'villocity-suite', false, dirname( plugin_basename( VILLOCITY_FILE ) ) . '/languages' );
	}

	/**
	 * One-time data upgrades, keyed off DB_VERSION.
	 */
	private function maybe_upgrade(): void {
		$installed = (int) get_option( self::DB_VERSION_OPTION, 0 );
		if ( $installed >= self::DB_VERSION ) {
			return;
		}

		if ( $installed < 1 ) {
			$this->modules->migrate_from_legacy();
		}

		update_option( self::DB_VERSION_OPTION, self::DB_VERSION, false );
	}

	/**
	 * Activation hook.
	 */
	public static function activate(): void {
		self::instance()->modules->enable_all_for_activation();

		// Modules register their post types on init; flush on the next request once they exist.
		update_option( ModuleRegistry::FLUSH_FLAG, 1, false );
	}

	/**
	 * Deactivation hook: let every enabled module clean up (cron events etc.).
	 */
	public static function deactivate(): void {
		self::instance()->modules->disable_all_for_deactivation();
		flush_rewrite_rules();
	}
}
