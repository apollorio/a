<?php

/**
 * Safety Gate — the /seguranca/ virtual page.
 *
 * A real URL, not a modal. Three reasons, in order of how much they matter:
 *
 *  1. A modal cannot be enforced. The old disclaimer was a checkbox in the
 *     DOM; a fetch to the thread endpoint never saw it, and neither did a
 *     thread link forwarded by the person you were being warned about.
 *  2. A member has to be able to leave and come back. Checking an Instagram
 *     mutual means opening Instagram — that destroys a modal and keeps a URL.
 *  3. It is the honest shape. This is a decision, not a dialog.
 *
 * Routing uses core's VirtualPageTrait so the gate bypasses the template
 * hierarchy exactly like every other Apollo member page.
 *
 * @package Apollo\Adverts
 */

declare(strict_types=1);

namespace Apollo\Adverts\Safety;

use Apollo\Core\Traits\BlankCanvasTrait;
use Apollo\Core\Traits\VirtualPageTrait;

if (! defined('ABSPATH')) {
    exit;
}

final class Gate
{
    use BlankCanvasTrait;
    use VirtualPageTrait;

    /** URL slug. Portuguese, like every other member-facing Apollo route. */
    public const SLUG = 'seguranca';

    /**
     * Wire the route and answer the core contract's render action.
     */
    public function boot(): void
    {
        $this->init_virtual_pages(
            'apollo_safety_page',
            array(self::SLUG => 'safety/gate'),
            APOLLO_ADVERTS_DIR . 'templates/',
            true, // Contacting anyone requires an account, so does the gate.
            5
        );

        add_action('apollo_safety_render', array($this, 'render'), 10, 1);
    }

    /**
     * The advert this gate is standing in front of.
     *
     * Read from the query string rather than a session because the gate URL is
     * shareable and bookmarkable by design — see the class docblock.
     */
    public static function advert_id(): int
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only route.
        return isset($_GET['anuncio']) ? absint(wp_unslash($_GET['anuncio'])) : 0;
    }

    /**
     * Where to send the member once the gate clears.
     *
     * Never trust it: an open redirect on a page whose entire job is trust
     * would be its own punchline. Same-host only, else fall back to the ad.
     */
    public static function redirect_to(int $post_id): string
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only route.
        $raw  = isset($_GET['redirect']) ? rawurldecode(wp_unslash($_GET['redirect'])) : '';
        $safe = wp_validate_redirect(esc_url_raw($raw), '');

        return $safe ?: (string) get_permalink($post_id);
    }

    /**
     * Print the gate body. One template part per idea, nothing inline.
     *
     * @param int $post_id Advert id.
     */
    public function render(int $post_id): void
    {
        $seller = (int) get_post_field('post_author', $post_id);

        // First in the DOM and last to leave. Everything below it ships at
        // opacity 0 and is revealed as one piece — see the part's docblock.
        self::part('preloader');

        self::part('seal');
        self::part('lede', array('post_id' => $post_id, 'seller_id' => $seller));
        self::part('target', array('post_id' => $post_id, 'seller_id' => $seller));
        self::part('rule');
        self::part('checks', array('post_id' => $post_id, 'seller_id' => $seller));
        self::part('note');
        self::part('verdict', array('post_id' => $post_id));
    }

    /**
     * Load one part from templates/parts/safety/.
     *
     * @param string $name Part file name without extension.
     * @param array  $args Variables exposed to the part.
     */
    public static function part(string $name, array $args = array()): void
    {
        $file = APOLLO_ADVERTS_DIR . 'templates/parts/safety/' . $name . '.php';

        if (! file_exists($file)) {
            return;
        }

        load_template($file, false, $args);
    }
}
