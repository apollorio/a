<?php
/**
 * Template: Admin Dashboard Page
 *
 * Shows classified stats + recent ads.
 * Rendered by Plugin::render_dashboard_page()
 *
 * @package Apollo\Adverts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$counts = wp_count_posts( APOLLO_CPT_CLASSIFIED );

$recent = get_posts(
	array(
		'post_type'      => APOLLO_CPT_CLASSIFIED,
		'post_status'    => array( 'publish', 'pending', 'expired' ),
		'posts_per_page' => 15,
		'orderby'        => 'date',
		'order'          => 'DESC',
	)
);

$status_labels = array(
	'publish' => __( 'Ativo', 'apollo-adverts' ),
	'pending' => __( 'Pendente', 'apollo-adverts' ),
	'expired' => __( 'Expirado', 'apollo-adverts' ),
	'draft'   => __( 'Rascunho', 'apollo-adverts' ),
);
?>

<div class="wrap">
	<h1><?php esc_html_e( 'Apollo Adverts — Dashboard', 'apollo-adverts' ); ?></h1>

	<div class="apollo-adverts-dashboard">

		<!-- Stats Cards -->
		<div class="apollo-dashboard-stats" style="display:flex; gap:20px; margin:20px 0;">
			<div class="stat-card" style="background:#fff; padding:20px; border:1px solid #ccd0d4; border-radius:4px; flex:1; text-align:center;">
				<div style="font-size:32px; font-weight:700; color:#2271b1;"><?php echo esc_html( (string) ( $counts->publish ?? 0 ) ); ?></div>
				<div style="color:#50575e;"><?php esc_html_e( 'Ativos', 'apollo-adverts' ); ?></div>
			</div>
			<div class="stat-card" style="background:#fff; padding:20px; border:1px solid #ccd0d4; border-radius:4px; flex:1; text-align:center;">
				<div style="font-size:32px; font-weight:700; color:#dba617;"><?php echo esc_html( (string) ( $counts->pending ?? 0 ) ); ?></div>
				<div style="color:#50575e;"><?php esc_html_e( 'Pendentes', 'apollo-adverts' ); ?></div>
			</div>
			<div class="stat-card" style="background:#fff; padding:20px; border:1px solid #ccd0d4; border-radius:4px; flex:1; text-align:center;">
				<div style="font-size:32px; font-weight:700; color:#d63638;"><?php echo esc_html( (string) ( $counts->expired ?? 0 ) ); ?></div>
				<div style="color:#50575e;"><?php esc_html_e( 'Expirados', 'apollo-adverts' ); ?></div>
			</div>
			<div class="stat-card" style="background:#fff; padding:20px; border:1px solid #ccd0d4; border-radius:4px; flex:1; text-align:center;">
				<div style="font-size:32px; font-weight:700; color:#50575e;"><?php echo esc_html( (string) ( $counts->trash ?? 0 ) ); ?></div>
				<div style="color:#50575e;"><?php esc_html_e( 'Na Lixeira', 'apollo-adverts' ); ?></div>
			</div>
		</div>

		<!-- Recent Table -->
		<div class="apollo-dashboard-recent" style="background:#fff; padding:20px; border:1px solid #ccd0d4; border-radius:4px;">
			<h2><?php esc_html_e( 'Anúncios Recentes', 'apollo-adverts' ); ?></h2>

			<?php if ( ! empty( $recent ) ) : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Título', 'apollo-adverts' ); ?></th>
							<th><?php esc_html_e( 'Autor', 'apollo-adverts' ); ?></th>
							<th><?php esc_html_e( 'Valor Ref.', 'apollo-adverts' ); ?></th>
							<th><?php esc_html_e( 'Status', 'apollo-adverts' ); ?></th>
							<th><?php esc_html_e( 'Data', 'apollo-adverts' ); ?></th>
							<th><?php esc_html_e( 'Ações', 'apollo-adverts' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $recent as $post ) : ?>
							<?php
							$author = get_userdata( $post->post_author );
							$status = $post->post_status;
							$label  = $status_labels[ $status ] ?? $status;
							?>
							<tr>
								<td>
									<a href="<?php echo esc_url( get_edit_post_link( $post->ID ) ); ?>">
										<strong><?php echo esc_html( $post->post_title ); ?></strong>
									</a>
								</td>
								<td><?php echo esc_html( $author ? $author->display_name : '—' ); ?></td>
								<td><?php echo esc_html( apollo_adverts_get_the_price( $post->ID ) ?: '—' ); ?></td>
								<td>
									<span class="apollo-status-badge status-<?php echo esc_attr( $status ); ?>">
										<?php echo esc_html( $label ); ?>
									</span>
								</td>
								<td><?php echo esc_html( get_the_date( 'd/m/Y', $post ) ); ?></td>
								<td>
									<a href="<?php echo esc_url( get_edit_post_link( $post->ID ) ); ?>"><?php esc_html_e( 'Editar', 'apollo-adverts' ); ?></a> |
									<a href="<?php echo esc_url( get_permalink( $post->ID ) ); ?>" target="_blank"><?php esc_html_e( 'Ver', 'apollo-adverts' ); ?></a>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php else : ?>
				<p><?php esc_html_e( 'Nenhum anúncio encontrado.', 'apollo-adverts' ); ?></p>
			<?php endif; ?>
		</div>

	</div>
</div>
