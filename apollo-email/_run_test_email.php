<?php
/**
 * Apollo Email — Test Runner.
 *
 * Sends a REAL verification email with a clickable link.
 * All emails are captured by Mailpit — open http://localhost:10000 to read them.
 *
 * Usage (from LocalWP's shell or WP-CLI shell):
 *   php _run_test_email.php                  # sends verification + welcome
 *   php _run_test_email.php verification     # verification only
 *   php _run_test_email.php welcome          # welcome only
 *   php _run_test_email.php all              # every template
 *
 * HOW TO RUN:
 *   In LocalWP → right-click "apollo" site → Open Site Shell → paste above command.
 *   OR in WP-CLI: cd to plugins/apollo-email and run the command.
 *
 * VIEWING EMAILS:
 *   Open http://localhost:10000 in your browser (Mailpit inbox).
 */

declare(strict_types=1);

// ── Boot WordPress ─────────────────────────────────────────────────────────────
$wp_root = dirname( __FILE__, 4 ); // plugins/apollo-email → wp-content/plugins → wp-content → public

// Must be defined BEFORE wp-load.php
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', $wp_root . '/' );
}

// LocalWP MySQL listens on TCP 127.0.0.1:10005 from any external shell.
// wp-config.php uses 'localhost' (socket). We pre-define so wp-config silently skips.
// Suppress the inevitable PHP 8 double-define warning with a targeted handler.
if ( ! defined( 'DB_HOST' ) ) {
	define( 'DB_HOST', '127.0.0.1:10005' );
}

set_error_handler( static function ( int $errno, string $errstr ): bool {
	// Silence only the double-define constant warning from wp-config.php.
	if ( $errno === E_WARNING && str_contains( $errstr, 'already defined' ) ) {
		return true; // handled — suppress
	}
	return false; // let default handler run
} );

$_SERVER['HTTP_HOST']   = 'apollo.local';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['SERVER_NAME'] = 'apollo.local';

require_once $wp_root . '/wp-load.php';

restore_error_handler();

// ── Bootstrap guard ────────────────────────────────────────────────────────────
if ( ! class_exists( 'Apollo\\Email\\Plugin' ) ) {
	$loader = WP_PLUGIN_DIR . '/apollo-email/apollo-email.php';
	if ( file_exists( $loader ) ) {
		require_once $loader;
		// Re-trigger plugins_loaded for CLI context where it may not have fired yet.
		do_action( 'plugins_loaded' );
		do_action( 'init' );
	}
}

if ( ! class_exists( 'Apollo\\Email\\Plugin' ) ) {
	echo "FATAL: apollo-email plugin class not found.\n";
	echo "Make sure the plugin is active and apollo-core is loaded.\n";
	exit( 1 );
}

$plugin = Apollo\Email\Plugin::instance();

// ── Mail transport — force SMTP from wp-config constants ───────────────────────
$smtp_host = defined( 'APOLLO_SMTP_HOST' ) ? (string) APOLLO_SMTP_HOST : '127.0.0.1';
$smtp_user = defined( 'APOLLO_SMTP_USER' ) ? (string) APOLLO_SMTP_USER : '';
$smtp_pass = defined( 'APOLLO_SMTP_PASS' ) ? (string) APOLLO_SMTP_PASS : '';
$smtp_port = defined( 'APOLLO_SMTP_PORT' ) ? (int) APOLLO_SMTP_PORT : 10001;

add_action(
	'phpmailer_init',
	function ( \PHPMailer\PHPMailer\PHPMailer $mailer ): void {
		global $smtp_host, $smtp_user, $smtp_pass, $smtp_port;

		$mailer->isSMTP();
		$mailer->Host       = $smtp_host;
		$mailer->Port       = $smtp_port;
		$mailer->SMTPSecure = 'ssl';
		$mailer->SMTPAuth   = ( '' !== $smtp_user );
		if ( $mailer->SMTPAuth ) {
			$mailer->Username = $smtp_user;
			$mailer->Password = $smtp_pass;
		}
	},
	99
);

