<?php
/**
 * Resale ticket card — one expandable stub for every advert surface.
 *
 * /casa Classificados + Acomoda, /anuncios grids, [apollo_classifieds] list.
 * Contact CTA is apollo_adverts_contact_url (login → safety → chat), never wa.me.
 * Seller PII is omitted from guest markup.
 *
 * @package Apollo\Adverts
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Print rt-card assets (canvas pages often skip wp_head enqueue).
 */
function apollo_adverts_print_rt_card_assets(): void {
	static $printed = false;
	if ( $printed ) {
		return;
	}
	$printed = true;
	$ver     = defined( 'APOLLO_ADVERTS_VERSION' ) ? APOLLO_ADVERTS_VERSION : '1.2.0';
	$url     = defined( 'APOLLO_ADVERTS_URL' ) ? APOLLO_ADVERTS_URL : '';
	if ( ! $url ) {
		return;
	}
	$nonce = function_exists( 'apollo_csp_nonce_attr' ) ? apollo_csp_nonce_attr() : '';
	printf(
		'<link rel="stylesheet" id="apollo-rt-card-css" href="%s" />' . "\n",
		esc_url( $url . 'assets/css/rt-card.css?v=' . rawurlencode( (string) $ver ) )
	);
	printf(
		'<script%s src="%s" defer id="apollo-rt-card-js"></script>' . "\n",
		$nonce, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		esc_url( $url . 'assets/js/rt-card.js?v=' . rawurlencode( (string) $ver ) )
	);
}

/**
 * Enqueue rt-card CSS/JS once per request.
 */
function apollo_adverts_enqueue_rt_card(): void {
	static $done = false;
	if ( $done ) {
		return;
	}
	$done = true;
	$ver  = defined( 'APOLLO_ADVERTS_VERSION' ) ? APOLLO_ADVERTS_VERSION : '1.2.0';
	$url  = defined( 'APOLLO_ADVERTS_URL' ) ? APOLLO_ADVERTS_URL : '';
	if ( $url ) {
		wp_enqueue_style( 'apollo-adverts-rt-card', $url . 'assets/css/rt-card.css', array(), $ver );
		wp_enqueue_script( 'apollo-adverts-rt-card', $url . 'assets/js/rt-card.js', array(), $ver, true );
	}
	apollo_adverts_print_rt_card_assets();
}

/**
 * @param int                  $post_id Post ID (classified or local).
 * @param array<string, mixed> $args    kind, variant, aria_hidden.
 * @return array<string, mixed>
 */
