<?php
/**
 * Directory Part — Stats Strip
 *
 * Horizontal stats row below hero.
 * Expects: $total_comunas, $total_nucleos, $total_members, $total_active
 *
 * @package Apollo\Groups
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="stats-strip">
	<div class="stat">
		<span class="stat-num" id="statComunas"><?php echo esc_html( $total_comunas ); ?></span>
		<span class="stat-label">Comunas</span>
	</div>
	<div class="stat">
		<span class="stat-num" id="statNucleos"><?php echo esc_html( $total_nucleos ); ?></span>
		<span class="stat-label">Núcleos</span>
	</div>
	<div class="stat">
		<span class="stat-num" id="statMembers"><?php echo esc_html( $total_members ); ?></span>
		<span class="stat-label">Membros</span>
	</div>
	<div class="stat">
		<span class="stat-num" id="statActive"><?php echo esc_html( $total_active ); ?></span>
		<span class="stat-label">Ativas hoje</span>
	</div>
</div>