$from_email = 'oi@apollo.rio.br';
$from_name  = 'Apollo Rio';
$to         = $argv[2] ?? 'apollo.rio.br@gmail.com';

add_filter( 'wp_mail_from',      fn() => $from_email, 99 );
add_filter( 'wp_mail_from_name', fn() => $from_name,  99 );

// ── Step 1: Force-refresh ALL seeded templates from _library HTML files ─────────
// This updates the CPT posts in the DB with the latest seed content.
// Critical: ensures {{verify_url}} and other tags are present in the stored HTML.
echo "\n=== Step 1: Refreshing seeded templates in DB ===\n";
$refresh_results = Apollo\Email\Activation::refreshAllTemplates();
foreach ( $refresh_results as $r ) {
	$icon = ( 'updated' === $r['status'] ) ? '✓' : '!';
	echo "  [{$icon}] {$r['slug']}: {$r['status']}\n";
}

// ── Step 2: Resolve target user ────────────────────────────────────────────────
// Prefer the admin user (ID 1). Falls back to any existing user.
$target_user_id = 1;
$target_user    = get_userdata( $target_user_id );
if ( ! $target_user ) {
	$users = get_users( array( 'number' => 1, 'orderby' => 'ID', 'order' => 'ASC' ) );
	if ( empty( $users ) ) {
		echo "\nFATAL: No users found in database.\n";
		exit( 1 );
	}
	$target_user    = $users[0];
	$target_user_id = $target_user->ID;
}

echo "\n=== Step 2: Target user ===\n";
echo "  ID:    {$target_user_id}\n";
echo "  Login: {$target_user->user_login}\n";
echo "  Email: {$target_user->user_email}\n";

// Override recipient to test address (never send to real users in test)
$recipient_email = $to;

// ── Step 3: Generate REAL verification token ────────────────────────────────────
// Stores _apollo_verification_token in user meta (24h TTL) so the click works.
if ( ! function_exists( 'Apollo\\Login\\apollo_generate_verification_token' ) ) {
	$login_functions = WP_PLUGIN_DIR . '/apollo-login/includes/functions.php';
	if ( file_exists( $login_functions ) ) {
		require_once $login_functions;
	}
}

$token = '';
if ( function_exists( 'Apollo\\Login\\apollo_generate_verification_token' ) ) {
	$token = \Apollo\Login\apollo_generate_verification_token( $target_user_id );
} else {
	// Fallback: generate manually (same logic as apollo-login)
	$token = wp_generate_password( 32, false );
	update_user_meta( $target_user_id, '_apollo_verification_token', $token );
	update_user_meta( $target_user_id, '_apollo_verification_token_expiry', time() + DAY_IN_SECONDS );
}

$verify_url = add_query_arg(
	array(
		'user'  => $target_user_id,
		'token' => $token,
	),
	home_url( '/verificar-email/' )
);

echo "\n=== Step 3: Verification token generated ===\n";
echo "  Token:      {$token}\n";
echo "  Verify URL: {$verify_url}\n";

// ── Step 4: Determine which templates to test ───────────────────────────────────
$template_arg = $argv[1] ?? 'registration';

$default_name = $target_user->display_name ?: $target_user->user_login;

