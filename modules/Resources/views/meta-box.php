<?php
/**
 * Resource meta box fields.
 *
 * @package VilloCity
 *
 * @var string                $url      Link URL.
 * @var string                $btn_text Button text.
 * @var string                $icon     Selected icon.
 * @var array<string, string> $choices  Icon choices.
 */

defined( 'ABSPATH' ) || exit;
?>
<p>
	<label for="voc-resource-url"><strong><?php esc_html_e( 'Link URL', 'villocity-suite' ); ?></strong></label><br>
	<input type="url" id="voc-resource-url" name="_resource_url" class="widefat" placeholder="https://" value="<?php echo esc_attr( $url ); ?>">
</p>
<p>
	<label for="voc-resource-btn"><strong><?php esc_html_e( 'Button text', 'villocity-suite' ); ?></strong></label><br>
	<input type="text" id="voc-resource-btn" name="_resource_btn_text" class="widefat" placeholder="<?php esc_attr_e( 'Visit website', 'villocity-suite' ); ?>" value="<?php echo esc_attr( $btn_text ); ?>">
</p>
<p>
	<label for="voc-resource-icon"><strong><?php esc_html_e( 'Icon', 'villocity-suite' ); ?></strong></label><br>
	<select id="voc-resource-icon" name="_resource_icon">
		<?php foreach ( $choices as $villocity_value => $villocity_label ) : ?>
			<option value="<?php echo esc_attr( $villocity_value ); ?>" <?php selected( $icon, $villocity_value ); ?>><?php echo esc_html( $villocity_label ); ?></option>
		<?php endforeach; ?>
	</select>
</p>
<p class="description"><?php esc_html_e( 'The main text box above is the short description shown on the card. Use "Order" in Page Attributes to sort cards.', 'villocity-suite' ); ?></p>
