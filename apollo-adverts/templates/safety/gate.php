<?php

/**
 * Template: /seguranca/?anuncio={id}
 *
 * The full-page security interstitial. Owns nothing but the shell — every idea
 * on the page is its own part under templates/parts/safety/.
 *
 * Bails in three cases, all of them by sending the member somewhere useful
 * rather than showing a warning that does not apply:
 *   · no advert            → marketplace
 *   · exempt advert        → the advert (hostel: its CTA is a booking link)
 *   · already cleared      → straight through to the chat
 *
 * @package Apollo\Adverts
 */

declare(strict_types=1);

use Apollo\Adverts\Safety\Gate;

if (! defined('ABSPATH')) {
    exit;
}

$apollo_gate_post = Gate::advert_id();

if (! $apollo_gate_post || ! get_post($apollo_gate_post)) {
    wp_safe_redirect(home_url('/anuncios/'));
    exit;
}

if (function_exists('apollo_safety_applies') && ! apollo_safety_applies($apollo_gate_post)) {
    wp_safe_redirect((string) get_permalink($apollo_gate_post));
    exit;
}

if (apollo_adverts_safety_cleared($apollo_gate_post)) {
    wp_safe_redirect(Gate::redirect_to($apollo_gate_post));
    exit;
}

wp_enqueue_style('apollo-adverts-safety-gate');
wp_enqueue_script('apollo-adverts-safety-gate');

if (function_exists('apollo_render_document_open')) {
    apollo_render_document_open(
        array(
            'title'      => __('Aviso de segurança — Apollo::Rio', 'apollo-adverts'),
            'body_class' => 'apollo-safety',
            'theme_color' => '#2D1D03',
        )
    );
}
?>

<?php /* data-boot flips to "ready" only when every signal has answered and its
        avatars have decoded. If one never answers it stays "loading" forever —
        deliberately. See templates/parts/safety/preloader.php. */ ?>
<div class="apollo-stage ap-safety" id="apSafetyStage" data-boot="loading">
	<div class="apollo-warn" id="apSafetyScroller">
		<div class="apollo-warn__inner" id="apSafetyInner">
			<?php apollo_safety_render($apollo_gate_post); ?>
		</div>
	</div>
</div>

<?php
if (function_exists('apollo_render_document_close')) {
    apollo_render_document_close();
}
