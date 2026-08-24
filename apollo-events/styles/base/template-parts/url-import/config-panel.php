<?php
/**
 * URL Import — REST connection config panel.
 *
 * @package Apollo\Event
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$default_coupon = (string) ( $import_config['defaultCoupon'] ?? 'apollo' );
?>
<details class="card ins imp-card" id="configPanel">
	<summary>
		<span class="imp-card-title"><span class="tag"><i class="ri-settings-3-line"></i></span> <?php esc_html_e( 'Configuração de conexão', 'apollo-events' ); ?></span>
		<i class="ri-arrow-right-s-line imp-chev" aria-hidden="true"></i>
	</summary>
	<div class="imp-config-body">
		<p class="imp-hint">
			<?php esc_html_e( 'BlueTicket importa via API server-side', 'apollo-events' ); ?>
			<code>apollo/v1/eventos/importar-url</code>
			<?php esc_html_e( '(capa → featured image, venue → _event_loc_id). Shotgun ainda usa scrape HTML +', 'apollo-events' ); ?>
			<code>POST /eventos</code>.
		</p>
		<div class="imp-field-grid">
			<div class="field full">
				<label class="field-label" for="cfgFetchProxy"><?php esc_html_e( 'Endpoint de fetch HTML (só Shotgun / fallback)', 'apollo-events' ); ?></label>
				<input class="apollo-input" type="text" id="cfgFetchProxy" placeholder="<?php echo esc_attr( rest_url( 'apollo/v1/fetch-html?url={url}' ) ); ?>">
			</div>
			<div class="field full">
				<label class="field-label" for="cfgWpBase"><?php esc_html_e( 'Base da REST API do WP', 'apollo-events' ); ?></label>
				<input class="apollo-input" type="text" id="cfgWpBase" placeholder="<?php echo esc_attr( rest_url() ); ?>" value="<?php echo esc_attr( $import_config['restUrl'] ?? '' ); ?>">
			</div>
			<div class="field">
				<label class="field-label" for="cfgImportPath"><?php esc_html_e( 'Endpoint importar-url (BlueTicket)', 'apollo-events' ); ?></label>
				<input class="apollo-input" type="text" id="cfgImportPath" placeholder="apollo/v1/eventos/importar-url" value="<?php echo esc_attr( $import_config['importPath'] ?? 'apollo/v1/eventos/importar-url' ); ?>">
			</div>
			<div class="field">
				<label class="field-label" for="cfgInsertPath"><?php esc_html_e( 'Endpoint inserção (Shotgun / avançado)', 'apollo-events' ); ?></label>
				<input class="apollo-input" type="text" id="cfgInsertPath" placeholder="apollo/v1/eventos" value="<?php echo esc_attr( $import_config['insertPath'] ?? 'apollo/v1/eventos' ); ?>">
			</div>
			<div class="field">
				<label class="field-label" for="cfgLocPath"><?php esc_html_e( 'Endpoint de busca de loc (Shotgun)', 'apollo-events' ); ?></label>
				<input class="apollo-input" type="text" id="cfgLocPath" placeholder="apollo/v1/local" value="<?php echo esc_attr( $import_config['locPath'] ?? 'apollo/v1/local' ); ?>">
			</div>
			<div class="field full">
				<label class="field-label" for="cfgAuth"><?php esc_html_e( 'Header Authorization (Application Password)', 'apollo-events' ); ?></label>
				<input class="apollo-input" type="password" id="cfgAuth" placeholder="Basic dXNlcjpzZW5oYS1kZS1hcGxpY2F0aXZv" autocomplete="off">
			</div>
			<div class="field">
				<label class="field-label" for="cfgStatus"><?php esc_html_e( 'Status do post', 'apollo-events' ); ?></label>
				<select class="apollo-select" id="cfgStatus">
					<option value="draft" <?php selected( ( $import_config['defaultStatus'] ?? 'draft' ), 'draft' ); ?>>draft</option>
					<option value="publish" <?php selected( ( $import_config['defaultStatus'] ?? 'draft' ), 'publish' ); ?>>publish</option>
				</select>
			</div>
			<div class="field">
				<label class="field-label" for="cfgCoupon"><?php esc_html_e( 'Cupom padrão', 'apollo-events' ); ?></label>
				<input class="apollo-input" type="text" id="cfgCoupon" value="<?php echo esc_attr( $default_coupon ); ?>">
			</div>
		</div>
	</div>
</details>
