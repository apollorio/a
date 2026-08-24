<?php

/**
 * Dashboard Panel — Settings (Configurações V2)
 *
 * Profile header + s-group form fields + toggle-sw preferences + save.
 *
 * Variables expected: $user_id, $display_name, $username, $avatar_url, $current_user
 *
 * @package Apollo\Dashboard
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ── Load user settings ──
$user_email    = $current_user->user_email ?? '';
$user_bio      = get_user_meta( $user_id, 'description', true );
$user_location = get_user_meta( $user_id, '_apollo_location', true );
$user_genre    = get_user_meta( $user_id, '_apollo_genre', true );

// ── Preferences (toggles) ──
$pref_dark    = get_user_meta( $user_id, '_apollo_pref_dark', true ) ?: 'on';
$pref_notif   = get_user_meta( $user_id, '_apollo_pref_notifications', true ) ?: 'on';
$pref_sound   = get_user_meta( $user_id, '_apollo_pref_sound', true ) ?: 'off';
$pref_email_n = get_user_meta( $user_id, '_apollo_pref_email_notif', true ) ?: 'on';
$pref_visible = get_user_meta( $user_id, '_apollo_pref_visible', true ) ?: 'on';
?>

<div class="tab-panel" id="panel-settings">
	<div class="panel-inner">

		<h1 class="d-title">Configurações</h1>

		<!-- ── Profile Header ── -->
		<div style="display:flex;align-items:center;gap:16px;margin-bottom:32px;">
			<div style="width:72px;height:72px;border-radius:50%;overflow:hidden;flex-shrink:0;border:3px solid var(--primary);">
				<img src="<?php echo esc_url( $avatar_url ); ?>" alt="<?php echo esc_attr( $display_name ); ?>" style="width:100%;height:100%;object-fit:cover;">
			</div>
			<div>
				<div style="font-size:18px;font-weight:700;color:var(--txt-color-hover);"><?php echo esc_html( $display_name ); ?></div>
				<div style="font-size:13px;color:var(--muted);font-family:var(--ff-mono);">@<?php echo esc_html( $username ); ?></div>
			</div>
		</div>

		<!-- ── Profile Settings ── -->
		<h2 class="d-subtitle">Perfil</h2>

		<div class="s-group">
			<label class="s-label" for="settingsName">Nome de exibição</label>
			<input type="text" class="apollo-input s-input" id="settingsName" value="<?php echo esc_attr( $display_name ); ?>" placeholder="Seu nome">
		</div>

		<div class="s-group">
			<label class="s-label" for="settingsEmail">E-mail</label>
			<input type="email" class="apollo-input s-input" id="settingsEmail" value="<?php echo esc_attr( $user_email ); ?>" placeholder="seu@email.com">
		</div>

		<div class="s-group">
			<label class="s-label" for="settingsLocation">Localização</label>
			<input type="text" class="apollo-input s-input" id="settingsLocation" value="<?php echo esc_attr( $user_location ); ?>" placeholder="Ex: Copacabana, Rio de Janeiro">
		</div>

		<div class="s-group">
			<label class="s-label" for="settingsGenre">Gênero musical favorito</label>
			<input type="text" class="apollo-input s-input" id="settingsGenre" value="<?php echo esc_attr( $user_genre ); ?>" placeholder="Ex: Techno, House, D&B">
		</div>

		<div class="s-group">
			<label class="s-label" for="settingsBio">Bio</label>
			<textarea class="apollo-textarea s-textarea" id="settingsBio" rows="3" placeholder="Conte algo sobre você..."><?php echo esc_textarea( $user_bio ); ?></textarea>
		</div>

		<!-- ── Preferences ── -->
		<h2 class="d-subtitle" style="margin-top:32px;">Preferências</h2>

		<div class="s-group toggle-row">
			<span class="s-label">Modo escuro</span>
			<div class="toggle-sw<?php echo $pref_dark === 'on' ? ' on' : ''; ?>" id="prefDark" onclick="this.classList.toggle('on')"></div>
		</div>

		<div class="s-group toggle-row">
			<span class="s-label">Notificações push</span>
			<div class="toggle-sw<?php echo $pref_notif === 'on' ? ' on' : ''; ?>" id="prefNotif" onclick="this.classList.toggle('on')"></div>
		</div>

		<div class="s-group toggle-row">
			<span class="s-label">Sons</span>
			<div class="toggle-sw<?php echo $pref_sound === 'on' ? ' on' : ''; ?>" id="prefSound" onclick="this.classList.toggle('on')"></div>
		</div>

		<div class="s-group toggle-row">
			<span class="s-label">Notificações por e-mail</span>
			<div class="toggle-sw<?php echo $pref_email_n === 'on' ? ' on' : ''; ?>" id="prefEmailNotif" onclick="this.classList.toggle('on')"></div>
		</div>

		<div class="s-group toggle-row">
			<span class="s-label">Perfil visível</span>
			<div class="toggle-sw<?php echo $pref_visible === 'on' ? ' on' : ''; ?>" id="prefVisible" onclick="this.classList.toggle('on')"></div>
		</div>

		<!-- ── Save Button ── -->
		<button class="btn-save" id="btnSaveSettings" onclick="saveSettings()">Salvar alterações</button>

	</div>
</div>