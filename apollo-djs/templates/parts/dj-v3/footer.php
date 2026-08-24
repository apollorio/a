<?php
/**
 * DJ v3 Partial: Footer
 *
 * Simple footer with branding and links.
 * Variables: $dj_name, $media_kit_url, $rider_url.
 *
 * @package Apollo\DJs
 */

defined( 'ABSPATH' ) || exit;
?>

<footer class="dj-footer">
	<div class="dj-footer-logo">apollo::rio &copy; <?php echo esc_html( gmdate( 'Y' ) ); ?></div>

	<div class="dj-footer-links">
		<?php if ( $media_kit_url ) : ?>
			<a href="<?php echo esc_url( $media_kit_url ); ?>" target="_blank" rel="noopener">
				<?php esc_html_e( 'Press Kit', 'apollo-djs' ); ?>
			</a>
		<?php endif; ?>

		<?php if ( $rider_url ) : ?>
			<a href="<?php echo esc_url( $rider_url ); ?>" target="_blank" rel="noopener">
				<?php esc_html_e( 'Tech Rider', 'apollo-djs' ); ?>
			</a>
		<?php endif; ?>

		<a href="<?php echo esc_url( home_url( '/djs' ) ); ?>">
			<?php esc_html_e( 'Todos os DJs', 'apollo-djs' ); ?>
		</a>
	</div>

	<div class="dj-footer-copy">
		<?php esc_html_e( 'Powered by DJ Booster™', 'apollo-djs' ); ?>
	</div>
</footer>
