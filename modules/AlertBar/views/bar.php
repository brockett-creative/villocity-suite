<?php
/**
 * Front-end alert bar markup.
 *
 * @package VilloCity
 *
 * @var array<string, mixed> $s       Settings.
 * @var string               $key     Content hash used to remember dismissal.
 * @var string               $icon    Severity icon SVG (trusted).
 * @var string               $x_icon  Close icon SVG (trusted).
 * @var array                $allowed Allowed HTML for the message.
 */

defined( 'ABSPATH' ) || exit;

$villocity_labels = array(
	'info'     => __( 'Site notice', 'villocity-suite' ),
	'warning'  => __( 'Warning', 'villocity-suite' ),
	'critical' => __( 'Emergency alert', 'villocity-suite' ),
);
?>
<div class="voc-alert-bar voc-alert-bar--<?php echo esc_attr( $s['severity'] ); ?>"
	role="region"
	aria-label="<?php echo esc_attr( $villocity_labels[ $s['severity'] ] ); ?>"
	data-voc-alert="<?php echo esc_attr( $key ); ?>"
	<?php echo $s['dismissible'] ? 'data-voc-dismissible' : ''; ?>>
	<div class="voc-alert-bar__inner">
		<?php echo $icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG from Icons. ?>
		<p class="voc-alert-bar__text"><?php echo wp_kses( $s['message'], $allowed ); ?></p>
		<?php if ( $s['button_text'] && $s['button_url'] ) : ?>
			<a class="voc-alert-bar__button" href="<?php echo esc_url( $s['button_url'] ); ?>"><?php echo esc_html( $s['button_text'] ); ?></a>
		<?php endif; ?>
	</div>
	<?php if ( $s['dismissible'] ) : ?>
		<button type="button" class="voc-alert-bar__dismiss" aria-label="<?php esc_attr_e( 'Dismiss notice', 'villocity-suite' ); ?>">
			<?php echo $x_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG from Icons. ?>
		</button>
	<?php endif; ?>
</div>
