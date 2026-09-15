<?php

/**
 * Part: safety/check — one signal block.
 *
 * Rendered three times. Ships in its loading state and is filled by
 * assets/js/safety/gate.js once the REST call answers, because two of the
 * three signals are network calls (Instagram mutuals is two paginated
 * follower fetches) and blocking the page on them would mean a member stares
 * at nothing before being told to be careful.
 *
 * `gate` is the honest label: "unlock" opens the conversation, "context" is
 * worth reading and opens nothing. Without it a white tick on the trust-votes
 * block reads as safe, and those can be farmed by the seller.
 *
 * @var string $key   Signal key: instagram | trust | verified.
 * @var string $title Block heading.
 * @var string $gate  unlock | context.
 * @var string $pair  Optional mono sub-line (the accounts being crossed).
 *
 * @package Apollo\Adverts
 */

if (! defined('ABSPATH')) {
    exit;
}

$gate  = $gate ?? 'unlock';
$pair  = $pair ?? '';
$label = $gate_label ?? (
	'context' === $gate
		? __('Só contexto', 'apollo-adverts')
		: __('Libera', 'apollo-adverts')
);
?>
<article class="ap-check is-loading" data-check="<?php echo esc_attr($key); ?>" data-state="off">
	<div class="ap-check__top">
		<span class="ap-check__mark" aria-hidden="true">
			<svg viewBox="0 0 22 22"><path class="ap-tick" d="M4.6 11.4 L9 15.6 L17.4 6.4"/></svg>
		</span>

		<span class="ap-check__id">
			<span class="ap-check__title"><?php echo esc_html($title); ?></span>
			<span class="ap-check__status" data-bind="status-<?php echo esc_attr($key); ?>">
				<?php esc_html_e('Consultando…', 'apollo-adverts'); ?>
			</span>
			<?php if ($pair) : ?>
				<span class="ap-check__pair"><?php echo esc_html($pair); ?></span>
			<?php endif; ?>
		</span>

		<span class="ap-check__gate" data-gate="<?php echo esc_attr($gate); ?>">
			<?php echo esc_html($label); ?>
		</span>
	</div>

	<div class="ap-check__body"></div>
</article>
