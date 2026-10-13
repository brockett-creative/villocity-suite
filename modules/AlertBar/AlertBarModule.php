<?php
/**
 * Alert Bar module: a site-wide notice banner with severity, optional button and schedule.
 *
 * @package VilloCity
 */

namespace BCG\VilloCity\Modules\AlertBar;

use BCG\VilloCity\Admin\AdminMenu;
use BCG\VilloCity\Module;
use BCG\VilloCity\Support\Icons;

defined( 'ABSPATH' ) || exit;

/**
 * Reference implementation of an options-based module (no custom post type).
 *
 * Differences from the 2.x version:
 *  - One option array instead of six separate options, with a migration from the old ones.
 *  - Can inject itself after <body> (the old "enable site-wide" checkbox did nothing
 *    without a shortcode), or stay shortcode-only.
 *  - Start/end scheduling actually exists.
 *  - CSS/JS are real files, enqueued only on pages that show the bar.
 *  - Dismissal is remembered per message, so a new alert re-appears for everyone.
 */
final class AlertBarModule extends Module {

	public const OPTION = 'villocity_alert_bar';
	private const PAGE  = 'villocity-alert-bar';

	/**
	 * Has the bar already been printed on this request?
	 *
	 * @var bool
	 */
	private bool $rendered = false;

	/**
	 * Module ID (matches 2.x).
	 */
	public static function id(): string {
		return 'alert_bar';
	}

	/**
	 * Label.
	 */
	public static function label(): string {
		return __( 'Alert Bar', 'villocity-suite' );
	}

	/**
	 * Description.
	 */
	public static function description(): string {
		return __( 'Accessible site-wide notice banner with severity levels, an optional button, and start/end scheduling.', 'villocity-suite' );
	}

	/**
	 * Shortcodes. [villocity_ticker] is kept as an alias for existing pages.
	 *
	 * @return string[]
	 */
	public static function shortcodes(): array {
		return array( 'villocity_alert_bar', 'villocity_ticker' );
	}

	/**
	 * Default settings.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults(): array {
		return array(
			'enabled'     => false,
			'message'     => '',
			'severity'    => 'info',
			'button_text' => '',
			'button_url'  => '',
			'placement'   => 'top',
			'starts_at'   => '',
			'ends_at'     => '',
			'dismissible' => true,
		);
	}

	/**
	 * Current settings.
	 *
	 * @return array<string, mixed>
	 */
	public static function settings(): array {
		$saved = get_option( self::OPTION, array() );
		return array_merge( self::defaults(), is_array( $saved ) ? $saved : array() );
	}

	/**
	 * Hooks.
	 */
	public function boot(): void {
		add_action( 'init', array( $this, 'register_setting' ) );
		add_action( 'init', array( $this, 'register_shortcodes' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
		add_action( 'wp_body_open', array( $this, 'maybe_print_top' ), 5 );

		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		}
	}

	/**
	 * First time the module is switched on: carry over the 2.x settings if present.
	 */
	public function on_enable(): void {
		if ( false !== get_option( self::OPTION, false ) ) {
			return;
		}

		$legacy_text = get_option( 'villocity_alert_text', false );
		if ( false === $legacy_text ) {
			return;
		}

		add_option(
			self::OPTION,
			self::sanitize(
				array(
					'enabled'     => 'yes' === get_option( 'villocity_alert_enabled', 'no' ),
					'message'     => $legacy_text,
					'severity'    => get_option( 'villocity_alert_type', 'info' ),
					'button_text' => get_option( 'villocity_alert_btn_text', '' ),
					'button_url'  => get_option( 'villocity_alert_btn_url', '' ),
					// 2.x only ever rendered via shortcode, so keep that behaviour.
					'placement'   => 'shortcode',
					'dismissible' => true,
				)
			)
		);
	}

	/**
	 * Register the option and its sanitizer.
	 */
	public function register_setting(): void {
		register_setting(
			self::PAGE,
			self::OPTION,
			array(
				'type'              => 'object',
				'sanitize_callback' => array( self::class, 'sanitize' ),
				'default'           => self::defaults(),
			)
		);
	}

	/**
	 * Sanitize settings.
	 *
	 * @param mixed $input Raw input.
	 * @return array<string, mixed>
	 */
	public static function sanitize( $input ): array {
		$input = is_array( $input ) ? $input : array();

		$severity  = in_array( $input['severity'] ?? '', array( 'info', 'warning', 'critical' ), true ) ? $input['severity'] : 'info';
		$placement = in_array( $input['placement'] ?? '', array( 'top', 'shortcode' ), true ) ? $input['placement'] : 'top';

		return array(
			'enabled'     => ! empty( $input['enabled'] ),
			'message'     => wp_kses( (string) ( $input['message'] ?? '' ), self::allowed_html() ),
			'severity'    => $severity,
			'button_text' => sanitize_text_field( (string) ( $input['button_text'] ?? '' ) ),
			'button_url'  => esc_url_raw( (string) ( $input['button_url'] ?? '' ) ),
			'placement'   => $placement,
			'starts_at'   => self::sanitize_datetime( $input['starts_at'] ?? '' ),
			'ends_at'     => self::sanitize_datetime( $input['ends_at'] ?? '' ),
			'dismissible' => ! empty( $input['dismissible'] ),
		);
	}

	/**
	 * HTML allowed inside the message.
	 *
	 * @return array<string, array<string, bool>>
	 */
	private static function allowed_html(): array {
		return array(
			'a'      => array(
				'href'   => true,
				'target' => true,
				'rel'    => true,
			),
			'strong' => array(),
			'em'     => array(),
			'br'     => array(),
		);
	}

	/**
	 * Accept the value of an <input type="datetime-local"> (site timezone) or ''.
	 *
	 * @param mixed $value Raw value.
	 */
	private static function sanitize_datetime( $value ): string {
		$value = sanitize_text_field( (string) $value );
		return preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $value ) ? $value : '';
	}

