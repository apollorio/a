<?php

/**
 * Safety REST — the three signals and the vouch that unlocks.
 *
 * WHAT IS REAL AND WHAT IS A SEAM
 * Trust votes and staff verification read Apollo's own data. Instagram mutuals
 * cannot: the overlap needs two paginated follower calls against a private
 * endpoint, which belongs in a sidecar with its own session and rate limits,
 * not in a page request. So it is a filter — `apollo_safety_ig_mutuals` — that
 * returns an empty list until Apollo_IG_Mutual or the Node sidecar answers it.
 *
 * Empty is the honest default. A signal that cannot be checked must read as
 * "not checked", never as "fine": the whole page exists because absence of
 * evidence gets mistaken for evidence of safety.
 *
 * @package Apollo\Adverts
 */

declare(strict_types=1);

namespace Apollo\Adverts\API;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if (! defined('ABSPATH')) {
    exit;
}

final class SafetyController
{
    private const NS = 'apollo/v1';

    /** Vouches are stored on the SELLER: "who says they know this person". */
    private const VOUCH_META = '_apollo_safety_vouches';

    public function register_routes(): void
    {
        register_rest_route(
            self::NS,
            '/safety/signals',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'signals'),
                'permission_callback' => array($this, 'can'),
                'args'                => array(
                    'advert' => array('required' => true, 'sanitize_callback' => 'absint'),
                ),
            )
        );

        register_rest_route(
            self::NS,
            '/safety/vouch',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'vouch'),
                'permission_callback' => array($this, 'can'),
                'args'                => array(
                    'advert'   => array('required' => true, 'sanitize_callback' => 'absint'),
                    'username' => array('required' => true, 'sanitize_callback' => 'sanitize_user'),
                ),
            )
        );

        // The other half of the loop: the witness answering.
        register_rest_route(
            self::NS,
            '/safety/vouch/confirm',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'confirm'),
                'permission_callback' => array($this, 'can'),
                'args'                => array(
                    'advert' => array('required' => true, 'sanitize_callback' => 'absint'),
                    'buyer'  => array('required' => true, 'sanitize_callback' => 'absint'),
                ),
            )
        );
    }

    /**
     * Logged in, and the advert has to exist. Nothing here is public: the gate
     * names people the viewer knows, which is not the internet's business.
     */
    public function can(WP_REST_Request $request)
    {
        if (! is_user_logged_in()) {
            return new WP_Error('apollo_safety_auth', __('Faça login.', 'apollo-adverts'), array('status' => 401));
        }

        if (! get_post((int) $request->get_param('advert'))) {
            return new WP_Error('apollo_safety_advert', __('Anúncio inexistente.', 'apollo-adverts'), array('status' => 404));
        }

        return true;
    }

    /**
     * All three signals for one advert, from the current member's point of view.
     */
    public function signals(WP_REST_Request $request): WP_REST_Response
    {
        $advert = (int) $request->get_param('advert');
        $seller = (int) get_post_field('post_author', $advert);
        $viewer = get_current_user_id();

        /**
         * People both members genuinely share.
         *
         * Answered by NativeGraph out of Apollo's own data — no API, no token,
         * no outbound request. The name is provider-neutral on purpose: the
         * shape is the private-API contract, so an Instagram sidecar can add
         * to this list later without the renderer learning where a person
         * came from.
         *
         *   array<int, array{id: string, username: string, full_name: string,
         *                    profile_pic_url: string|null}>
         *
         * @param array $mutuals Empty until a provider answers.
         * @param int   $viewer  Viewer user id.
         * @param int   $seller  Seller user id.
         */
        $mutuals = (array) apply_filters('apollo_safety_mutuals', array(), $viewer, $seller);

        /** Optional enrichment slot for a future Instagram sidecar. */
        $mutuals = (array) apply_filters('apollo_safety_ig_mutuals', $mutuals, $viewer, $seller);

        /**
         * Apollo members who marked this seller reliable. Context only.
         *
         * @param array $voters Same person shape as above.
         * @param int   $seller Seller user id.
         */
        $voters = (array) apply_filters('apollo_safety_trust_votes', array(), $seller);

        /* Vouches are stored on the seller, so they accumulate across every
           buyer who ever asked. Showing them raw would mean a stranger's
           witness unlocks YOUR gate — the same false-confidence bug as
           intersecting the auto-connected social graph. A vouch only counts
           for this buyer if that witness is also THIS buyer's mutual. */
        $handles = wp_list_pluck($mutuals, 'username');
        $vouches = array_values(
            array_filter(
                $this->vouches($seller),
                static function ($v) use ($handles) {
                    return isset($v['username']) && in_array($v['username'], (array) $handles, true);
                }
            )
        );

        return new WP_REST_Response(
            array(
                'instagram' => array(
                    'found'     => count($mutuals),
                    'mutuals'   => array_values($mutuals),
                    'confirmed' => array_values($vouches),
                    /* "Was the question actually asked?" The preloader hangs on
                       this: no provider means silence, and silence must never
                       render as a clean result. */
                    'provider'  => (bool) has_filter('apollo_safety_mutuals'),
                ),
                'trust'     => array('count' => count($voters), 'voters' => array_values($voters)),
                'verified'  => $this->is_verified($seller),
                'cleared'   => apollo_adverts_safety_cleared($advert, $viewer),
            ),
            200
        );
    }

    /**
     * Ask one mutual to confirm they know the seller.
     *
     * The witness must actually be a mutual — otherwise the endpoint is a
     * self-service unlock button, which is exactly the hole the gate exists to
     * close. With no provider wired there are no mutuals, so nothing vouches,
     * so nothing unlocks. That is correct, not broken.
     */
    public function vouch(WP_REST_Request $request)
    {
        $advert   = (int) $request->get_param('advert');
        $username = (string) $request->get_param('username');
        $seller   = (int) get_post_field('post_author', $advert);
        $viewer   = get_current_user_id();

        $mutuals = (array) apply_filters('apollo_safety_mutuals', array(), $viewer, $seller);
        $mutuals = (array) apply_filters('apollo_safety_ig_mutuals', $mutuals, $viewer, $seller);
        $handles = wp_list_pluck($mutuals, 'username');

        if (! in_array($username, (array) $handles, true)) {
            return new WP_Error(
                'apollo_safety_not_mutual',
                __('Essa pessoa não é amiga em comum de vocês dois.', 'apollo-adverts'),
                array('status' => 422)
            );
        }

        /**
         * Deliver the confirmation request and report whether it was granted.
         *
         * Synchronous today because the gate is a decision the member is making
         * right now. A provider that needs to notify and wait should return
         * false and clear the pair later via apollo_adverts_safety_clear().
         *
         * @param bool   $confirmed Default false — silence is not consent.
         * @param string $username  Witness handle.
         * @param int    $seller    Seller user id.
         * @param int    $viewer    Viewer user id.
         */
        $confirmed = (bool) apply_filters('apollo_safety_vouch_request', false, $username, $seller, $viewer);

        if (! $confirmed) {
            /* Nobody answers a REST call instantly, so the honest reply is
               "asked". The request is recorded and announced; the witness
               answers through /safety/vouch/confirm, which clears the pair
               then. Until that happens the gate stays shut. */
            $witness = get_user_by('login', $username);

            if ($witness) {
                $this->remember_request($advert, $viewer, (int) $witness->ID);

                /**
                 * A witness has been asked to confirm they know the seller.
                 *
                 * apollo-notif listens here and delivers it. No email, no API,
                 * no token — it is one Apollo member asking another.
                 *
                 * @param int $witness_id Person being asked.
                 * @param int $seller     Person they are being asked about.
                 * @param int $buyer      Person waiting at the gate.
                 * @param int $advert     Advert in question.
                 */
                do_action('apollo_safety_vouch_requested', (int) $witness->ID, $seller, $viewer, $advert);
            }

            return new WP_REST_Response(array('confirmed' => false, 'asked' => true, 'username' => $username), 202);
        }

        $this->store_vouch($seller, $username, $mutuals);
        apollo_adverts_safety_clear($advert, $viewer, 'ig_vouch:' . $username);

        return new WP_REST_Response(array('confirmed' => true, 'username' => $username), 200);
    }

    /**
     * The witness answers yes.
     *
     * No token and no signed link: the witness has to BE the witness, logged
     * in as themselves, and still be a genuine mutual of both sides at the
     * moment they answer. A link that carries its own authority is a link that
     * can be forwarded to the person it was meant to vouch for.
     */
    public function confirm(WP_REST_Request $request)
    {
        $advert  = (int) $request->get_param('advert');
        $buyer   = (int) $request->get_param('buyer');
        $seller  = (int) get_post_field('post_author', $advert);
        $witness = get_current_user_id();

        if ($witness === $seller || $witness === $buyer) {
            return new WP_Error(
                'apollo_safety_self',
                __('Você não pode confirmar a si mesmo.', 'apollo-adverts'),
                array('status' => 403)
            );
        }

        if (! $this->was_asked($advert, $buyer, $witness)) {
            return new WP_Error(
                'apollo_safety_not_asked',
                __('Ninguém pediu sua confirmação para esse anúncio.', 'apollo-adverts'),
                array('status' => 403)
            );
        }

        // Still a mutual of both? Re-checked now, not trusted from the request.
        $mutuals = (array) apply_filters('apollo_safety_mutuals', array(), $buyer, $seller);
        $ids     = array_map('intval', wp_list_pluck($mutuals, 'id'));

        if (! in_array($witness, $ids, true)) {
            return new WP_Error(
                'apollo_safety_not_mutual',
                __('Você não é uma pessoa em comum entre essas duas contas.', 'apollo-adverts'),
                array('status' => 422)
            );
        }

        $login = (string) wp_get_current_user()->user_login;
        $this->store_vouch($seller, $login, $mutuals);
        apollo_adverts_safety_clear($advert, $buyer, 'vouch:' . $login);
        $this->forget_request($advert, $buyer, $witness);

        return new WP_REST_Response(array('confirmed' => true, 'username' => $login), 200);
    }

    /** Pending requests live on the witness: "what am I being asked?" */
    private function request_key(int $advert, int $buyer): string
    {
        return 'ap_vouch_' . $advert . '_' . $buyer;
    }

    private function remember_request(int $advert, int $buyer, int $witness): void
    {
        update_user_meta($witness, $this->request_key($advert, $buyer), time());
    }

    private function was_asked(int $advert, int $buyer, int $witness): bool
    {
        return (bool) get_user_meta($witness, $this->request_key($advert, $buyer), true);
    }

    private function forget_request(int $advert, int $buyer, int $witness): void
    {
        delete_user_meta($witness, $this->request_key($advert, $buyer));
    }

    /**
     * Vouches recorded against a seller.
     *
     * @return array<int, array<string, mixed>>
     */
    private function vouches(int $seller): array
    {
        $stored = get_user_meta($seller, self::VOUCH_META, true);

        return is_array($stored) ? array_values($stored) : array();
    }

    /**
     * Record a vouch on the seller, keyed by handle so a witness counts once.
     *
     * @param array $mutuals Mutual list, to copy the witness's display data.
     */
    private function store_vouch(int $seller, string $username, array $mutuals): void
    {
        $stored = get_user_meta($seller, self::VOUCH_META, true);
        $stored = is_array($stored) ? $stored : array();

        $person = array('username' => $username, 'full_name' => $username, 'profile_pic_url' => null);
        foreach ($mutuals as $m) {
            if (isset($m['username']) && $m['username'] === $username) {
                $person = $m;
                break;
            }
        }

        $person['confirmed_at'] = time();
        $stored[$username]      = $person;

        update_user_meta($seller, self::VOUCH_META, $stored);
    }

    /**
     * Has staff verified this seller?
     */
    private function is_verified(int $seller): bool
    {
        /**
         * Filter the Apollo verification flag.
         *
         * @param bool $verified Default reads the profile meta.
         * @param int  $seller   Seller user id.
         */
        return (bool) apply_filters(
            'apollo_safety_is_verified',
            (bool) get_user_meta($seller, '_apollo_verified', true),
            $seller
        );
    }
}