// Full payload for each template slug
$all_payloads = array(

	'verification' => array(
		'subject' => '[Apollo Teste] Confirme seu email',
		'data'    => array(
			'user_id'           => $target_user_id,
			'user_name'         => $default_name,
			'username'          => $target_user->user_login,
			'user_email'        => $target_user->user_email,
			'verify_url'        => $verify_url,
			'confirmation_link' => $verify_url,
			'registration_date' => wp_date( 'd/m/Y', strtotime( $target_user->user_registered ) ),
			'expires_in'        => '24 horas',
			'expiration_time'   => '24 horas',
			'site_name'         => get_bloginfo( 'name' ),
			'site_url'          => home_url( '/' ),
		),
	),

	'welcome' => array(
		'subject' => '[Apollo Teste] Bem-vindo(a)!',
		'data'    => array(
			'user_id'      => $target_user_id,
			'user_name'    => $default_name,
			'username'     => $target_user->user_login,
			'user_email'   => $target_user->user_email,
			'profile_url'  => home_url( '/id/' . $target_user->user_login ),
			'plan_name'    => 'Apollo Gratuito',
			'member_since' => wp_date( 'd/m/Y', strtotime( $target_user->user_registered ) ),
			'site_name'    => get_bloginfo( 'name' ),
			'site_url'     => home_url( '/' ),
		),
	),

	'password-reset' => array(
		'subject' => '[Apollo Teste] Redefinir senha',
		'data'    => array(
			'user_id'           => $target_user_id,
			'user_name'         => $default_name,
			'username'          => $target_user->user_login,
			'reset_url'         => home_url( '/acesso/?action=reset&key=TESTTOKEN&login=' . rawurlencode( $target_user->user_login ) ),
			'confirmation_link' => home_url( '/acesso/?action=reset&key=TESTTOKEN&login=' . rawurlencode( $target_user->user_login ) ),
			'expires_in'        => '1 hora',
			'expiration_time'   => '1 hora',
			'user_email'        => $target_user->user_email,
			'registration_date' => wp_date( 'd/m/Y', strtotime( $target_user->user_registered ) ),
			'site_name'         => get_bloginfo( 'name' ),
		),
	),

	'notification' => array(
		'subject' => '[Apollo Teste] Comunicado da plataforma',
		'data'    => array(
			'user_id'     => $target_user_id,
			'user_name'   => $default_name,
			'title'       => 'Novidades Apollo 2026',
			'message'     => 'Temos novidades importantes para compartilhar com você.',
			'action_url'  => home_url( '/' ),
			'action_text' => 'Explorar Plataforma',
			'site_name'   => get_bloginfo( 'name' ),
		),
	),

	'digest' => array(
		'subject' => '[Apollo Teste] Seu resumo semanal',
		'data'    => array(
			'user_id'         => $target_user_id,
			'user_name'       => $default_name,
			'digest_title'    => 'Resumo da Semana',
			'digest_intro'    => 'O que aconteceu na plataforma esta semana:',
			'digest_sections' => array(
				array(
					'title' => 'Social',
					'items' => array(
						array( 'heading' => '5 novas conexões', 'message' => 'DJs e produtores entraram na comunidade.' ),
						array( 'heading' => 'Post em destaque', 'message' => 'Seu post recebeu 12 reações WOW esta semana.', 'time' => 'há 2 dias' ),
					),
				),
				array(
					'title' => 'Eventos',
					'items' => array(
						array( 'heading' => 'Drum & Bass @ Lapa', 'message' => 'Amanhã 23h — Match 89% com seus gostos.', 'url' => home_url( '/eventos' ) ),
					),
				),
			),
			'site_name' => get_bloginfo( 'name' ),
			'site_url'  => home_url( '/' ),
		),
	),

	'task-reminder' => array(
		'subject' => '[Apollo Teste] Lembrete de Tarefa',
		'data'    => array(
			'user_id'             => $target_user_id,
			'user_name'           => $default_name,
			'task_name'           => 'Confirmar som e iluminação',
			'task_date'           => wp_date( 'd/m/Y', strtotime( '+2 days' ) ),
			'task_deadline_label' => 'em 2 dias',
			'task_priority'       => 'Alta',
			'task_project'        => 'Festival Eletrônico Lapa 2026',
			'task_project_url'    => home_url( '/gestor/?event=42' ),
			'task_assigned_by'    => 'Marcos Silva',
			'assigned_by_url'     => home_url( '/id/marcos-silva' ),
			'task_url'            => home_url( '/gestor/?event=42&task=108' ),
			'current_year'        => wp_date( 'Y' ),
			'site_name'           => get_bloginfo( 'name' ),
			'site_url'            => home_url( '/' ),
		),
	),
);

// Determine test set
if ( 'all' === $template_arg ) {
	$tests = $all_payloads;
} elseif ( 'registration' === $template_arg ) {
	// Default: only the registration-related emails (verification + welcome)
	$tests = array(
		'verification' => $all_payloads['verification'],
		'welcome'      => $all_payloads['welcome'],
	);
} elseif ( isset( $all_payloads[ $template_arg ] ) ) {
	$tests = array( $template_arg => $all_payloads[ $template_arg ] );
} else {
	echo "\nUnknown template: {$template_arg}\n";
	echo "Available: " . implode( ', ', array_keys( $all_payloads ) ) . ", all, registration\n";
	exit( 1 );
}

