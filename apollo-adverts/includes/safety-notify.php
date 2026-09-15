<?php

/**
 * Safety vouch notifications — deep link for the witness.
 *
 * apollo-notif may listen later; until then we store a transient the witness
 * can see and expose the canonical URL on the vouch_requested action.
 *
 * @package Apollo\Adverts
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Meta key for a pending vouch ask on the witness.
 */
function apollo_adverts_safety_request_key(int $advert, int $buyer): string
{
    return 'ap_vouch_' . $advert . '_' . $buyer;
}

/**
 * Was this witness asked to confirm for this buyer/advert pair?
 */
function apollo_adverts_safety_was_asked(int $advert, int $buyer, int $witness): bool
{
    if (! $advert || ! $buyer || ! $witness) {
        return false;
    }

    return (bool) get_user_meta($witness, apollo_adverts_safety_request_key($advert, $buyer), true);
}

/**
 * Deep link the witness opens to confirm.
 */
function apollo_adverts_safety_witness_url(int $advert, int $buyer): string
{
    if (class_exists('\\Apollo\\Adverts\\Safety\\Gate')) {
        return \Apollo\Adverts\Safety\Gate::witness_url($advert, $buyer);
    }

    return (string) add_query_arg(
        array(
            'anuncio' => $advert,
            'buyer'   => $buyer,
            'witness' => '1',
        ),
        home_url('/seguranca/')
    );
}

/**
 * When a buyer asks a mutual, remember a transient inbox item for the witness
 * and fire a filterable URL for notif plugins.
 *
 * @param int $witness_id Witness user id.
 * @param int $seller     Seller user id.
 * @param int $buyer      Buyer user id.
 * @param int $advert     Advert id.
 */
function apollo_adverts_safety_on_vouch_requested(int $witness_id, int $seller, int $buyer, int $advert): void
{
    if (! $witness_id || ! $advert || ! $buyer) {
        return;
    }

    $url = apollo_adverts_safety_witness_url($advert, $buyer);
    $seller_user = get_userdata($seller);
    $buyer_user  = get_userdata($buyer);

    set_transient(
        'ap_safety_inbox_' . $witness_id,
        array(
            'advert'  => $advert,
            'buyer'   => $buyer,
            'seller'  => $seller,
            'url'     => $url,
            'at'      => time(),
            'message' => sprintf(
                /* translators: 1: buyer handle, 2: seller handle */
                __('%1$s pediu que você confirme conhecer %2$s', 'apollo-adverts'),
                $buyer_user ? '@' . $buyer_user->user_login : '#' . $buyer,
                $seller_user ? '@' . $seller_user->user_login : '#' . $seller
            ),
        ),
        3 * DAY_IN_SECONDS
    );

    /**
     * Witness confirmation URL (for apollo-notif or email).
     *
     * @param string $url         Deep link.
     * @param int    $witness_id  Witness.
     * @param int    $seller      Seller.
     * @param int    $buyer       Buyer.
     * @param int    $advert      Advert.
     */
    do_action('apollo_adverts_safety_witness_link', $url, $witness_id, $seller, $buyer, $advert);
}
add_action('apollo_safety_vouch_requested', 'apollo_adverts_safety_on_vouch_requested', 10, 4);

/**
 * Admin-bar nudge when the current member has a pending vouch ask.
 */
function apollo_adverts_safety_admin_bar(WP_Admin_Bar $bar): void
{
    if (! is_user_logged_in() || is_admin()) {
        return;
    }

    $payload = get_transient('ap_safety_inbox_' . get_current_user_id());
    if (! is_array($payload) || empty($payload['url'])) {
        return;
    }

    $bar->add_node(
        array(
            'id'    => 'apollo-safety-vouch',
            'title' => esc_html__('Confirmar pessoa · Apollo', 'apollo-adverts'),
            'href'  => esc_url($payload['url']),
            'meta'  => array('class' => 'apollo-safety-vouch-nudge'),
        )
    );
}
add_action('admin_bar_menu', 'apollo_adverts_safety_admin_bar', 80);
