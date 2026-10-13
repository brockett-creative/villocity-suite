<?php
/**
 * VilloCity > Modules: switch modules on or off for this site.
 *
 * @package VilloCity
 */

namespace BCG\VilloCity\Admin;

use BCG\VilloCity\ModuleRegistry;

defined( 'ABSPATH' ) || exit;

/**
 * Module toggle screen. Saves through admin-post.php with a nonce and capability check.
 *
 * Who may toggle modules defaults to administrators. To restrict it to BCG staff only,
 * filter `villocity_manage_modules_capability`, or lock the list in wp-config.php
 * with VILLOCITY_MODULES.
 */
final class ModulesPage {

	public const SLUG    = 'villocity-modules';
	private const ACTION = 'villocity_save_modules';

	/**
	 * Registry.
	 *
	 * @var ModuleRegistry
	 */
	private ModuleRegistry $modules;

	/**
	 * Constructor.
	 *
	 * @param ModuleRegistry $modules Registry.
	 */
	public function __construct( ModuleRegistry $modules ) {
		$this->modules = $modules;
	}

	/**
	 * Capability required to change modules.
	 */
	public static function capability(): string {
		return (string) apply_filters( 'villocity_manage_modules_capability', 'manage_options' );
	}

	/**
	 * Hooks.
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'add_page' ), 20 );
		add_action( 'admin_post_' . self::ACTION, array( $this, 'save' ) );
	}

	/**
	 * Add submenu.
	 */
	public function add_page(): void {
		add_submenu_page(
			AdminMenu::SLUG,
			__( 'VilloCity Modules', 'villocity-suite' ),
			__( 'Modules', 'villocity-suite' ),
			self::capability(),
			self::SLUG,
			array( $this, 'render' )
		);
	}

	/**
	 * Handle the form post.
	 */
	public function save(): void {
		if ( ! current_user_can( self::capability() ) ) {
			wp_die( esc_html__( 'You are not allowed to change VilloCity modules.', 'villocity-suite' ), 403 );
		}
		check_admin_referer( self::ACTION );

		if ( $this->modules->is_locked() ) {
			wp_safe_redirect( admin_url( 'admin.php?page=' . self::SLUG . '&locked=1' ) );
			exit;
		}

		$requested = isset( $_POST['modules'] ) && is_array( $_POST['modules'] )
			? array_map( 'sanitize_key', wp_unslash( $_POST['modules'] ) )
			: array();

		$saved   = $this->modules->set_enabled( $requested );
		$dropped = array_diff( $requested, $saved );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => self::SLUG,
					'updated' => 1,
					'dropped' => $dropped ? implode( ',', $dropped ) : false,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Render the screen.
	 */
	public function render(): void {
		if ( ! current_user_can( self::capability() ) ) {
			return;
		}

		$enabled = $this->modules->enabled_ids();
		$locked  = $this->modules->is_locked();
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Display-only flags set by our own redirect.
		$updated = isset( $_GET['updated'] );
		$dropped = isset( $_GET['dropped'] ) ? array_filter( explode( ',', sanitize_text_field( wp_unslash( $_GET['dropped'] ) ) ) ) : array();
		// phpcs:enable
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'VilloCity Modules', 'villocity-suite' ); ?></h1>

			<?php if ( $updated ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Modules saved.', 'villocity-suite' ); ?></p></div>
			<?php endif; ?>

			<?php if ( $dropped ) : ?>
				<div class="notice notice-warning"><p>
					<?php
					printf(
						/* translators: %s: comma-separated module IDs. */
						esc_html__( 'Not enabled because a required module is off: %s', 'villocity-suite' ),
						esc_html( implode( ', ', $dropped ) )
					);
					?>
				</p></div>
			<?php endif; ?>

			<?php if ( $locked ) : ?>
				<div class="notice notice-info"><p>
					<?php esc_html_e( 'This site\'s modules are set in wp-config.php (VILLOCITY_MODULES) and cannot be changed here.', 'villocity-suite' ); ?>
				</p></div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION ); ?>">
				<?php wp_nonce_field( self::ACTION ); ?>

				<table class="widefat striped" style="max-width:900px">
					<thead>
						<tr>
							<td class="check-column"></td>
							<th scope="col"><?php esc_html_e( 'Module', 'villocity-suite' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Description', 'villocity-suite' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Requires', 'villocity-suite' ); ?></th>
						</tr>
					</thead>
					<tbody>
					<?php foreach ( $this->modules->all() as $id => $class ) : ?>
						<tr>
							<th scope="row" class="check-column">
								<input type="checkbox" id="voc-mod-<?php echo esc_attr( $id ); ?>" name="modules[]" value="<?php echo esc_attr( $id ); ?>"
									<?php checked( in_array( $id, $enabled, true ) ); ?> <?php disabled( $locked ); ?>>
							</th>
							<td><label for="voc-mod-<?php echo esc_attr( $id ); ?>"><strong><?php echo esc_html( $class::label() ); ?></strong></label><br><code><?php echo esc_html( $id ); ?></code></td>
							<td><?php echo esc_html( $class::description() ); ?></td>
							<td><?php echo esc_html( $class::requires() ? implode( ', ', $class::requires() ) : '—' ); ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>

				<?php
				if ( ! $locked ) {
					submit_button( __( 'Save modules', 'villocity-suite' ) );
				}
				?>
			</form>
		</div>
		<?php
	}
}
