<?php

/**
 * Part: safety/preloader — the page holds itself back until it can tell the truth.
 *
 * The gate makes a claim about a stranger. A half-drawn version of that claim
 * is worse than no version: a member who sees two ticks land and the third
 * still spinning reads "2 de 3" and stops reading. So nothing is shown until
 * every signal has answered AND its avatars have actually decoded — then the
 * loader crossfades into the whole page at once.
 *
 * IF A SIGNAL NEVER ANSWERS, THIS SPINS FOREVER, ON PURPOSE.
 * Not a bug and not laziness. "Could not check" must never be allowed to look
 * like "checked, and fine" — an empty state on a security page reads as a
 * clean bill of health. An unresolved check therefore never resolves the page,
 * the proceed button is never rendered, and the member is never handed a
 * conclusion Apollo did not actually reach.
 *
 * A signal that answers "zero" is NOT unresolved — that is a real result and
 * the page shows it as the hard stop it is.
 *
 * @package Apollo\Adverts
 */

if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="ap-preloader" id="apPreloader" role="status" aria-live="polite">
	<span class="ap-preloader__seal" aria-hidden="true">
		<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 1L20.2169 2.82598C20.6745 2.92766 21 3.33347 21 3.80217V13.7889C21 15.795 19.9974 17.6684 18.3282 18.7812L12 23L5.6718 18.7812C4.00261 17.6684 3 15.795 3 13.7889V3.80217C3 3.33347 3.32553 2.92766 3.78307 2.82598L12 1ZM12 3.04879L5 4.60434V13.7889C5 15.1263 5.6684 16.3752 6.7812 17.1171L12 20.5963L17.2188 17.1171C18.3316 16.3752 19 15.1263 19 13.7889V4.60434L12 3.04879ZM11 15H13V17H11V15ZM11 7H13V13H11V7Z"/></svg>
	</span>

	<span class="ap-preloader__label"><?php esc_html_e('Verificando este perfil…', 'apollo-adverts'); ?></span>

	<span class="ap-preloader__steps" aria-hidden="true">
		<i data-step="instagram"></i>
		<i data-step="trust"></i>
		<i data-step="verified"></i>
	</span>
</div>
