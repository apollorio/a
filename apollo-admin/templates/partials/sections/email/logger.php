<?php
/**
 * Email Section — Logger
 *
 * Page ID: page-email-logger
 *
 * @package Apollo\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="page" id="page-email-logger">
	<div class="panel">
		<div class="spreadsheet-toolbar">
			<input type="text" id="apollo-log-search" class="apollo-input spreadsheet-search" placeholder="<?php esc_attr_e( 'Buscar por e-mail ou assunto...', 'apollo-admin' ); ?>">
			<select id="apollo-log-status" class="select" style="height:32px;font-size:11px;width:120px">
				<option value=""><?php esc_html_e( 'Todos', 'apollo-admin' ); ?></option>
				<option value="sent"><?php esc_html_e( 'Enviado', 'apollo-admin' ); ?></option>
				<option value="failed"><?php esc_html_e( 'Falhou', 'apollo-admin' ); ?></option>
				<option value="queued"><?php esc_html_e( 'Na fila', 'apollo-admin' ); ?></option>
			</select>
			<span id="apollo-log-count" class="spreadsheet-count"></span>
		</div>
		<div style="overflow-x:auto">
			<table class="data-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Timestamp', 'apollo-admin' ); ?></th>
						<th><?php esc_html_e( 'Para', 'apollo-admin' ); ?></th>
						<th><?php esc_html_e( 'Assunto', 'apollo-admin' ); ?></th>
						<th><?php esc_html_e( 'Template', 'apollo-admin' ); ?></th>
						<th><?php esc_html_e( 'Status', 'apollo-admin' ); ?></th>
					</tr>
				</thead>
				<tbody id="apollo-email-log-body">
					<tr id="apollo-log-loading">
						<td colspan="5" style="text-align:center;padding:40px;color:var(--c-muted)">
							<i class="ri-loader-4-line ri-spin" style="font-size:24px"></i>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
</div>

<script>
(function () {
	'use strict';

	const BASE   = '<?php echo esc_js( rest_url( 'apollo/v1/email/log' ) ); ?>';
	const NONCE  = '<?php echo esc_js( wp_create_nonce( 'wp_rest' ) ); ?>';
	const body   = document.getElementById( 'apollo-email-log-body' );
	const count  = document.getElementById( 'apollo-log-count' );
	const search = document.getElementById( 'apollo-log-search' );
	const filter = document.getElementById( 'apollo-log-status' );

	const statusBadge = {
		sent   : '<span style="color:var(--c-success)"><i class="ri-check-line"></i> Enviado</span>',
		failed : '<span style="color:var(--c-danger)"><i class="ri-close-line"></i> Falhou</span>',
		queued : '<span style="color:var(--c-warning)"><i class="ri-time-line"></i> Na fila</span>',
	};

	function esc(str) {
		const d = document.createElement('div');
		d.appendChild(document.createTextNode(str || ''));
		return d.innerHTML;
	}

	function render(items, total) {
		count.textContent = total + ' registro' + (total !== 1 ? 's' : '');
		if (!items || !items.length) {
			body.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:32px;color:var(--c-muted)">Nenhum registro encontrado.</td></tr>';
			return;
		}
		body.innerHTML = items.map(function (r) {
			const badge = statusBadge[r.status] || esc(r.status);
			return '<tr>' +
				'<td style="white-space:nowrap">' + esc(r.sent_at || r.created_at || '') + '</td>' +
				'<td>' + esc(r.to_email || '') + '</td>' +
				'<td>' + esc(r.subject || '') + '</td>' +
				'<td><code>' + esc(r.template || '—') + '</code></td>' +
				'<td>' + badge + '</td>' +
				'</tr>';
		}).join('');
	}

	function load() {
		body.innerHTML = '<tr id="apollo-log-loading"><td colspan="5" style="text-align:center;padding:40px;color:var(--c-muted)"><i class="ri-loader-4-line ri-spin" style="font-size:24px"></i></td></tr>';

		const params = new URLSearchParams({ per_page: 500, page: 1 });
		const s = search.value.trim();
		const f = filter.value;
		if (s) params.set('email', s);
		if (f) params.set('status', f);

		fetch(BASE + '?' + params.toString(), {
			headers: { 'X-WP-Nonce': NONCE }
		})
		.then(function (r) { return r.json(); })
		.then(function (data) { render(data.items || [], data.total || 0); })
		.catch(function () {
			body.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:32px;color:var(--c-danger)">Erro ao carregar logs.</td></tr>';
		});
	}

	let debounce;
	search.addEventListener('input', function () {
		clearTimeout(debounce);
		debounce = setTimeout(load, 400);
	});
	filter.addEventListener('change', load);

	load();
}());
</script>