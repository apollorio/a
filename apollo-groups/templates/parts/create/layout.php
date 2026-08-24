<?php
/**
 * Criar Comuna — layout + form (Apollo+).
 *
 * Non-admins: comuna only (hidden type). Admins: optional Núcleo select.
 * Backend remains groups; public vocabulary is always "comuna".
 *
 * @package Apollo\Groups
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rest_url   = rest_url( 'apollo/v1/groups' );
$users_url  = rest_url( 'apollo/v1/users' );
$nonce      = wp_create_nonce( 'wp_rest' );
$is_admin   = current_user_can( 'manage_options' );
$me         = wp_get_current_user();
$me_avatar  = function_exists( 'apollo_get_user_avatar_url' )
	? apollo_get_user_avatar_url( $me->ID, 'thumbnail' )
	: get_avatar_url( $me->ID );
$preselect  = sanitize_text_field( wp_unslash( $_GET['tipo'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
if ( ! in_array( $preselect, array( 'comuna', 'nucleo' ), true ) ) {
	$preselect = '';
}
if ( 'nucleo' === $preselect && ! $is_admin ) {
	$preselect = 'comuna';
}

$back_url = home_url( '/comunas/' );
?>
<div class="crc-screen">
	<a class="crc-back" href="<?php echo esc_url( $back_url ); ?>">
		<i class="ri-arrow-left-line" aria-hidden="true"></i>
		<?php esc_html_e( 'Voltar para comunas', 'apollo-groups' ); ?>
	</a>

	<section class="crc-hero">
		<div class="crc-hero-bg" aria-hidden="true">Comuna</div>
		<div class="crc-hero-inner">
			<p class="crc-eyebrow"><?php esc_html_e( 'Nova', 'apollo-groups' ); ?></p>
			<h1 class="display-text"><?php esc_html_e( 'Crie sua', 'apollo-groups' ); ?><br><span class="thin"><?php esc_html_e( 'comuna', 'apollo-groups' ); ?></span></h1>
			<p class="crc-hero-desc">
				<?php esc_html_e( 'Uma comuna é uma comunidade aberta. Qualquer pessoa pode entrar — você define o nome, a vibe e as regras.', 'apollo-groups' ); ?>
			</p>
		</div>
	</section>

	<div class="crc-success" id="crcSuccess" aria-live="polite">
		<i class="ri-checkbox-circle-line crc-success-icon" aria-hidden="true"></i>
		<h3><?php esc_html_e( 'Comuna criada', 'apollo-groups' ); ?></h3>
		<p><?php esc_html_e( 'Redirecionando você em instantes…', 'apollo-groups' ); ?></p>
	</div>

	<form class="crc-form" id="crcForm" autocomplete="off" novalidate>
		<?php if ( $is_admin ) : ?>
			<div class="crc-field crc-select-wrap">
				<select class="crc-select" id="crcType" name="type" required>
					<option value="" disabled <?php selected( $preselect, '' ); ?>></option>
					<option value="comuna" <?php selected( $preselect, 'comuna' ); ?>><?php esc_html_e( 'Comuna — comunidade aberta', 'apollo-groups' ); ?></option>
					<option value="nucleo" <?php selected( $preselect, 'nucleo' ); ?>><?php esc_html_e( 'Núcleo — equipe de trabalho (admin)', 'apollo-groups' ); ?></option>
				</select>
				<label class="crc-label" for="crcType"><?php esc_html_e( 'Tipo', 'apollo-groups' ); ?></label>
				<i class="ri-arrow-down-s-line crc-select-arrow" aria-hidden="true"></i>
				<p class="crc-hint"><?php esc_html_e( 'Núcleo é privado e reservado à equipe Apollo.', 'apollo-groups' ); ?></p>
			</div>
		<?php else : ?>
			<input type="hidden" id="crcType" name="type" value="comuna">
		<?php endif; ?>

		<div class="crc-field">
			<input type="text" class="crc-input" id="crcName" name="name" placeholder=" " required maxlength="100">
			<label class="crc-label" for="crcName"><?php esc_html_e( 'Nome', 'apollo-groups' ); ?></label>
		</div>

		<div class="crc-field">
			<textarea class="crc-textarea" id="crcDesc" name="description" placeholder=" " rows="3"></textarea>
			<label class="crc-label" for="crcDesc"><?php esc_html_e( 'Descrição', 'apollo-groups' ); ?></label>
		</div>

		<div class="crc-field">
			<input type="text" class="crc-input" id="crcTags" name="tags" placeholder=" ">
			<label class="crc-label" for="crcTags"><?php esc_html_e( 'Tags (vírgula)', 'apollo-groups' ); ?></label>
			<p class="crc-hint"><?php esc_html_e( 'Ex.: techno, baile, centro', 'apollo-groups' ); ?></p>
		</div>

		<div class="crc-field">
			<textarea class="crc-textarea" id="crcRules" name="rules" placeholder=" " rows="3"></textarea>
			<label class="crc-label" for="crcRules"><?php esc_html_e( 'Regras (uma por linha)', 'apollo-groups' ); ?></label>
		</div>

		<div class="crc-field">
			<p class="crc-admins-label">
				<i class="ri-shield-star-line" aria-hidden="true"></i>
				<?php esc_html_e( 'Administradores', 'apollo-groups' ); ?>
			</p>
			<p class="crc-hint" style="margin-top:0;">
				<?php esc_html_e( 'Quem escolher aqui vira administrador da comuna assim que ela for criada.', 'apollo-groups' ); ?>
			</p>

			<input type="hidden" id="crcAdmins" name="admin_ids" value="[]">

			<div class="crc-admins-search-wrap" style="margin-top:14px;">
				<i class="ri-search-line crc-admins-search-icon" aria-hidden="true"></i>
				<input type="search" class="crc-input" id="crcAdminSearch" placeholder=" " autocomplete="off"
					aria-controls="crcAdminList"
					aria-label="<?php esc_attr_e( 'Buscar pessoas', 'apollo-groups' ); ?>">
				<label class="crc-label" for="crcAdminSearch" style="left:24px;"><?php esc_html_e( 'Buscar pessoas', 'apollo-groups' ); ?></label>
			</div>

			<div class="crc-chips" id="crcAdminChips" aria-live="polite">
				<span class="crc-chip is-locked">
					<img src="<?php echo esc_url( $me_avatar ); ?>" alt="" loading="lazy">
					<span><?php echo esc_html( $me->display_name ); ?> · <?php esc_html_e( 'você', 'apollo-groups' ); ?></span>
				</span>
			</div>

			<div class="crc-admins-list" id="crcAdminList" role="listbox" aria-multiselectable="true"
				aria-label="<?php esc_attr_e( 'Resultados da busca', 'apollo-groups' ); ?>"></div>

			<p class="crc-admins-status" id="crcAdminStatus" aria-live="polite"></p>
		</div>

		<div class="crc-error" id="crcError" role="alert"></div>

		<div class="crc-actions">
			<button type="submit" class="crc-submit" id="crcSubmit">
				<span><?php esc_html_e( 'Criar comuna', 'apollo-groups' ); ?></span>
				<i class="ri-arrow-right-line" aria-hidden="true"></i>
			</button>
		</div>
	</form>
</div>

<script>
(function () {
	'use strict';

	var REST = <?php echo wp_json_encode( $rest_url ); ?>;
	var USERS_REST = <?php echo wp_json_encode( $users_url ); ?>;
	var NONCE = <?php echo wp_json_encode( $nonce ); ?>;
	var ENTITY_BASE = <?php echo wp_json_encode( trailingslashit( home_url( '/grupo' ) ) ); ?>;
	var IS_ADMIN = <?php echo $is_admin ? 'true' : 'false'; ?>;
	var ME_ID = <?php echo (int) $me->ID; ?>;
	var MAX_ADMINS = 20;

	var form = document.getElementById('crcForm');
	var err = document.getElementById('crcError');
	var submit = document.getElementById('crcSubmit');
	var success = document.getElementById('crcSuccess');
	if (!form || !submit) return;

	function showError(msg) {
		if (!err) return;
		err.textContent = msg || '';
		err.classList.toggle('is-visible', !!msg);
	}

	// ── Administradores multi-select ──────────────────────────────
	var adminsField = document.getElementById('crcAdmins');
	var adminSearch = document.getElementById('crcAdminSearch');
	var adminChips = document.getElementById('crcAdminChips');
	var adminList = document.getElementById('crcAdminList');
	var adminStatus = document.getElementById('crcAdminStatus');
	var selected = [];
	var lastResults = [];
	var searchTimer = null;
	var searchToken = 0;

	function setStatus(msg) {
		if (!adminStatus) return;
		adminStatus.textContent = msg || '';
		adminStatus.classList.toggle('is-visible', !!msg);
	}

	function syncAdminsField() {
		if (!adminsField) return;
		adminsField.value = JSON.stringify(selected.map(function (u) { return u.id; }));
	}

	function isSelected(id) {
		return selected.some(function (u) { return u.id === id; });
	}

	function renderChips() {
		if (!adminChips) return;
		Array.prototype.slice.call(adminChips.querySelectorAll('.crc-chip:not(.is-locked)'))
			.forEach(function (el) { el.remove(); });

		selected.forEach(function (user) {
			var chip = document.createElement('span');
			chip.className = 'crc-chip';

			var img = document.createElement('img');
			img.src = user.avatar || '';
			img.alt = '';
			img.loading = 'lazy';
			chip.appendChild(img);

			var name = document.createElement('span');
			name.textContent = user.name;
			chip.appendChild(name);

			var remove = document.createElement('button');
			remove.type = 'button';
			remove.className = 'crc-chip-remove';
			remove.innerHTML = '<i class="ri-close-line" aria-hidden="true"></i>';
			remove.setAttribute('aria-label', <?php echo wp_json_encode( __( 'Remover', 'apollo-groups' ) ); ?> + ' ' + user.name);
			remove.addEventListener('click', function () {
				selected = selected.filter(function (u) { return u.id !== user.id; });
				syncAdminsField();
				renderChips();
				renderResults(lastResults);
			});
			chip.appendChild(remove);

			adminChips.appendChild(chip);
		});
	}

	function renderResults(users) {
		if (!adminList) return;
		adminList.innerHTML = '';
		lastResults = users || [];

		lastResults.forEach(function (user) {
			var row = document.createElement('button');
			row.type = 'button';
			row.className = 'crc-admin-row';
			row.setAttribute('role', 'option');
			row.setAttribute('aria-selected', isSelected(user.id) ? 'true' : 'false');

			var img = document.createElement('img');
			img.src = user.avatar || '';
			img.alt = '';
			img.loading = 'lazy';
			row.appendChild(img);

			var name = document.createElement('span');
			name.className = 'crc-admin-name';
			name.textContent = user.name;
			row.appendChild(name);

			var handle = document.createElement('span');
			handle.className = 'crc-admin-user';
			handle.textContent = user.username ? '@' + user.username : '';
			row.appendChild(handle);

			var check = document.createElement('i');
			check.className = 'crc-admin-check ' + (isSelected(user.id) ? 'ri-checkbox-circle-fill' : 'ri-add-circle-line');
			check.setAttribute('aria-hidden', 'true');
			row.appendChild(check);

			row.addEventListener('click', function () {
				if (isSelected(user.id)) {
					selected = selected.filter(function (u) { return u.id !== user.id; });
				} else {
					if (selected.length >= MAX_ADMINS) {
						setStatus(<?php echo wp_json_encode( __( 'Limite de administradores atingido', 'apollo-groups' ) ); ?>);
						return;
					}
					selected.push(user);
					setStatus('');
				}
				syncAdminsField();
				renderChips();
				renderResults(lastResults);
			});

			adminList.appendChild(row);
		});
	}

	function searchUsers(query) {
		var token = ++searchToken;
		setStatus(<?php echo wp_json_encode( __( 'Buscando…', 'apollo-groups' ) ); ?>);

		var sep = USERS_REST.indexOf('?') === -1 ? '?' : '&';
		var url = USERS_REST + sep + 'per_page=20&search=' + encodeURIComponent(query);
		fetch(url, {
			headers: { 'X-WP-Nonce': NONCE },
			credentials: 'same-origin'
		}).then(function (res) {
			return res.ok ? res.json() : { users: [] };
		}).then(function (data) {
			if (token !== searchToken) return;
			var users = (data && data.users ? data.users : []).map(function (u) {
				return {
					id: parseInt(u.id, 10),
					name: u.display_name || u.username || ('#' + u.id),
					username: u.username || '',
					avatar: u.avatar_url || ''
				};
			}).filter(function (u) {
				return u.id && u.id !== ME_ID;
			});
			renderResults(users);
			setStatus(users.length ? '' : <?php echo wp_json_encode( __( 'Ninguém encontrado', 'apollo-groups' ) ); ?>);
		}).catch(function () {
			if (token !== searchToken) return;
			renderResults([]);
			setStatus(<?php echo wp_json_encode( __( 'Não foi possível buscar agora', 'apollo-groups' ) ); ?>);
		});
	}

	if (adminSearch) {
		adminSearch.addEventListener('input', function () {
			var query = (adminSearch.value || '').trim();
			window.clearTimeout(searchTimer);

			if (query.length < 2) {
				searchToken++;
				renderResults([]);
				setStatus('');
				return;
			}

			searchTimer = window.setTimeout(function () {
				searchUsers(query);
			}, 280);
		});

		adminSearch.addEventListener('keydown', function (e) {
			if (e.key === 'Enter') {
				e.preventDefault();
			}
		});
	}

	form.addEventListener('submit', async function (e) {
		e.preventDefault();

		var typeEl = document.getElementById('crcType');
		var type = typeEl ? String(typeEl.value || 'comuna') : 'comuna';
		var name = (document.getElementById('crcName').value || '').trim();
		var desc = (document.getElementById('crcDesc').value || '').trim();
		var tags = (document.getElementById('crcTags').value || '').trim();
		var rules = (document.getElementById('crcRules').value || '').trim();

		if (!name) {
			showError(<?php echo wp_json_encode( __( 'Nome é obrigatório', 'apollo-groups' ) ); ?>);
			return;
		}
		if (IS_ADMIN && !type) {
			showError(<?php echo wp_json_encode( __( 'Selecione o tipo', 'apollo-groups' ) ); ?>);
			return;
		}
		if (!IS_ADMIN) {
			type = 'comuna';
		}

		submit.disabled = true;
		var label = submit.querySelector('span');
		if (label) label.textContent = <?php echo wp_json_encode( __( 'Criando…', 'apollo-groups' ) ); ?>;
		showError('');

		try {
			var res = await fetch(REST, {
				method: 'POST',
				headers: {
					'X-WP-Nonce': NONCE,
					'Content-Type': 'application/json'
				},
				credentials: 'same-origin',
				body: JSON.stringify({
					name: name,
					type: type,
					description: desc,
					tags: tags,
					rules: rules,
					admin_ids: selected.map(function (u) { return u.id; })
				})
			});
			var data = await res.json().catch(function () { return {}; });

			if (res.ok && (data.id || data.slug)) {
				form.classList.add('is-hidden');
				if (success) success.classList.add('is-visible');
				var slug = data.slug || data.id;
				setTimeout(function () {
					window.location.href = ENTITY_BASE + encodeURIComponent(String(slug));
				}, 1600);
				return;
			}

			showError(data.message || data.error || <?php echo wp_json_encode( __( 'Erro ao criar. Tente novamente.', 'apollo-groups' ) ); ?>);
			submit.disabled = false;
			if (label) label.textContent = <?php echo wp_json_encode( __( 'Criar comuna', 'apollo-groups' ) ); ?>;
		} catch (ex) {
			showError(<?php echo wp_json_encode( __( 'Erro de conexão. Verifique sua internet.', 'apollo-groups' ) ); ?>);
			submit.disabled = false;
			if (label) label.textContent = <?php echo wp_json_encode( __( 'Criar comuna', 'apollo-groups' ) ); ?>;
		}
	});
})();
</script>
