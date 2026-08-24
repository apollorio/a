<?php
/**
 * Template: Create / Edit Event — Base Style (v2 Modular)
 *
 * Orchestrator for the frontend event manager (blank canvas).
 * Design source: _dev web/screen/single cpt/event/add-new/form.html
 *
 * Parts loaded from: styles/base/template-parts/create/
 *   - styles.php        → Exact CSS from form.html
 *   - topbar.php        → Fixed topbar
 *   - overlay.php       → Shared overlay
 *   - panels.php        → Activity / apps / profile panels
 *   - aside.php         → Sidebar (author + co-author events)
 *   - form-header.php   → Title + save actions
 *   - form-basic.php    → Title, datetime widget, about
 *   - form-media.php    → Cover, video, audio, gallery
 *   - form-taxonomy.php → Season, tickets, status, privacy, genres
 *   - form-venue.php    → Local search + info
 *   - form-lineup.php   → Lineup builder
 *   - form-coauthors.php→ Equipe do Evento (Event's Team · edit permission)
 *   - form-access.php   → Early Bird + Listas
 *   - form-coupons.php  → Coupons
 *   - form-summary.php  → Receipt + weather + record
 *   - modals.php        → DJ + venue modals + toast
 *   - scripts.php       → Config bootstrap + form/shell JS
 *
 * @package Apollo\Event
 * @since   2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( function_exists( 'apollo_event_ensure_helpers' ) ) {
	apollo_event_ensure_helpers();
} elseif ( defined( 'APOLLO_EVENT_DIR' ) && is_readable( APOLLO_EVENT_DIR . 'includes/bootstrap.php' ) ) {
	require_once APOLLO_EVENT_DIR . 'includes/bootstrap.php';
}

if ( ! is_user_logged_in() ) {
	echo '<p class="a-eve-form__noauth">' . esc_html__( 'Faça login para criar eventos.', 'apollo-events' ) . '</p>';
	return;
}

$current_user = wp_get_current_user();
$parts        = __DIR__ . '/template-parts/create/';
$shared       = __DIR__ . '/template-parts/shared/';
$shell_active = 'create';

/* ─── Edit mode (?edit={id} or ?event={id}) ───────────────── */
$edit_id = 0;
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
if ( isset( $_GET['edit'] ) ) {
	$edit_id = absint( wp_unslash( $_GET['edit'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
} elseif ( isset( $_GET['event'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$edit_id = absint( wp_unslash( $_GET['event'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
}

$is_edit      = false;
$edit_event   = null;
$edit_payload = array();

if ( $edit_id > 0 ) {
	$edit_post = get_post( $edit_id );
	$can_edit  = $edit_post
		&& APOLLO_EVENT_CPT === $edit_post->post_type
		&& (
			(int) $edit_post->post_author === (int) $current_user->ID
			|| current_user_can( 'edit_post', $edit_id )
			|| apollo_event_user_is_coauthor( $edit_id, (int) $current_user->ID )
		);

	if ( $can_edit ) {
		$is_edit    = true;
		$edit_event = $edit_post;
		$edit_payload = apollo_event_prepare_form_payload( $edit_id );
	} else {
		$edit_id = 0;
	}
}

/* ─── Lookups ─────────────────────────────────────────────── */
$locals = get_posts(
	array(
		// CPT real é 'local' (APOLLO_LOCAL_CPT em apollo-loc) — 'loc' retornava
		// SEMPRE vazio → datalist de locais em branco no formulário.
		'post_type'              => defined( 'APOLLO_LOCAL_CPT' ) ? APOLLO_LOCAL_CPT : 'local',
		'post_status'            => 'publish',
		'posts_per_page'         => 500,
		'orderby'                => 'title',
		'order'                  => 'ASC',
		'no_found_rows'          => true,
		'update_post_meta_cache' => true,
		'update_post_term_cache' => false,
	)
);

$djs = get_posts(
	array(
		// Same lesson as the loc query above: never hardcode a CPT slug that
		// another Apollo plugin owns — honour its constant when present.
		'post_type'              => defined( 'APOLLO_DJ_CPT' ) ? APOLLO_DJ_CPT : 'dj',
		'post_status'            => 'publish',
		'posts_per_page'         => 500,
		'orderby'                => 'title',
		'order'                  => 'ASC',
		'no_found_rows'          => true,
		'update_post_meta_cache' => true,
		'update_post_term_cache' => false,
	)
);

$sounds = get_terms(
	array(
		'taxonomy'   => defined( 'APOLLO_EVENT_TAX_SOUND' ) ? APOLLO_EVENT_TAX_SOUND : 'sound',
		'hide_empty' => false,
	)
);
if ( is_wp_error( $sounds ) ) {
	$sounds = array();
}

$seasons = get_terms(
	array(
		'taxonomy'   => defined( 'APOLLO_EVENT_TAX_SEASON' ) ? APOLLO_EVENT_TAX_SEASON : 'season',
		'hide_empty' => false,
	)
);
if ( is_wp_error( $seasons ) ) {
	$seasons = array();
}

/* ─── User events (author OR co-author) for sidebar ───────── */
$sidebar_events = apollo_event_get_user_manageable_events( (int) $current_user->ID, 50 );

/* ─── Loc / DJ catalogs for JS ────────────────────────────── */
/**
 * First non-empty meta value across a list of candidate keys.
 *
 * The loc CPT carries TWO historic meta families — `_local_*` (what
 * apollo_event_get_loc() and the single event page read) and `_loc_*` (what
 * this form used to read alone). Reading only one of them left the picker with
 * a blank address and null coordinates for half the catalog.
 *
 * @param int      $post_id Loc post id.
 * @param string[] $keys    Candidate meta keys, most authoritative first.
 * @return string
 */
$apollo_first_meta = static function ( int $post_id, array $keys ): string {
	foreach ( $keys as $key ) {
		$value = get_post_meta( $post_id, $key, true );
		if ( is_scalar( $value ) && '' !== (string) $value ) {
			return (string) $value;
		}
	}
	return '';
};

$locs_js = array();
foreach ( $locals as $local_post ) {
	$lat  = $apollo_first_meta( (int) $local_post->ID, array( '_local_lat', '_loc_lat' ) );
	$lon  = $apollo_first_meta( (int) $local_post->ID, array( '_local_lng', '_loc_lng', '_loc_lon' ) );
	$addr = $apollo_first_meta(
		(int) $local_post->ID,
		array( '_local_address', '_loc_address', '_loc_endereco' )
	);

	$city = $apollo_first_meta( (int) $local_post->ID, array( '_local_city', '_local_bairro' ) );
	if ( '' !== $city && '' !== $addr && false === stripos( $addr, $city ) ) {
		$addr .= ' · ' . $city;
	} elseif ( '' === $addr ) {
		$addr = $city;
	}

	$gallery_raw = get_post_meta( $local_post->ID, '_local_gallery', true );
	if ( ! is_array( $gallery_raw ) ) {
		$gallery_raw = get_post_meta( $local_post->ID, '_loc_gallery', true );
	}
	$images = array();
	if ( is_array( $gallery_raw ) ) {
		foreach ( $gallery_raw as $att_id ) {
			$url = wp_get_attachment_image_url( absint( $att_id ), 'medium' );
			if ( $url ) {
				$images[] = $url;
			}
		}
	}
	$thumb = get_the_post_thumbnail_url( $local_post->ID, 'medium' );
	if ( $thumb && empty( $images ) ) {
		$images[] = $thumb;
	}

	$locs_js[] = array(
		'id'      => (int) $local_post->ID,
		'name'    => $local_post->post_title,
		'address' => (string) $addr,
		'lat'     => $lat !== '' ? (float) $lat : null,
		'lon'     => $lon !== '' ? (float) $lon : null,
		'images'  => $images,
	);
}

$djs_js = array();
foreach ( $djs as $dj_post ) {
	/*
	 * Thumbnail chain: featured image → _dj_image (attachment id OR URL, both
	 * shapes exist in the wild). Without the fallback most DJs render as a
	 * blank avatar in the picker even though they do have artwork.
	 */
	$dj_thumb = function_exists( 'apollo_event_dj_thumb' )
		? apollo_event_dj_thumb( (int) $dj_post->ID )
		: (string) ( get_the_post_thumbnail_url( $dj_post->ID, 'thumbnail' ) ?: '' );

	$djs_js[] = array(
		'id'     => (int) $dj_post->ID,
		'name'   => $dj_post->post_title,
		'thumb'  => $dj_thumb,
		'handle' => (string) get_post_meta( $dj_post->ID, '_dj_instagram', true ),
	);
}

/* Seed chip cache for already-selected team members (edit mode). */
$team_seed_js = array();
if ( ! empty( $edit_payload['coauthors'] ) && is_array( $edit_payload['coauthors'] ) ) {
	foreach ( $edit_payload['coauthors'] as $team_uid ) {
		$team_uid = absint( $team_uid );
		$team_u   = $team_uid ? get_userdata( $team_uid ) : false;
		if ( ! $team_u ) {
			continue;
		}
		$team_seed_js[] = array(
			'id'       => $team_uid,
			'name'     => (string) $team_u->display_name,
			'username' => (string) $team_u->user_login,
			'avatar'   => get_avatar_url( $team_uid, array( 'size' => 64 ) ) ?: '',
		);
	}
}

$form_config = array(
	'restUrl'      => esc_url_raw( rest_url( 'apollo/v1/eventos' ) ),
	'usersUrl'     => esc_url_raw( rest_url( 'apollo/v1/users' ) ),
	'djsUrl'       => esc_url_raw( rest_url( 'apollo/v1/djs' ) ),
	'locsUrl'      => esc_url_raw( rest_url( 'apollo/v1/local' ) ),
	'nonce'        => wp_create_nonce( 'wp_rest' ),
	'mediaNonce'   => wp_create_nonce( 'media-form' ),
	// PHASE 007: was '/painel/eventos/' (still works, untouched route) — now
	// points at the real KPI dashboard so Save/Cancel lands somewhere that
	// actually reflects the event just saved.
	'dashboardUrl' => home_url( '/eventos/meus/' ),
	'createUrl'    => home_url( '/novo-evento/' ),
	'editId'       => $is_edit ? $edit_id : 0,
	'editEvent'    => $is_edit ? $edit_payload : null,
	'events'       => $sidebar_events,
	'locs'         => $locs_js,
	'djs'          => $djs_js,
	'teamSeed'     => $team_seed_js,
	'maxTeam'      => 20,
	/*
	 * Who may upload a file into the Apollo media library?
	 *   apollo  = administrator · MOD = editor · cena+ = author · cena = contributor
	 * Everyone else gets the external-image-URL option only. The capability check
	 * is the real gate (upload_files); the role list is the Apollo-side intent,
	 * and either one being true is enough.
	 */
	/*
	 * Open-Meteo is not in this site's connect-src CSP, so the forecast widget
	 * only produced repeated console violations. Opt-in: flip this true ONLY
	 * after adding https://api.open-meteo.com to connect-src.
	 */
	'weatherEnabled' => (bool) apply_filters( 'apollo_event_weather_enabled', false ),
	'canUpload'    => (
		current_user_can( 'upload_files' )
		|| (bool) array_intersect(
			array( 'administrator', 'editor', 'author', 'contributor' ),
			(array) $current_user->roles
		)
	),
	'user'         => array(
		'id'       => (int) $current_user->ID,
		'name'     => $current_user->display_name,
		'initials' => apollo_event_user_initials( $current_user->display_name ),
		'avatar'   => get_avatar_url( $current_user->ID, array( 'size' => 96 ) ),
		'role'     => ! empty( $current_user->roles[0] ) ? $current_user->roles[0] : '',
	),
	'i18n'         => array(
		'createTitle'  => __( 'Criar Novo Evento', 'apollo-events' ),
		'editTitle'    => __( 'Editar Evento', 'apollo-events' ),
		'createSub'    => __( 'Preencha os dados para publicar um novo evento na Apollo.', 'apollo-events' ),
		'save'         => __( 'Salvar Evento', 'apollo-events' ),
		'update'       => __( 'Atualizar Evento', 'apollo-events' ),
		'saved'        => __( 'Evento salvo!', 'apollo-events' ),
		'updated'      => __( 'Evento atualizado!', 'apollo-events' ),
		'deleted'      => __( 'Evento deletado', 'apollo-events' ),
		'errDelete'    => __( 'Só é possível deletar ao editar um evento', 'apollo-events' ),
		'errDeleteFail'=> __( 'Falha ao deletar', 'apollo-events' ),
		'errRequired'  => __( 'Corrija os campos destacados', 'apollo-events' ),
		'errNetwork'   => __( 'Falha de rede ao salvar. Tente de novo.', 'apollo-events' ),
		'coauthors'    => __( 'Equipe do Evento', 'apollo-events' ),
		'noUsers'      => __( 'Ninguém encontrado', 'apollo-events' ),
		'searching'    => __( 'Buscando…', 'apollo-events' ),
		'teamLimit'    => __( 'Limite da equipe atingido', 'apollo-events' ),
		'searchFail'   => __( 'Não foi possível buscar agora', 'apollo-events' ),
	),
	// _debugCatalog removed (2026-07-21): its only consumer was the localhost
	// debug beacon stripped from apollo-events-create-bridge.js.
);

$event_v = defined( 'APOLLO_EVENT_VERSION' ) ? APOLLO_EVENT_VERSION : '1.0.0';
$page_title = $is_edit
	? __( 'Editar Evento', 'apollo-events' )
	: __( 'Criar Novo Evento', 'apollo-events' );

wp_enqueue_media();

ob_start();
?>
<script>/* About editor glyphs: skip remote SVG fetch tier; RemixIcon webfont below. */
window.apolloIconConfig = Object.assign({}, window.apolloIconConfig || {}, { remoteFetch: false });</script>
<link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
<?php
$about_css_rel = 'assets/css/apollo-events-about-editor.css';
$about_css_ver = function_exists( 'apollo_event_asset_ver' ) ? apollo_event_asset_ver( $about_css_rel ) : ( defined( 'APOLLO_EVENT_VERSION' ) ? APOLLO_EVENT_VERSION : '1.0.0' );
?>
<link rel="stylesheet" href="<?php echo esc_url( APOLLO_EVENT_URL . $about_css_rel . '?v=' . rawurlencode( (string) $about_css_ver ) ); ?>">
<?php
require $shared . 'shell-styles.php';
$extra_head = ob_get_clean();

/* ── ONE SHELL (unification, 2026-08-05) ─────────────────────────────────────
   This file used to hand-roll its own Apollo+ document: apollo_render_document_open()
   → <body class="ax-body"> → apollo_render_app_shell() → the LOCAL
   template-parts/shared/{topbar,overlay,panels,aside}.php → <div class="ax-shell">
   → <main class="ax-main">.

   That made it a SECOND Apollo+ shell, structurally divergent from the one every
   phase-001..010 screen uses via apollo_plus_open():

     · a different aside — this file's local shared/aside.php, not
       apollo-templates' apollo-plus/aside.php, so the gestor group (Meus eventos,
       Meus anúncios, Minhas Comunas, Meus Núcleos, Meus projetos, Minhas tarefas)
       simply did not exist here. Navigating /eventos/meus → /novo-evento changed
       the sidebar out from under the user.
     · a different .ax-main geometry — shell-styles.php declares
       .ax-main{padding:50px 30px 180px;max-width:1040px} while the shared
       aside-styles.php declares .ax-main{padding:0;width:100%;max-width:none}
       plus the ≥1000px pinned-aside offsets. Two owners, opposite intents.
     · a different topbar CSS source, so the two shells drifted independently.

   It now mounts on the single entry point. apollo_plus_open() gained `theme` and
   `html_class` in the same pass precisely so this conversion loses nothing: the
   dark theme and is-logged class this screen has always set are passed through,
   not dropped. The local shared/{topbar,overlay,panels,aside}.php requires are
   gone — the shell owns all four now.

   shell-styles.php is still required above, but ONLY for its component layer
   (buttons, cards, .field-input, the .as2 combobox…) which this form depends on;
   its shell half now self-skips, see the guard at the top of that file. */
apollo_plus_open(
	array(
		'title'      => $page_title . ' — Apollo::Rio',
		'extra_head' => $extra_head,
		'screen'     => 'eventos/novo',
		'theme'      => 'dark',
		'html_class' => 'is-logged',
	)
);
?>

	<!-- PHP HANDOFF: names map 1:1 to REST create/update + Registry meta (see form.html map). -->

			<?php require $parts . 'form-header.php'; ?>

			<form id="eventForm" method="post" onsubmit="event.preventDefault();" novalidate>
				<input type="hidden" id="a_eve_rest_url" value="<?php echo esc_attr( $form_config['restUrl'] ); ?>">
				<input type="hidden" id="a_eve_rest_nonce" value="<?php echo esc_attr( $form_config['nonce'] ); ?>">
				<input type="hidden" id="a_eve_edit_id" value="<?php echo esc_attr( (string) ( $is_edit ? $edit_id : 0 ) ); ?>">

				<?php require $parts . 'form-basic.php'; ?>
				<?php require $parts . 'form-coauthors.php'; ?>
				<?php require $parts . 'form-media.php'; ?>
				<?php require $parts . 'form-taxonomy.php'; ?>
				<?php require $parts . 'form-venue.php'; ?>
				<?php require $parts . 'form-lineup.php'; ?>
				<?php require $parts . 'form-access.php'; ?>
				<?php require $parts . 'form-coupons.php'; ?>
				<?php require $parts . 'form-summary.php'; ?>
			</form>

	<?php require $parts . 'modals.php'; ?>
	<?php require $parts . 'scripts.php'; ?>

<?php
/* Closes </main>, fires apollo/plus/before_close (support runtime + topbar
   behaviour) and closes the document — the exact counterpart of the
   apollo_plus_open() above. */
apollo_plus_close();
