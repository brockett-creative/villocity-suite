<?php
/**
 * Top-level "VilloCity" admin menu and the General settings screen.
 *
 * @package VilloCity
 */

namespace BCG\VilloCity\Admin;

use BCG\VilloCity\Branding;
use BCG\VilloCity\Plugin;

defined( 'ABSPATH' ) || exit;

/**
 * Owns the parent menu that every module's screens hang off (slug: villocity).
 */
final class AdminMenu {

	public const SLUG = 'villocity';

	/**
	 * Hooks.
	 */
	public function register(): void {
		// Priority 9 so the parent exists before modules add submenus at the default 10.
		add_action( 'admin_menu', array( $this, 'add_menu' ), 9 );
	}

	/**
	 * Add the top-level menu and its General page.
	 */
	public function add_menu(): void {
		add_menu_page(
			__( 'VilloCity', 'villocity-suite' ),
			__( 'VilloCity', 'villocity-suite' ),
			'manage_options',
			self::SLUG,
			array( $this, 'render' ),
			'dashicons-building',
			3
		);

		add_submenu_page(
			self::SLUG,
			__( 'VilloCity Settings', 'villocity-suite' ),
			__( 'Settings', 'villocity-suite' ),
			'manage_options',
			self::SLUG,
			array( $this, 'render' )
		);
	}

	/**
	 * Render the General settings screen.
	 */
	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$b       = Branding::get();
		$enabled = Plugin::instance()->modules()->enabled_ids();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'VilloCity Settings', 'villocity-suite' ); ?></h1>

			<p>
				<?php
				printf(
					/* translators: 1: plugin version, 2: number of enabled modules. */
					esc_html__( 'Version %1$s. %2$d module(s) enabled on this site.', 'villocity-suite' ),
					esc_html( VILLOCITY_VERSION ),
					count( $enabled )
				);
				?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . ModulesPage::SLUG ) ); ?>"><?php esc_html_e( 'Manage modules', 'villocity-suite' ); ?></a>
			</p>

			<form method="post" action="options.php">
				<?php settings_fields( 'villocity_general' ); ?>
				<h2><?php esc_html_e( 'Brand colours', 'villocity-suite' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="voc-primary"><?php esc_html_e( 'Primary colour', 'villocity-suite' ); ?></label></th>
						<td><input type="color" id="voc-primary" name="<?php echo esc_attr( Branding::OPTION ); ?>[primary]" value="<?php echo esc_attr( $b['primary'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="voc-accent"><?php esc_html_e( 'Accent colour', 'villocity-suite' ); ?></label></th>
						<td><input type="color" id="voc-accent" name="<?php echo esc_attr( Branding::OPTION ); ?>[accent]" value="<?php echo esc_attr( $b['accent'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Elementor', 'villocity-suite' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( Branding::OPTION ); ?>[inherit_elementor]" value="1" <?php checked( $b['inherit_elementor'] ); ?>>
								<?php esc_html_e( 'Use Elementor global colours when available (the colours above become the fallback).', 'villocity-suite' ); ?>
							</label>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