// ── Step 5: Send ────────────────────────────────────────────────────────────────
echo "\n=== Step 5: Sending emails ===\n";
echo "  To:   {$recipient_email}\n";
echo "  From: {$from_email}\n\n";

$all_passed = true;
foreach ( $tests as $slug => $config ) {
	$result = $plugin->sender()->sendTemplate( $recipient_email, $config['subject'], $slug, $config['data'] );

	if ( $result['success'] ) {
		echo "  ✓  [{$slug}]  SENT";
		if ( ! empty( $result['log_id'] ) ) {
			echo "  (log #{$result['log_id']})";
		}
		echo "\n";
	} else {
		$all_passed = false;
		echo "  ✗  [{$slug}]  FAILED — " . ( $result['error'] ?? 'unknown error' ) . "\n";
	}
}

// ── Summary ─────────────────────────────────────────────────────────────────────
echo "\n===================================================\n";
echo "  Mailpit inbox:   http://localhost:10000\n";
echo "  Verify link:     {$verify_url}\n";
echo "  Click the link above to test the verification flow.\n";
echo "  Success redirects to: " . home_url( '/acesso/?verified=success' ) . "\n";
echo "===================================================\n\n";

exit( $all_passed ? 0 : 1 );


$from_email = 'oi@apollo.rio.br';
$from_name  = 'Apollo Rio';
$to         = 'rafapevalle@gmail.com';

// Override from address
add_filter( 'wp_mail_from',      fn() => $from_email, 99 );
add_filter( 'wp_mail_from_name', fn() => $from_name,  99 );

// Boot apollo-email plugin manually if not booted
if ( ! class_exists( 'Apollo\\Email\\Plugin' ) ) {
	$loader = WP_PLUGIN_DIR . '/apollo-email/apollo-email.php';
	if ( file_exists( $loader ) ) {
		require_once $loader;
	}
}

if ( ! class_exists( 'Apollo\\Email\\Plugin' ) ) {
	echo "ERROR: apollo-email plugin not available.\n";
	exit( 1 );
}

$plugin = Apollo\Email\Plugin::instance();

