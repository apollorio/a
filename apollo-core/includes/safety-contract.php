<?php

/**
 * Safety Contract — the shared wire for "this hands a member to a stranger".
 *
 * Sibling of surface-contract.php and card-contract.php. A surface is the page
 * you open, a card is the item you click, and a safety gate is what stands
 * between the click and the stranger.
 *
 * WHY IT LIVES IN CORE AND NOT IN apollo-adverts
 * Adverts is not the only plugin that will ever hand one member to another.
 * The gate is a platform rule, not a marketplace feature, so the DECISION
 * lives here and the PIXELS live in the plugin that owns the surface. Any
 * plugin opts in with one apollo_safety_register() call instead of growing a
 * second divergent gate — the mistake card-contract.php was written to undo
 * after the accommodation card existed four times in three shapes.
 *
 * THE RULE, IN ONE SENTENCE
 * Every advert that opens a chat prints the gate; the only thing that skips it
 * is a listing whose CTA was never a chat.
 *
 * Registers no CPT, meta or table.
 *
 * @package Apollo\Core
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

/**
 * The one store. Read with no arguments, write with both.
 *
 * A single static beats a global because nothing outside this file can reach
 * past the accessor and leave the registry in a shape the readers do not
 * expect.
 *
 * @param string|null $post_type Post type to register, or null to read.
 * @param array|null  $args      Registration args, or null to read.
 * @return array<string, array>
 */
function apollo_safety_registry(?string $post_type = null, ?array $args = null): array
{
    static $registry = array();

    if (null !== $post_type) {
        $registry[$post_type] = (array) $args;
    }

    /**
     * Filter the safety registry.
     *
     * @param array $registry Post type => args.
     */
    return apply_filters('apollo_safety_registry', $registry);
}

/**
 * Opt a post type into the gate.
 *
 * @param string $post_type Post type slug.
 * @param array  $args      {
 *     @type callable $exempt_cb Returns a non-empty reason slug to SKIP the
 *                               gate for a given post id. Exemptions are the
 *                               only escape hatch and they must be a fact
 *                               about the listing, never a user preference.
 *     @type string   $label     Human label for admin surfaces.
 * }
 */
function apollo_safety_register(string $post_type, array $args = array()): void
{
    apollo_safety_registry($post_type, $args);
}

/**
 * Why this post skips the gate — empty string means it does NOT skip.
 *
 * Default-deny on purpose: an unregistered post type has no chat CTA to guard,
 * and a registered one is gated unless its own callback names a reason. A bug
 * in a plugin's callback therefore fails toward showing the warning, never
 * toward hiding it.
 *
 * @param int $post_id Post id.
 * @return string Reason slug, or '' when the gate applies.
 */
function apollo_safety_exemption(int $post_id): string
{
    $post = get_post($post_id);
    if (! $post) {
        return 'no_post';
    }

    $registry = apollo_safety_registry();
    if (! isset($registry[$post->post_type])) {
        return 'not_registered';
    }

    $reason = '';
    $args   = $registry[$post->post_type];

    if (isset($args['exempt_cb']) && is_callable($args['exempt_cb'])) {
        $reason = (string) call_user_func($args['exempt_cb'], $post_id);
    }

    /**
     * Filter the exemption reason.
     *
     * Returning a non-empty string SKIPS the safety gate. Do not use this to
     * turn the gate off wholesale — that is what the registry is for.
     *
     * @param string $reason  Reason slug, '' when gated.
     * @param int    $post_id Post id.
     */
    return (string) apply_filters('apollo_safety_exemption', $reason, $post_id);
}

/**
 * Does this post have to pass the gate?
 *
 * @param int $post_id Post id.
 */
function apollo_safety_applies(int $post_id): bool
{
    return '' === apollo_safety_exemption($post_id);
}

/**
 * Canonical URL of the interstitial for a post.
 *
 * A real URL, not a modal, because the gate is a decision the member must be
 * able to leave and come back to — and because a URL is the only form a
 * server-side redirect can enforce when someone posts the chat link directly.
 *
 * @param int    $post_id  Post being contacted about.
 * @param string $redirect Where to send the member once the gate clears.
 */
function apollo_safety_url(int $post_id, string $redirect = ''): string
{
    $url = add_query_arg(
        array_filter(
            array(
                'anuncio'  => (int) $post_id,
                'redirect' => $redirect ? rawurlencode($redirect) : null,
            )
        ),
        home_url('/seguranca/')
    );

    /**
     * Filter the safety gate URL.
     *
     * @param string $url      Gate URL.
     * @param int    $post_id  Post id.
     * @param string $redirect Post-gate destination.
     */
    return (string) apply_filters('apollo_safety_url', $url, $post_id, $redirect);
}

/**
 * Print the gate for a post. The owning plugin supplies the markup.
 *
 * Core deliberately renders nothing itself: it holds the rule, not the design.
 * A plugin that registers a post type must also answer this action, or the
 * gate URL resolves to an empty page and the omission is loud.
 *
 * @param int $post_id Post id.
 */
function apollo_safety_render(int $post_id): void
{
    /**
     * Render the safety gate body.
     *
     * @param int $post_id Post id.
     */
    do_action('apollo_safety_render', $post_id);
}
