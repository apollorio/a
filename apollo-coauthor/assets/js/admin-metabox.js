/**
 * Apollo CoAuthor — Admin Metabox Script.
 *
 * Select2 user search + sortable drag & drop for co-author ordering.
 *
 * @package Apollo\CoAuthor
 * @since   1.0.0
 */

/* global jQuery, apolloCoauthor */
(function ($) {
	'use strict';

	const config = window.apolloCoauthor || {};
	const $list  = $('#apollo-coauthor-list');
	const $select = $('#apollo-coauthor-select');

	/**
	 * Initialize Select2 with AJAX user search.
	 */
	function initSelect2() {
		$select.select2({
			placeholder: config.i18n?.search || 'Buscar usuários...',
			allowClear: true,
			minimumInputLength: 2,
			ajax: {
				url: config.searchUrl,
				dataType: 'json',
				delay: 300,
				headers: { 'X-WP-Nonce': config.nonce },
				data: function (params) {
					return { q: params.term };
				},
				processResults: function (data) {
					// Filter out already added co-authors.
					const existing = getExistingIds();
					const filtered = data.filter(function (user) {
						return existing.indexOf(user.id) === -1;
					});
					return {
						results: filtered.map(function (user) {
							return {
								id: user.id,
								text: user.display_name + ' (@' + user.user_login + ')',
								avatar_url: user.avatar_url,
								display_name: user.display_name,
								user_login: user.user_login
							};
						})
					};
				},
				cache: true
			},
			language: {
				noResults: function () {
					return config.i18n?.noResults || 'Nenhum usuário encontrado';
				},
				searching: function () {
					return 'Buscando...';
				},
				inputTooShort: function () {
					return 'Digite pelo menos 2 caracteres...';
				}
			}
		});

		// On selection, add the user to the list.
		$select.on('select2:select', function (e) {
			const user = e.params.data;
			addCoauthor(user);
			$select.val(null).trigger('change');
		});
	}

	/**
	 * Initialize jQuery UI Sortable for drag & drop reordering.
	 */
	function initSortable() {
		$list.sortable({
			handle: '.drag-handle',
			placeholder: 'apollo-coauthor-placeholder',
			opacity: 0.7,
			cursor: 'grabbing',
			containment: 'parent'
		});
	}

	/**
	 * Get list of currently added co-author IDs.
	 *
	 * @return {number[]}
	 */
	function getExistingIds() {
		const ids = [];
		$list.find('li').each(function () {
			ids.push(parseInt($(this).data('a-user'), 10));
		});
		return ids;
	}

	/**
	 * Add a co-author to the sortable list.
	 *
	 * @param {Object} user User data from Select2.
	 */
	function addCoauthor(user) {
		const avatarUrl = user.avatar_url || '';
		const displayName = user.display_name || user.text;
		const userLogin = user.user_login || '';

		const $li = $(
			'<li data-a-user="' + parseInt(user.id, 10) + '">' +
				'<span class="drag-handle dashicons dashicons-menu"></span>' +
				'<img src="' + escHtml(avatarUrl) + '" alt="" width="28" height="28">' +
				'<span class="coauthor-name">' +
					escHtml(displayName) +
					' <small>(@' + escHtml(userLogin) + ')</small>' +
				'</span>' +
				'<button type="button" class="remove-coauthor" title="' + (config.i18n?.remove || 'Remover') + '">&times;</button>' +
				'<input type="hidden" name="apollo_coauthors[]" value="' + parseInt(user.id, 10) + '">' +
			'</li>'
		);

		$list.append($li);
		$list.sortable('refresh');
	}

	/**
	 * Handle remove button clicks.
	 */
	function initRemove() {
		$list.on('click', '.remove-coauthor', function (e) {
			e.preventDefault();
			$(this).closest('li').fadeOut(200, function () {
				$(this).remove();
			});
		});
	}

	/**
	 * Basic HTML escaping.
	 *
	 * @param {string} str
	 * @return {string}
	 */
	function escHtml(str) {
		if (!str) return '';
		const div = document.createElement('div');
		div.appendChild(document.createTextNode(str));
		return div.innerHTML;
	}

	/**
	 * Init on DOM ready.
	 */
	$(function () {
		if (!$list.length || !$select.length) {
			return;
		}
		initSelect2();
		initSortable();
		initRemove();
	});

})(jQuery);