function apollo_adverts_rt_card_data( int $post_id, array $args = array() ): array {
	$kind     = isset( $args['kind'] ) ? (string) $args['kind'] : 'ticket';
	$is_accom = ( 'accommodation' === $kind );
	$logged   = is_user_logged_in();
	$post     = get_post( $post_id );

	$is_hostel  = function_exists( 'apollo_adverts_is_hostel' )
		? apollo_adverts_is_hostel( $post_id )
		: '1' === (string) get_post_meta( $post_id, '_classified_hostel', true );
	$hostel_url = $is_hostel ? (string) get_post_meta( $post_id, '_classified_hostel_url', true ) : '';

	$locked = $is_accom
		? ( ! $logged && ! $is_hostel )
		: ! $logged;

	$permalink = (string) get_permalink( $post_id );
	$image     = get_the_post_thumbnail_url( $post_id, 'large' )
		?: ( get_the_post_thumbnail_url( $post_id, 'medium' ) ?: '' );
	if ( '' === $image ) {
		$image = $is_accom
			? 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?w=800&q=75'
			: 'https://images.unsplash.com/photo-1459749411175-04bf5292ceea?w=800&q=75';
	}

	$event_title = (string) get_post_meta( $post_id, '_classified_event_title', true );
	if ( '' === $event_title ) {
		$event_title = get_the_title( $post_id );
	}
	$event_loc  = (string) get_post_meta( $post_id, '_classified_event_loc', true );
	$event_date = (string) get_post_meta( $post_id, '_classified_event_date', true );
	if ( $is_accom ) {
		if ( '' === $event_loc ) {
			$event_loc = (string) get_post_meta( $post_id, '_classified_loc', true );
		}
		if ( '' === $event_loc ) {
			$areas = wp_get_post_terms( $post_id, 'local_area', array( 'fields' => 'names' ) );
			$event_loc = ( ! is_wp_error( $areas ) && ! empty( $areas ) ) ? (string) $areas[0] : '';
		}
		if ( '' === $event_date ) {
			$event_date = (string) get_post_meta( $post_id, '_classified_avail_start', true );
		}
	}

	$date_label = '';
	if ( $event_date ) {
		$ts = strtotime( $event_date );
		$date_label = $ts ? wp_date( 'd M', $ts ) : $event_date;
	}

	$qty   = max( 1, (int) get_post_meta( $post_id, '_classified_quantity', true ) );
	$price = get_post_meta( $post_id, '_classified_price', true );
	if ( ( '' === $price || false === $price ) && $is_accom ) {
		$price = get_post_meta( $post_id, '_local_price_range', true );
	}
	$price_n = is_numeric( $price ) ? (float) $price : 0.0;
	$total   = $is_accom ? $price_n : ( $price_n * $qty );

	$condition = strtolower( (string) get_post_meta( $post_id, '_classified_condition', true ) );
	$type_map  = array(
		'meia'   => 'MEIA-ENTRADA',
		'inteira'=> 'INTEIRA',
		'pista'  => 'PISTA',
		'vip'    => 'VIP',
		'camarote' => 'CAMAROTE',
		'mesa'   => 'MESA',
	);
	$type_label = $type_map[ $condition ] ?? '';
	if ( '' === $type_label && ! $is_accom ) {
		$intent = wp_get_post_terms( $post_id, 'classified_intent', array( 'fields' => 'names' ) );
		$type_label = ( ! is_wp_error( $intent ) && ! empty( $intent ) )
			? mb_strtoupper( (string) $intent[0] )
			: 'REPASSE';
	}
	if ( $is_accom ) {
		$type_label = $is_hostel ? 'HOSTEL' : 'ESTADIA';
	}

	$desc = $post ? wp_trim_words( wp_strip_all_tags( (string) $post->post_content ), 42, '…' ) : '';
	if ( '' === $desc ) {
		$desc = $is_accom
			? __( 'Hospedagem anunciada na rede. Detalhes e encontro combinados com o anfitrião.', 'apollo-adverts' )
			: __( 'Ingresso original. Transferência e pagamento combinados diretamente com o vendedor.', 'apollo-adverts' );
	}

	$seller = array(
		'name'     => '',
		'handle'   => '',
		'avatar'   => '',
		'verified' => false,
	);
	if ( $logged && $post ) {
		$user = get_userdata( (int) $post->post_author );
		if ( $user ) {
			$seller['name']     = (string) $user->display_name;
			$seller['handle']   = '@' . $user->user_login;
			$seller['avatar']   = (string) get_avatar_url( (int) $user->ID, array( 'size' => 88 ) );
			$seller['verified'] = (bool) get_user_meta( (int) $user->ID, '_apollo_verified', true );
		}
	}

	$contact = $permalink;
	if ( $is_hostel && $hostel_url ) {
		$contact = $hostel_url;
	} elseif ( function_exists( 'apollo_adverts_contact_url' ) ) {
		$contact = apollo_adverts_contact_url( $post_id );
	}

	$status = 'ativo';
	if ( $post && 'expired' === $post->post_status ) {
		$status = 'encerrado';
	}

	$announced = $post ? get_the_date( 'c', $post_id ) : '';

	$domains = function_exists( 'get_the_terms' )
		? get_the_terms( $post_id, defined( 'APOLLO_TAX_CLASSIFIED_DOMAIN' ) ? APOLLO_TAX_CLASSIFIED_DOMAIN : 'classified_domain' )
		: array();
	$domains = is_wp_error( $domains ) || empty( $domains ) ? '' : implode( ' ', wp_list_pluck( $domains, 'slug' ) );

	return array(
		'id'          => $post_id,
		'kind'        => $kind,
		'locked'      => $locked,
		'hostel'      => $is_hostel,
		'status'      => $status,
		'title'       => $event_title,
		'venue'       => $event_loc,
		'date'        => $date_label,
		'image'       => $image,
		'desc'        => $desc,
		'qty'         => $qty,
		'type_label'  => $type_label,
		'price'       => $price_n,
		'total'       => $total,
		'price_raw'   => $price,
		'seller'      => $seller,
		'permalink'   => $permalink,
		'contact'     => $contact,
		'announced'   => $announced,
		'domains'     => $domains,
		'is_meia'     => ( 'meia' === $condition ),
		'aria_hidden' => ! empty( $args['aria_hidden'] ),
		'variant'     => isset( $args['variant'] ) ? (string) $args['variant'] : 'grid',
	);
}

/**
 * @param int                  $post_id Post ID.
 * @param array<string, mixed> $args    kind, variant, aria_hidden.
 */
function apollo_adverts_render_rt_card( int $post_id, array $args = array() ): void {
	if ( $post_id < 1 ) {
		return;
	}
	apollo_adverts_enqueue_rt_card();
	$data = apollo_adverts_rt_card_data( $post_id, $args );
	$variant = isset( $args['variant'] ) ? (string) $args['variant'] : 'grid';
	$kind    = isset( $args['kind'] ) ? (string) $args['kind'] : 'ticket';
	$file    = APOLLO_ADVERTS_DIR . 'templates/parts/rt-card.php';
	if ( 'market' === $variant && 'ticket' === $kind ) {
		$file = APOLLO_ADVERTS_DIR . 'templates/parts/rt-card-market.php';
	}
	if ( is_readable( $file ) ) {
		include $file;
	}
}
