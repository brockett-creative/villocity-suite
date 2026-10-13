<?php
/**
 * Knows which modules exist, which are switched on for this site, and boots them.
 *
 * @package VilloCity
 */

namespace BCG\VilloCity;

defined( 'ABSPATH' ) || exit;

/**
 * Per-site module switchboard.
 *
 * Which modules are on is stored in the `villocity_enabled_modules` option, unless
 * the site's wp-config.php defines VILLOCITY_MODULES, in which case that list wins
 * and the toggles on the Modules screen are locked. Example:
 *
 *     define( 'VILLOCITY_MODULES', 'alert_bar,resources' );
 */
final class ModuleRegistry {

	public const OPTION     = 'villocity_enabled_modules';
	public const FLUSH_FLAG = 'villocity_flush_rewrite_rules';

	/**
	 * Option used by the Gemini-built 2.x plugin.
	 */
	private const LEGACY_OPTION = 'villocity_active_modules';

	/**
	 * Map of module ID => class name.
	 *
	 * @var array<string, class-string<Module>>
	 */
	private array $classes = array();

	/**
	 * Booted module instances, keyed by ID.
	 *
	 * @var array<string, Module>
	 */
	private array $booted = array();

	/**
	 * Constructor.
	 *
	 * @param array<int, class-string<Module>> $module_classes Classes from config/modules.php.
	 */
	public function __construct( array $module_classes ) {
		foreach ( $module_classes as $class ) {
			if ( is_subclass_of( $class, Module::class ) ) {
				$this->classes[ $class::id() ] = $class;
			}
		}
	}

	/**
	 * Every module this build of the plugin ships, keyed by ID.
	 *
	 * @return array<string, class-string<Module>>
	 */
	public function all(): array {
		return $this->classes;
	}

	/**
	 * True when wp-config.php controls the module list.
	 */
	public function is_locked(): bool {
		return defined( 'VILLOCITY_MODULES' );
	}

	/**
	 * IDs of modules that should run on this site, with dependencies satisfied.
	 *
	 * @return string[]
	 */
	public function enabled_ids(): array {
		if ( $this->is_locked() ) {
			$raw = constant( 'VILLOCITY_MODULES' );
			$ids = is_array( $raw ) ? $raw : explode( ',', (string) $raw );
		} else {
			$ids = (array) get_option( self::OPTION, array() );
		}

		/**
		 * Filter the enabled module IDs for this site.
		 *
		 * @param string[] $ids Module IDs.
		 */
		$ids = (array) apply_filters( 'villocity_enabled_modules', array_map( 'trim', array_map( 'strval', $ids ) ) );

		return $this->resolve( $ids );
	}

	/**
	 * Is a given module enabled?
	 *
	 * @param string $id Module ID.
	 */
	public function is_enabled( string $id ): bool {
		return in_array( $id, $this->enabled_ids(), true );
	}

	/**
	 * Instantiate and boot every enabled module.
	 */
	public function boot_enabled(): void {
		foreach ( $this->enabled_ids() as $id ) {
			$this->booted[ $id ] = new $this->classes[ $id ]();
			$this->booted[ $id ]->boot();
		}

		// Post types are registered on init (priority 10); flush afterwards if something changed.
		add_action( 'init', array( $this, 'maybe_flush_rewrite_rules' ), 999 );
		add_action( 'init', array( $this, 'silence_disabled_shortcodes' ), 999 );
	}

	/**
	 * Shortcodes from switched-off modules output nothing rather than raw "[tag]" text.
	 */
	public function silence_disabled_shortcodes(): void {
		foreach ( array_diff_key( $this->classes, $this->booted ) as $class ) {
			foreach ( $class::shortcodes() as $tag ) {
				if ( ! shortcode_exists( $tag ) ) {
					add_shortcode( $tag, '__return_empty_string' );
				}
			}
		}
	}

	/**
	 * Flush rewrite rules once after modules were switched on or off.
	 */
	public function maybe_flush_rewrite_rules(): void {
		if ( get_option( self::FLUSH_FLAG ) ) {
			delete_option( self::FLUSH_FLAG );
			flush_rewrite_rules();
		}
	}

	/**
	 * Save a new set of enabled modules, running enable/disable callbacks for the difference.
	 *
	 * @param string[] $requested Module IDs the admin ticked.
	 * @return string[] The IDs actually saved (unknown IDs and unmet dependencies dropped).
	 */
	public function set_enabled( array $requested ): array {
		$before = $this->enabled_ids();
		$after  = $this->resolve( $requested );

		foreach ( array_diff( $before, $after ) as $id ) {
			( new $this->classes[ $id ]() )->on_disable();
		}
		foreach ( array_diff( $after, $before ) as $id ) {
			( new $this->classes[ $id ]() )->on_enable();
		}

		update_option( self::OPTION, array_values( $after ) );
		update_option( self::FLUSH_FLAG, 1, false );

		return $after;
	}

	/**
	 * Copy the enabled-module list from the 2.x plugin, for modules that have been ported.
	 * Runs once (see Plugin::maybe_upgrade()).
	 */
	public function migrate_from_legacy(): void {
		if ( false !== get_option( self::OPTION, false ) ) {
			return;
		}

		$legacy = get_option( self::LEGACY_OPTION, false );
		if ( ! is_array( $legacy ) ) {
			// Fresh install: start with nothing switched on.
			add_option( self::OPTION, array() );
			return;
		}

		$ported = array_values( array_intersect( array_map( 'strval', $legacy ), array_keys( $this->classes ) ) );
		add_option( self::OPTION, array() );
		$this->set_enabled( $ported );
	}

	/**
	 * Plugin deactivated: let enabled modules clean up, but remember the selection.
	 */
	public function disable_all_for_deactivation(): void {
		foreach ( $this->enabled_ids() as $id ) {
			( new $this->classes[ $id ]() )->on_disable();
		}
	}

	/**
	 * Plugin (re)activated: let enabled modules set themselves up again.
	 */
	public function enable_all_for_activation(): void {
		foreach ( $this->enabled_ids() as $id ) {
			( new $this->classes[ $id ]() )->on_enable();
		}
	}

	/**
	 * Drop unknown IDs and any module whose dependencies are not also enabled.
	 *
	 * @param string[] $ids Requested IDs.
	 * @return string[]
	 */
	private function resolve( array $ids ): array {
		$ids = array_values( array_unique( array_intersect( $ids, array_keys( $this->classes ) ) ) );

		do {
			$changed = false;
			foreach ( $ids as $key => $id ) {
				$missing = array_diff( $this->classes[ $id ]::requires(), $ids );
				if ( $missing ) {
					unset( $ids[ $key ] );
					$changed = true;
				}
			}
		} while ( $changed );

		return array_values( $ids );
	}
}
