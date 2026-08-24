/**
 * Apollo Membership — Frontend JS
 *
 * @package Apollo\Membership
 */

(function ($) {
	'use strict';

	window.ApolloMembership = {

		init: function () {
			this.bindEvents();
		},

		bindEvents: function () {
			// Achievement filtering
			$(document).on('change', '.apollo-achievement-filter', this.filterAchievements);

			// Leaderboard pagination
			$(document).on('click', '.apollo-leaderboard-more', this.loadMoreLeaderboard);
		},

		/**
		 * Filter achievements list by type
		 */
		filterAchievements: function () {
			var filter = $(this).val();
			var $list = $(this).closest('.apollo-achievements-wrapper').find('.apollo-achievements-list');

			if (!filter || filter === 'all') {
				$list.find('.apollo-achievement-item').show();
				return;
			}

			$list.find('.apollo-achievement-item').each(function () {
				var type = $(this).data('type');
				$(this).toggle(type === filter);
			});
		},

		/**
		 * Load more leaderboard entries
		 */
		loadMoreLeaderboard: function (e) {
			e.preventDefault();
			var $btn = $(this);
			var nextPage = parseInt($btn.data('page'), 10) + 1;
			var limit = parseInt($btn.data('limit'), 10) || 10;

			$btn.prop('disabled', true).text(apollo_membership_vars.loading || 'Carregando...');

			$.ajax({
				url: apollo_membership_vars.rest_url + 'leaderboard',
				method: 'GET',
				data: { limit: limit, page: nextPage },
				beforeSend: function (xhr) {
					xhr.setRequestHeader('X-WP-Nonce', apollo_membership_vars.nonce);
				},
				success: function (response) {
					if (response.items && response.items.length > 0) {
						var $table = $btn.closest('.apollo-leaderboard').find('tbody');
						$.each(response.items, function (i, item) {
							var row = '<tr>' +
								'<td class="pos">' + item.position + '</td>' +
								'<td class="avatar"><img src="' + item.avatar + '" width="32" height="32" style="border-radius:50%" /></td>' +
								'<td class="name">' + item.display_name + '</td>' +
								'<td class="points">' + item.total_points.toLocaleString() + '</td>' +
								'</tr>';
							$table.append(row);
						});
						$btn.data('page', nextPage).prop('disabled', false).text(apollo_membership_vars.load_more || 'Carregar mais');
					} else {
						$btn.remove();
					}
				},
				error: function () {
					$btn.prop('disabled', false).text(apollo_membership_vars.load_more || 'Carregar mais');
				}
			});
		}
	};

	$(document).ready(function () {
		ApolloMembership.init();
	});

})(jQuery);
