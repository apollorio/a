<?php

/**
 * Apollo Email Verification Template — same centered auth shell as /registre.
 *
 * @package Apollo\Login
 */

if (! defined('ABSPATH')) {
	exit;
}

$js_config = array(
	'ajaxUrl'            => admin_url('admin-ajax.php'),
	'nonce'              => wp_create_nonce('apollo_auth_nonce'),
	'loginUrl'           => \Apollo\Login\apollo_login_canonical_login_url(),
	'registerUrl'        => \Apollo\Login\apollo_login_register_url(),
	'verifyEmailUrl'     => home_url('/verificar-email/?pending=1'),
	'redirectAfterLogin' => home_url('/feed'),
	'strings'            => array(
		'loginSuccess' => __('Acesso autorizado. Redirecionando...', 'apollo-login'),
	),
);

?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="light">

<head>
	<?php
	if (function_exists('apollo_render_document_head')) {
		apollo_render_document_head(
			array(
				'title'      => esc_html(get_bloginfo('name')) . ' - ' . esc_html__('Verificar e-mail', 'apollo-login'),
				'auth_lite'  => false,
				'skip_seo'  => true,
				'extra_head' => '<meta name="robots" content="noindex,nofollow">',
			)
		);
	}
	require APOLLO_LOGIN_DIR . 'templates/parts/auth-head.php';
	?>
</head>

<body data-apollo-page="verificar-email" data-apollo-auth="1" data-state="normal" data-auth-page="verify-email">

	<?php require APOLLO_LOGIN_DIR . 'templates/parts/auth-background.php'; ?>

	<div class="auth-stage">
		<div class="terminal-wrapper tl" id="auth-card">
			<div class="notification-area"></div>

			<?php
			$apollo_auth_page_subtitle = __('Verificação de e-mail', 'apollo-login');
			require APOLLO_LOGIN_DIR . 'templates/parts/new_header.php';
			?>

			<div class="scroll-area">
				<section id="verify-email-section">
					<?php echo do_shortcode('[apollo_verify_email]'); ?>
				</section>
			</div>

			<?php require APOLLO_LOGIN_DIR . 'templates/parts/new_footer.php'; ?>
		</div>
	</div>

	<?php
	if (function_exists('apollo_render_report_modal')) {
		apollo_render_report_modal('apolloReportTrigger', 'frontend');
	}
	?>

	<?php require APOLLO_LOGIN_DIR . 'templates/parts/auth-scripts.php'; ?>

</body>

</html>
