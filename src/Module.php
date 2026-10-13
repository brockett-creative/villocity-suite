<?php
/**
 * Base class every module extends.
 *
 * @package VilloCity
 */

namespace BCG\VilloCity;

defined( 'ABSPATH' ) || exit;

/**
 * A module is a self-contained feature (post type, shortcode, settings page...).
 *
 * Rules every module follows:
 *  - Nothing happens at file-load time. All hooks are added inside boot().
 *  - boot() is only called when the module is enabled on this site.
 *  - Assets are registered in boot() and enqueued only when actually rendered.
 *  - Anything scheduled or created in on_enable() is removed in on_disable().
 */
abstract class Module {

	/**
	 * Stable machine ID. Never change it once released: it is stored in each site's options.
	 */
	abstract public static function id(): string;

	/**
	 * Human-readable name shown on the Modules screen.
	 */
	abstract public static function label(): string;

	/**
	 * One-sentence description shown on the Modules screen.
	 */
	abstract public static function description(): string;

	/**
	 * IDs of other modules this one needs. The Modules screen enforces this.
	 *
	 * @return string[]
	 */
	public static function requires(): array {
		return array();
	}

	/**
	 * Shortcode tags this module provides. When the module is switched off, these
	 * still render as nothing instead of leaving "[tag]" visible in page content.
	 *
	 * @return string[]
	 */
	public static function shortcodes(): array {
		return array();
	}

	/**
	 * Register hooks. Called on plugins_loaded when the module is enabled.
	 */
	abstract public function boot(): void;

	/**
	 * Runs once when the module is switched on (also on plugin activation if already on).
	 */
	public function on_enable(): void {}

	/**
	 * Runs once when the module is switched off or the plugin is deactivated.
	 * Remove cron events here. Do NOT delete content; that is uninstall's job.
	 */
	public function on_disable(): void {}

	/**
	 * Absolute path inside this module's folder.
	 *
	 * @param string $relative Path relative to the module folder.
	 */
	protected function path( string $relative = '' ): string {
		return VILLOCITY_DIR . 'modules/' . $this->folder() . '/' . ltrim( $relative, '/' );
	}

	/**
	 * Public URL inside this module's folder.
	 *
	 * @param string $relative Path relative to the module folder.
	 */
	protected function url( string $relative = '' ): string {
		return VILLOCITY_URL . 'modules/' . $this->folder() . '/' . ltrim( $relative, '/' );
	}

	/**
	 * Asset version string: file modification time in dev, plugin version otherwise.
	 *
	 * @param string $relative Asset path relative to the module folder.
	 */
	protected function asset_version( string $relative ): string {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && file_exists( $this->path( $relative ) ) ) {
			return (string) filemtime( $this->path( $relative ) );
		}
		return VILLOCITY_VERSION;
	}

	/**
	 * Render a PHP template from this module's views/ folder and return the HTML.
	 *
	 * @param string               $template File name without .php.
	 * @param array<string, mixed> $vars     Variables made available to the template.
	 */
	protected function view( string $template, array $vars = array() ): string {
		$file = $this->path( 'views/' . $template . '.php' );
		if ( ! is_readable( $file ) ) {
			return '';
		}
		ob_start();
		( static function ( string $__file, array $__vars ): void {
			extract( $__vars, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- Template scope only.
			require $__file;
		} )( $file, $vars );
		return (string) ob_get_clean();
	}

	/**
	 * Folder name of the module (the namespace segment after Modules\).
	 */
	private function folder(): string {
		$parts = explode( '\\', static::class );
		$index = array_search( 'Modules', $parts, true );
		return false === $index ? '' : $parts[ $index + 1 ];
	}
}
