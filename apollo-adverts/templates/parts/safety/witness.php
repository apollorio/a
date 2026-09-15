<?php

/**
 * Part: safety/witness — confirm you know the seller.
 *
 * Opened by the deep link /seguranca/?anuncio={id}&buyer={id}&witness=1 after
 * a buyer tapped "Pedir confirmação". One yes clears the buyer's gate.
 *
 * @var int $post_id
 * @var int $seller_id
 * @var int $buyer_id
 *
 * @package Apollo\Adverts
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

$post_id   = (int) ($args['post_id'] ?? $post_id ?? 0);
$seller_id = (int) ($args['seller_id'] ?? $seller_id ?? 0);
$buyer_id  = (int) ($args['buyer_id'] ?? $buyer_id ?? 0);

$seller = get_userdata($seller_id);
$buyer  = get_userdata($buyer_id);
$title  = get_the_title($post_id);

$seller_name = $seller ? ($seller->display_name ?: $seller->user_login) : __('anunciante', 'apollo-adverts');
$buyer_name  = $buyer ? ($buyer->display_name ?: $buyer->user_login) : __('alguém', 'apollo-adverts');
$seller_h    = $seller ? '@' . $seller->user_login : '';
$buyer_h     = $buyer ? '@' . $buyer->user_login : '';
?>
<header class="ap-lede">
	<span class="ap-seal" aria-hidden="true">
		<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 1L20.2169 2.82598C20.6745 2.92766 21 3.33347 21 3.80217V13.7889C21 15.795 19.9974 17.6684 18.3282 18.7812L12 23L5.6718 18.7812C4.00261 17.6684 3 15.795 3 13.7889V3.80217C3 3.33347 3.32553 2.92766 3.78307 2.82598L12 1ZM12 3.04879L5 4.60434V13.7889C5 15.1263 5.6684 16.3752 6.7812 17.1171L12 20.5963L17.2188 17.1171C18.3316 16.3752 19 15.1263 19 13.7889V4.60434L12 3.04879ZM11 15H13V17H11V15ZM11 7H13V13H11V7Z"/></svg>
	</span>
	<span class="ap-kicker"><?php esc_html_e('Apollo · Confirmação', 'apollo-adverts'); ?></span>
	<h1><?php esc_html_e('Alguém pediu sua confirmação', 'apollo-adverts'); ?></h1>
	<p>
		<?php
		printf(
			/* translators: 1: buyer name, 2: seller handle */
			esc_html__('%1$s quer falar com %2$s e pediu que você confirme que conhece essa pessoa de verdade.', 'apollo-adverts'),
			esc_html($buyer_name),
			esc_html($seller_h ?: $seller_name)
		);
		?>
	</p>
</header>

<article class="ap-witness" id="apWitness"
	data-advert="<?php echo esc_attr((string) $post_id); ?>"
	data-buyer="<?php echo esc_attr((string) $buyer_id); ?>">
	<div class="ap-witness__who">
		<span class="ap-witness__lbl"><?php esc_html_e('Quem pediu', 'apollo-adverts'); ?></span>
		<strong><?php echo esc_html($buyer_name); ?></strong>
		<span class="ap-witness__h"><?php echo esc_html($buyer_h); ?></span>
	</div>
	<div class="ap-witness__who">
		<span class="ap-witness__lbl"><?php esc_html_e('Sobre', 'apollo-adverts'); ?></span>
		<strong><?php echo esc_html($seller_name); ?></strong>
		<span class="ap-witness__h"><?php echo esc_html($seller_h); ?></span>
	</div>
	<div class="ap-witness__ad">
		<span class="ap-witness__lbl"><?php esc_html_e('Anúncio', 'apollo-adverts'); ?></span>
		<strong><?php echo esc_html($title); ?></strong>
	</div>

	<p class="ap-witness__say">
		<?php esc_html_e('Só confirme se você realmente conhece essa pessoa. Uma confirmação falsa coloca o comprador em risco.', 'apollo-adverts'); ?>
	</p>

	<div class="ap-witness__actions">
		<button type="button" class="btn btn-primary" id="apWitnessConfirm">
			<?php esc_html_e('Confirmo que conheço', 'apollo-adverts'); ?>
		</button>
		<a class="btn btn-secondary" href="<?php echo esc_url(home_url('/')); ?>">
			<?php esc_html_e('Agora não', 'apollo-adverts'); ?>
		</a>
	</div>

	<p class="ap-witness__done" id="apWitnessDone" hidden>
		<?php esc_html_e('Obrigado — a conversa do comprador foi liberada.', 'apollo-adverts'); ?>
	</p>
</article>
