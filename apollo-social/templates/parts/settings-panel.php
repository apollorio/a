<?php

/**
 * Template Part: Settings Panel
 *
 * Shown when user clicks the "settings" tab. Contains profile visibility,
 * notification preferences and feed customisation toggles.
 * Uses .s-* class namespace with icon-based toggles per approved design.
 *
 * @package Apollo\Social
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;

$current_user = wp_get_current_user();
?>
<div class="settings-panel content-panel" id="tab-settings" role="tabpanel" aria-labelledby="tab-settings">

	<!-- ─── Perfil & Visibilidade ─── -->
	<div class="s-group gsap-el">
		<div class="s-label">
			<i class="ri-user-settings-line"></i> Perfil &amp; Visibilidade
		</div>

		<div class="s-row" data-setting="profile_public">
			<span>Perfil público</span>
			<i class="ri-toggle-fill s-toggle active" onclick="toggleSetting(this)"></i>
		</div>

		<div class="s-row" data-setting="show_online">
			<span>Mostrar status online</span>
			<i class="ri-toggle-fill s-toggle active" onclick="toggleSetting(this)"></i>
		</div>

		<div class="s-row" data-setting="show_ranking">
			<span>Mostrar no ranking</span>
			<i class="ri-toggle-fill s-toggle active" onclick="toggleSetting(this)"></i>
		</div>
	</div>

	<!-- ─── Notificações ─── -->
	<div class="s-group gsap-el">
		<div class="s-label">
			<i class="ri-notification-3-line"></i> Notificações
		</div>

		<div class="s-row" data-setting="notif_mentions">
			<span>Menções</span>
			<i class="ri-toggle-fill s-toggle active" onclick="toggleSetting(this)"></i>
		</div>

		<div class="s-row" data-setting="notif_depoimentos">
			<span>Depoimentos</span>
			<i class="ri-toggle-fill s-toggle active" onclick="toggleSetting(this)"></i>
		</div>

		<div class="s-row" data-setting="notif_email_digest">
			<span>E-mails de resumo</span>
			<i class="ri-toggle-line s-toggle" onclick="toggleSetting(this)"></i>
		</div>
	</div>

	<!-- ─── Feed & Conteúdo ─── -->
	<div class="s-group gsap-el">
		<div class="s-label">
			<i class="ri-filter-3-line"></i> Feed &amp; Conteúdo
		</div>

		<div class="s-row" data-setting="autoplay_media">
			<span>Autoplay de mídia</span>
			<i class="ri-toggle-line s-toggle" onclick="toggleSetting(this)"></i>
		</div>

		<div class="s-row" data-setting="compact_mode">
			<span>Modo compacto</span>
			<i class="ri-toggle-line s-toggle" onclick="toggleSetting(this)"></i>
		</div>

		<div class="s-row" data-setting="nsfw_blur">
			<span>NSFW oculto</span>
			<i class="ri-toggle-fill s-toggle active" onclick="toggleSetting(this)"></i>
		</div>
	</div>

	<!-- ─── Save Button ─── -->
	<div class="s-footer gsap-el">
		<button type="button" class="btn-save-settings" id="btn-save-settings" data-tooltip="Salvar preferências">
			<i class="ri-save-line"></i>
		</button>
	</div>

</div>
