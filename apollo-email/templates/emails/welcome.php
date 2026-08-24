<?php
/**
 * Welcome email — transactional shell (white / minimal).
 *
 * @package Apollo\Email
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user = esc_html( $user_name ?? 'Novo(a) Membro' );

$doc_title     = 'Apollo::Rio — Boas-vindas';
$preview_text  = 'Bem-vindx ao Apollo::Rio — sua conta está ativa.';
$kicker        = 'Boas-vindas';
$headline      = 'Bem-vindx<br>ao Apollo.';
$intro         = sprintf(
	'Olá, %1$s! Você entrou na plataforma %2$s. Complete seu perfil e explore a cena.',
	$user,
	esc_html( $site_name ?? 'Apollo Rio' )
);
$cta_url       = $profile_url ?? ( $site_url ?? '#' );
$cta_label     = 'Acessar meu perfil';
$cta_merge_tag = 'profile_url';
$show_notice   = false;

require __DIR__ . '/partials/transactional-layout.php';
