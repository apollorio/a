<?php

/**
 * Template: Single Classified View — V2 Resale Ticket
 *
 * Rendered by [apollo_classified] shortcode.
 * Override in theme: theme/apollo-adverts/single.php
 *
 * Available variables:
 *   $post       — WP_Post
 *   $post_id    — int
 *   $images     — array of WP_Post (attachments)
 *   $meta       — array of meta values
 *   $domains    — array of WP_Term
 *   $intents    — array of WP_Term
 *   $is_expired — bool
 *   $is_owner   — bool
 *
 * @package Apollo\Adverts
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$price          = apollo_adverts_get_the_price( $post_id );
$original_price = get_post_meta( $post_id, '_classified_original_price', true );
$negotiable     = ! empty( $meta['_classified_negotiable'] );
$condition      = apollo_adverts_get_condition_label( $post_id );
$intent         = apollo_adverts_get_intent_label( $post_id );
$location       = $meta['_classified_loc'] ?? '';
$phone          = $meta['_classified_contact_phone'] ?? '';
$whatsapp       = $meta['_classified_contact_whatsapp'] ?? '';
$expires        = $meta['_classified_expires_at'] ?? '';
$views          = (int) get_post_meta( $post_id, '_classified_views', true );

$author      = get_userdata( $post->post_author );
$author_url  = $author ? home_url( '/id/' . $author->user_login ) : '#';
$author_name = $author ? $author->display_name : '';
// Seller identity + direct contact (phone/WhatsApp/profile link) are
// member-only — genuinely not rendered for guests, not just CSS-hidden.
$is_logged_in_viewer = is_user_logged_in();
?>

<div class="apollo-adverts-single<?php echo $is_expired ? ' is-expired' : ''; ?>">

	<?php if ( $is_expired ) : ?>
		<div class="apollo-adverts-notice warning">
			<i class="ri-error-warning-line"></i>
			<?php esc_html_e( 'Este anúncio expirou.', 'apollo-adverts' ); ?>
			<?php if ( $is_owner ) : ?>
				<?php
				$renew_url = wp_nonce_url(
					add_query_arg(
						array(
							'action'  => 'apollo_renew_ad',
							'post_id' => $post_id,
						),
						admin_url( 'admin-post.php' )
					),
					'apollo_renew_' . $post_id
				);
				?>
				<a href="<?php echo esc_url( $renew_url ); ?>" class="button"><?php esc_html_e( 'Renovar Anúncio', 'apollo-adverts' ); ?></a>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<div class="apollo-adverts-single-layout">

		<!-- Galeria -->
		<div class="apollo-adverts-single-gallery">
			<?php if ( ! empty( $images ) ) : ?>
				<div class="apollo-gallery-main">
					<?php
					$main_img = wp_get_attachment_image_src( $images[0]->ID, 'classified-large' );
					if ( $main_img ) :
						?>
						<img src="<?php echo esc_url( $main_img[0] ); ?>" alt="<?php echo esc_attr( $post->post_title ); ?>" id="apollo-gallery-main-img" />
					<?php endif; ?>
				</div>

				<?php if ( count( $images ) > 1 ) : ?>
					<div class="apollo-gallery-thumbs">
						<?php
						foreach ( $images as $idx => $img ) :
							$thumb = wp_get_attachment_image_src( $img->ID, 'classified-thumb' );
							$full  = wp_get_attachment_image_src( $img->ID, 'classified-large' );
							if ( ! $thumb || ! $full ) {
								continue;
							}
							?>
							<button type="button"
								class="apollo-gallery-thumb<?php echo $idx === 0 ? ' active' : ''; ?>"
								data-full="<?php echo esc_url( $full[0] ); ?>">
								<img src="<?php echo esc_url( $thumb[0] ); ?>" alt="" />
							</button>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			<?php else : ?>
				<div class="apollo-gallery-placeholder">
					<i class="ri-ticket-line"></i>
				</div>
			<?php endif; ?>
		</div>

		<!-- Informações -->
		<div class="apollo-adverts-single-info">

			<h1 class="apollo-adverts-single-title"><?php echo esc_html( $post->post_title ); ?></h1>

			<?php if ( $price ) : ?>
				<div class="apollo-adverts-single-price">
					<span class="price-label"><?php esc_html_e( 'Preço pedido', 'apollo-adverts' ); ?></span>
					<span class="price-value"><?php echo esc_html( $price ); ?></span>
					<?php if ( $negotiable ) : ?>
						<span class="price-negotiable"><?php esc_html_e( 'A combinar', 'apollo-adverts' ); ?></span>
					<?php endif; ?>
				</div>
				<?php if ( $original_price ) : ?>
					<div style="margin-top:-12px;margin-bottom:24px;">
						<span style="font-family:var(--av2-ff-mono,monospace);font-size:11px;color:var(--av2-grey-light,#999);text-decoration:line-through;">
							<?php echo esc_html( apollo_adverts_format_price( (float) $original_price ) ); ?>
						</span>
						<span style="font-family:var(--av2-ff-main,sans-serif);font-size:9px;color:var(--av2-grey-light,#999);text-transform:uppercase;letter-spacing:0.5px;margin-left:4px;">
							<?php esc_html_e( 'Valor original', 'apollo-adverts' ); ?>
						</span>
					</div>
				<?php endif; ?>
			<?php endif; ?>

			<!-- Meta -->
			<div class="apollo-adverts-single-meta">
				<?php if ( $intent ) : ?>
					<div class="meta-row">
						<span class="meta-label"><?php esc_html_e( 'Intenção', 'apollo-adverts' ); ?></span>
						<span class="meta-value"><?php echo esc_html( $intent ); ?></span>
					</div>
				<?php endif; ?>

				<?php if ( $condition ) : ?>
					<div class="meta-row">
						<span class="meta-label"><?php esc_html_e( 'Condição', 'apollo-adverts' ); ?></span>
						<span class="meta-value"><?php echo esc_html( $condition ); ?></span>
					</div>
				<?php endif; ?>

				<?php if ( ! empty( $domains ) ) : ?>
					<div class="meta-row">
						<span class="meta-label"><?php esc_html_e( 'Categoria', 'apollo-adverts' ); ?></span>
						<span class="meta-value">
							<?php echo esc_html( implode( ', ', wp_list_pluck( $domains, 'name' ) ) ); ?>
						</span>
					</div>
				<?php endif; ?>

				<?php if ( $location ) : ?>
					<div class="meta-row">
						<span class="meta-label"><i class="ri-map-pin-line" style="font-size:12px;vertical-align:-1px;margin-right:2px;"></i> <?php esc_html_e( 'Local', 'apollo-adverts' ); ?></span>
						<span class="meta-value"><?php echo esc_html( $location ); ?></span>
					</div>
				<?php endif; ?>

				<div class="meta-row">
					<span class="meta-label"><?php esc_html_e( 'Publicado', 'apollo-adverts' ); ?></span>
					<span class="meta-value"><?php echo esc_html( get_the_date( 'd/m/Y', $post ) ); ?></span>
				</div>

				<?php if ( $expires ) : ?>
					<div class="meta-row">
						<span class="meta-label"><?php esc_html_e( 'Válido até', 'apollo-adverts' ); ?></span>
						<span class="meta-value"><?php echo esc_html( $expires ); ?></span>
					</div>
				<?php endif; ?>

				<div class="meta-row">
					<span class="meta-label"><?php esc_html_e( 'Visualizações', 'apollo-adverts' ); ?></span>
					<span class="meta-value"><?php echo esc_html( (string) $views ); ?></span>
				</div>
			</div>

			<!-- Contato — Apollo conecta pessoas -->
			<div class="apollo-adverts-single-contact">
				<h3><i class="ri-shield-check-line" style="color:var(--av2-primary,FF9820);margin-right:6px;"></i><?php esc_html_e( 'Interessado? Inicie uma conversa', 'apollo-adverts' ); ?></h3>
				<p class="contact-bridge-note"><?php esc_html_e( 'Apollo conecta pessoas. Converse pelo Chat para combinar os detalhes diretamente com o anunciante.', 'apollo-adverts' ); ?></p>

				<?php if ( $is_logged_in_viewer ) : ?>
					<?php
					// Chat CTA principal — aciona o modal de segurança
					?>
					<button type="button" class="apollo-adverts-chat-btn" id="apollo-safety-trigger" data-post-id="<?php echo esc_attr( (string) $post_id ); ?>">
						<i class="ri-chat-3-line"></i> <?php esc_html_e( 'Iniciar Chat', 'apollo-adverts' ); ?>
					</button>
				<?php else : ?>
					<a href="<?php echo esc_url( home_url( '/acesso?redirect=' . rawurlencode( get_permalink( $post_id ) ) ) ); ?>" class="apollo-adverts-chat-btn apollo-adverts-chat-login">
						<i class="ri-chat-3-line"></i> <?php esc_html_e( 'Faça login para contatar', 'apollo-adverts' ); ?>
					</a>
				<?php endif; ?>

				<?php if ( $is_logged_in_viewer && $author ) : ?>
					<div class="contact-author">
						<?php echo get_avatar( $author->ID, 40 ); ?>
						<span style="display:inline-flex;align-items:center;gap:5px;">
							<?php
							printf( '<a href="%s">%s</a>', esc_url( $author_url ), esc_html( $author_name ) );
							if ( function_exists( 'apollo_get_membership_badge_html' ) ) {
								echo apollo_get_membership_badge_html( $author->ID, 'sm' );
							}
							?>
						</span>
					</div>
				<?php elseif ( $author ) : ?>
					<div class="contact-author contact-author--locked">
						<i class="ri-lock-2-line" aria-hidden="true"></i>
						<span><?php esc_html_e( 'Identidade do vendedor visível apenas para membros', 'apollo-adverts' ); ?></span>
					</div>
				<?php endif; ?>

				<?php if ( $is_logged_in_viewer && $phone ) : ?>
					<a href="tel:<?php echo esc_attr( $phone ); ?>" class="contact-phone">
						<i class="ri-phone-line"></i> <?php echo esc_html( $phone ); ?>
					</a>
				<?php endif; ?>

				<?php if ( $is_logged_in_viewer && $whatsapp ) : ?>
					<?php
					$wa_number = preg_replace( '/[^0-9]/', '', $whatsapp );
					$wa_text   = rawurlencode( sprintf( __( 'Olá! Vi seu anúncio "%s" e tenho interesse.', 'apollo-adverts' ), $post->post_title ) );
					?>
					<a href="https://wa.me/<?php echo esc_attr( $wa_number ); ?>?text=<?php echo $wa_text; ?>" class="contact-whatsapp" target="_blank" rel="noopener">
						<i class="ri-whatsapp-line"></i> <?php esc_html_e( 'WhatsApp', 'apollo-adverts' ); ?>
					</a>
				<?php elseif ( ! $is_logged_in_viewer && ( $phone || $whatsapp ) ) : ?>
					<a href="<?php echo esc_url( home_url( '/acesso?redirect=' . rawurlencode( get_permalink( $post_id ) ) ) ); ?>" class="contact-locked">
						<i class="ri-lock-2-line"></i> <?php esc_html_e( 'Entre para ver o contato', 'apollo-adverts' ); ?>
					</a>
				<?php endif; ?>

			</div>

			<!-- Ações -->
			<div class="apollo-adverts-single-actions">
				<?php echo apollo_adverts_fav_button( $post_id ); ?>
				<?php echo apollo_adverts_wow_reactions( $post_id ); ?>

				<?php if ( $is_owner ) : ?>
					<?php
					$submit_page = get_option( 'apollo_adverts_submit_page_id', 0 );
					$edit_url    = $submit_page ? add_query_arg( 'edit', $post_id, get_permalink( $submit_page ) ) : '#';
					?>
					<a href="<?php echo esc_url( $edit_url ); ?>" class="button"><i class="ri-edit-line" style="margin-right:4px;"></i><?php esc_html_e( 'Editar Anúncio', 'apollo-adverts' ); ?></a>
				<?php endif; ?>

				<?php
				// ── Report button ──
				if ( function_exists( 'apollo_render_report_button' ) ) {
					apollo_render_report_button( $post_id, 'inline' );
				}
				?>
			</div>
		</div>
	</div>

	<!-- Descrição -->
	<div class="apollo-adverts-single-description">
		<h2><?php esc_html_e( 'Descrição', 'apollo-adverts' ); ?></h2>
		<div class="entry-content">
			<?php echo wp_kses_post( wpautop( $post->post_content ) ); ?>
		</div>
	</div>

</div>

<?php if ( $is_logged_in_viewer ) : ?>
<!-- ═══════════════════════════════════════════════════════
	MODAL DE SEGURANÇA — Verificação antes do Chat
	Only rendered for logged-in viewers — this is the only place
	$author_url / $author->ID (seller identity) reach the page
	source, so guests must never receive this markup at all.
	═══════════════════════════════════════════════════════ -->
<div class="safety-overlay" id="apollo-safety-overlay">
	<div class="safety-modal">

		<div class="safety-modal__icon">
			<i class="ri-shield-check-line"></i>
		</div>

		<h3 class="safety-modal__title"><?php esc_html_e( 'Antes de prosseguir', 'apollo-adverts' ); ?></h3>
		<p class="safety-modal__subtitle"><?php esc_html_e( 'Verificação de segurança', 'apollo-adverts' ); ?></p>

		<div class="safety-steps">
			<div class="safety-step">
				<span class="safety-step__num">1</span>
				<div class="safety-step__text">
					<div class="safety-step__label"><?php esc_html_e( 'Abra o perfil do vendedor', 'apollo-adverts' ); ?></div>
					<div class="safety-step__desc"><?php esc_html_e( 'Verifique o histórico e a reputação do anunciante na comunidade Apollo.', 'apollo-adverts' ); ?></div>
				</div>
			</div>
			<div class="safety-step">
				<span class="safety-step__num">2</span>
				<div class="safety-step__text">
					<div class="safety-step__label"><?php esc_html_e( 'Confira votos "Confiável"', 'apollo-adverts' ); ?></div>
					<div class="safety-step__desc"><?php printf( esc_html__( 'Veja se %1$samigos em comum%2$s marcaram esse perfil como confiável.', 'apollo-adverts' ), '<strong>', '</strong>' ); ?></div>
				</div>
			</div>
			<div class="safety-step">
				<span class="safety-step__num">3</span>
				<div class="safety-step__text">
					<div class="safety-step__label"><?php esc_html_e( 'Peça confirmação a um amigo', 'apollo-adverts' ); ?></div>
					<div class="safety-step__desc"><?php esc_html_e( 'Se possível, pergunte a alguém que já negociou com esse vendedor.', 'apollo-adverts' ); ?></div>
				</div>
			</div>
		</div>

		<div class="safety-tip">
			<i class="ri-instagram-line"></i>
			<p><?php printf( esc_html__( '%1$sDica:%2$s Confirme o Instagram do vendedor. Perfis com fotos reais e histórico consistente são mais confiáveis.', 'apollo-adverts' ), '<strong>', '</strong>' ); ?></p>
		</div>

		<div class="safety-alert">
			<i class="ri-error-warning-line"></i>
			<p><?php esc_html_e( 'Apollo conecta pessoas — não intermedia transações. Negocie e combine diretamente com o vendedor.', 'apollo-adverts' ); ?></p>
		</div>

		<div class="safety-confirm">
			<input type="checkbox" id="apollo-safety-checkbox" />
			<label for="apollo-safety-checkbox"><?php esc_html_e( 'Entendo que toda negociação acontece diretamente entre as partes e que Apollo não é responsável por transações.', 'apollo-adverts' ); ?></label>
		</div>

		<div class="safety-actions">
			<?php if ( $author ) : ?>
				<a href="<?php echo esc_url( $author_url ); ?>" class="safety-btn safety-btn--profile" target="_blank">
					<i class="ri-user-line" style="margin-right:4px;"></i> <?php esc_html_e( 'Abrir Perfil do Vendedor', 'apollo-adverts' ); ?>
				</a>
			<?php endif; ?>

			<button type="button" class="safety-btn safety-btn--proceed" id="apollo-safety-proceed" disabled>
				<i class="ri-chat-3-line" style="margin-right:4px;"></i> <?php esc_html_e( 'Abrir Chat', 'apollo-adverts' ); ?>
			</button>

			<button type="button" class="safety-btn safety-btn--cancel" id="apollo-safety-cancel">
				<?php esc_html_e( 'Cancelar', 'apollo-adverts' ); ?>
			</button>
		</div>
	</div>
</div>

<script>
	(function() {
		var trigger = document.getElementById('apollo-safety-trigger');
		var overlay = document.getElementById('apollo-safety-overlay');
		var checkbox = document.getElementById('apollo-safety-checkbox');
		var proceed = document.getElementById('apollo-safety-proceed');
		var cancel = document.getElementById('apollo-safety-cancel');

		if (!trigger || !overlay) return;

		trigger.addEventListener('click', function() {
			overlay.classList.add('is-active');
			document.body.style.overflow = 'hidden';
		});

		if (cancel) {
			cancel.addEventListener('click', function() {
				overlay.classList.remove('is-active');
				document.body.style.overflow = '';
			});
		}

		overlay.addEventListener('click', function(e) {
			if (e.target === overlay) {
				overlay.classList.remove('is-active');
				document.body.style.overflow = '';
			}
		});

		if (checkbox && proceed) {
			checkbox.addEventListener('change', function() {
				proceed.disabled = !this.checked;
			});

			proceed.addEventListener('click', function() {
				if (checkbox.checked) {
					overlay.classList.remove('is-active');
					document.body.style.overflow = '';
					<?php
					// Dispara chat via apollo-chat, se disponível
					?>
					var postId = trigger.getAttribute('data-post-id');
					if (typeof window.ApolloChat !== 'undefined' && window.ApolloChat.open) {
						window.ApolloChat.open(<?php echo (int) ( $author ? $author->ID : 0 ); ?>, postId);
					} else {
						<?php if ( $author ) : ?>
							window.location.href = '<?php echo esc_url( $author_url ); ?>';
						<?php endif; ?>
					}
				}
			});
		}
	})();
</script>
<?php endif; // $is_logged_in_viewer ?>