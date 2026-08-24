<?php
/**
 * URL Import — page header.
 *
 * @package Apollo\Event
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<header class="imp-header">
	<p class="label-section"><i class="ri-calendar-event-line"></i> apollo-events · importador</p>
	<h1 class="display-text">
		<?php echo esc_html( $import_config['i18n']['pageTitle'] ?? __( 'Importador de Eventos', 'apollo-events' ) ); ?>
		<span>/ <?php echo esc_html( $import_config['i18n']['pageSubtitle'] ?? __( 'Shotgun + BlueTicket', 'apollo-events' ) ); ?></span>
	</h1>
	<p class="txt-secondary muted imp-sub">
		<?php esc_html_e( 'Cola a URL do evento. O importador detecta a plataforma, busca o HTML, extrai os campos mapeados para o CPT', 'apollo-events' ); ?>
		<code>event</code>
		<?php esc_html_e( 'e só libera o envio quando todas as luzes ficarem verdes.', 'apollo-events' ); ?>
	</p>
</header>
