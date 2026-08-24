<?php

/**
 * Template: Classifieds List
 *
 * Rendered by [apollo_classifieds] shortcode.
 * Override in theme: theme/apollo-adverts/list.php
 *
 * Available variables:
 *   $query       — WP_Query instance
 *   $search_form — Apollo\Adverts\Form instance (search scheme)
 *   $atts        — shortcode attributes
 *   $paged       — current page number
 *
 * Adapted from WPAdverts templates/list.php
 *
 * @package Apollo\Adverts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="apollo-adverts-archive">

	<!-- Cabeçalho -->
	<div class="apollo-adverts-archive-header">
		<h1><?php esc_html_e( 'Classificados', 'apollo-adverts' ); ?></h1>
		<p><?php esc_html_e( 'Ingressos · Revenda · Troca', 'apollo-adverts' ); ?></p>
	</div>

	<!-- Formulário de Busca -->
	<div class="apollo-adverts-search-form">
		<form method="get" action="">
			<div class="apollo-search-fields">
				<?php echo $search_form->render(); ?>
			</div>
			<div class="apollo-search-submit">
				<button type="submit" class="button"><span><?php esc_html_e( 'Buscar', 'apollo-adverts' ); ?></span></button>
			</div>
		</form>
	</div>

	<!-- Results Count -->
	<div class="apollo-adverts-results-header">
		<span class="apollo-adverts-count">
			<?php
			printf(
				esc_html( _n( '%d anúncio encontrado', '%d anúncios encontrados', $query->found_posts, 'apollo-adverts' ) ),
				$query->found_posts
			);
			?>
		</span>
	</div>

	<!-- Listings Grid -->
	<?php if ( $query->have_posts() ) : ?>
		<div class="apollo-adverts-list">
			<?php
			while ( $query->have_posts() ) :
				$query->the_post();
				?>
				<?php
				apollo_adverts_load_template(
					'list-item.php',
					array(
						'post_id' => get_the_ID(),
					)
				);
				?>
			<?php endwhile; ?>
		</div>

		<!-- Pagination -->
		<?php if ( $query->max_num_pages > 1 ) : ?>
			<div class="apollo-adverts-pagination">
				<?php
				echo wp_kses_post(
					paginate_links(
						array(
							'total'     => $query->max_num_pages,
							'current'   => $paged,
							'format'    => '?paged=%#%',
							'prev_text' => '&laquo;',
							'next_text' => '&raquo;',
						)
					)
				);
				?>
			</div>
		<?php endif; ?>

	<?php else : ?>
		<div class="apollo-adverts-empty">
			<p><?php esc_html_e( 'Nenhum anúncio encontrado. Tente ajustar seus filtros.', 'apollo-adverts' ); ?></p>
		</div>
	<?php endif; ?>

	<?php wp_reset_postdata(); ?>
</div>
