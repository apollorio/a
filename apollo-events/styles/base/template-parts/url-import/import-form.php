<?php
/**
 * URL Import — URL input + log console + paste fallback.
 *
 * @package Apollo\Event
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="card ins imp-card">
	<div class="imp-card-head">
		<span class="imp-card-title"><span class="tag">1</span> <?php esc_html_e( 'URL do evento', 'apollo-events' ); ?></span>
	</div>
	<div class="imp-import-row">
		<input class="apollo-input" type="text" id="urlInput" placeholder="https://www.blueticket.com.br/evento/41379?c=apollo  ou  https://shotgun.live/pt-br/events/...">
		<button type="button" class="btn btn-accent" id="btnImport"><i class="ri-radar-line"></i> <?php esc_html_e( 'Detectar & Importar', 'apollo-events' ); ?></button>
	</div>

	<div class="imp-console ins" id="logConsole"></div>

	<div class="imp-paste" id="pasteBox">
		<p class="imp-paste-note">
			<?php esc_html_e( 'Não foi possível buscar a página automaticamente. Abra a URL em outra aba, use "Exibir código-fonte" (Ctrl+U), copie tudo e cole abaixo.', 'apollo-events' ); ?>
		</p>
		<div class="field">
			<label class="field-label" for="pasteHtml"><?php esc_html_e( 'HTML colado', 'apollo-events' ); ?></label>
			<textarea class="apollo-input" id="pasteHtml" rows="6" placeholder="<!DOCTYPE html>..."></textarea>
		</div>
		<div class="imp-actions">
			<button type="button" class="btn btn-primary btn-sm" id="btnParsePasted"><i class="ri-code-s-slash-line"></i> <?php esc_html_e( 'Parsear HTML colado', 'apollo-events' ); ?></button>
			<button type="button" class="btn btn-ghost btn-sm" id="btnCancelPaste"><?php esc_html_e( 'Cancelar', 'apollo-events' ); ?></button>
		</div>
	</div>
</div>
