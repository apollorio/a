<?php

/**
 * Single classified — layout.
 *
 * Left: the same marketplace card that announced this listing on /anuncios.
 * Right: title/meta/description + #contato pre-contact (safety gate when
 * required, otherwise the chat CTA).
 *
 * Expects from single-classified.php: $classified_id, $is_ticket, $is_accom,
 * $needs_gate.
 *
 * @package Apollo\Adverts
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

$post_id             = (int) $classified_id;
$is_logged_in_viewer = is_user_logged_in();
$price               = get_post_meta($post_id, '_classified_price', true);
$currency            = get_post_meta($post_id, '_classified_currency', true) ?: 'BRL';
$currency_symbol     = $currency === 'USD' ? '$' : ($currency === 'EUR' ? '€' : 'R$');
$negotiable          = get_post_meta($post_id, '_classified_negotiable', true);
$location            = (string) get_post_meta($post_id, '_classified_loc', true);
$event_title         = (string) get_post_meta($post_id, '_classified_event_title', true);
$event_date          = (string) get_post_meta($post_id, '_classified_event_date', true);
$event_location      = (string) get_post_meta($post_id, '_classified_event_loc', true);
$qty                 = (int) get_post_meta($post_id, '_classified_quantity', true);

$card_file = APOLLO_ADVERTS_DIR . 'templates/marketplace/parts/' . ($is_accom && ! $is_ticket ? 'card-accommodation.php' : 'card-ticket.php');

/* Cards on the single must not navigate to themselves or open the archive popup. */
$mk_single_mode = true;
?>
<div class="mk-single" itemscope itemtype="https://schema.org/Product">
	<nav class="mk-single__nav" aria-label="<?php esc_attr_e('Navegação do anúncio', 'apollo-adverts'); ?>">
		<a href="<?php echo esc_url(home_url('/anuncios/')); ?>">
			<i class="ri-arrow-left-line" aria-hidden="true"></i>
			<?php esc_html_e('Todos os anúncios', 'apollo-adverts'); ?>
		</a>
	</nav>

	<div class="mk-single__grid">
		<div class="mk-single__card">
			<?php
			if (is_readable($card_file)) {
				$GLOBALS['post'] = get_post($post_id);
				setup_postdata($GLOBALS['post']);
				include $card_file;
				wp_reset_postdata();
			}
			?>
		</div>

		<div class="mk-single__panel">
			<p class="mk-single__eyebrow">
				<?php
				if ($is_ticket) {
					esc_html_e('Repasse de ingresso', 'apollo-adverts');
				} elseif ($is_accom) {
					esc_html_e('Hospedagem', 'apollo-adverts');
				} else {
					esc_html_e('Anúncio', 'apollo-adverts');
				}
				?>
			</p>

			<h1 class="mk-single__title" itemprop="name"><?php echo esc_html(get_the_title($post_id)); ?></h1>

			<?php if ($price !== '' && $price !== null) : ?>
				<p class="mk-single__price" itemprop="offers" itemscope itemtype="https://schema.org/Offer">
					<span itemprop="priceCurrency" content="<?php echo esc_attr($currency); ?>"></span>
					<strong itemprop="price" content="<?php echo esc_attr((string) $price); ?>">
						<?php echo esc_html($currency_symbol . ' ' . number_format((float) $price, 2, ',', '.')); ?>
					</strong>
					<?php if ($negotiable) : ?>
						<small><?php esc_html_e('(negociável)', 'apollo-adverts'); ?></small>
					<?php endif; ?>
				</p>
			<?php endif; ?>

			<div class="mk-single__meta">
				<?php if ($event_date) : ?>
					<span><i class="ri-calendar-line" aria-hidden="true"></i> <?php echo esc_html($event_date); ?></span>
				<?php endif; ?>
				<?php if ($event_location || $location) : ?>
					<span><i class="ri-map-pin-line" aria-hidden="true"></i> <?php echo esc_html($event_location ?: $location); ?></span>
				<?php endif; ?>
				<?php if ($qty > 1) : ?>
					<span>
						<?php
						printf(
							esc_html(
								/* translators: %d: ticket count */
								_n('%d ingresso', '%d ingressos', $qty, 'apollo-adverts')
							),
							$qty
						);
						?>
					</span>
				<?php endif; ?>
			</div>

			<?php if ($is_ticket && ($event_title || $event_date || $event_location)) : ?>
				<div class="mk-single__box">
					<h3><i class="ri-calendar-event-line" aria-hidden="true"></i> <?php esc_html_e('Dados do evento', 'apollo-adverts'); ?></h3>
					<?php if ($event_title) : ?>
						<p><strong><?php esc_html_e('Evento:', 'apollo-adverts'); ?></strong> <?php echo esc_html($event_title); ?></p>
					<?php endif; ?>
					<?php if ($event_date) : ?>
						<p><strong><?php esc_html_e('Data:', 'apollo-adverts'); ?></strong> <?php echo esc_html($event_date); ?></p>
					<?php endif; ?>
					<?php if ($event_location) : ?>
						<p><?php echo esc_html($event_location); ?></p>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<?php if ($is_accom && $location) : ?>
				<div class="mk-single__box">
					<h3><i class="ri-map-pin-line" aria-hidden="true"></i> <?php esc_html_e('Localização', 'apollo-adverts'); ?></h3>
					<p><?php echo esc_html($location); ?></p>
				</div>
			<?php endif; ?>

			<?php
			$content = get_post_field('post_content', $post_id);
			if (is_string($content) && trim($content) !== '') :
				?>
				<div class="mk-single__body mk-single__box" itemprop="description">
					<?php
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the_content filters.
					echo apply_filters('the_content', $content);
					?>
				</div>
			<?php endif; ?>

			<section class="mk-single__contact mk-single__box" id="contato" aria-labelledby="mk-single-contact-title">
				<h3 id="mk-single-contact-title">
					<i class="ri-shield-check-line" aria-hidden="true"></i>
					<?php
					echo esc_html(
						$needs_gate
							? __('Antes de falar com o anunciante', 'apollo-adverts')
							: __('Contato', 'apollo-adverts')
					);
					?>
				</h3>

				<?php if ($needs_gate) : ?>
					<p style="margin:0 0 14px;font-size:14px;color:var(--muted);">
						<?php esc_html_e('Confirme pessoas em comum (ou verificação Apollo) antes de abrir a conversa. O servidor bloqueia o chat até essa etapa.', 'apollo-adverts'); ?>
					</p>
					<div class="apollo-stage ap-safety" id="apSafetyStage" data-boot="loading">
						<div class="apollo-warn" id="apSafetyScroller">
							<div class="apollo-warn__inner" id="apSafetyInner">
								<?php
								if (function_exists('apollo_safety_render')) {
									apollo_safety_render($post_id);
								}
								?>
							</div>
						</div>
					</div>
				<?php elseif (function_exists('apollo_adverts_chat_button')) : ?>
					<?php
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- button helper escapes.
					echo apollo_adverts_chat_button($post_id);
					?>
				<?php else : ?>
					<a class="apollo-adverts-chat-btn" href="<?php echo esc_url(home_url('/acesso?redirect=' . rawurlencode((string) get_permalink($post_id)))); ?>">
						<i class="ri-lock-2-line" aria-hidden="true"></i>
						<?php esc_html_e('Entre para contatar', 'apollo-adverts'); ?>
					</a>
				<?php endif; ?>
			</section>
		</div>
	</div>
</div>
<?php
unset($mk_single_mode);
