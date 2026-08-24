<?php
/**
 * Single Part — Sidebar Rules
 *
 * Numbered rules list.
 * Expects: $group_rules (string, newline-separated lines)
 *
 * @package Apollo\Groups
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;

$rules_list = array();
if ( ! empty( $group_rules ) ) {
	$rules_list = array_filter( array_map( 'trim', explode( "\n", $group_rules ) ) );
}

if ( empty( $rules_list ) ) {
	return;
}
?>
<div class="sb-section">
	<div class="sb-section-title"><i class="ri-shield-check-line"></i> Regras</div>
	<div class="sb-rules">
		<?php foreach ( $rules_list as $i => $rule ) : ?>
			<div class="sb-rule">
				<span class="sb-rule-num"><?php echo esc_html( $i + 1 ); ?></span>
				<span><?php echo esc_html( $rule ); ?></span>
			</div>
		<?php endforeach; ?>
	</div>
</div>
