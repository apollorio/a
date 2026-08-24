<?php

/**
 * Part: safety/verdict — the decision bar.
 *
 * Ships hidden and locked. JS reveals it once all three signals have answered,
 * and the proceed button stays absent — not disabled, absent — until one of
 * them actually unlocked. A greyed-out button you are told not to press still
 * reads as a button; a missing one reads as a closed door.
 *
 * "Voltar ao anúncio" is always present, because a warning with no way out is
 * a dead end, not a safeguard.
 *
 * @var int $post_id
 *
 * @package Apollo\Adverts
 */

use Apollo\Adverts\Safety\Gate;

if (! defined('ABSPATH')) {
    exit;
}
?>
<footer class="apollo-warn__foot" id="apSafetyFoot" data-verdict="pending" hidden>
	<div>
		<span class="ap-verdict">
			<i class="ap-verdict__icon" id="apVerdictIcon" hidden aria-hidden="true">
				<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 22C6.47715 22 2 17.5228 2 12C2 6.47715 6.47715 2 12 2C17.5228 2 22 6.47715 22 12C22 17.5228 17.5228 22 12 22ZM11 15V17H13V15H11ZM11 7V13H13V7H11Z"/></svg>
			</i>
			<b data-bind="verdict-title">—</b>
			<span data-bind="verdict-sub">—</span>
		</span>

		<span class="ap-actions">
			<a class="btn btn-secondary" href="<?php echo esc_url((string) get_permalink($post_id)); ?>">
				<?php esc_html_e('Voltar ao anúncio', 'apollo-adverts'); ?>
			</a>

			<button type="button" class="btn btn-primary" id="apSafetyProceed"
				data-advert="<?php echo esc_attr((string) $post_id); ?>"
				data-redirect="<?php echo esc_url(Gate::redirect_to($post_id)); ?>"
				hidden>
				<?php esc_html_e('Abrir conversa', 'apollo-adverts'); ?>
			</button>
		</span>
	</div>
</footer>
