<?php
/**
 * Single Event — Footer visual
 *
 * @package Apollo\Event
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$foot_img = $banner ?: '';
$foot_sub = trim( $meta_date . ( $loc_name ? ' · ' . $loc_name : '' ) . ' · Rio de Janeiro' );
?>
<!-- FOOTER -->
<footer class="ev-foot" data-ev="footer">
  <?php if ( $foot_img ) : ?>
  <img src="<?php echo esc_url( $foot_img ); ?>" alt="" loading="lazy">
  <?php endif; ?>
  <div class="ev-foot-ov">
    <small><?php echo esc_html( $foot_sub ); ?></small>
    <strong>
		<?php
		foreach ( $title_parts as $i => $line ) {
			echo esc_html( $line );
			if ( $i < count( $title_parts ) - 1 ) {
				echo '<br>';
			}
		}
		?>
	</strong>
  </div>
</footer>
