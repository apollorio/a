<?php
/**
 * DJ v3 Partial: FAB Menu (Mobile-first bottom sheet)
 *
 * Floating action button + full navigation sheet.
 * Variables: $dj_id, $dj_events_count.
 *
 * @package Apollo\DJs
 */

defined( 'ABSPATH' ) || exit;
?>

<!-- FAB Button -->
<button class="nh-menu-fab" id="nhMenuFab" aria-label="<?php esc_attr_e( 'Menu Apollo', 'apollo-djs' ); ?>" aria-expanded="false" aria-haspopup="true">
	<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"
		 width="20" height="20" style="display:block;flex-shrink:0;pointer-events:none">
		<path d="M21 17C21 19.2091 19.2091 21 17 21C14.7909 21 13 19.2091 13 17C13 14.7909 14.7909 13 17 13C19.2091 13 21 14.7909 21 17ZM11 7C11 9.20914 9.20914 11 7 11C4.79086 11 3 9.20914 3 7C3 4.79086 4.79086 3 7 3C9.20914 3 11 4.79086 11 7ZM21 7C21 9.20914 19.2091 11 17 11C16.2584 11 15.5634 10.7972 14.9678 10.4453L10.4453 14.9678C10.7972 15.5634 11 16.2584 11 17C11 19.2091 9.20914 21 7 21C4.79086 21 3 19.2091 3 17C3 14.7909 4.79086 13 7 13C7.74116 13 8.43593 13.2022 9.03125 13.5537L13.5537 9.03125C13.2022 8.43593 13 7.74116 13 7C13 4.79086 14.7909 3 17 3C19.2091 3 21 4.79086 21 7Z"/>
	</svg>
</button>

<!-- Sheet -->
<div class="nh-menu-sheet" id="nhMenuSheet" role="menu" aria-label="<?php esc_attr_e( 'Menu de navegação', 'apollo-djs' ); ?>">

	<!-- Page sections -->
	<span class="nh-sheet-label"><?php esc_html_e( 'Nesta página', 'apollo-djs' ); ?></span>

	<?php if ( $dj_events_count > 0 ) : ?>
		<a href="#agenda" class="nh-sheet-item" role="menuitem">
			<i class="ri-calendar-todo-line"></i><?php esc_html_e( 'Agenda', 'apollo-djs' ); ?>
		</a>
	<?php endif; ?>

	<a href="#sonoridades" class="nh-sheet-item" role="menuitem">
		<i class="ri-headphone-line"></i><?php esc_html_e( 'Sounds', 'apollo-djs' ); ?>
	</a>

	<a href="#bio" class="nh-sheet-item" role="menuitem">
		<i class="ri-user-star-line"></i><?php esc_html_e( 'Bio', 'apollo-djs' ); ?>
	</a>

	<a href="#galeria" class="nh-sheet-item" role="menuitem">
		<i class="ri-image-line"></i><?php esc_html_e( 'Visuals', 'apollo-djs' ); ?>
	</a>

	<a href="#booking" class="nh-sheet-item nh-sheet-item--accent" role="menuitem">
		<i class="ri-mail-send-line"></i><?php esc_html_e( 'Booking', 'apollo-djs' ); ?>
	</a>

	<div class="nh-sheet-divider" aria-hidden="true"></div>

	<!-- Apollo ecosystem -->
	<span class="nh-sheet-label"><?php esc_html_e( 'Apollo', 'apollo-djs' ); ?></span>

	<a href="<?php echo esc_url( home_url( '/eventos' ) ); ?>" class="nh-sheet-item" role="menuitem">
		<i class="ri-calendar-event-line"></i><?php esc_html_e( 'Eventos', 'apollo-djs' ); ?>
	</a>

	<a href="<?php echo esc_url( home_url( '/djs' ) ); ?>" class="nh-sheet-item" role="menuitem">
		<i class="ri-music-2-line"></i><?php esc_html_e( 'DJs & Artistas', 'apollo-djs' ); ?>
	</a>

	<a href="<?php echo esc_url( home_url( '/criativo' ) ); ?>" class="nh-sheet-item" role="menuitem">
		<i class="ri-map-pin-2-line"></i><?php esc_html_e( 'Espaços', 'apollo-djs' ); ?>
	</a>

	<a href="<?php echo esc_url( home_url( '/classificados' ) ); ?>" class="nh-sheet-item" role="menuitem">
		<i class="ri-exchange-box-line"></i><?php esc_html_e( 'Classificados', 'apollo-djs' ); ?>
	</a>

	<div class="nh-sheet-divider" aria-hidden="true"></div>

	<?php if ( is_user_logged_in() ) : ?>
		<?php
		$user = wp_get_current_user();
		$profile_url = home_url( '/id/' . $user->user_login );
		?>
		<a href="<?php echo esc_url( $profile_url ); ?>" class="nh-sheet-item" role="menuitem">
			<i class="ri-user-line"></i><?php esc_html_e( 'Meu perfil', 'apollo-djs' ); ?>
		</a>
	<?php else : ?>
		<a href="<?php echo esc_url( home_url( '/acesso' ) ); ?>" class="nh-sheet-item nh-sheet-item--cta" role="menuitem">
			<i class="ri-login-circle-line"></i><?php esc_html_e( 'Entrar / Cadastrar', 'apollo-djs' ); ?>
		</a>
	<?php endif; ?>
</div>
