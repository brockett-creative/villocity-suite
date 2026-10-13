<?php
/**
 * Alert Bar settings screen.
 *
 * @package VilloCity
 *
 * @var array<string, mixed> $s      Settings.
 * @var string               $option Option name.
 * @var string               $group  Settings group.
 * @var bool                 $live   Whether the bar is showing right now.
 */

defined( 'ABSPATH' ) || exit;

$villocity_name = static function ( string $field ) use ( $option ): string {
	return $option . '[' . $field . ']';
};
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Alert Bar', 'villocity-suite' ); ?></h1>

	<p>
		<?php if ( $live ) : ?>
			<span class="dashicons dashicons-yes-alt" style="color:#008a20" aria-hidden="true"></span>
			<?php esc_html_e( 'The alert bar is showing on the site right now.', 'villocity-suite' ); ?>
		<?php else : ?>
			<span class="dashicons dashicons-hidden" aria-hidden="true"></span>
			<?php esc_html_e( 'The alert bar is not showing right now (off, empty, or outside its schedule).', 'villocity-suite' ); ?>
		<?php endif; ?>
	</p>

	<form method="post" action="options.php">
		<?php settings_fields( $group ); ?>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Status', 'villocity-suite' ); ?></th>
				<td><label><input type="checkbox" name="<?php echo esc_attr( $villocity_name( 'enabled' ) ); ?>" value="1" <?php checked( $s['enabled'] ); ?>> <?php esc_html_e( 'Show the alert bar', 'villocity-suite' ); ?></label></td>
			</tr>
			<tr>
				<th scope="row"><label for="voc-ab-message"><?php esc_html_e( 'Message', 'villocity-suite' ); ?></label></th>
				<td>
					<textarea id="voc-ab-message" class="large-text" rows="3" name="<?php echo esc_attr( $villocity_name( 'message' ) ); ?>"><?php echo esc_textarea( $s['message'] ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Links, bold and italics are allowed.', 'villocity-suite' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="voc-ab-severity"><?php esc_html_e( 'Severity', 'villocity-suite' ); ?></label></th>
				<td>
					<select id="voc-ab-severity" name="<?php echo esc_attr( $villocity_name( 'severity' ) ); ?>">
						<option value="info" <?php selected( $s['severity'], 'info' ); ?>><?php esc_html_e( 'Information (brand colour)', 'villocity-suite' ); ?></option>
						<option value="warning" <?php selected( $s['severity'], 'warning' ); ?>><?php esc_html_e( 'Warning (amber)', 'villocity-suite' ); ?></option>
						<option value="critical" <?php selected( $s['severity'], 'critical' ); ?>><?php esc_html_e( 'Emergency (red)', 'villocity-suite' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="voc-ab-btn-text"><?php esc_html_e( 'Button', 'villocity-suite' ); ?></label></th>
				<td>
					<input id="voc-ab-btn-text" type="text" class="regular-text" placeholder="<?php esc_attr_e( 'Label, e.g. Learn more', 'villocity-suite' ); ?>" name="<?php echo esc_attr( $villocity_name( 'button_text' ) ); ?>" value="<?php echo esc_attr( $s['button_text'] ); ?>">
					<input type="url" class="regular-text" placeholder="https://" aria-label="<?php esc_attr_e( 'Button link', 'villocity-suite' ); ?>" name="<?php echo esc_attr( $villocity_name( 'button_url' ) ); ?>" value="<?php echo esc_attr( $s['button_url'] ); ?>">
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Schedule', 'villocity-suite' ); ?></th>
				<td>
					<label><?php esc_html_e( 'Start', 'villocity-suite' ); ?> <input type="datetime-local" name="<?php echo esc_attr( $villocity_name( 'starts_at' ) ); ?>" value="<?php echo esc_attr( $s['starts_at'] ); ?>"></label>
					&nbsp;
					<label><?php esc_html_e( 'End', 'villocity-suite' ); ?> <input type="datetime-local" name="<?php echo esc_attr( $villocity_name( 'ends_at' ) ); ?>" value="<?php echo esc_attr( $s['ends_at'] ); ?>"></label>
					<p class="description">
						<?php
						printf(
							/* translators: %s: site timezone name. */
							esc_html__( 'Optional. Times are in the site timezone (%s). If the site uses page caching, the change appears when the cache next refreshes.', 'villocity-suite' ),
							esc_html( wp_timezone_string() )
						);
						?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Placement', 'villocity-suite' ); ?></th>
				<td>
					<fieldset>
						<label><input type="radio" name="<?php echo esc_attr( $villocity_name( 'placement' ) ); ?>" value="top" <?php checked( $s['placement'], 'top' ); ?>> <?php esc_html_e( 'Automatically at the top of every page', 'villocity-suite' ); ?></label><br>
						<label><input type="radio" name="<?php echo esc_attr( $villocity_name( 'placement' ) ); ?>" value="shortcode" <?php checked( $s['placement'], 'shortcode' ); ?>> <?php esc_html_e( 'Only where the [villocity_alert_bar] shortcode is placed (e.g. an Elementor header template)', 'villocity-suite' ); ?></label>
					</fieldset>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Visitors', 'villocity-suite' ); ?></th>
				<td><label><input type="checkbox" name="<?php echo esc_attr( $villocity_name( 'dismissible' ) ); ?>" value="1" <?php checked( $s['dismissible'] ); ?>> <?php esc_html_e( 'Let visitors close the bar (it returns when the message changes)', 'villocity-suite' ); ?></label></td>
			</tr>
		</table>
		<?php submit_button(); ?>
	</form>
</div>
