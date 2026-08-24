<?php
/**
 * Agent self-availability template.
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
<body <?php body_class( 'apollo-scheduler apollo-scheduler-agent' ); ?>>
<main class="apollo-scheduler-canvas" data-apollo-scheduler-config="<?php echo esc_attr( (string) $config ); ?>">
	<header class="apollo-scheduler-header">
		<h1><?php esc_html_e( 'My Availability', 'apollo-scheduler' ); ?></h1>
	</header>
	<div id="apollo-scheduler-agent-plan" class="apollo-scheduler-agent-plan"></div>
</main>
<?php wp_footer(); ?>
</body>
</html>
