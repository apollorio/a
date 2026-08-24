<?php

/**
 * Plugin Constants
 *
 * All constants for the Apollo Adverts plugin.
 * Adapted from WPAdverts constants + apollo-registry.json spec.
 *
 * @package Apollo\Adverts
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

// ═══════════════════════════════════════════════════════════════════════════
// REST API
// ═══════════════════════════════════════════════════════════════════════════

define('APOLLO_ADVERTS_REST_NAMESPACE', 'apollo/v1');

// ═══════════════════════════════════════════════════════════════════════════
// CPT & TAXONOMY — from apollo-registry.json
// ═══════════════════════════════════════════════════════════════════════════

if (! defined('APOLLO_CPT_CLASSIFIED')) {
    define('APOLLO_CPT_CLASSIFIED', 'classified');
}

// APOLLO_TAX_CLASSIFIED_DOMAIN / APOLLO_TAX_CLASSIFIED_INTENT — defined by apollo-core
// (config/constants.php via ConfigLoader::define_constants). Do not redefine here.

// ═══════════════════════════════════════════════════════════════════════════
// META KEYS — from apollo-registry.json
// ═══════════════════════════════════════════════════════════════════════════

define(
    'APOLLO_ADVERTS_META_KEYS',
    array(
        '_classified_price'            => array(
            'type'    => 'float',
            'default' => 0,
        ),
        '_classified_currency'         => array(
            'type'    => 'string',
            'default' => 'BRL',
        ),
        '_classified_negotiable'       => array(
            'type'    => 'bool',
            'default' => false,
        ),
        '_classified_condition'        => array(
            'type'    => 'string',
            'default' => 'usado',
            'values'  => array('novo', 'usado', 'recondicionado'),
        ),
        '_classified_loc'              => array(
            'type'    => 'string',
            'default' => '',
        ),
        '_classified_contact_phone'    => array(
            'type'    => 'string',
            'default' => '',
        ),
        '_classified_contact_whatsapp' => array(
            'type'    => 'string',
            'default' => '',
        ),
        '_classified_expires_at'       => array(
            'type'    => 'string',
            'default' => '',
        ),
        '_classified_featured'         => array(
            'type'    => 'bool',
            'default' => false,
        ),
        '_classified_views'            => array(
            'type'    => 'int',
            'default' => 0,
        ),
        // === MIGRATED FROM apollo-classifieds (FASE 1 merge) ===
        '_classified_type'             => array(
            'type'    => 'string',
            'default' => 'general',
            'values'  => array('general', 'ticket', 'accommodation', 'ticket_sell', 'rent_space'),
        ),
        '_classified_event_title'      => array(
            'type'    => 'string',
            'default' => '',
        ),
        '_classified_event_date'       => array(
            'type'    => 'string',
            'default' => '',
        ),
        '_classified_event_loc'         => array(
            'type'    => 'string',
            'default' => '',
        ),
        '_classified_rating'           => array(
            'type'    => 'float',
            'default' => 0,
        ),
        '_classified_badge'            => array(
            'type'    => 'string',
            'default' => '',
        ),
        // Ticket resale: how many tickets the seller is offering. The sell
        // form always posted `quantity` but there was no key to land in, so
        // the value was discarded on every submission.
        '_classified_quantity'         => array(
            'type'    => 'int',
            'default' => 1,
        ),
        // Link back to the `event` CPT when the advert is a resale for a real
        // Apollo event. The denormalised _classified_event_* fields above are
        // snapshots for card rendering; this is the actual relation.
        '_classified_event_id'         => array(
            'type'    => 'int',
            'default' => 0,
        ),
        // === ACCOMMODATION BLOCK ===
        // Mirrors the accommodation block registered in apollo-core's
        // MetaRegistry (classified map). Only meaningful when
        // _classified_type is 'accommodation' or 'rent_space'.
        // _classified_hostel is the privacy switch: hostels are public
        // businesses, so their listing is not auth-gated and the chat CTA is
        // replaced by _classified_hostel_url.
        '_classified_hostel'           => array(
            'type'    => 'bool',
            'default' => false,
        ),
        '_classified_hostel_url'       => array(
            'type'    => 'url',
            'default' => '',
        ),
        '_classified_min_nights'       => array(
            'type'    => 'int',
            'default' => 0,
        ),
        '_classified_max_days'         => array(
            'type'    => 'int',
            'default' => 0,
        ),
        // Availability window — non-hostel only (hostels are always open).
        '_classified_avail_start'      => array(
            'type'    => 'date',
            'default' => '',
        ),
        '_classified_avail_end'        => array(
            'type'    => 'date',
            'default' => '',
        ),
    )
);

// ═══════════════════════════════════════════════════════════════════════════
// ACCOMMODATION — which _classified_type values count as a stay
// ═══════════════════════════════════════════════════════════════════════════

define('APOLLO_ADVERTS_ACCOMMODATION_TYPES', array('accommodation', 'rent_space'));

// Ticket resale types — always identity-locked for guests, no exceptions.
define('APOLLO_ADVERTS_TICKET_TYPES', array('ticket', 'ticket_sell'));

// ═══════════════════════════════════════════════════════════════════════════
// TYPE VOCABULARY — canonical stored value per advert kind
// ═══════════════════════════════════════════════════════════════════════════
//
// _classified_type's enum grew two names for each of the two real kinds:
//   ticket_sell  ≡ ticket          (resale)
//   rent_space   ≡ accommodation   (stay)
// The frontend forms speak the first name of each pair; every marketplace
// READ query (ticket-carousel.php, accommodation-grid.php,
// classifieds-page.php) filters on the second. Both spellings are legal per
// the enum, which is exactly why the split went unnoticed: nothing ever
// errored, adverts simply never matched a query.
//
// Fix is one-directional on purpose: the aliases stay ACCEPTED on input (old
// clients, saved drafts, the demo seeder) but are CANONICALISED to
// ticket/accommodation before hitting the DB, so there is one stored
// vocabulary and the read queries keep working untouched.
define(
    'APOLLO_ADVERTS_TYPE_CANONICAL',
    array(
        'ticket_sell' => 'ticket',
        'rent_space'  => 'accommodation',
    )
);

// ═══════════════════════════════════════════════════════════════════════════
// ADMIN-ONLY META — never writable from the frontend, at any layer
// ═══════════════════════════════════════════════════════════════════════════
//
// The hostel switch is an editorial claim, not a seller preference: it decides
// whether a listing bypasses the auth gate and is shown openly to the whole
// internet. Only staff verify that a listing really is a registered hostel, so
// the pair is:
//   · absent from every frontend form (nothing to submit),
//   · absent from ClassifiedsController's REST field map (nothing to POST),
//   · gated behind manage_options in its register_meta auth_callback, so the
//     CPT's generic REST meta endpoint refuses it too.
// Enforced at the write boundary, not just hidden in the UI.
define(
    'APOLLO_ADVERTS_ADMIN_ONLY_META',
    array(
        '_classified_hostel',
        '_classified_hostel_url',
        // The hostel RELATION (2026-08-17). Supersedes the boolean above: it
        // points an advert at a `hostel` post instead of merely flagging it.
        // Same enforcement, same reason — an advert belongs either to a member
        // or to an official hostel, and only staff decide which. Listed here so
        // the auth_callback branches to manage_options and the CPT's generic
        // REST meta endpoint refuses it, not just the admin UI.
        '_classified_hostel_id',
        // Pre-existing editorial flags, same reasoning — a seller must not be
        // able to promote their own listing or hand themselves a badge.
        '_classified_featured',
        '_classified_badge',
    )
);

// ═══════════════════════════════════════════════════════════════════════════
// MEMBER-ONLY META — the READ-side counterpart to ADMIN_ONLY_META
// ═══════════════════════════════════════════════════════════════════════════
//
// ADMIN_ONLY_META above guards the WRITE boundary. Nothing guarded the READ
// boundary, and that asymmetry was a live PII leak:
//
//   ClassifiedsController::prepare_item() loops APOLLO_ADVERTS_META_KEYS and
//   returns EVERY key. GET /apollo/v1/classifieds is permission_callback
//   __return_true. So an anonymous request returned every seller's phone
//   number and WhatsApp, alongside their user id and display name.
//
// The 2026-07-28 seller-privacy pass ($seller_privacy_leak_fix_2026_07_28)
// hardened five TEMPLATES and left the API open — a fix applied at the render
// layer instead of the data layer. This constant moves the rule to the data
// layer, where every consumer inherits it.
//
// A hostel listing is deliberately public (see the note above), but "public"
// means its title, price, image and booking URL. It never means a person's
// phone number, so this list is NOT hostel-exempt.
define(
    'APOLLO_ADVERTS_MEMBER_ONLY_META',
    array(
        '_classified_contact_phone',
        '_classified_contact_whatsapp',
    )
);

// ═══════════════════════════════════════════════════════════════════════════
// CLASSIFIED INTENTS — from apollo-registry.json
// ═══════════════════════════════════════════════════════════════════════════

define(
    'APOLLO_ADVERTS_INTENTS',
    array(
        'vendo'   => 'Vendo',
        'compro'  => 'Compro',
        'troco'   => 'Troco',
        'alugo'   => 'Alugo',
        'procuro' => 'Procuro',
    )
);

// ═══════════════════════════════════════════════════════════════════════════
// CONDITIONS
// ═══════════════════════════════════════════════════════════════════════════

define(
    'APOLLO_ADVERTS_CONDITIONS',
    array(
        'novo'           => 'Novo',
        'usado'          => 'Usado',
        'recondicionado' => 'Recondicionado',
    )
);

// ═══════════════════════════════════════════════════════════════════════════
// DEFAULTS
// ═══════════════════════════════════════════════════════════════════════════

define('APOLLO_ADVERTS_DEFAULT_EXPIRATION', 30); // days
define('APOLLO_ADVERTS_MAX_IMAGES', 8);
define('APOLLO_ADVERTS_POSTS_PER_PAGE', 12);

// ═══════════════════════════════════════════════════════════════════════════
// CURRENCY — Valor de referência apenas (informativo)
// Apollo NÃO processa pagamentos. Somos ponte de conexão entre pessoas.
// A negociação e transação final acontecem fora da plataforma.
// ═══════════════════════════════════════════════════════════════════════════

define(
    'APOLLO_ADVERTS_CURRENCY',
    array(
        'code'          => 'BRL',
        'sign'          => 'R$',
        'sign_type'     => 'p', // p=prefix, s=suffix
        'decimals'      => 2,
        'char_decimal'  => ',',
        'char_thousand' => '.',
    )
);

// ═══════════════════════════════════════════════════════════════════════════
// IMAGE SIZES
// ═══════════════════════════════════════════════════════════════════════════

define(
    'APOLLO_ADVERTS_IMAGE_SIZES',
    array(
        'classified-list'    => array(
            'width'  => 310,
            'height' => 310,
            'crop'   => true,
        ),
        'classified-gallery' => array(
            'width'  => 650,
            'height' => 400,
            'crop'   => false,
        ),
        'classified-thumb'   => array(
            'width'  => 150,
            'height' => 105,
            'crop'   => true,
        ),
    )
);
