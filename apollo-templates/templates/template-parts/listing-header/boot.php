<?php

/**
 * Apollo Listing Header — per-instance boot.
 *
 * The lab's `<script id="header-02-init">` did two jobs: it built the DOM and
 * it wired the behaviour. Only the second job is left — PHP already rendered
 * the markup — so this file is just a config handoff.
 *
 * The config goes through apollo_json_for_script() (apollo-core), not a bare
 * wp_json_encode(): month labels come from date_i18n() and a filter panel's
 * option labels come from editor-authored taxonomy terms, so the payload is
 * author-influenced text inside a <script> block. JSON escaping alone leaves
 * `</script>` intact; the HEX flags do not. Same call, same reason, as
 * portal/data.php.
 *
 * @package Apollo\Templates
 * @since   1.5.1
 *
 * @var array<string, mixed> $alh
 */

if (! defined('ABSPATH')) {
    exit;
}

$alh_config = array(
    'root'   => $alh['id'],
    'month'  => $alh['month']['index'],
    'year'   => $alh['year'],
    'months' => $alh['months'],
);

$alh_json = function_exists('apollo_json_for_script')
    ? apollo_json_for_script($alh_config)
    : wp_json_encode($alh_config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
?>
<script>
(function () {
    var cfg = <?php echo $alh_json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON-encoded with HEX flags above. ?>;
    /* The runtime <script> is emitted immediately before this one, so it is
       always parsed by now — but a theme override of the orchestrator could
       reorder them, and a header that silently does nothing is the worst
       failure mode. Fall back to DOMContentLoaded rather than assume. */
    var boot = function () {
        if (window.ApolloListingHeader) { window.ApolloListingHeader.create(cfg); }
    };
    if (window.ApolloListingHeader) { boot(); }
    else { document.addEventListener('DOMContentLoaded', boot); }
})();
</script>
