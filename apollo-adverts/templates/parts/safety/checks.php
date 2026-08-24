<?php

/**
 * Part: safety/checks — the three signals.
 *
 * Order is deliberate. Instagram mutuals first because it is the only signal
 * the seller does not control and the buyer can re-verify by hand on a
 * platform we do not own. Apollo trust votes second, marked context-only.
 * Staff verification last because it is the rarest.
 *
 * @var int $post_id
 * @var int $seller_id
 *
 * @package Apollo\Adverts
 */

use Apollo\Adverts\Safety\Gate;

if (! defined('ABSPATH')) {
    exit;
}

$ap_viewer = wp_get_current_user();
$ap_seller = get_userdata($seller_id);

$ap_pair = ($ap_viewer && $ap_seller)
    ? sprintf(
        /* translators: 1: viewer handle, 2: seller handle */
        __('cruzando @%1$s e @%2$s', 'apollo-adverts'),
        $ap_viewer->user_login,
        $ap_seller->user_login
    )
    : '';

/* Not "no Instagram". The list is computed from Apollo's own depoimentos —
   people who wrote about you, or about whom you wrote — because no Instagram
   API returns a follower list and every integration that claims to is a
   scraper one layout change away from lying to a buyer. */
?>
<div class="ap-checks" data-advert="<?php echo esc_attr((string) $post_id); ?>"
	data-seller="<?php echo esc_attr((string) $seller_id); ?>">
	<?php
	Gate::part(
		'check',
		array(
			'key'   => 'instagram',
			'title' => __('Pessoas que vocês dois conhecem', 'apollo-adverts'),
			'gate'  => 'unlock',
			'pair'  => $ap_pair,
		)
	);

	Gate::part(
		'check',
		array(
			'key'   => 'trust',
			'title' => __('Já negociaram com ele na Apollo', 'apollo-adverts'),
			'gate'  => 'context',
		)
	);

	Gate::part(
		'check',
		array(
			'key'   => 'verified',
			'title' => __('Verificado pela equipe Apollo', 'apollo-adverts'),
			'gate'  => 'unlock',
		)
	);
	?>
</div>
