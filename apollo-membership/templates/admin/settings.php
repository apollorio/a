<?php

/**
 * Admin Template: Settings Page
 *
 * @package Apollo\Membership
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings        = apollo_membership_get_settings();
$trigger_points  = (array) get_option( 'apollo_membership_trigger_points', array() );
$award_triggers  = apollo_get_point_award_triggers();
$deduct_triggers = apollo_get_point_deduct_triggers();
$saved           = isset( $_GET['saved'] ) && $_GET['saved'] === '1';

// Note: Form processing is handled in Plugin::handle_settings_save() before output starts.
?>

<div class="wrap apollo-membership-settings">
	<h1><?php esc_html_e( 'Apollo Membership — Configurações', 'apollo-membership' ); ?></h1>

	<?php if ( $saved ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Configurações salvas com sucesso.', 'apollo-membership' ); ?></p>
		</div>
	<?php endif; ?>

	<form method="post" action="">
		<?php wp_nonce_field( 'apollo_membership_settings' ); ?>

		<div class="settings-section">
			<h2><?php esc_html_e( 'Configurações Gerais', 'apollo-membership' ); ?></h2>
			<table class="form-table">
				<tr>
					<th><label for="manager_capability"><?php esc_html_e( 'Capability para gerenciar', 'apollo-membership' ); ?></label></th>
					<td>
						<select name="manager_capability" id="manager_capability">
							<option value="manage_options" <?php selected( $settings['manager_capability'] ?? '', 'manage_options' ); ?>>manage_options (Admin)</option>
							<option value="edit_others_posts" <?php selected( $settings['manager_capability'] ?? '', 'edit_others_posts' ); ?>>edit_others_posts (Editor)</option>
						</select>
					</td>
				</tr>
				<tr>
					<th><label for="image_width"><?php esc_html_e( 'Tamanho das imagens', 'apollo-membership' ); ?></label></th>
					<td>
						<input type="number" name="image_width" id="image_width" value="<?php echo esc_attr( $settings['image_width'] ?? 50 ); ?>" class="apollo-input" min="16" max="256" style="width:70px" /> x
						<input type="number" name="image_height" id="image_height" value="<?php echo esc_attr( $settings['image_height'] ?? 50 ); ?>" class="apollo-input" min="16" max="256" style="width:70px" /> px
					</td>
				</tr>
				<tr>
					<th><label for="debug_mode"><?php esc_html_e( 'Modo debug', 'apollo-membership' ); ?></label></th>
					<td>
						<label class="custom-checkbox">
							<input type="checkbox" name="debug_mode" id="debug_mode" <?php checked( $settings['debug_mode'] ?? 'off', 'on' ); ?> />
							<?php esc_html_e( 'Ativar log detalhado', 'apollo-membership' ); ?>
						</label>
					</td>
				</tr>
			</table>
		</div>

		<div class="settings-section">
			<h2><?php esc_html_e( 'Pontos por Trigger (Gamification)', 'apollo-membership' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Configure quantos pontos cada ação vale automaticamente. Defina 0 para desativar.', 'apollo-membership' ); ?></p>

			<h3><?php esc_html_e( 'Triggers de Premiação', 'apollo-membership' ); ?></h3>
			<table class="apollo-trigger-points-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Ação', 'apollo-membership' ); ?></th>
						<th><?php esc_html_e( 'Pontos', 'apollo-membership' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $award_triggers as $trigger => $label ) : ?>
						<tr>
							<td><?php echo esc_html( $label ); ?></td>
							<td>
								<input type="number" name="trigger_points[<?php echo esc_attr( $trigger ); ?>class="apollo-input">]"
									value="<?php echo esc_attr( $trigger_points[ $trigger ] ?? 0 ); ?>"
									min="0" max="1000" />
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<h3><?php esc_html_e( 'Triggers de Dedução', 'apollo-membership' ); ?></h3>
			<table class="apollo-trigger-points-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Ação', 'apollo-membership' ); ?></th>
						<th><?php esc_html_e( 'Pontos', 'apollo-membership' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $deduct_triggers as $trigger => $label ) : ?>
						<tr>
							<td><?php echo esc_html( $label ); ?></td>
							<td>
								<input type="number" name="trigger_points[<?php echo esc_attr( $trigger ); ?>class="apollo-input">]"
									value="<?php echo esc_attr( abs( (int) ( $trigger_points[ $trigger ] ?? 0 ) ) ); ?>"
									min="0" max="1000" />
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<p class="submit">
			<input type="submit" name="apollo_membership_save_settings" class="button-primary" value="<?php esc_attr_e( 'Salvar Configurações', 'apollo-membership' ); ?>" />
		</p>
	</form>
</div>