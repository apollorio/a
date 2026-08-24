/**
 * Apollo Membership — Admin JS
 *
 * @package Apollo\Membership
 */

(function ($) {
	'use strict';

	var ApolloMembershipAdmin = {

		init: function () {
			this.bindEvents();
		},

		bindEvents: function () {
			// Tools page — recalculate points
			$(document).on('click', '#apollo-recalc-points', this.recalcPoints);

			// Tools page — recalculate achievements
			$(document).on('click', '#apollo-recalc-achievements', this.recalcAchievements);

			// Tools page — recalculate ranks
			$(document).on('click', '#apollo-recalc-ranks', this.recalcRanks);

			// Tools page — reset triggers
			$(document).on('click', '#apollo-reset-triggers', this.resetTriggers);

			// Tools page — re-seed defaults
			$(document).on('click', '#apollo-seed-defaults', this.seedDefaults);

			// Badge assignment (AJAX)
			$(document).on('click', '.apollo-assign-badge-btn', this.assignBadge);
		},

		/**
		 * Generic AJAX tool handler
		 */
		_toolAction: function ($btn, action, label) {
			var $spinner = $btn.siblings('.spinner');
			var $result = $btn.siblings('.tool-result');

			$btn.prop('disabled', true);
			$spinner.addClass('is-active');
			$result.hide();

			$.ajax({
				url: apolloMembershipAdmin.ajax_url,
				method: 'POST',
				data: {
					action: action,
					nonce: apolloMembershipAdmin.nonce
				},
				success: function (response) {
					if (response.success) {
						$result.removeClass('error').addClass('success')
							.text(response.data.message || 'Concluído com sucesso.').show();
					} else {
						$result.removeClass('success').addClass('error')
							.text(response.data || 'Erro ao processar.').show();
					}
				},
				error: function () {
					$result.removeClass('success').addClass('error')
						.text('Erro de conexão.').show();
				},
				complete: function () {
					$btn.prop('disabled', false);
					$spinner.removeClass('is-active');
				}
			});
		},

		/**
		 * Recalculate all user points
		 */
		recalcPoints: function (e) {
			e.preventDefault();
			ApolloMembershipAdmin._toolAction($(this), 'apollo_membership_recalc_points', 'Recalcular Pontos');
		},

		/**
		 * Recalculate achievements
		 */
		recalcAchievements: function (e) {
			e.preventDefault();
			ApolloMembershipAdmin._toolAction($(this), 'apollo_membership_recalc_achievements', 'Recalcular Achievements');
		},

		/**
		 * Recalculate ranks
		 */
		recalcRanks: function (e) {
			e.preventDefault();
			ApolloMembershipAdmin._toolAction($(this), 'apollo_membership_recalc_ranks', 'Recalcular Ranks');
		},

		/**
		 * Reset trigger counts
		 */
		resetTriggers: function (e) {
			e.preventDefault();
			if (!confirm('Tem certeza que deseja resetar todos os contadores de triggers?')) {
				return;
			}
			ApolloMembershipAdmin._toolAction($(this), 'apollo_membership_reset_triggers', 'Resetar Triggers');
		},

		/**
		 * Re-seed default trigger points
		 */
		seedDefaults: function (e) {
			e.preventDefault();
			ApolloMembershipAdmin._toolAction($(this), 'apollo_membership_seed_defaults', 'Re-seeder');
		},

		/**
		 * Assign badge via REST API
		 */
		assignBadge: function (e) {
			e.preventDefault();
			var $btn = $(this);
			var userId = $btn.data('user-id');
			var badge = $btn.closest('.apollo-badge-assign').find('select').val();

			$.ajax({
				url: apolloMembershipAdmin.rest_url + 'membership-badge',
				method: 'POST',
				data: JSON.stringify({ user_id: userId, badge: badge }),
				contentType: 'application/json',
				beforeSend: function (xhr) {
					xhr.setRequestHeader('X-WP-Nonce', apolloMembershipAdmin.rest_nonce);
				},
				success: function (response) {
					if (response.success) {
						$btn.closest('.apollo-badge-assign').find('.badge-status')
							.text('Badge atualizado!').show().delay(2000).fadeOut();
					}
				}
			});
		}
	};

	$(document).ready(function () {
		ApolloMembershipAdmin.init();
	});

})(jQuery);
