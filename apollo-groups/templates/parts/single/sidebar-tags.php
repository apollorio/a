<?php
/**
 * Single Part — Sidebar Tags
 *
 * Tag pills section.
 * Expects: $group_tags (array|string comma-separated)
 *
 * @package Apollo\Groups
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;

// Parse tags
$tags = array();
if ( ! empty( $group_tags ) ) {
	if ( is_string( $group_tags ) ) {
		$tags = array_filter( array_map( 'trim', explode( ',', $group_tags ) ) );
	} elseif ( is_array( $group_tags ) ) {
		$tags = $group_tags;
	}
}

if ( empty( $tags ) ) {
	return;
}
?>
<div class="sb-section">
	<div class="sb-section-title"><i class="ri-exchange-box-line"></i> Tags</div>
	<div class="sb-tags">
		<?php foreach ( $tags as $tag ) : ?>
			<span class="sb-tag"><?php echo esc_html( $tag ); ?></span>
		<?php endforeach; ?>
	</div>
</div>
