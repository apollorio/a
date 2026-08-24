<?php
/**
 * Directory Part — Search Bar
 *
 * Search input + Create button.
 * Expects: $is_logged, $create_url, $is_nucleos
 *
 * @package Apollo\Groups
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;

$placeholder = $is_nucleos ? 'Buscar núcleos...' : 'Buscar comunas e núcleos...';
?>
<div class="wrap">
	<div class="search-bar g-fade">
		<input
			type="text"
			class="apollo-input search-input"
			id="searchInput"
			placeholder="<?php echo esc_attr( $placeholder ); ?>"
			autocomplete="off"
		>
		<?php if ( $is_logged ) : ?>
			<a href="<?php echo esc_url( $create_url ); ?>" class="btn-create">
				<i class="ri-add-line"></i> Criar <?php echo $is_nucleos ? 'Núcleo' : 'Comuna'; ?>
			</a>
		<?php endif; ?>
	</div>
</div>