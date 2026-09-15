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
			<?php esc_html_e( 'O navegador não consegue buscar HTML de shotgun.live / blueticket.com.br diretamente (CORS). Duas saídas: (1) endpoint server-side de fetch HTML no painel Avançado, ou (2) colar o HTML manualmente — Ctrl+U na página do evento, copie tudo e cole quando pedido.', 'apollo-events' ); ?>
		</p>
		<div class="imp-field-grid">
			<div class="field full">
				<label class="field-label" for="cfgWpBase"><?php esc_html_e( 'Base da REST API do WP', 'apollo-events' ); ?></label>
				<input class="apollo-input" type="text" id="cfgWpBase" placeholder="<?php echo esc_attr( rest_url() ); ?>" value="<?php echo esc_attr( $import_config['restUrl'] ?? '' ); ?>">
			</div>
			<div class="field">
				<label class="field-label" for="cfgImportPath"><?php esc_html_e( 'Endpoint importar-url', 'apollo-events' ); ?></label>
				<input class="apollo-input" type="text" id="cfgImportPath" placeholder="apollo/v1/eventos/importar-url" value="<?php echo esc_attr( $import_config['importPath'] ?? 'apollo/v1/eventos/importar-url' ); ?>">
			</div>
			<div class="field">
				<label class="field-label" for="cfgLocPath"><?php esc_html_e( 'Endpoint de busca de loc', 'apollo-events' ); ?></label>
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
		<details class="imp-advanced">
			<summary><?php esc_html_e( 'Avançado (fallback HTML colado)', 'apollo-events' ); ?></summary>
			<div class="imp-field-grid">
				<div class="field full">
					<label class="field-label" for="cfgFetchProxy"><?php esc_html_e( 'Endpoint fetch HTML (emergência)', 'apollo-events' ); ?></label>
					<input class="apollo-input" type="text" id="cfgFetchProxy" placeholder="<?php echo esc_attr( rest_url( 'apollo/v1/fetch-html?url={url}' ) ); ?>">
				</div>
				<div class="field full">
					<label class="field-label" for="cfgInsertPath"><?php esc_html_e( 'Endpoint inserção legado POST /eventos', 'apollo-events' ); ?></label>
					<input class="apollo-input" type="text" id="cfgInsertPath" placeholder="apollo/v1/eventos" value="<?php echo esc_attr( $import_config['insertPath'] ?? 'apollo/v1/eventos' ); ?>">
				</div>
			</div>
		</details>
	</div>
</details>
