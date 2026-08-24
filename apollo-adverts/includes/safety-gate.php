<?php

/**
 * Safety Gate — apollo-adverts' answer to the core safety contract.
 *
 * Core holds the rule (includes/safety-contract.php). This file holds the
 * three things only adverts can know: which listings are exempt, what the
 * gate looks like, and where the chat CTA has to go instead.
 *
 * ENFORCEMENT IS SERVER-SIDE, NOT COSMETIC
 * The old disclaimer was a modal with a checkbox — anyone who opened devtools,
 * or who was sent a thread URL directly, walked straight past it. The gate is
 * a real page AND a guard on the endpoint that mints the thread, so skipping
 * the page cannot mint a thread. The page is the courtesy; the guard is the
 * rule.
 *
 * THE EXEMPTION, AND WHY IT IS THE ONLY ONE
 * A hostel advert's CTA was never a chat: it is an outbound link to that
 * hostel's own booking page (_classified_hostel_url), so there is no stranger
 * to warn about and nothing for the gate to stand in front of. Every other
 * advert — tickets, rooms, rent_space, whatever ships next — is one member
 * handing money to another and is gated by default.
 *
 * @package Apollo\Adverts
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Is this advert owned by an official hostel?
 *
 * _classified_hostel_id is the relation that supersedes the older
 * _classified_hostel boolean (apollo-core MetaRegistry, 2026-08-17). The
 * boolean is still read for one release so listings written before the
 * migration keep their exemption instead of silently growing a gate.
 *
 * @param int $post_id Advert id.
 */
function apollo_adverts_is_hostel_listing(int $post_id): bool
{
    if ((int) get_post_meta($post_id, '_classified_hostel_id', true) > 0) {
        return true;
    }

    // Read fallback, one release only. Remove with the boolean itself.
    return (bool) get_post_meta($post_id, '_classified_hostel', true);
}

/**
 * Exemption callback handed to the core contract.
 *
 * Returns a reason slug to SKIP the gate, '' to enforce it. Anything this
 * function cannot positively identify as hostel-owned is gated — the failure
 * direction is deliberate.
 *
 * @param int $post_id Advert id.
 */
function apollo_adverts_safety_exemption(int $post_id): string
{
    if (! apollo_adverts_is_hostel_listing($post_id)) {
        return '';
    }

    // A hostel with no booking URL still has no chat CTA to guard, but it is a
    // broken listing rather than an exempt one — say so instead of pretending.
    $url = (string) get_post_meta($post_id, '_classified_hostel_url', true);

    return $url ? 'hostel_booking_link' : 'hostel_missing_url';
}

/**
 * Opt `classified` into the gate.
 *
 * Runs on init so the CPT exists and so any plugin loading later can still
 * filter apollo_safety_registry.
 */
function apollo_adverts_safety_register(): void
{
    if (! function_exists('apollo_safety_register')) {
        return; // apollo-core too old — fail visible, not silent. See notice below.
    }

    apollo_safety_register(
        APOLLO_CPT_CLASSIFIED,
        array(
            'exempt_cb' => 'apollo_adverts_safety_exemption',
            'label'     => __('Anúncios', 'apollo-adverts'),
        )
    );
}
add_action('init', 'apollo_adverts_safety_register', 20);

/**
 * A missing contract is a silently ungated marketplace. Say it out loud.
 */
function apollo_adverts_safety_contract_notice(): void
{
    if (function_exists('apollo_safety_register') || ! current_user_can('manage_options')) {
        return;
    }

    printf(
        '<div class="notice notice-error"><p><strong>%s</strong> %s</p></div>',
        esc_html__('apollo-adverts:', 'apollo-adverts'),
        esc_html__(
            'apollo-core não expõe o contrato de segurança — os anúncios estão abrindo conversa SEM o aviso. Atualize o apollo-core.',
            'apollo-adverts'
        )
    );
}
add_action('admin_notices', 'apollo_adverts_safety_contract_notice');

/* ═══════════════════════════════════════════════════════════════════════════
 * CLEARANCE — per (member, advert) pair, and it expires.
 *
 * Not permanent: the warning is about a decision the member is making today,
 * and a listing three weeks stale deserves the question again. Not on the
 * advert either — clearance is the VIEWER's state, not the seller's.
 * ═══════════════════════════════════════════════════════════════════════════ */

/**
 * Transient key for one member/advert pair.
 */
function apollo_adverts_safety_key(int $post_id, int $user_id): string
{
    return 'ap_safety_' . $user_id . '_' . $post_id;
}

/**
 * Has this member cleared the gate for this advert?
 */
function apollo_adverts_safety_cleared(int $post_id, int $user_id = 0): bool
{
    $user_id = $user_id ?: get_current_user_id();
    if (! $user_id) {
        return false;
    }

    return (bool) get_transient(apollo_adverts_safety_key($post_id, $user_id));
}

/**
 * Record clearance. Called only after a signal actually confirmed.
 *
 * @param int $post_id Advert id.
 * @param int $user_id Viewer id.
 * @param string $via  Which signal cleared it, for the audit trail.
 */
function apollo_adverts_safety_clear(int $post_id, int $user_id, string $via): void
{
    if (! $post_id || ! $user_id) {
        return;
    }

    set_transient(
        apollo_adverts_safety_key($post_id, $user_id),
        array('via' => $via, 'at' => time()),
        12 * HOUR_IN_SECONDS
    );

    do_action('apollo_adverts_safety_cleared', $post_id, $user_id, $via);
}

