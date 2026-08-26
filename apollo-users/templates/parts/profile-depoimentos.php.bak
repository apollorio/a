<?php
/**
 * Profile Depoimentos Section
 *
 * Testimonials stored as WordPress comments (comment_type = 'apollo_depoimento').
 * 2-column grid with quote cards matching reference design.
 *
 * @package Apollo\Users
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Variables: $user, $user_id, $is_own_profile, $display_name

// Fetch depoimentos (comments with custom type targeting this user)
$depoimentos = get_comments( [
	'type'    => 'apollo_depoimento',
	'parent'  => $user_id, // We store target_user_id in comment_parent
	'status'  => 'approve',
	'orderby' => 'comment_date',
	'order'   => 'DESC',
	'number'  => 20,
] );

$depo_count = count( $depoimentos );

// Can write depoimento?
$can_write = is_user_logged_in() && ! $is_own_profile;
if ( $can_write ) {
	// Check if user already wrote one
	$existing = get_comments( [
		'type'    => 'apollo_depoimento',
		'parent'  => $user_id,
		'user_id' => get_current_user_id(),
		'count'   => true,
	] );
	if ( $existing > 0 ) {
		$can_write = false;
	}
}
?>

<section class="depoimentos-section" id="depoimentos-anchor">

	<!-- Divider -->
	<div class="section-divider">
		<div class="section-divider__line"></div>
		<span class="section-divider__label">Comunidade</span>
		<div class="section-divider__line"></div>
	</div>

	<!-- Header -->
	<div class="depo-header">
		<div class="depo-header__text">
			<h2 class="depo-header__title">Depoimentos</h2>
			<p class="depo-header__sub">O que dizem sobre <?php echo esc_html( explode( ' ', $display_name )[0] ); ?></p>
		</div>
		<?php if ( $depo_count ) : ?>
			<span class="depo-header__count"><?php echo (int) $depo_count; ?></span>
		<?php endif; ?>
	</div>

	<!-- Write form -->
	<?php if ( $can_write ) : ?>
	<form class="depo-form" id="depo-submit-form">
		<textarea name="depo_text" id="depo-text" placeholder="Escreva um depoimento sobre <?php echo esc_attr( explode( ' ', $display_name )[0] ); ?>…" maxlength="500" rows="3"></textarea>
		<button type="submit" class="depo-submit-btn">
			<i class="ri-send-plane-2-line"></i> Enviar
		</button>
	</form>
	<?php endif; ?>

	<!-- Grid -->
	<div class="depo-grid" id="depo-grid">
		<?php if ( $depoimentos ) : ?>
			<?php foreach ( $depoimentos as $depo ) :
				$author_id   = (int) $depo->user_id;
				$author_user = get_userdata( $author_id );
				$author_name = $author_user ? $author_user->display_name : $depo->comment_author;
				$author_avatar = $author_id
					? ( function_exists( 'Apollo\Users\apollo_get_user_avatar_url' )
						? \Apollo\Users\apollo_get_user_avatar_url( $author_id, 'thumb' )
						: get_avatar_url( $author_id, [ 'size' => 96 ] ) )
					: get_avatar_url( $depo->comment_author_email, [ 'size' => 96 ] );
				$author_role = $author_user
					? ( get_user_meta( $author_id, '_apollo_membership', true ) ?: '' )
					: '';
				$role_label = match( $author_role ) {
					'prod'    => 'Produtor',
					'dj'      => 'DJ',
					'host'    => 'Host',
					'govern'  => 'Governança',
					default   => 'Membro',
				};
				$can_delete = ( $is_own_profile || $author_id === get_current_user_id() || current_user_can( 'manage_options' ) );
			?>
			<div class="depo-card" data-depo-id="<?php echo (int) $depo->comment_ID; ?>">
				<p class="depo-quote"><?php echo esc_html( $depo->comment_content ); ?></p>
				<div class="depo-author">
					<img src="<?php echo esc_url( $author_avatar ); ?>" class="depo-avatar" alt="<?php echo esc_attr( $author_name ); ?>">
					<div class="depo-info">
						<div class="depo-name"><?php echo esc_html( $author_name ); ?></div>
						<div class="depo-role"><?php echo esc_html( $role_label ); ?></div>
					</div>
					<?php if ( $can_delete ) : ?>
						<button class="depo-delete-btn" data-depo-id="<?php echo (int) $depo->comment_ID; ?>" title="Remover">
							<i class="ri-close-line"></i>
						</button>
					<?php endif; ?>
				</div>
			</div>
			<?php endforeach; ?>
		<?php else : ?>
			<div class="depo-empty" style="grid-column:1/-1; text-align:center; padding:32px; color:var(--txt-muted); font-size:13px;">
				Nenhum depoimento ainda.
			</div>
		<?php endif; ?>
	</div>

</section>
