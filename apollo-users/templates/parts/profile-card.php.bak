<?php
/**
 * Profile Card — Left column (2/3)
 *
 * Avatar, name, tags, bio, link, sound tags, SoundCloud player, member since
 *
 * @package Apollo\Users
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Variables from single-profile.php:
// $user, $user_id, $is_own_profile, $display_name, $bio, $avatar_url
// $nucleo_tags, $sound_tags, $soundcloud_url, $member_year, $website
?>
<div class="profile-card">

	<!-- Header: Avatar + Identity -->
	<div class="pc-header">
		<img src="<?php echo esc_url( $avatar_url ); ?>"
		     alt="<?php echo esc_attr( $display_name ); ?>"
		     class="pc-avatar">

		<div class="pc-identity-wrap">
			<h1 class="pc-name"><?php echo esc_html( $display_name ); ?></h1>

			<?php if ( ! empty( $nucleo_tags ) ) : ?>
			<div class="pc-tags">
				<?php foreach ( $nucleo_tags as $tag ) : ?>
					<span class="pc-tag"><?php echo esc_html( $tag ); ?></span>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>

			<span class="pc-handle">@<?php echo esc_html( $user->user_login ); ?></span>
		</div>
	</div>

	<!-- Bio -->
	<?php if ( $bio ) : ?>
		<p class="pc-bio"><?php echo wp_kses_post( nl2br( esc_html( $bio ) ) ); ?></p>
	<?php endif; ?>

	<!-- Hub link -->
	<a href="<?php echo esc_url( home_url( '/hub/' . $user->user_login ) ); ?>" class="pc-link" target="_blank">
		<i class="ri-link-m"></i>
		apollo.rio.br/hub/<?php echo esc_html( $user->user_login ); ?>
	</a>

	<!-- Sound tags -->
	<?php if ( ! empty( $sound_tags ) ) : ?>
	<div class="sound-tags">
		<?php foreach ( $sound_tags as $st ) : ?>
			<span class="pc-tag"><?php echo esc_html( $st ); ?></span>
		<?php endforeach; ?>
	</div>
	<?php endif; ?>

	<!-- Own-profile actions -->
	<?php if ( $is_own_profile ) : ?>
	<div class="pc-actions">
		<a href="<?php echo esc_url( home_url( '/editar-perfil/' ) ); ?>" class="pc-action-btn">
			<i class="ri-edit-line"></i> Editar Perfil
		</a>
		<a href="<?php echo esc_url( home_url( '/minha-conta/' ) ); ?>" class="pc-action-btn">
			<i class="ri-settings-3-line"></i> Configurações
		</a>
	</div>
	<?php endif; ?>

	<!-- Bottom: Player + Member Since -->
	<div class="pc-bottom">
		<div class="member-since">Member since <?php echo esc_html( $member_year ); ?></div>

		<?php if ( $soundcloud_url ) : ?>
		<div class="player-divider"></div>

		<div class="soundcloud-player">
			<iframe id="sc-widget"
			        allow="autoplay"
			        style="position:absolute;width:1px;height:1px;opacity:0;pointer-events:none;"
			        scrolling="no"
			        frameborder="no"
			        src="https://w.soundcloud.com/player/?url=<?php echo rawurlencode( $soundcloud_url ); ?>&auto_play=false&show_artwork=false">
			</iframe>

			<div class="media-card">
				<div class="media-card__header">
					<div class="media-card__header__logo">
						<i class="ri-soundcloud-fill"></i>
					</div>
				</div>
				<div class="media-card__content">
					<div class="media-card__content__info">
						<h4 id="track-name">Loading…</h4>
						<p id="track-artist">&nbsp;</p>
					</div>
					<button class="media-card__content__btn" id="play-btn">
						<span class="material-symbols-outlined" id="play-icon">play_arrow</span>
					</button>
				</div>
				<div class="slider">
					<div class="slider__track" id="progress-track">
						<div class="slider__progress" id="progress-bar"></div>
					</div>
					<span class="slider__time" id="time-display">0:00</span>
				</div>
			</div>
		</div>
		<?php endif; ?>
	</div>
</div>
