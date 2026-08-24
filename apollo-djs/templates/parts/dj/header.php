<?php
/**
 * Template Part: DJ Single - Header
 *
 * @package Apollo\DJs
 */
defined( 'ABSPATH' ) || exit;
?>
<header class="dj-header">
	<div class="dj-header-left">
		<span><?php echo esc_html( apply_filters( 'apollo_dj_roster_label', 'Apollo::rio · DJ Roster' ) ); ?></span>
		<strong id="dj-header-name"><?php echo esc_html( strtoupper( $dj_name ) ); ?></strong>
	</div>
	<?php if ( ! empty( $media_kit_url ) ) : ?>
	<a href="<?php echo esc_url( $media_kit_url ); ?>" id="mediakit-link" class="dj-pill-link" target="_blank" rel="noopener noreferrer">
		<i class="ri-clipboard-line"></i> <?php esc_html_e( 'Media kit', 'apollo-djs' ); ?>
	</a>
	<?php endif; ?>
</header>
