<?php
/**
 * Email verification — transactional shell (white / minimal).
 *
 * @package Apollo\Email
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$verify_url = $verify_url ?? ( $confirmation_link ?? '#' );

$doc_title     = 'Apollo::Rio — Confirme sua conta';
$preview_text  = 'Quase lá! Confirme seu e-mail para ativar sua conta na plataforma Apollo::Rio.';
$kicker        = 'Confirmação de Cadastro';
$headline      = 'Quase lá.<br>Falta só um<br>último passo.';
$intro         = 'Só precisamos confirmar que foi você mesmo quem solicitou este registro. Isso nos ajuda a manter sua conta protegida antes de liberar o acesso à plataforma.';
$cta_url       = $verify_url;
$cta_label     = 'Confirmar minha conta';
$cta_merge_tag = 'verify_url';
$notice_title  = 'Não solicitou este cadastro?';
$notice_body   = 'Nenhuma ação é necessária. Apenas desconsidere e exclua este e-mail. Seus dados estão seguros e a conta não será ativada.';

require __DIR__ . '/partials/transactional-layout.php';
