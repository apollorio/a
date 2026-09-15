<?php
/**
 * Marketplace — screen layout (approved market.html contract).
 *
 * @package Apollo\Adverts
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mk = __DIR__ . '/';
$mk_logged = is_user_logged_in();
$mk_create = $mk_logged
	? home_url( '/novo-anuncio/' )
	: home_url( '/acesso?redirect=' . rawurlencode( home_url( '/novo-anuncio/' ) ) );
?>
<div class="mk-screen">
	<?php require $mk . 'head.php'; ?>
	<?php require $mk . 'tickets-stage.php'; ?>
	<?php require $mk . 'accom-stage.php'; ?>

	<?php
	$mk_label = __( 'Outros anúncios', 'apollo-adverts' );
	$mk_icon  = 'ri-price-tag-3-line';
	$mk_type  = 'other';
	$mk_card  = 'card-ticket.php';
	require $mk . 'section.php';
	?>

	<a class="mk-fab" href="<?php echo esc_url( $mk_create ); ?>" aria-label="<?php esc_attr_e( 'Novo anúncio', 'apollo-adverts' ); ?>">
		<i class="ri-add-line" aria-hidden="true"></i>
	</a>
</div>

<script<?php echo function_exists( 'apollo_csp_nonce_attr' ) ? apollo_csp_nonce_attr() : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	(function () {
		'use strict';

		var pills = document.querySelectorAll('[data-mk-filter]');
		var search = document.getElementById('classificadosSearchInput') || document.getElementById('mkSearch');

		function applyFilters() {
			var f = 'all';
			pills.forEach(function (x) {
				if (x.classList.contains('is-active') || x.classList.contains('active')) {
					f = x.getAttribute('data-mk-filter') || 'all';
				}
			});
			var q = (search && search.value) ? search.value.trim().toLowerCase() : '';

			document.querySelectorAll('[data-mk-section]').forEach(function (sec) {
				var kind = sec.getAttribute('data-mk-section');
				var typeHit = (f === 'all' || f === kind);
				if (f !== 'all' && f !== 'ticket' && f !== 'accommodation') {
					typeHit = true;
				}
				sec.style.display = typeHit ? '' : 'none';
			});

			document.querySelectorAll('[data-mk-domains], [data-classified-id]').forEach(function (card) {
				var d = (card.getAttribute('data-mk-domains') || '').split(' ');
				var domainHit = (f === 'all' || f === 'ticket' || f === 'accommodation' || d.indexOf(f) !== -1);
				var text = (card.getAttribute('aria-label') || card.textContent || '').toLowerCase();
				var textHit = !q || text.indexOf(q) !== -1;
				card.classList.toggle('is-mk-hidden', !(domainHit && textHit));
			});
		}

		pills.forEach(function (p) {
			p.addEventListener('click', function () {
				pills.forEach(function (x) {
					x.classList.remove('is-active', 'active');
				});
				p.classList.add('is-active', 'active');
				applyFilters();
			});
		});
		if (search) {
			search.addEventListener('input', applyFilters);
		}
	})();
</script>
