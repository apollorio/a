<?php

/**
 * Template: /seguranca/?anuncio={id}
 *
 * The full-page security interstitial. Owns nothing but the shell — every idea
 * on the page is its own part under templates/parts/safety/.
 *
 * Buyer mode bails when:
 *   · no advert            → marketplace
 *   · exempt advert        → the advert (hostel: its CTA is a booking link)
 *   · already cleared      → straight through to the chat
 *
 * Witness mode (?witness=1&buyer=) skips the "cleared" redirect — the witness
 * is answering for someone else.
 *
 * @package Apollo\Adverts
 */

declare(strict_types=1);

use Apollo\Adverts\Safety\Gate;

if (! defined('ABSPATH')) {
    exit;
}

$apollo_gate_post = Gate::advert_id();
$apollo_witness   = Gate::is_witness_mode();

if (! $apollo_gate_post || ! get_post($apollo_gate_post)) {
    wp_safe_redirect(home_url('/anuncios/'));
    exit;
}

if (function_exists('apollo_safety_applies') && ! apollo_safety_applies($apollo_gate_post)) {
    wp_safe_redirect((string) get_permalink($apollo_gate_post));
    exit;
}

if (! $apollo_witness && apollo_adverts_safety_cleared($apollo_gate_post)) {
    wp_safe_redirect(Gate::redirect_to($apollo_gate_post));
    exit;
}

/* Witness deep link without a valid pending ask → marketplace. */
if (
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    isset($_GET['witness']) && ! $apollo_witness
) {
    wp_safe_redirect(home_url('/anuncios/'));
    exit;
}

wp_enqueue_style('apollo-adverts-safety-gate');
wp_enqueue_script('apollo-adverts-safety-gate');

if (function_exists('apollo_render_document_open')) {
    apollo_render_document_open(
        array(
            'title'       => $apollo_witness
                ? __('Confirmar pessoa — Apollo::Rio', 'apollo-adverts')
                : __('Aviso de segurança — Apollo::Rio', 'apollo-adverts'),
            'body_class'  => 'apollo-safety',
            'theme_color' => '#2D1D03',
        )
    );
}

$boot = $apollo_witness ? 'ready' : 'loading';
?>

<div class="apollo-stage ap-safety<?php echo $apollo_witness ? ' is-witness' : ''; ?>" id="apSafetyStage" data-boot="<?php echo esc_attr($boot); ?>"<?php echo $apollo_witness ? ' data-mode="witness"' : ''; ?>>
	<div class="apollo-warn" id="apSafetyScroller">
		<div class="apollo-warn__inner" id="apSafetyInner">
			<?php apollo_safety_render($apollo_gate_post); ?>
		</div>
	</div>
	<?php
	if (! $apollo_witness) {
		Gate::part('verdict', array('post_id' => $apollo_gate_post));
	}
	?>
</div>

<?php
if (function_exists('apollo_render_document_close')) {
    apollo_render_document_close();
}