// Template test payloads
$templates = array(
	'welcome' => array(
		'subject' => '[Apollo] Bem-vindx ao Apollo Rio!',
		'data'    => array(
			'user_name'    => 'Rafael',
			'username'     => 'rafapevalle',
			'profile_url'  => 'https://apollo.rio.br/id/rafapevalle',
			'plan_name'    => 'Apollo Pro',
			'member_since' => date( 'd/m/Y' ),
			'site_name'    => 'Apollo Rio',
			'site_url'     => 'https://apollo.rio.br',
		),
	),
	'verification' => array(
		'subject' => '[Apollo] Confirme seu email',
		'data'    => array(
			'user_name'         => 'Rafael',
			'username'          => 'rafapevalle',
			'verify_url'        => 'https://apollo.rio.br/?apollo_verify=abc123xyz',
			'registration_date' => date( 'd/m/Y' ),
			'expires_in'        => '48 horas',
			'site_name'         => 'Apollo Rio',
		),
	),
	'password-reset' => array(
		'subject' => '[Apollo] Redefinir sua senha',
		'data'    => array(
			'user_name'  => 'Rafael',
			'reset_url'  => 'https://apollo.rio.br/?apollo_reset=xyz789abc',
			'expires_in' => '1 hora',
			'site_name'  => 'Apollo Rio',
		),
	),
	'notification' => array(
		'subject' => '[Apollo] Comunicado importante',
		'data'    => array(
			'user_name'   => 'Rafael',
			'title'       => 'Novidades da Plataforma',
			'message'     => 'Temos novidades importantes para compartilhar com você esta semana.',
			'action_url'  => 'https://apollo.rio.br',
			'action_text' => 'Ver Novidades',
			'site_name'   => 'Apollo Rio',
			'items'       => array(
				array( 'heading' => 'Novos eventos esta semana', 'message' => 'Confira a programação completa de festas e shows no Rio.' ),
				array( 'heading' => 'DJs em destaque', 'message' => 'Perfis verificados de novos artistas na plataforma.' ),
				array( 'heading' => 'Grupos ativos', 'message' => 'Novos grupos de House Music e Techno disponíveis para participar.' ),
			),
		),
	),
	'digest' => array(
		'subject' => '[Apollo] Seu resumo mastigado da semana',
		'data'    => array(
			'user_name'      => 'Rafael',
			'digest_title'   => 'Seu Resumo Mastigado',
			'week_range'     => 'Semana de ' . date( 'd/m' ) . ' — ' . date( 'd/m', strtotime( '+6 days' ) ),
			'total_activity' => 124,
			'read_pct'       => 78,
			'site_name'      => 'Apollo Rio',
			'site_url'       => 'https://apollo.rio.br',
			'digest_sections' => array(
				'social' => array(
					'count' => 23,
					'action_url' => 'https://apollo.rio.br/social',
					'items' => array(
						array( 'heading' => '12 novas conexões', 'message' => 'DJ Mariana, Pedro Bass, Luna Groove e mais 9 entraram na comunidade.' ),
						array( 'heading' => 'Posts em destaque', 'message' => 'Seu post "Setup minimalista para DJ" recebeu 34 reações WOW e 8 depoimentos.', 'time' => 'há 2 dias' ),
						array( 'heading' => 'Grupos ativos', 'message' => 'House Music RJ (5 novos posts) • Produtores Cariocas (3 novos posts)' ),
					),
				),
				'documents' => array(
					'count' => 4,
					'action_url' => 'https://apollo.rio.br/docs',
					'items' => array(
						array( 'heading' => 'Contrato atualizado', 'message' => 'Rider técnico do evento Sunset Theory foi revisado por Marcus.', 'time' => 'ontem' ),
						array( 'heading' => 'Novo documento compartilhado', 'message' => 'Planilha de custos — Festival Verão 2025.' ),
					),
				),
				'events' => array(
					'count' => 6,
					'action_url' => 'https://apollo.rio.br/eventos',
					'items' => array(
						array( 'heading' => 'Sunset Theory @ Fabrika', 'message' => '28 Jun — Deep house sessions. Match 92% com seus gostos.', 'url' => 'https://apollo.rio.br/events/sunset-theory' ),
						array( 'heading' => 'Bass Culture @ Lapa', 'message' => '30 Jun — Drum & Bass underground. Match 87%.', 'url' => 'https://apollo.rio.br/events/bass-culture' ),
						array( 'heading' => 'Workshop Produção Eletrônica', 'message' => '02 Jul — Ableton Live com mestres da cena local.' ),
					),
				),
				'chats' => array(
					'count' => 18,
					'action_url' => 'https://apollo.rio.br/chat',
					'items' => array(
						array( 'heading' => 'Equipe DJ (8 msgs)', 'message' => 'Marcus: "Confirmado o som pra sexta?" — Luna: "Vou levar o CDJ extra."', 'time' => 'há 3h' ),
						array( 'heading' => 'Pedro Bass (5 msgs)', 'message' => 'Sobre a collab do remix — aguardando seus stems.', 'time' => 'há 6h' ),
						array( 'heading' => 'Produtores RJ (5 msgs)', 'message' => 'Discussão sobre sample packs e plugins gratuitos.' ),
					),
				),
				'notifications' => array(
					'count' => 14,
					'action_url' => 'https://apollo.rio.br/notificacoes',
					'items' => array(
						array( 'heading' => '3 menções em posts', 'message' => 'Você foi mencionadx em conversas sobre o evento Sunset Theory.' ),
						array( 'heading' => '5 novos uploads curtidos', 'message' => 'Suas tracks receberam reações positivas.' ),
						array( 'heading' => '6 comentários pendentes', 'message' => 'Novos depoimentos nos seus eventos e publicações.' ),
					),
				),
				'profile' => array(
					'count' => 47,
					'action_url' => 'https://apollo.rio.br/id/rafapevalle',
					'items' => array(
						array( 'heading' => '47 visitas ao seu perfil', 'message' => 'Aumento de 23% em relação à semana anterior. Top visitantes: 3 promoters, 12 DJs.' ),
						array( 'heading' => 'Busca orgânica', 'message' => '18 pessoas encontraram você via busca por "DJ house Rio de Janeiro".' ),
					),
				),
				'tracks' => array(
					'count' => 8,
					'action_url' => 'https://apollo.rio.br/radio',
					'items' => array(
						array( 'heading' => 'Novo release: "Midnight Groove"', 'message' => 'DJ Mariana lançou uma track de deep house. 120 BPM.', 'url' => 'https://apollo.rio.br/tracks/midnight-groove' ),
						array( 'heading' => '4 tracks da sua wishlist', 'message' => 'Artistas que você segue lançaram novas faixas esta semana.' ),
						array( 'heading' => 'Playlist atualizada', 'message' => 'Apollo Weekly Mix — 12 faixas selecionadas pela curadoria.' ),
					),
				),
				'projects' => array(
					'count' => 5,
					'action_url' => 'https://apollo.rio.br/gestor',
					'items' => array(
						array( 'heading' => 'Festival Verão 2025', 'message' => '2 tarefas pendentes: "Confirmar line-up" (prazo amanhã) e "Enviar rider técnico".', 'time' => 'prazo: amanhã' ),
						array( 'heading' => 'Evento Mensal Lapa', 'message' => '3 tarefas concluídas esta semana. Progresso: 72%.' ),
					),
				),
				'mural' => array(
					'count' => 7,
					'action_url' => 'https://apollo.rio.br/gestor/mural',
					'items' => array(
						array( 'heading' => 'Nota fixada por Marcus', 'message' => '"Reunião de alinhamento sexta 15h — todos confirmem presença."', 'time' => 'há 1 dia' ),
						array( 'heading' => 'Lembrete da equipe', 'message' => 'Luna adicionou: "Confirmar reserva do espaço até quarta."' ),
						array( 'heading' => '3 notas arquivadas', 'message' => 'Notas antigas foram movidas para o arquivo da equipe.' ),
					),
				),
			),
		),
	),
	'task-deadline' => array(
		'subject' => '[Apollo] Prazo de tarefa se aproximando!',
		'data'    => array(
			'user_name'      => 'Rafael',
			'task_title'     => 'Confirmar line-up Festival Verão',
			'task_url'       => 'https://apollo.rio.br/gestor/tarefa/142',
			'project_name'   => 'Festival Verão 2025',
			'project_url'    => 'https://apollo.rio.br/gestor/projeto/festival-verao-2025',
			'deadline_date'  => date( 'd/m/Y', strtotime( '+1 day' ) ),
			'deadline_label' => 'Amanhã',
			'assigned_by'    => 'Marcus Silva',
			'priority'       => 'Alta',
			'site_name'      => 'Apollo Rio',
			'site_url'       => 'https://apollo.rio.br',
		),
	),
);

// Run tests
$template_arg = $argv[1] ?? null;
$tests = $template_arg ? array( $template_arg => $templates[ $template_arg ] ?? null ) : $templates;

echo "\n=== Apollo Email Test Suite ===\n";
echo 'To:   ' . $to . "\n";
echo 'From: ' . $from_email . "\n\n";

foreach ( $tests as $slug => $config ) {
	if ( ! $config ) {
		echo "[{$slug}] SKIP — unknown template\n";
		continue;
	}

	$result = $plugin->sender()->sendTemplate( $to, $config['subject'], $slug, $config['data'] );

	$status = $result['success'] ? 'SUCCESS ✓' : 'FAILED  ✗';
	echo "[{$slug}] {$status}";
	if ( ! empty( $result['error'] ) ) {
		echo '  → ' . $result['error'];
	}
	if ( ! empty( $result['log_id'] ) ) {
		echo '  (log #' . $result['log_id'] . ')';
	}
	echo "\n";
}

echo "\n================================\n\n";
