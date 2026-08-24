<?php

/**
 * Template Part: Scripts — Explore JS + initialization
 *
 * Expected vars: $rest_url, $nonce, $char_limit, $user_id
 *
 * @package Apollo\Social
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- Explore JS -->
<?php if ( defined( 'APOLLO_SOCIAL_URL' ) && defined( 'APOLLO_SOCIAL_VERSION' ) ) : ?>
	<script src="<?php echo esc_url( APOLLO_SOCIAL_URL . 'assets/js/explore.js' ); ?>?v=<?php echo esc_attr( APOLLO_SOCIAL_VERSION ); ?>" defer></script>
<?php endif; ?>

<script>
	document.addEventListener('DOMContentLoaded', () => {
		/* Init ApolloExplore */
		if (typeof ApolloExplore !== 'undefined') {
			ApolloExplore.init({
				rest: '<?php echo esc_js( $rest_url ); ?>',
				nonce: '<?php echo esc_js( $nonce ); ?>',
				limit: <?php echo (int) $char_limit; ?>,
				userId: <?php echo (int) $user_id; ?>
			});
		}
	});
</script>

<?php
// Modal now rendered globally via wp_footer hook in apollo-core
// Trigger: [data-apollo-report-trigger] or #feedReportTrigger
?>
