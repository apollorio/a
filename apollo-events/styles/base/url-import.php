<?php

/**
 * Template: URL Event Importer — /eventos/importa
 *
 * Modular orchestrator (create-event pattern). Markup in template-parts/url-import/;
 * logic in assets/js/url-import/*.js; REST boot via UrlImportPage::config().
 *
 * @package Apollo\Event
 * @since   1.7.0
 */

if (! defined('ABSPATH')) {
	exit;
}

use Apollo\Event\Import\UrlImportPage;

if (function_exists('apollo_event_ensure_helpers')) {
	apollo_event_ensure_helpers();
} elseif (defined('APOLLO_EVENT_DIR') && is_readable(APOLLO_EVENT_DIR . 'includes/bootstrap.php')) {
	require_once APOLLO_EVENT_DIR . 'includes/bootstrap.php';
}

if (! is_user_logged_in()) {
	wp_safe_redirect(home_url('/acesso'));
	exit;
}

$parts         = __DIR__ . '/template-parts/url-import/';
$shared        = __DIR__ . '/template-parts/shared/';
$import_config = UrlImportPage::config();
$page_title    = (string) ($import_config['i18n']['pageTitle'] ?? __('Importador de Eventos', 'apollo-events'));

ob_start();
?>
<script>
	window.apolloIconConfig = Object.assign({}, window.apolloIconConfig || {}, {
		remoteFetch: false
	});
</script>
<link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
<?php
require $shared . 'shell-styles.php';
require $parts . 'styles.php';
$extra_head = ob_get_clean();

$plus_open = function_exists('apollo_plus_open');

if ($plus_open) {
	apollo_plus_open(
		array(
			'title'      => $page_title . ' — Apollo::Rio',
			'extra_head' => $extra_head,
			'screen'     => 'eventos/importa',
			'theme'      => 'dark',
			'html_class' => 'is-logged',
		)
	);
} else {
	status_header(200);
	nocache_headers();
?>
	<!DOCTYPE html>
	<html lang="<?php echo esc_attr(get_bloginfo('language')); ?>" class="dark-mode is-logged" data-theme="dark">

	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
		<title><?php echo esc_html($page_title); ?> — Apollo::Rio</title>
		<script src="https://cdn.apollo.rio.br/v1.0.0/core.js?v=Random.x.1&versao=bb" fetchpriority="high"></script>
		<link rel="stylesheet" href="https://cdn.apollo.rio.br/v1.0.0/css/uni.theme.v2.1.css">
		<?php
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $extra_head;
		?>
	</head>

	<body>
	<?php } ?>

	<div class="imp-wrap">

		<?php require $parts . 'header.php'; ?>
		<?php require $parts . 'config-panel.php'; ?>
		<?php require $parts . 'import-form.php'; ?>
		<?php require $parts . 'checklist.php'; ?>
		<?php require $parts . 'results.php'; ?>

	</div>

	<?php require $parts . 'scripts.php'; ?>

	<?php
	if ($plus_open) {
		apollo_plus_close();
	} else {
	?>
	</body>

	</html>
<?php
		exit;
	}
