<?php
/**
 * Template: Depoimento List
 *
 * Variables from shortcode: $depoimentos (WP_Comment[]), $total (int), $post_id (int)
 *
 * @package Apollo\Comment
 */

use Apollo\Comment\Depoimento;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<section class="depoimentos-section" data-post-id="<?php echo esc_attr( $post_id ); ?>">
	<div class="depoimentos-header">
		<h3 class="depoimentos-title">
			<i class="ri-chat-quote-line"></i>
			Depoimentos
			<?php if ( $total > 0 ) : ?>
				<span class="depoimentos-count">(<?php echo esc_html( $total ); ?>)</span>
			<?php endif; ?>
		</h3>
	</div>

	<?php if ( empty( $depoimentos ) ) : ?>
		<div class="depoimentos-empty">
			<i class="ri-chat-off-line"></i>
			<p>Nenhum depoimento ainda. Seja o primeiro!</p>
		</div>
	<?php else : ?>
		<div class="depoimentos-list">
			<?php
			foreach ( $depoimentos as $comment ) :
				$user_id      = (int) $comment->user_id;
				$avatar_url   = Depoimento::get_avatar_url( $user_id, $comment->comment_author_email );
				$display_name = $user_id
					? ( get_userdata( $user_id )->display_name ?? $comment->comment_author )
					: $comment->comment_author;
				$badge_html   = Depoimento::get_badge_html( $user_id );
				$groups       = Depoimento::get_user_groups( $user_id );
				$groups_str   = ! empty( $groups ) ? implode( ' · ', wp_list_pluck( $groups, 'name' ) ) : '';
				$profile_url  = $user_id ? home_url( '/id/' . get_userdata( $user_id )->user_login ) : '';
				$date_display = wp_date( 'j M Y', strtotime( $comment->comment_date ) );
				?>

			<div class="depoimento-card" id="depoimento-<?php echo esc_attr( $comment->comment_ID ); ?>"
				data-a-user="<?php echo esc_attr( (string) $user_id ); ?>"
				data-a-component="depoimento">
				<!-- Avatar -->
				<span class="depoimento-avatar">
					<?php if ( $profile_url ) : ?>
						<a href="<?php echo esc_url( $profile_url ); ?>">
							<img src="<?php echo esc_url( $avatar_url ); ?>"
								alt="<?php echo esc_attr( $display_name ); ?>"
								class="depoimento-avatar-img" loading="lazy">
						</a>
					<?php else : ?>
						<img src="<?php echo esc_url( $avatar_url ); ?>"
							alt="<?php echo esc_attr( $display_name ); ?>"
							class="depoimento-avatar-img" loading="lazy">
					<?php endif; ?>
				</span>

				<!-- Content -->
				<div class="depoimento-body">
					<p class="depoimento-text"><?php echo wp_kses_post( $comment->comment_content ); ?></p>

					<div class="depoimento-meta">
						<p class="depoimento-author">
							<?php if ( $profile_url ) : ?>
								<a href="<?php echo esc_url( $profile_url ); ?>"><?php echo esc_html( $display_name ); ?></a>
							<?php else : ?>
								<?php echo esc_html( $display_name ); ?>
							<?php endif; ?>
							<?php echo $badge_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</p>
						<?php if ( $groups_str ) : ?>
							<p class="depoimento-groups"><?php echo esc_html( $groups_str ); ?></p>
						<?php endif; ?>
					</div>
				</div>
			</div>

			<?php endforeach; ?>
		</div>

		<?php if ( $total > count( $depoimentos ) ) : ?>
			<div class="depoimentos-load-more">
				<button class="depoimento-load-more-btn"
						data-post-id="<?php echo esc_attr( $post_id ); ?>"
						data-offset="<?php echo esc_attr( count( $depoimentos ) ); ?>"
						data-total="<?php echo esc_attr( $total ); ?>">
					<i class="ri-arrow-down-line"></i> Carregar mais depoimentos
				</button>
			</div>
		<?php endif; ?>
	<?php endif; ?>
</section>
