<?php
/**
 * Admin View: Templates.
 *
 * @var array $templates
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$test_sent  = isset( $_GET['test_sent'] ) ? absint( $_GET['test_sent'] ) : null;
$test_slug  = isset( $_GET['test_slug'] ) ? sanitize_text_field( $_GET['test_slug'] ) : '';
$test_error = isset( $_GET['test_error'] ) ? sanitize_text_field( $_GET['test_error'] ) : '';
$test_to    = isset( $_GET['test_to'] ) ? sanitize_email( wp_unslash( $_GET['test_to'] ) ) : '';
if ( ! is_email( $test_to ) ) {
	$test_to = wp_get_current_user()->user_email;
}

$type_labels = array(
	'transactional' => __( 'Transacional', 'apollo-email' ),
	'marketing'     => __( 'Marketing', 'apollo-email' ),
	'digest'        => __( 'Digest', 'apollo-email' ),
);
?>
<div class="wrap apollo-email-admin">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Templates de E-mail', 'apollo-email' ); ?></h1>
	<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=email_aprio' ) ); ?>" class="page-title-action">
		<?php esc_html_e( 'Novo Template', 'apollo-email' ); ?>
	</a>
	<hr class="wp-header-end" />

	<?php if ( $test_sent === 1 ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php echo esc_html( sprintf( __( 'E-mail de teste "%1$s" enviado para %2$s!', 'apollo-email' ), $test_slug, $test_to ) ); ?></p>
		</div>
	<?php elseif ( $test_sent === 0 ) : ?>
		<div class="notice notice-error is-dismissible">
			<p><?php echo esc_html( sprintf( __( 'Falha ao enviar teste "%s": %s', 'apollo-email' ), $test_slug, $test_error ) ); ?></p>
		</div>
	<?php endif; ?>

	<div class="apollo-email-card apollo-email-card-full" style="margin-bottom:20px;">
		<h2 style="margin-top:0;"><?php esc_html_e( 'Enviar testes de template', 'apollo-email' ); ?></h2>
		<p class="description" style="margin-top:0;">
			<?php esc_html_e( 'Defina o destinatário abaixo. Cada botão "Enviar Teste" na tabela usa este endereço (ex.: mail-tester.com, Gmail, Mailpit).', 'apollo-email' ); ?>
		</p>
		<p>
			<label for="apollo-template-test-to"><strong><?php esc_html_e( 'Enviar para:', 'apollo-email' ); ?></strong></label><br>
			<input type="email" id="apollo-template-test-to" class="regular-text" value="<?php echo esc_attr( $test_to ); ?>" placeholder="test@mail-tester.com" />
		</p>
		<p class="description">
			<?php esc_html_e( 'Edite assunto e corpo em "Editar" (CPT email_aprio). Templates standalone (verification) usam o arquivo PHP em templates/emails/ — edite o assunto no admin; o HTML vem do arquivo.', 'apollo-email' ); ?>
		</p>
	</div>

	<?php if ( empty( $templates ) ) : ?>
		<div class="apollo-email-empty">
			<span class="dashicons dashicons-email-alt2"></span>
			<p><?php esc_html_e( 'Nenhum template encontrado. Desative e reative o plugin para gerar os templates padrão.', 'apollo-email' ); ?></p>
			<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=email_aprio' ) ); ?>" class="button button-primary">
				<?php esc_html_e( 'Criar Template', 'apollo-email' ); ?>
			</a>
		</div>
	<?php else : ?>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th class="column-title"><?php esc_html_e( 'Título', 'apollo-email' ); ?></th>
					<th class="column-slug"><?php esc_html_e( 'Slug', 'apollo-email' ); ?></th>
					<th class="column-subject"><?php esc_html_e( 'Assunto', 'apollo-email' ); ?></th>
					<th class="column-type"><?php esc_html_e( 'Tipo', 'apollo-email' ); ?></th>
					<th class="column-variables"><?php esc_html_e( 'Variáveis', 'apollo-email' ); ?></th>
					<th class="column-date"><?php esc_html_e( 'Modificado', 'apollo-email' ); ?></th>
					<th class="column-actions"><?php esc_html_e( 'Ações', 'apollo-email' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $templates as $tpl ) : ?>
					<tr>
						<td class="column-title">
							<strong>
								<a href="<?php echo esc_url( get_edit_post_link( $tpl['id'] ) ); ?>">
									<?php echo esc_html( $tpl['title'] ); ?>
								</a>
							</strong>
						</td>
						<td class="column-slug">
							<code><?php echo esc_html( $tpl['slug'] ); ?></code>
						</td>
						<td class="column-subject">
							<?php echo esc_html( $tpl['subject'] ?? '—' ); ?>
						</td>
						<td class="column-type">
							<?php
							$type       = $tpl['type'] ?? 'transactional';
							$type_class = 'badge badge-' . sanitize_html_class( $type );
							?>
							<span class="<?php echo esc_attr( $type_class ); ?>">
								<?php echo esc_html( $type_labels[ $type ] ?? ucfirst( $type ) ); ?>
							</span>
						</td>
						<td class="column-variables">
							<?php
							$vars = $tpl['variables'] ?? array();
							if ( is_array( $vars ) && ! empty( $vars ) ) {
								foreach ( $vars as $v ) {
									echo '<code class="var-tag">{{' . esc_html( $v ) . '}}</code> ';
								}
							} else {
								echo '—';
							}
							?>
						</td>
						<td class="column-date">
							<?php echo esc_html( wp_date( 'd/m/Y H:i', strtotime( $tpl['modified'] ) ) ); ?>
						</td>
						<td class="column-actions">
							<a href="<?php echo esc_url( get_edit_post_link( $tpl['id'] ) ); ?>" class="button button-small">
								<?php esc_html_e( 'Editar', 'apollo-email' ); ?>
							</a>
							<a href="
							<?php
							echo esc_url(
								add_query_arg(
									array(
										'page'    => 'apollo-email-templates',
										'preview' => $tpl['id'],
									),
									admin_url( 'admin.php' )
								)
							);
							?>
							" class="button button-small" target="_blank">
								<?php esc_html_e( 'Preview', 'apollo-email' ); ?>
							</a>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="apollo-template-test-form" style="display:inline;">
								<input type="hidden" name="action" value="apollo_email_template_test" />
								<input type="hidden" name="template_slug" value="<?php echo esc_attr( $tpl['slug'] ); ?>" />
								<input type="hidden" name="test_email" value="<?php echo esc_attr( $test_to ); ?>" class="apollo-template-test-email-field" />
								<?php wp_nonce_field( 'apollo_email_template_test_' . $tpl['slug'] ); ?>
								<button type="submit" class="button button-small button-primary" title="<?php esc_attr_e( 'Enviar teste para o destinatário configurado acima', 'apollo-email' ); ?>">
									<span class="dashicons dashicons-email" style="vertical-align:middle;font-size:14px;width:14px;height:14px;margin-right:2px;"></span>
									<?php esc_html_e( 'Enviar Teste', 'apollo-email' ); ?>
								</button>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>

	<div class="apollo-email-card apollo-email-card-full" style="margin-top:24px;">
		<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:16px;">
			<div>
				<h3 style="margin:0 0 4px;"><?php esc_html_e( 'Referência de Placeholders (Merge Tags)', 'apollo-email' ); ?></h3>
				<p class="description" style="margin:0;"><?php esc_html_e( 'Clique em qualquer tag para copiar. Use no assunto ou no corpo do template — substituídas automaticamente na hora do envio.', 'apollo-email' ); ?></p>
			</div>
			<div>
				<input
					type="search"
					id="apollo-tag-search"
					placeholder="<?php esc_attr_e( 'Buscar placeholder...', 'apollo-email' ); ?>"
					class="regular-text"
					style="max-width:280px;"
					autocomplete="off"
				/>
			</div>
		</div>

		<?php
		use Apollo\Email\Template\MergeTagRegistry;

		$context_labels = array(
			'global'       => array( 'label' => __( 'Site / Global', 'apollo-email' ), 'color' => '#6c757d' ),
			'user'         => array( 'label' => __( 'Usuário', 'apollo-email' ), 'color' => '#0073aa' ),
			'auth'         => array( 'label' => __( 'Auth / Segurança', 'apollo-email' ), 'color' => '#d63638' ),
			'notification' => array( 'label' => __( 'Notificação', 'apollo-email' ), 'color' => '#0085ba' ),
			'event'        => array( 'label' => __( 'Evento', 'apollo-email' ), 'color' => '#46b450' ),
			'digest'       => array( 'label' => __( 'Digest', 'apollo-email' ), 'color' => '#826eb4' ),
		);

		$all_tags     = MergeTagRegistry::getAllTags();
		$grouped_tags = MergeTagRegistry::getTagsByContext();
		?>

		<div id="apollo-tag-table-wrap">
			<?php foreach ( $grouped_tags as $ctx_key => $ctx_tags ) : ?>
				<?php
				$ctx_info  = $context_labels[ $ctx_key ] ?? array( 'label' => ucfirst( $ctx_key ), 'color' => '#555' );
				$ctx_label = $ctx_info['label'];
				$ctx_color = $ctx_info['color'];
				?>
				<div class="apollo-tag-group" data-context="<?php echo esc_attr( $ctx_key ); ?>">
					<h4 class="apollo-tag-group-title" style="margin:16px 0 6px;padding:6px 10px;border-left:4px solid <?php echo esc_attr( $ctx_color ); ?>;background:#f6f7f7;">
						<?php echo esc_html( $ctx_label ); ?>
						<span class="apollo-tag-count" style="font-weight:normal;color:#888;font-size:12px;margin-left:6px;">(<?php echo count( $ctx_tags ); ?>)</span>
					</h4>
					<table class="widefat striped apollo-tag-table" style="width:100%;table-layout:fixed;">
						<thead>
							<tr>
								<th style="width:200px;"><?php esc_html_e( 'Placeholder', 'apollo-email' ); ?></th>
								<th><?php esc_html_e( 'Descrição', 'apollo-email' ); ?></th>
								<th style="width:220px;"><?php esc_html_e( 'Fonte', 'apollo-email' ); ?></th>
								<th style="width:180px;"><?php esc_html_e( 'Exemplo', 'apollo-email' ); ?></th>
								<th style="width:70px;text-align:center;"><?php esc_html_e( 'Copiar', 'apollo-email' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $ctx_tags as $tag ) : ?>
								<?php
								$tag_full  = '{{' . $tag['tag'] . '}}';
								$row_attrs = 'data-tag="' . esc_attr( $tag['tag'] ) . '" data-desc="' . esc_attr( $tag['description'] ) . '" data-source="' . esc_attr( $tag['source'] ) . '"';
								?>
								<tr class="apollo-tag-row" <?php echo $row_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
									<td>
										<code class="apollo-copy-tag" data-value="<?php echo esc_attr( $tag_full ); ?>" style="cursor:pointer;font-size:13px;" title="<?php esc_attr_e( 'Clique para copiar', 'apollo-email' ); ?>">
											<?php echo esc_html( $tag_full ); ?>
										</code>
									</td>
									<td><?php echo esc_html( $tag['description'] ); ?></td>
									<td><span style="font-size:11px;color:#666;"><?php echo esc_html( $tag['source'] ); ?></span></td>
									<td><span style="font-size:11px;color:#888;font-style:italic;"><?php echo esc_html( $tag['example'] ); ?></span></td>
									<td style="text-align:center;">
										<button type="button" class="button button-small apollo-copy-btn" data-value="<?php echo esc_attr( $tag_full ); ?>" title="<?php esc_attr_e( 'Copiar', 'apollo-email' ); ?>">
											<span class="dashicons dashicons-clipboard" style="vertical-align:middle;font-size:14px;width:14px;height:14px;"></span>
										</button>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endforeach; ?>

			<p id="apollo-tag-no-results" style="display:none;color:#888;padding:16px 0;">
				<?php esc_html_e( 'Nenhum placeholder encontrado para esta busca.', 'apollo-email' ); ?>
			</p>
		</div><!-- #apollo-tag-table-wrap -->

		<div id="apollo-copy-toast" style="
			display:none;position:fixed;bottom:28px;left:50%;transform:translateX(-50%);
			background:#1d2327;color:#fff;padding:10px 20px;border-radius:4px;
			font-size:13px;z-index:99999;white-space:nowrap;pointer-events:none;">
			<?php esc_html_e( 'Copiado!', 'apollo-email' ); ?>
		</div>
	</div><!-- .apollo-email-card -->
</div><!-- .wrap -->

<script>
(function () {
	'use strict';

	const testToInput = document.getElementById('apollo-template-test-to');
	if (testToInput) {
		const syncTestEmailFields = function () {
			document.querySelectorAll('.apollo-template-test-email-field').forEach(function (field) {
				field.value = testToInput.value;
			});
		};
		testToInput.addEventListener('input', syncTestEmailFields);
		testToInput.addEventListener('change', syncTestEmailFields);
		document.querySelectorAll('.apollo-template-test-form').forEach(function (form) {
			form.addEventListener('submit', syncTestEmailFields);
		});
	}

	// ── Search ──────────────────────────────────────────────────
	const input  = document.getElementById('apollo-tag-search');
	const noRes  = document.getElementById('apollo-tag-no-results');
	const groups = document.querySelectorAll('.apollo-tag-group');

	if (input) {
		input.addEventListener('input', function () {
			const q = this.value.toLowerCase().trim();
			let anyVisible = false;

			groups.forEach(function (group) {
				const rows = group.querySelectorAll('.apollo-tag-row');
				let groupVisible = false;

				rows.forEach(function (row) {
					const haystack = [
						row.dataset.tag,
						row.dataset.desc,
						row.dataset.source,
						row.dataset.context,
					].join(' ').toLowerCase();

					const show = q === '' || haystack.includes(q);
					row.style.display = show ? '' : 'none';
					if (show) { groupVisible = true; anyVisible = true; }
				});

				group.style.display = groupVisible ? '' : 'none';
			});

			noRes.style.display = (!anyVisible && q !== '') ? 'block' : 'none';
		});
	}

	// ── Copy to clipboard ───────────────────────────────────────
	const toast = document.getElementById('apollo-copy-toast');
	let toastTimer;

	function copyTag(value) {
		if (!value) return;
		if (navigator.clipboard && navigator.clipboard.writeText) {
			navigator.clipboard.writeText(value).then(showToast);
		} else {
			// IE/legacy fallback
			const ta = document.createElement('textarea');
			ta.value = value;
			ta.style.position = 'fixed';
			ta.style.opacity  = '0';
			document.body.appendChild(ta);
			ta.select();
			document.execCommand('copy');
			document.body.removeChild(ta);
			showToast();
		}
	}

	function showToast() {
		if (!toast) return;
		clearTimeout(toastTimer);
		toast.style.display = 'block';
		toastTimer = setTimeout(function () { toast.style.display = 'none'; }, 1800);
	}

	document.querySelectorAll('.apollo-copy-btn, .apollo-copy-tag').forEach(function (el) {
		el.addEventListener('click', function () {
			copyTag(this.dataset.value || this.textContent.trim());
		});
	});

})();
</script>

