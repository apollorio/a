<?php
/**
 * Generic notification — transactional shell (white / minimal).
 *
 * @package Apollo\Email
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user    = esc_html( $user_name ?? 'usuário(a)' );
$title   = esc_html( $title ?? 'Notificação' );
$message = ! empty( $message ) ? wp_kses_post( $message ) : '';

$doc_title     = 'Apollo::Rio — ' . $title;
$preview_text  = $title;
$kicker        = 'Notificação';
$headline      = $title;
$intro         = 'Olá, ' . $user . '!' . ( $message ? '<br><br>' . $message : '' );
$cta_url       = $action_url ?? '#';
$cta_label     = $action_text ?? 'Ver detalhes';
$cta_merge_tag = 'action_url';
$show_cta      = ! empty( $action_url );
$show_fallback = $show_cta;
$show_notice   = false;

require __DIR__ . '/partials/transactional-layout.php';
