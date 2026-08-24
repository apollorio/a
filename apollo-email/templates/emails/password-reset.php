<?php
/**
 * Password reset — transactional shell (white / minimal).
 *
 * @package Apollo\Email
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$reset_url = $reset_url ?? ( $confirmation_link ?? '#' );
$user      = esc_html( $user_name ?? 'usuário(a)' );
$expires   = esc_html( $expires_in ?? '1 hora' );

$doc_title     = 'Apollo::Rio — Recuperar senha';
$preview_text  = 'Redefina sua senha na Apollo::Rio.';
$kicker        = 'Segurança da Conta';
$headline      = 'Nova chave<br>de acesso.';
$intro         = sprintf(
	'Olá, %1$s! Recebemos uma solicitação para redefinir sua senha no %2$s. O link abaixo é válido por %3$s.',
	$user,
	esc_html( $site_name ?? 'Apollo Rio' ),
	$expires
);
$cta_url       = $reset_url;
$cta_label     = 'Redefinir minha senha';
$cta_merge_tag = 'reset_url';
$notice_title  = 'Não solicitou esta redefinição?';
$notice_body   = 'Ignore este e-mail com segurança. Sua senha atual permanece inalterada até que você crie uma nova pelo link acima.';

require __DIR__ . '/partials/transactional-layout.php';
