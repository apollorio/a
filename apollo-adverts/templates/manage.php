<?php

/**
 * Template: Manage Classifieds (User)
 *
 * Standalone "Meus Anúncios" template.
 * Override in theme: theme/apollo-adverts/manage.php
 *
 * Available variables:
 *   $ads       — WP_Query (user's classifieds)
 *   $user_id   — int
 *   $paged     — int
 *   $statuses  — array (available status labels)
 *
 * @package Apollo\Adverts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$submit_page = get_option( 'apollo_adverts_submit_page_id', 0 );

$statuses = array(
	'publish' => __( 'Ativo', 'apollo-adverts' ),
	'pending' => __( 'Pendente', 'apollo-adverts' ),
	'draft'   => __( 'Rascunho', 'apollo-adverts' ),
	'expired' => __( 'Expirado', 'apollo-adverts' ),
);
?>

<div class="apollo-adverts-manage-wrap">

	<div class="apollo-adverts-manage-header">
		<h2><?php esc_html_e( 'Meus Anúncios', 'apollo-adverts' ); ?></h2>
		<?php if ( $submit_page ) : ?>
			<a href="<?php echo esc_url( get_permalink( $submit_page ) ); ?>" class="button button-primary">
				<span><i class="ri-add-line" style="margin-right:4px;"></i><?php esc_html_e( 'Criar Anúncio', 'apollo-adverts' ); ?></span>
			</a>
		<?php endif; ?>
	</div>

	<?php if ( $ads->have_posts() ) : ?>

		<table class="apollo-adverts-manage-table">
			<thead>
				<tr>
					<th class="col-image"><?php esc_html_e( 'Imagem', 'apollo-adverts' ); ?></th>
					<th class="col-title"><?php esc_html_e( 'Título', 'apollo-adverts' ); ?></th>
					<th class="col-price"><?php esc_html_e( 'Valor Ref.', 'apollo-adverts' ); ?></th>
					<th class="col-status"><?php esc_html_e( 'Status', 'apollo-adverts' ); ?></th>
					<th class="col-expires"><?php esc_html_e( 'Expira em', 'apollo-adverts' ); ?></th>
					<th class="col-views"><?php esc_html_e( 'Views', 'apollo-adverts' ); ?></th>
					<th class="col-actions"><?php esc_html_e( 'Ações', 'apollo-adverts' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				while ( $ads->have_posts() ) :
					$ads->the_post();
					?>
					<?php
					$ad_id      = get_the_ID();
					$status     = get_post_status();
					$status_lbl = $statuses[ $status ] ?? ucfirst( $status );
					$price      = apollo_adverts_get_the_price( $ad_id );
					$img        = apollo_adverts_get_main_image( $ad_id, 'classified-thumb' );
					$expires    = get_post_meta( $ad_id, '_classified_expires_at', true );
					$view_count = (int) get_post_meta( $ad_id, '_classified_views', true );
					$is_expired = apollo_adverts_is_expired( $ad_id );
					?>
					<tr class="status-<?php echo esc_attr( $status ); ?>">
						<td class="col-image">
							<?php if ( $img ) : ?>
								<img src="<?php echo esc_url( $img ); ?>" alt="" width="60" height="60" />
							<?php else : ?>
								<span class="no-image"><i class="ri-image-line"></i></span>
							<?php endif; ?>
						</td>
						<td class="col-title">
							<a href="<?php echo esc_url( get_permalink( $ad_id ) ); ?>">
								<?php echo esc_html( get_the_title( $ad_id ) ); ?>
							</a>
							<span class="row-date"><?php echo esc_html( get_the_date( 'd/m/Y', $ad_id ) ); ?></span>
						</td>
						<td class="col-price"><?php echo $price ? esc_html( $price ) : '—'; ?></td>
						<td class="col-status">
							<span class="status-badge status-<?php echo esc_attr( $status ); ?>">
								<?php echo esc_html( $status_lbl ); ?>
							</span>
						</td>
						<td class="col-expires">
							<?php
							if ( $expires ) {
								echo esc_html( $expires );
							} else {
								echo '—';
							}
							?>
						</td>
						<td class="col-views"><?php echo esc_html( (string) $view_count ); ?></td>
						<td class="col-actions">
							<?php if ( $submit_page ) : ?>
								<a href="<?php echo esc_url( add_query_arg( 'edit', $ad_id, get_permalink( $submit_page ) ) ); ?>" class="button button-small" title="<?php esc_attr_e( 'Editar', 'apollo-adverts' ); ?>">
									<i class="ri-edit-line"></i>
								</a>
							<?php endif; ?>

							<?php if ( $status === 'publish' ) : ?>
								<button type="button" class="button button-small apollo-toggle-status" data-id="<?php echo (int) $ad_id; ?>" data-status="draft" title="<?php esc_attr_e( 'Pausar', 'apollo-adverts' ); ?>">
									<i class="ri-eye-off-line"></i>
								</button>
							<?php elseif ( $status === 'draft' ) : ?>
								<button type="button" class="button button-small apollo-toggle-status" data-id="<?php echo (int) $ad_id; ?>" data-status="publish" title="<?php esc_attr_e( 'Ativar', 'apollo-adverts' ); ?>">
									<i class="ri-eye-line"></i>
								</button>
							<?php endif; ?>

							<?php if ( $is_expired ) : ?>
								<?php
								$renew_url = wp_nonce_url(
									add_query_arg(
										array(
											'action'  => 'apollo_renew_ad',
											'post_id' => $ad_id,
										),
										admin_url( 'admin-post.php' )
									),
									'apollo_renew_' . $ad_id
								);
								?>
								<a href="<?php echo esc_url( $renew_url ); ?>" class="button button-small" title="<?php esc_attr_e( 'Renovar', 'apollo-adverts' ); ?>">
									<i class="ri-refresh-line"></i>
								</a>
							<?php endif; ?>

							<?php
							$delete_url = wp_nonce_url(
								add_query_arg(
									array(
										'action'  => 'apollo_delete_ad',
										'post_id' => $ad_id,
									),
									admin_url( 'admin-post.php' )
								),
								'apollo_delete_' . $ad_id
							);
							?>
							<a href="<?php echo esc_url( $delete_url ); ?>" class="button button-small delete" title="<?php esc_attr_e( 'Excluir', 'apollo-adverts' ); ?>" onclick="return confirm('<?php esc_attr_e( 'Tem certeza que deseja excluir este anúncio?', 'apollo-adverts' ); ?>');">
								<i class="ri-delete-bin-line"></i>
							</a>
						</td>
					</tr>
				<?php endwhile; ?>
			</tbody>
		</table>

		<?php wp_reset_postdata(); ?>

		<?php
		$total_pages = $ads->max_num_pages;
		if ( $total_pages > 1 ) :
			?>
			<div class="apollo-adverts-pagination">
				<?php
				echo paginate_links(
					array(
						'total'   => $total_pages,
						'current' => $paged,
						'format'  => '?paged=%#%',
					)
				);
				?>
			</div>
		<?php endif; ?>

	<?php else : ?>
		<div class="apollo-adverts-empty">
			<p><?php esc_html_e( 'Você ainda não possui anúncios.', 'apollo-adverts' ); ?></p>
			<?php if ( $submit_page ) : ?>
				<a href="<?php echo esc_url( get_permalink( $submit_page ) ); ?>" class="button button-primary">
					<?php esc_html_e( 'Criar primeiro anúncio', 'apollo-adverts' ); ?>
				</a>
			<?php endif; ?>
		</div>
	<?php endif; ?>

</div>
