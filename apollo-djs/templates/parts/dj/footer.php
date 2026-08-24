<?php
/**
 * Template Part: DJ Single - Footer
 *
 * @package Apollo\DJs
 */
defined( 'ABSPATH' ) || exit;
?>
<footer class="dj-footer">
	<span>
		<?php echo esc_html( apply_filters( 'apollo_dj_footer_brand', 'Apollo::rio' ) ); ?><br>
		<?php esc_html_e( 'Roster preview', 'apollo-djs' ); ?>
	</span>
	<span>
		<?php esc_html_e( 'Para bookers,', 'apollo-djs' ); ?><br>
		<?php esc_html_e( 'selos e clubes', 'apollo-djs' ); ?>
	</span>
</footer>
