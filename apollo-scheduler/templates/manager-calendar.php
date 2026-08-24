<?php
/**
 * Manager calendar template.
 *
 * @package Apollo\Scheduler
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var string $config wp_json_encode config */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'apollo-scheduler apollo-scheduler-manager' ); ?>>
<main class="apollo-scheduler-canvas" data-apollo-scheduler-config="<?php echo esc_attr( (string) $config ); ?>">
	<header class="apollo-scheduler-header">
		<h1><?php esc_html_e( 'Nucleo Schedule', 'apollo-scheduler' ); ?></h1>
		<div class="apollo-scheduler-kpis">
			<div class="apollo-scheduler-kpi"><span><?php esc_html_e( 'Utilization', 'apollo-scheduler' ); ?></span><strong id="apollo-kpi-utilization">—</strong></div>
			<div class="apollo-scheduler-kpi"><span><?php esc_html_e( 'Today', 'apollo-scheduler' ); ?></span><strong id="apollo-kpi-today">—</strong></div>
		</div>
	</header>
	<div id="apollo-scheduler-manager-calendar" class="apollo-scheduler-manager-calendar"></div>
	<div id="apollo-scheduler-manager-list" class="apollo-scheduler-manager-list"></div>
</main>
<?php wp_footer(); ?>
</body>
</html>
