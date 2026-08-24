<?php
/**
 * Directory Part — Tabs
 *
 * Tab navigation: Todos, Comunas, Núcleos, Meus.
 * Expects: $is_nucleos, $is_logged, $total_comunas, $total_nucleos
 *
 * @package Apollo\Groups
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap">
	<div class="tabs" id="dirTabs">
		<button class="tab active" data-filter="all">
			Todos <span class="tab-count"><?php echo esc_html( ( $total_comunas ?? 0 ) + ( $total_nucleos ?? 0 ) ); ?></span>
		</button>
		<button class="tab" data-filter="comuna">
			Comunas <span class="tab-count"><?php echo esc_html( $total_comunas ?? 0 ); ?></span>
		</button>
		<button class="tab" data-filter="nucleo">
			Núcleos <span class="tab-count"><?php echo esc_html( $total_nucleos ?? 0 ); ?></span>
		</button>
		<?php if ( $is_logged ) : ?>
			<button class="tab" data-filter="my">
				Meus <span class="tab-count">—</span>
			</button>
		<?php endif; ?>
	</div>
</div>
