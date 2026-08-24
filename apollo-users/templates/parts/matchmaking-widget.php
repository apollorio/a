<?php
/**
 * Matchmaking Widget Part
 *
 * Used by [apollo_matchmaking] shortcode
 *
 * @package Apollo\Users
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user_id = get_current_user_id();
if ( ! $user_id ) {
	return;
}
?>

<div class="apollo-matchmaking-widget" id="apollo-matchmaking">
	<h3><?php esc_html_e( 'Seus Matches' ); ?></h3>

	<div class="apollo-matches-list" id="matches-list">
		<p class="apollo-loading">
			<span class="dashicons dashicons-update-alt spin"></span>
			<?php esc_html_e( 'Carregando...' ); ?>
		</p>
	</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
	const container = document.getElementById('matches-list');

	fetch('<?php echo esc_url( rest_url( 'apollo/v1/profile/matches' ) ); ?>', {
		headers: {
			'X-WP-Nonce': '<?php echo wp_create_nonce( 'wp_rest' ); ?>'
		}
	})
	.then(res => res.json())
	.then(data => {
		if (data.matches && data.matches.length > 0) {
			let html = '<ul class="apollo-match-list">';
			data.matches.forEach(match => {
				html += `
					<li class="apollo-match-item">
						<a href="${match.profile_url}">
							<img src="${match.avatar_url}" alt="${match.display_name}" class="apollo-match-avatar" />
							<span class="apollo-match-name">${match.display_name}</span>
						</a>
					</li>
				`;
			});
			html += '</ul>';
			container.innerHTML = html;
		} else {
			container.innerHTML = '<p class="apollo-no-matches"><?php esc_html_e( 'Nenhum match ainda. Continue explorando!' ); ?></p>';
		}
	})
	.catch(err => {
		container.innerHTML = '<p class="apollo-error"><?php esc_html_e( 'Erro ao carregar matches.' ); ?></p>';
	});
});
</script>

<style>
.apollo-matchmaking-widget {
	background: var(--apollo-card-bg, #fff);
	border-radius: var(--apollo-radius, 12px);
	padding: 20px;
	box-shadow: var(--apollo-shadow, 0 2px 8px rgba(var(--rgb-d),0.1));
}

.apollo-match-list {
	list-style: none;
	padding: 0;
	margin: 0;
}

.apollo-match-item {
	padding: 12px 0;
	border-bottom: 1px solid var(--apollo-border, #e2e8f0);
}

.apollo-match-item:last-child {
	border-bottom: none;
}

.apollo-match-item a {
	display: flex;
	align-items: center;
	gap: 12px;
	text-decoration: none;
	color: inherit;
}

.apollo-match-avatar {
	width: 48px;
	height: 48px;
	border-radius: 50%;
	object-fit: cover;
}

.apollo-match-name {
	font-weight: 500;
}
</style>
