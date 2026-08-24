<?php

/**
 * Bulk Editor Overview Template
 *
 * Lists all available content types with links to their spreadsheet editors.
 *
 * @package Apollo\Sheets
 * @var array $types  Content types from Manager::get_content_types()
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap apollo-bulk-overview">
	<h1 class="wp-heading-inline">
		<span class="dashicons dashicons-grid-view" style="margin-right:8px;"></span>
		<?php esc_html_e( 'Apollo Bulk Editor', 'apollo-sheets' ); ?>
	</h1>
	<p class="description" style="margin-top:8px;">
		<?php esc_html_e( 'Selecione um tipo de conteúdo para editar em planilha. Apenas administradores e moderadores têm acesso.', 'apollo-sheets' ); ?>
	</p>

	<hr class="wp-header-end">

	<div class="apollo-bulk-grid" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap:16px; margin-top:24px;">
		<?php
		foreach ( $types as $content ) :
			$slug  = $content['slug'];
			$label = $content['label'];
			$icon  = $content['icon'];
			$url   = admin_url( 'admin.php?page=apollo-bulk-' . $slug );

			// Get count
			if ( $content['type'] === 'users' ) {
				$count_result = count_users();
				$count        = $count_result['total_users'];
			} elseif ( $content['type'] === 'comments' ) {
				$count_obj = wp_count_comments();
				$count     = $count_obj->total_comments;
			} else {
				$count_obj = wp_count_posts( $slug );
				$count     = 0;
				if ( $count_obj ) {
					foreach ( array( 'publish', 'draft', 'pending', 'private', 'future' ) as $s ) {
						$count += (int) ( $count_obj->$s ?? 0 );
					}
				}
			}
			?>
			<a href="<?php echo esc_url( $url ); ?>" class="apollo-bulk-card" style="display:block; padding:20px; background:#1d2327; border:1px solid #3c434a; border-radius:8px; text-decoration:none; color:#c3c4c7; transition:all .2s;">
				<div style="display:flex; align-items:center; gap:12px; margin-bottom:12px;">
					<span class="dashicons <?php echo esc_attr( $icon ); ?>" style="font-size:28px; width:28px; height:28px; color:#72aee6;"></span>
					<span style="font-size:16px; font-weight:600; color:#fff;"><?php echo esc_html( $label ); ?></span>
				</div>
				<div style="display:flex; justify-content:space-between; align-items:center;">
					<span style="font-size:24px; font-weight:700; color:#72aee6;"><?php echo number_format_i18n( $count ); ?></span>
					<span style="font-size:12px; color:#8c8f94; text-transform:uppercase;"><?php esc_html_e( 'registros', 'apollo-sheets' ); ?></span>
				</div>
			</a>
		<?php endforeach; ?>
	</div>

	<style>
		.apollo-bulk-card:hover {
			border-color: #72aee6 !important;
			transform: translateY(-2px);
			box-shadow: 0 4px 12px rgba(var(--rgb-d), 0.3);
		}
	</style>
</div>