/* ═══════════════════════════════════════════════════════════════════════════
 * THE GUARD — this is the part that actually enforces anything.
 *
 * apollo-chat mints a thread at apollo/v1/chat/thread-for-context. Guarding
 * the button alone guards nothing: the endpoint is reachable with a fetch, and
 * a thread URL forwarded by a scammer skips every screen we draw. So the
 * endpoint refuses unless the pair is cleared, and the refusal carries the
 * gate URL so the client can send the member to the right place.
 * ═══════════════════════════════════════════════════════════════════════════ */

/**
 * Refuse thread creation for a gated advert the member has not cleared.
 *
 * @param mixed           $result  Pre-dispatch short circuit.
 * @param WP_REST_Server  $server  Server.
 * @param WP_REST_Request $request Request.
 * @return mixed
 */
function apollo_adverts_safety_guard_rest($result, $server, $request)
{
    if (null !== $result) {
        return $result; // Someone already answered.
    }

    if (false === strpos((string) $request->get_route(), '/chat/thread-for-context')) {
        return $result;
    }

    $post_id = (int) $request->get_param('classified_id');
    if (! $post_id) {
        $post_id = (int) $request->get_param('context_id');
    }
    if (! $post_id || ! function_exists('apollo_safety_applies')) {
        return $result;
    }

    if (! apollo_safety_applies($post_id)) {
        return $result; // Exempt — hostel booking link, nothing to guard.
    }

    if (apollo_adverts_safety_cleared($post_id)) {
        return $result;
    }

    return new WP_Error(
        'apollo_safety_required',
        __('Confirme a segurança deste anúncio antes de abrir a conversa.', 'apollo-adverts'),
        array(
            'status'   => 403,
            'gate_url' => apollo_safety_url($post_id, (string) get_permalink($post_id)),
        )
    );
}
add_filter('rest_pre_dispatch', 'apollo_adverts_safety_guard_rest', 10, 3);

/* ═══════════════════════════════════════════════════════════════════════════
 * BOOT + ASSETS
 * ═══════════════════════════════════════════════════════════════════════════ */

/**
 * Boot the /seguranca/ route.
 *
 * The class file is required explicitly rather than left to the autoloader:
 * apollo-adverts ships a composer PSR-4 map but no guarantee vendor/ is
 * deployed, and a missing route here means an ungated marketplace.
 */
function apollo_adverts_safety_boot(): void
{
    if (! class_exists('\\Apollo\\Adverts\\Safety\\Gate')) {
        $file = APOLLO_ADVERTS_DIR . 'src/Safety/Gate.php';
        if (! file_exists($file)) {
            return;
        }
        require_once $file;
    }

    ( new \Apollo\Adverts\Safety\Gate() )->boot();
}
add_action('init', 'apollo_adverts_safety_boot', 6);

/**
 * Register the gate's own stylesheet and script.
 *
 * Registered always, enqueued only by templates/safety/gate.php — the gate is
 * one page and has no business adding weight to every advert.
 */
function apollo_adverts_safety_assets(): void
{
    $ver = defined('APOLLO_ADVERTS_VERSION') ? APOLLO_ADVERTS_VERSION : null;

    wp_register_style(
        'apollo-adverts-safety-gate',
        APOLLO_ADVERTS_URL . 'assets/css/safety-gate.css',
        array(),
        $ver
    );

    wp_register_script(
        'apollo-adverts-safety-gate',
        APOLLO_ADVERTS_URL . 'assets/js/safety/gate.js',
        array(),
        $ver,
        true
    );

    wp_localize_script(
        'apollo-adverts-safety-gate',
        'ApolloSafety',
        array(
            'rest'  => esc_url_raw(rest_url('apollo/v1/safety/')),
            'nonce' => wp_create_nonce('wp_rest'),
        )
    );
}
add_action('wp_enqueue_scripts', 'apollo_adverts_safety_assets', 5);

/**
 * Register the safety REST routes.
 */
function apollo_adverts_safety_rest(): void
{
    if (! class_exists('\\Apollo\\Adverts\\API\\SafetyController')) {
        $file = APOLLO_ADVERTS_DIR . 'src/API/SafetyController.php';
        if (! file_exists($file)) {
            return;
        }
        require_once $file;
    }

    ( new \Apollo\Adverts\API\SafetyController() )->register_routes();
}
add_action('rest_api_init', 'apollo_adverts_safety_rest');

/* ═══════════════════════════════════════════════════════════════════════════
 * THE PROVIDER — Apollo's own graph, no API and no token.
 *
 * There is no Instagram endpoint that returns a follower list, so every
 * "mutual friends" integration is a session scraper: it needs a live cookie,
 * it breaks on layout changes and it gets the account banned. A safety feature
 * Meta can switch off is not a safety feature. This one runs on data we own.
 * ═══════════════════════════════════════════════════════════════════════════ */

/**
 * Register the native graph as the mutuals provider.
 */
function apollo_adverts_safety_provider(): void
{
    if (! class_exists('\\Apollo\\Adverts\\Safety\\NativeGraph')) {
        $file = APOLLO_ADVERTS_DIR . 'src/Safety/NativeGraph.php';
        if (! file_exists($file)) {
            return;
        }
        require_once $file;
    }

    add_filter(
        'apollo_safety_mutuals',
        static function (array $mutuals, int $viewer, int $seller): array {
            return array_merge(
                $mutuals,
                \Apollo\Adverts\Safety\NativeGraph::mutuals($viewer, $seller)
            );
        },
        10,
        3
    );

    \Apollo\Adverts\Safety\NativeGraph::watch();
}
add_action('init', 'apollo_adverts_safety_provider', 7);
