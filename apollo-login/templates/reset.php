<?php
/**
 * Apollo Password Reset Landing Page (/reset/) — same shell as /verificar-email.
 *
 * @package Apollo\Login
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$auth_config = apply_filters(
	'apollo_auth_config',
	array(
		'ajax_url'             => admin_url( 'admin-ajax.php' ),
		'nonce'                => wp_create_nonce( 'apollo_auth_nonce' ),
		'max_failed_attempts'  => 3,
		'lockout_duration'     => 60,
		'simon_levels'         => 4,
		'reaction_targets'     => 4,
		'redirect_after_login' => home_url( '/feed' ),
		'terms_url'            => home_url( '/termos-e-politica/' ),
	)
);

$js_config = array(
	'ajaxUrl'             => $auth_config['ajax_url'],
	'nonce'               => $auth_config['nonce'],
	'loginUrl'            => \Apollo\Login\apollo_login_canonical_login_url(),
	'passwordRecoveryUrl' => \Apollo\Login\apollo_login_password_recovery_url(),
	'redirectAfterLogin'  => $auth_config['redirect_after_login'],
	'strings'             => array(
		'loginSuccess' => __( 'Acesso autorizado. Redirecionando...', 'apollo-login' ),
		'loginFailed'  => __( 'Credenciais incorretas. Tente novamente.', 'apollo-login' ),
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
				'title'      => esc_html(get_bloginfo('name')) . ' - ' . esc_html__('Redefinir senha', 'apollo-login'),
				'auth_lite'  => false,
				'skip_seo'   => true,
				'extra_head' => '<meta name="robots" content="noindex,nofollow">',
			)
		);
	}
	require APOLLO_LOGIN_DIR . 'templates/parts/auth-head.php';
	?>
</head>

<body data-apollo-page="reset" data-apollo-auth="1" data-state="normal" data-auth-page="reset-password">

	<?php require APOLLO_LOGIN_DIR . 'templates/parts/auth-background.php'; ?>

	<div class="auth-stage">
		<div class="terminal-wrapper tl" id="auth-card">
			<div class="notification-area"></div>

			<?php
			$apollo_auth_page_subtitle = __( 'Segurança da conta', 'apollo-login' );
			require APOLLO_LOGIN_DIR . 'templates/parts/new_header.php';
			?>

			<div class="scroll-area">
				<section id="reset-password-section">
					<?php echo do_shortcode( '[apollo_password_reset]' ); ?>
				</section>
			</div>

			<?php require APOLLO_LOGIN_DIR . 'templates/parts/new_footer.php'; ?>
		</div>
	</div>

	<?php
	if ( function_exists( 'apollo_render_report_modal' ) ) {
		apollo_render_report_modal( 'apolloReportTrigger', 'frontend' );
	}
	?>

	<?php require APOLLO_LOGIN_DIR . 'templates/parts/auth-scripts.php'; ?>

</body>

</html>
