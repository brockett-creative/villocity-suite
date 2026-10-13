<?php
/**
 * Resource card grid.
 *
 * @package VilloCity
 *
 * @var array<int, array<string, mixed>> $items   Cards.
 * @var int                              $columns Max columns on wide screens.
 */

defined( 'ABSPATH' ) || exit;
?>
<ul class="voc-resources" style="--voc-resources-cols: <?php echo (int) $columns; ?>;">
	<?php foreach ( $items as $villocity_item ) : ?>
		<li class="voc-resources__card">
			<span class="voc-resources__icon"><?php echo $villocity_item['icon']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG from Icons. ?></span>
			<h3 class="voc-resources__title"><?php echo esc_html( $villocity_item['title'] ); ?></h3>
			<?php if ( $villocity_item['desc'] ) : ?>
				<p class="voc-resources__desc"><?php echo esc_html( $villocity_item['desc'] ); ?></p>
			<?php endif; ?>
			<?php if ( $villocity_item['url'] ) : ?>
				<a class="voc-resources__link" href="<?php echo esc_url( $villocity_item['url'] ); ?>"
					<?php echo $villocity_item['external'] ? 'target="_blank" rel="noopener"' : ''; ?>>
					<?php echo esc_html( $villocity_item['btn_text'] ); ?>
					<span class="screen-reader-text">
						<?php
						/* translators: %s: resource name. */
						printf( esc_html__( 'for %s', 'villocity-suite' ), esc_html( $villocity_item['title'] ) );
						if ( $villocity_item['external'] ) {
							echo ' ' . esc_html__( '(opens in a new tab)', 'villocity-suite' );
						}
						?>
					</span>
					<span aria-hidden="true">&rarr;</span>
				</a>
			<?php endif; ?>
		</li>
	<?php endforeach; ?>
</ul>
