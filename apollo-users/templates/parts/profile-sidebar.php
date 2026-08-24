<?php

/**
 * Profile Sidebar — Right column (1/3)
 *
 * Card 1: Ratings (anonymous voting, 3 categories, 3 icons each)
 * Card 2: Stats (visible ONLY to own user)
 * Card 3: Pubs (list format, not sub-cards)
 *
 * @package Apollo\Users
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Variables from single-profile.php:
// $user, $user_id, $is_own_profile, $is_admin, $averages, $user_votes, $profile_views

$categories = \Apollo\Users\Components\RatingHandler::CATEGORIES;
$max_score  = \Apollo\Users\Components\RatingHandler::MAX_SCORE;
$can_vote   = is_user_logged_in() && ! $is_own_profile;
?>
<div class="summary-sidebar">

	<!-- ═══ RATINGS CARD ═══ -->
	<div class="summary-card" id="ratings-card">
		<div class="sc-header">
			<span><i class="ri-star-smile-line"></i> Ratings</span>
			<?php if ( $is_admin ) : ?>
				<button class="admin-voters-btn" data-target="<?php echo (int) $user_id; ?>" title="Ver votantes">
					<i class="ri-shield-keyhole-line"></i>
				</button>
			<?php endif; ?>
		</div>

		<div class="ratings-wrap" style="display:flex; flex-direction:column; gap:8px;">
			<?php
			foreach ( $categories as $key => $meta ) :
				$avg        = $averages[ $key ] ?? 0;
				$user_score = $user_votes[ $key ] ?? 0;
				?>
				<div class="rating-row" data-category="<?php echo esc_attr( $key ); ?>">
					<div class="stat-label"><?php echo esc_html( $meta['label'] ); ?></div>
					<div class="rating-icons">
						<?php
						for ( $i = 1; $i <= $max_score; $i++ ) :
							$filled_class = '';
							if ( $can_vote ) {
								// Show user's own vote
								$filled_class = ( $i <= $user_score ) ? 'filled' : '';
							} else {
								// Show average (round to nearest int for display)
								$filled_class = ( $i <= round( $avg ) ) ? 'filled' : '';
							}
							?>
							<i class="<?php echo esc_attr( $meta['icon'] ); ?> rating-emoji <?php echo esc_attr( $filled_class ); ?>"
								style="color:<?php echo esc_attr( $meta['color'] ); ?>;"
								data-score="<?php echo (int) $i; ?>"
								<?php echo $can_vote ? '' : ''; ?>></i>
						<?php endfor; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>

	<!-- ═══ STATS CARD (own profile only) ═══ -->
	<?php if ( $is_own_profile ) : ?>
		<div class="summary-card">
			<div class="sc-header"><i class="ri-bar-chart-fill"></i> Stats</div>
			<div class="stats-grid">
				<div class="stat-item">
					<span class="stat-label">Favs</span>
					<span class="stat-val"><?php echo esc_html( get_user_meta( $user_id, '_apollo_favorites_count', true ) ?: '0' ); ?></span>
				</div>
				<div class="stat-item">
					<span class="stat-label">Hits</span>
					<span class="stat-val"><?php echo esc_html( $profile_views ? number_format_i18n( $profile_views ) : '0' ); ?></span>
				</div>
				<div class="stat-item">
					<span class="stat-label">Visits</span>
					<span class="stat-val"><?php echo esc_html( get_user_meta( $user_id, '_apollo_unique_visits', true ) ?: '0' ); ?></span>
				</div>
				<div class="stat-item">
					<span class="stat-label">Rate</span>
					<?php
					$all_avg  = array_values( $averages );
					$non_zero = array_filter( $all_avg, fn( $v ) => $v > 0 );
					$overall  = $non_zero ? round( array_sum( $non_zero ) / count( $non_zero ), 1 ) : 0;
					?>
					<span class="stat-val"><?php echo esc_html( $overall ); ?></span>
				</div>
			</div>
		</div>
	<?php endif; ?>

	<!-- ═══ PUBS CARD (as list) ═══ -->
	<div class="summary-card">
		<div class="sc-header"><i class="ri-article-line"></i> Pubs</div>
		<div class="pub-list">
			<?php
			$user_posts = get_posts(
				array(
					'author'         => $user_id,
					'posts_per_page' => 5,
					'post_status'    => 'publish',
					'post_type'      => array( 'post', 'apollo_event', 'apollo_classified' ),
				)
			);

			if ( $user_posts ) :
				foreach ( $user_posts as $pub ) :
					?>
					<a href="<?php echo esc_url( get_permalink( $pub ) ); ?>" class="pub-item">
						<span class="pub-title"><?php echo esc_html( $pub->post_title ); ?></span>
						<div class="pub-meta"><?php echo wp_kses_post( apollo_time_ago_html( get_the_date( 'Y-m-d H:i:s', $pub ) ) ); ?></div>
					</a>
					<?php
				endforeach;
			else :
				?>
				<div class="pub-empty">Nenhuma publicação ainda.</div>
			<?php endif; ?>
		</div>
	</div>

</div>

<?php
// Admin voters modal (hidden, opened via JS)
?>
<?php if ( $is_admin ) : ?>
	<div id="admin-voters-modal" class="admin-modal" style="display:none;">
		<div class="admin-modal__backdrop"></div>
		<div class="admin-modal__content">
			<div class="admin-modal__header">
				<h3>Votos — <?php echo esc_html( $display_name ); ?></h3>
				<button class="admin-modal__close"><i class="ri-close-line"></i></button>
			</div>
			<div class="admin-modal__body" id="admin-voters-list">
				<p style="color:var(--txt-muted);text-align:center;">Carregando…</p>
			</div>
		</div>
	</div>
<?php endif; ?>