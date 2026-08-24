<?php
/**
 * Admin View: Configurações.
 *
 * Uses WordPress Settings API — sections and fields are
 * registered in AdminPage::registerSettings().
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap apollo-email-admin">
	<h1><?php esc_html_e( 'Configurações — Email Apollo', 'apollo-email' ); ?></h1>

	<form method="post" action="options.php">
		<?php
		settings_fields( 'apollo_email_settings' );
		?>

		<div class="apollo-email-settings-wrap">
			<!-- General -->
			<div class="apollo-email-settings-section">
				<h2><span class="dashicons dashicons-admin-generic"></span> <?php esc_html_e( 'Geral', 'apollo-email' ); ?></h2>
				<table class="form-table" role="presentation">
					<?php do_settings_fields( 'apollo_email_settings', 'apollo_email_general' ); ?>
				</table>
			</div>

			<!-- Transport -->
			<div class="apollo-email-settings-section">
				<h2><span class="dashicons dashicons-migrate"></span> <?php esc_html_e( 'Transporte', 'apollo-email' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Configure como os e-mails serão enviados. Os campos de SMTP, SES ou SendGrid serão usados conforme o método selecionado.', 'apollo-email' ); ?></p>
				<table class="form-table" role="presentation">
					<?php do_settings_fields( 'apollo_email_settings', 'apollo_email_transport' ); ?>
				</table>
			</div>

			<!-- Tracking -->
			<div class="apollo-email-settings-section">
				<h2><span class="dashicons dashicons-chart-bar"></span> <?php esc_html_e( 'Rastreamento', 'apollo-email' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Ative rastreamento para monitorar aberturas e cliques nos e-mails enviados.', 'apollo-email' ); ?></p>
				<table class="form-table" role="presentation">
					<?php do_settings_fields( 'apollo_email_settings', 'apollo_email_tracking' ); ?>
				</table>
			</div>

			<!-- Branding -->
			<div class="apollo-email-settings-section">
				<h2><span class="dashicons dashicons-art"></span> <?php esc_html_e( 'Identidade Visual', 'apollo-email' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Personalize a aparência dos e-mails enviados.', 'apollo-email' ); ?></p>
				<table class="form-table" role="presentation">
					<?php do_settings_fields( 'apollo_email_settings', 'apollo_email_branding' ); ?>
				</table>
			</div>
		</div>

		<?php submit_button( __( 'Salvar Configurações', 'apollo-email' ) ); ?>
	</form>

	<!-- System Info -->
	<div class="apollo-email-card apollo-email-card-full" style="margin-top: 20px;">
		<h3><?php esc_html_e( 'Informações do Sistema', 'apollo-email' ); ?></h3>
		<table class="widefat striped" style="max-width: 600px;">
			<tbody>
				<tr>
					<td><?php esc_html_e( 'Plugin Version', 'apollo-email' ); ?></td>
					<td><code><?php echo esc_html( APOLLO_EMAIL_VERSION ); ?></code></td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'PHP Version', 'apollo-email' ); ?></td>
					<td><code><?php echo esc_html( PHP_VERSION ); ?></code></td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'WordPress Version', 'apollo-email' ); ?></td>
					<td><code><?php echo esc_html( get_bloginfo( 'version' ) ); ?></code></td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'Cron Status', 'apollo-email' ); ?></td>
					<td>
						<?php
						$next_run = wp_next_scheduled( APOLLO_EMAIL_CRON_HOOK );
						if ( $next_run ) {
							echo '<span class="badge badge-sent">' . esc_html__( 'Ativo', 'apollo-email' ) . '</span> — ';
							echo esc_html(
								sprintf(
									__( 'Próximo: %s', 'apollo-email' ),
									wp_date( 'd/m/Y H:i:s', $next_run )
								)
							);
						} else {
							echo '<span class="badge badge-failed">' . esc_html__( 'Inativo', 'apollo-email' ) . '</span>';
						}
						?>
					</td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'Apollo Core', 'apollo-email' ); ?></td>
					<td>
						<?php if ( defined( 'APOLLO_CORE_BOOTSTRAPPED' ) ) : ?>
							<span class="badge badge-sent"><?php esc_html_e( 'Conectado', 'apollo-email' ); ?></span>
							<?php if ( defined( 'APOLLO_VERSION' ) ) : ?>
								<code><?php echo esc_html( APOLLO_VERSION ); ?></code>
							<?php endif; ?>
						<?php else : ?>
							<span class="badge badge-failed"><?php esc_html_e( 'Não encontrado', 'apollo-email' ); ?></span>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'DB Version', 'apollo-email' ); ?></td>
					<td><code><?php echo esc_html( get_option( 'apollo_email_db_version', '—' ) ); ?></code></td>
				</tr>
			</tbody>
		</table>
	</div>

	<!-- Template Refresh Tool -->
	<div class="apollo-email-card apollo-email-card-full" style="margin-top:24px;">
		<h3><?php esc_html_e( 'Ferramentas de Manutenção', 'apollo-email' ); ?></h3>
		<p class="description">
			<?php esc_html_e( 'Re-importa o conteúdo HTML dos templates padrão a partir dos arquivos-fonte. Use ao atualizar os arquivos de design. Não apaga templates customizados — apenas atualiza o post_content dos 5 slugs padrão.', 'apollo-email' ); ?>
		</p>

		<?php
		// Handle refresh action
		if (
			isset( $_POST['apollo_refresh_templates'] ) &&
			check_admin_referer( 'apollo_email_refresh_templates', '_apollo_refresh_nonce' ) &&
			current_user_can( 'manage_options' )
		) {
			$refresh_results = \Apollo\Email\Activation::refreshAllTemplates();
			echo '<div class="notice notice-success is-dismissible" style="margin:12px 0;"><p><strong>';
			esc_html_e( 'Templates atualizados:', 'apollo-email' );
			echo '</strong></p><ul style="margin: 4px 0 4px 24px; list-style:disc;">';
			foreach ( $refresh_results as $r ) {
				echo '<li><code>' . esc_html( $r['slug'] ) . '</code> — ' . esc_html( $r['status'] ) . '</li>';
			}
			echo '</ul></div>';
		}
		?>

		<form method="post" style="margin-top:12px;">
			<?php wp_nonce_field( 'apollo_email_refresh_templates', '_apollo_refresh_nonce' ); ?>
			<button type="submit" name="apollo_refresh_templates" value="1" class="button button-secondary"
				onclick="return confirm('<?php echo esc_js( __( 'Isso vai sobrescrever o conteúdo dos 5 templates padrão. Continuar?', 'apollo-email' ) ); ?>')">
				<span class="dashicons dashicons-update" style="vertical-align:middle;margin-top:-2px;"></span>
				<?php esc_html_e( 'Re-importar Templates Padrão', 'apollo-email' ); ?>
			</button>
			<span class="description" style="margin-left:8px;">
				<?php esc_html_e( 'welcome, verification, password-reset, notification, digest', 'apollo-email' ); ?>
			</span>
		</form>
	</div>
</div>

