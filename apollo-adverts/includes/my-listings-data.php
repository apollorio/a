<?php

/**
 * Meus Anúncios — real data provider (PHASE 008).
 *
 * Same query shape as the existing, working BuddyPress "Meus Anúncios" tab
 * (apollo_adverts_bp_content_list() in buddypress.php): post_type=classified,
 * author=$user_id, post_status=[publish,pending,draft,expired]. Not a
 * duplicated query engine — same filters, same real data, just reused for
 * the new top-level Blank Canvas Apollo+ screen. Nothing here is simulated
 * (no _official_layout/js/*-meus.js fixture content).
 *
 * @package Apollo\Adverts
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! function_exists('apollo_adverts_get_user_listings')) {
    /**
     * @param int $user_id
     * @param int $paged
     * @return array<string, mixed>
     */
    function apollo_adverts_get_user_listings(int $user_id, int $paged = 1): array
    {
        $statuses = array( 'publish', 'pending', 'draft', 'expired' );

        // Single unpaginated pull (a user's own listings are always a small
        // set) so counts and the paged table slice come from one real query
        // instead of two.
        $all = new WP_Query(
            array(
                'post_type'      => APOLLO_CPT_CLASSIFIED,
                'post_status'    => $statuses,
                'author'         => $user_id,
                'posts_per_page' => -1,
                'orderby'        => 'date',
                'order'          => 'DESC',
                'no_found_rows'  => false,
            )
        );

        $counts = array_fill_keys($statuses, 0);
        $items  = array();

        foreach ($all->posts as $post) {
            $status = (string) $post->post_status;
            if (isset($counts[$status])) {
                $counts[$status]++;
            }
            $items[] = $post;
        }

        $total       = count($items);
        $per_page    = defined('APOLLO_ADVERTS_POSTS_PER_PAGE') ? APOLLO_ADVERTS_POSTS_PER_PAGE : 12;
        $total_pages = $per_page > 0 ? (int) ceil($total / $per_page) : 1;
        $paged       = max(1, min($paged, max(1, $total_pages)));
        $offset      = ($paged - 1) * $per_page;
        $page_items  = array_slice($items, $offset, $per_page);

        wp_reset_postdata();

        return array(
            'total'       => $total,
            'counts'      => $counts,
            'items'       => $page_items,
            'paged'       => $paged,
            'total_pages' => max(1, $total_pages),
        );
    }
}