	/**
	 * Is the bar enabled, non-empty and inside its schedule right now?
	 *
	 * @param array<string, mixed> $s Settings.
	 */
	public static function is_live( array $s ): bool {
		if ( ! $s['enabled'] || '' === trim( wp_strip_all_tags( $s['message'] ) ) ) {
			return false;
		}

		$tz  = wp_timezone();
		$now = new \DateTimeImmutable( 'now', $tz );

		if ( $s['starts_at'] && $now < new \DateTimeImmutable( $s['starts_at'], $tz ) ) {
			return false;
		}
		if ( $s['ends_at'] && $now >= new \DateTimeImmutable( $s['ends_at'], $tz ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Register shortcodes.
	 */
	public function register_shortcodes(): void {
		foreach ( self::shortcodes() as $tag ) {
			add_shortcode( $tag, array( $this, 'shortcode' ) );
		}
	}

	/**
	 * Register (not enqueue) front-end assets.
	 */
	public function register_assets(): void {
		wp_register_style( 'villocity-alert-bar', $this->url( 'assets/alert-bar.css' ), array(), $this->asset_version( 'assets/alert-bar.css' ) );
		wp_register_script(
			'villocity-alert-bar',
			$this->url( 'assets/alert-bar.js' ),
			array(),
			$this->asset_version( 'assets/alert-bar.js' ),
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);

		// When the bar is auto-inserted, load its CSS in <head> to avoid a flash of unstyled content.
		$s = self::settings();
		if ( 'top' === $s['placement'] && self::is_live( $s ) ) {
			wp_enqueue_style( 'villocity-alert-bar' );
		}
	}

	/**
	 * Print right after <body> when placement is "top".
	 */
	public function maybe_print_top(): void {
		$s = self::settings();
		if ( 'top' === $s['placement'] ) {
			echo $this->render( $s ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in the view.
		}
	}

	/**
	 * Shortcode callback.
	 *
	 * @return string
	 */
	public function shortcode(): string {
		return $this->render( self::settings() );
	}

	/**
	 * Build the bar HTML (once per request).
	 *
	 * @param array<string, mixed> $s Settings.
	 */
	private function render( array $s ): string {
		if ( $this->rendered || ! self::is_live( $s ) ) {
			return '';
		}
		$this->rendered = true;

		wp_enqueue_style( 'villocity-alert-bar' );
		if ( $s['dismissible'] ) {
			wp_enqueue_script( 'villocity-alert-bar' );
		}

		return $this->view(
			'bar',
			array(
				's'       => $s,
				'key'     => substr( md5( $s['message'] . '|' . $s['severity'] . '|' . $s['starts_at'] ), 0, 10 ),
				'icon'    => Icons::svg( 'info' === $s['severity'] ? 'info' : 'alert' ),
				'x_icon'  => Icons::svg( 'x' ),
				'allowed' => self::allowed_html(),
			)
		);
	}

	/**
	 * Admin page under VilloCity.
	 */
	public function add_settings_page(): void {
		add_submenu_page(
			AdminMenu::SLUG,
			__( 'Alert Bar', 'villocity-suite' ),
			__( 'Alert Bar', 'villocity-suite' ),
			'manage_options',
			self::PAGE,
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Render the settings form.
	 */
	public function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$html = $this->view(
			'settings',
			array(
				's'      => self::settings(),
				'option' => self::OPTION,
				'group'  => self::PAGE,
				'live'   => self::is_live( self::settings() ),
			)
		);
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in the view.
	}
}
