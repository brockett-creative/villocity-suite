<?php
/**
 * Site-wide brand colours shared by every module, exposed as CSS custom properties.
 *
 * @package VilloCity
 */

namespace BCG\VilloCity;

defined( 'ABSPATH' ) || exit;

/**
 * Stores two brand colours and prints them as --voc-* variables.
 *
 * Module stylesheets only ever reference var(--voc-primary) etc., never the option,
 * so there is exactly one place colours come from.
 */
final class Branding {

	public const OPTION = 'villocity_branding';

	/**
	 * Default values.
	 *
	 * @return array{primary: string, accent: string, inherit_elementor: bool}
	 */
	public static function defaults(): array {
		return array(
			'primary'           => '#860038',
			'accent'            => '#fadceb',
			'inherit_elementor' => true,
		);
	}

	/**
	 * Current settings merged over defaults.
	 *
	 * @return array{primary: string, accent: string, inherit_elementor: bool}
	 */
	public static function get(): array {
		$saved = get_option( self::OPTION, array() );
		return array_merge( self::defaults(), is_array( $saved ) ? $saved : array() );
	}

	/**
	 * Hooks.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_setting' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'print_variables' ), 1 );
	}

	/**
	 * Register the option with a sanitizer (used by the General settings form).
	 */
	public function register_setting(): void {
		register_setting(
			'villocity_general',
			self::OPTION,
			array(
				'type'              => 'object',
				'sanitize_callback' => array( self::class, 'sanitize' ),
				'default'           => self::defaults(),
			)
		);
	}

	/**
	 * Sanitize submitted values.
	 *
	 * @param mixed $input Raw input.
	 * @return array{primary: string, accent: string, inherit_elementor: bool}
	 */
	public static function sanitize( $input ): array {
		$input    = is_array( $input ) ? $input : array();
		$defaults = self::defaults();

		return array(
			'primary'           => self::hex_or( $input['primary'] ?? '', $defaults['primary'] ),
			'accent'            => self::hex_or( $input['accent'] ?? '', $defaults['accent'] ),
			'inherit_elementor' => ! empty( $input['inherit_elementor'] ),
		);
	}

	/**
	 * A valid hex colour, or the fallback (sanitize_hex_color returns '' or null when invalid).
	 *
	 * @param mixed  $value    Raw value.
	 * @param string $fallback Fallback colour.
	 */
	private static function hex_or( $value, string $fallback ): string {
		$color = sanitize_hex_color( (string) $value );
		return $color ? $color : $fallback;
	}

	/**
	 * Attach the variables to a tiny registered stylesheet handle so they are cacheable
	 * and can be depended on by module styles.
	 */
	public function print_variables(): void {
		$b       = self::get();
		$primary = $b['inherit_elementor'] ? 'var(--e-global-color-primary, ' . $b['primary'] . ')' : $b['primary'];
		$accent  = $b['inherit_elementor'] ? 'var(--e-global-color-accent, ' . $b['accent'] . ')' : $b['accent'];

		wp_register_style( 'villocity-base', false, array(), VILLOCITY_VERSION );
		wp_enqueue_style( 'villocity-base' );
		wp_add_inline_style(
			'villocity-base',
			sprintf( ':root{--voc-primary:%s;--voc-accent:%s;}', esc_attr( $primary ), esc_attr( $accent ) )
		);
	}
}
