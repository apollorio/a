<?php
/**
 * Single Part — Composer
 *
 * Post composer with media tools (Image, Spotify, SoundCloud, YouTube, Event, Poll).
 * Expects: $is_logged, $is_member, $user_id
 *
 * @package Apollo\Groups
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! $is_logged || ! $is_member ) {
	return;
}

// Current user avatar
$current_avatar = get_user_meta( $user_id, '_apollo_avatar_url', true );
?>
<div class="composer g-fade" id="postComposer"
	data-a-user="<?php echo esc_attr( (string) $user_id ); ?>"
	data-a-component="group-composer">
	<div class="composer-avatar">
		<?php if ( $current_avatar ) : ?>
			<img src="<?php echo esc_url( $current_avatar ); ?>" alt="" loading="lazy">
		<?php endif; ?>
	</div>
	<div class="composer-body">
		<textarea
			class="apollo-textarea composer-input"
			id="composerInput"
			placeholder="Compartilhe com a comuna..."
			rows="1"
		></textarea>
		<div class="composer-tools">
			<button class="composer-tool" title="Imagem" data-tool="image">
				<i class="ri-image-line"></i>
			</button>
			<button class="composer-tool" title="Spotify" data-tool="spotify">
				<i class="ri-spotify-line"></i>
			</button>
			<button class="composer-tool" title="SoundCloud" data-tool="soundcloud">
				<i class="ri-soundcloud-line"></i>
			</button>
			<button class="composer-tool" title="YouTube" data-tool="youtube">
				<i class="ri-youtube-line"></i>
			</button>
			<button class="composer-tool" title="Evento" data-tool="event">
				<i class="ri-calendar-event-line"></i>
			</button>
			<button class="composer-tool" title="Enquete" data-tool="poll">
				<i class="ri-bar-chart-horizontal-line"></i>
			</button>
			<button class="composer-send" id="composerSend">Postar</button>
		</div>
	</div>
</div>