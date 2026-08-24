<?php
/**
 * Template: Meus Eventos — Base Style (v3, form.html app-shell)
 *
 * Rebuilt on the SAME app-shell as the create/edit form (form.html):
 * shared topbar + overlay + panels + .ax-aside drawer + shell styles.
 * Shows events where the current user is author OR co-author.
 * Edit links point to the frontend form /novo-evento/?edit={id}.
 *
 * Shell parts:  styles/base/template-parts/shared/
 * Main parts:   styles/base/template-parts/dashboard/
 *
 * @package Apollo\Event
 * @since   3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( function_exists( 'apollo_event_ensure_helpers' ) ) {
	apollo_event_ensure_helpers();
} elseif ( defined( 'APOLLO_EVENT_DIR' ) && is_readable( APOLLO_EVENT_DIR . 'includes/bootstrap.php' ) ) {
	require_once APOLLO_EVENT_DIR . 'includes/bootstrap.php';
}

/* ─── Auth guard ──────────────────────────────────────────── */
if ( ! is_user_logged_in() ) {
	wp_safe_redirect( home_url( '/acesso' ) );
	exit;
}

$current_user = wp_get_current_user();

/* ─── Events the user can manage (author OR co-author) ────── */
$events_data = apollo_event_get_user_manageable_events( (int) $current_user->ID, 50 );

$counts = array(
	'published' => 0,
	'draft'     => 0,
	'scheduled' => 0,
	'coauthor'  => 0,
);

$enriched = array();
foreach ( $events_data as $ev_row ) {
	$ev_status = $ev_row['status'] ?? '';
	if ( 'publish' === $ev_status ) {
		$counts['published']++;
	} elseif ( in_array( $ev_status, array( 'draft', 'pending' ), true ) ) {
		$counts['draft']++;
	} elseif ( 'future' === $ev_status ) {
		$counts['scheduled']++;
	}
	if ( ! empty( $ev_row['is_coauthor'] ) && empty( $ev_row['is_author'] ) ) {
		$counts['coauthor']++;
	}

	$ev_start               = $ev_row['start_date'] ?? '';
	$ev_parsed              = $ev_start ? apollo_event_parse_date( $ev_start ) : null;
	$ev_row['date_display'] = $ev_parsed ? strtoupper( $ev_parsed['day'] . ' ' . $ev_parsed['month_pt'] ) : '';
	$enriched[]             = $ev_row;
}
$events_data = $enriched;
$total_count = count( $events_data );

$stats = array(
	'total'     => $total_count,
	'published' => $counts['published'],
	'draft'     => $counts['draft'],
	'coauthor'  => $counts['coauthor'],
);

/* Transactions placeholder (populated via hook/REST in future). */
$transactions = apply_filters( 'apollo/event/dashboard_transactions', array(), $current_user->ID );

/* ─── Shell context (drives shared aside + panels) ────────── */
$sidebar_events = $events_data;
$is_edit        = false;
$edit_id        = 0;
$shell_active   = 'dashboard';

$parts  = __DIR__ . '/template-parts/dashboard/';
$shared = __DIR__ . '/template-parts/shared/';

ob_start();
?>
<script>window.apolloIconConfig=window.apolloIconConfig||{};</script>
<?php
require $shared . 'shell-styles.php';
require $parts . 'styles.php';
$extra_head = ob_get_clean();

$page_title = __( 'Meus Eventos', 'apollo-events' );

/* ── ONE SHELL (unification, 2026-08-05) ─────────────────────────────────────
   Converted off its hand-rolled Apollo+ document onto the single entry point,
   for the same reasons documented at the head of create-event.php: this screen
   used to render the LOCAL template-parts/shared/aside.php (no gestor group)
   and shell-styles.php's competing .ax-main geometry, so it was a structurally
   different Apollo+ shell from every phase-001..010 screen.

   theme/html_class are passed through, so the dark + is-logged treatment this
   dashboard has always had is preserved exactly. */
apollo_plus_open(
	array(
		'title'      => $page_title . ' — Apollo::Rio',
		'extra_head' => $extra_head,
		'screen'     => 'eventos/painel',
		'theme'      => 'dark',
		'html_class' => 'is-logged',
	)
);
?>

	<?php do_action( 'apollo_event_dashboard_before', $current_user ); ?>

			<?php require $parts . 'header.php'; ?>
			<?php require $parts . 'stats.php'; ?>
			<?php require $parts . 'filter-bar.php'; ?>

			<div class="ev-grid" id="evGrid">
				<?php if ( ! empty( $events_data ) ) : ?>
					<?php foreach ( $events_data as $ev ) : ?>
						<?php require $parts . 'event-card-manage.php'; ?>
					<?php endforeach; ?>
				<?php else : ?>
					<div class="ev-empty">
						<i class="ri-calendar-event-line"></i>
						<p><?php esc_html_e( 'Nenhum evento ainda. Crie seu primeiro!', 'apollo-events' ); ?></p>
						<a href="<?php echo esc_url( home_url( '/novo-evento/' ) ); ?>" target="_blank" rel="noopener" class="btn btn-primary"><i class="ri-add-line"></i> <?php esc_html_e( 'Novo Evento', 'apollo-events' ); ?></a>
					</div>
				<?php endif; ?>
			</div>

			<?php require $parts . 'transactions.php'; ?>

	<?php do_action( 'apollo_event_dashboard_after', $current_user ); ?>

	<?php require $parts . 'scripts.php'; ?>

<?php
apollo_plus_close();
