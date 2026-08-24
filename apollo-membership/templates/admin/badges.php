<?php

/**
 * Admin Template: Badges Management
 *
 * Lists all badge types, assigns to users.
 * MEMBERSHIP BADGES ARE ADMIN-ONLY — no relation to gamification points.
 *
 * @package Apollo\Membership
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load CDN for RemixIcon + Apollo icons
$cdn_url = defined( 'APOLLO_CDN_URL' ) ? APOLLO_CDN_URL : 'https://cdn.apollo.rio.br/v1.0.0/';

$badge_types = APOLLO_MEMBERSHIP_BADGE_TYPES;

// Handle bulk assignment
if ( isset( $_POST['apollo_assign_badge'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'apollo_assign_badge' ) ) {
	$target_user  = (int) $_POST['target_user_id'];
	$target_badge = sanitize_text_field( $_POST['target_badge'] );

	if ( $target_user && $target_badge ) {
		$result = apollo_membership_set_user_badge( $target_user, $target_badge );
		if ( is_wp_error( $result ) ) {
			$error_msg = $result->get_error_message();
		} else {
			$success_msg = sprintf( __( 'Badge "%1$s" atribuído ao usuário #%2$d.', 'apollo-membership' ), $badge_types[ $target_badge ]['label'] ?? $target_badge, $target_user );
		}
	}
}

// Get badge counts
global $wpdb;
$badge_counts = array();
foreach ( $badge_types as $key => $type ) {
	$badge_counts[ $key ] = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key = '_apollo_membership' AND meta_value = %s",
			$key
		)
	);
}
?>

<div class="wrap apollo-badges-admin">
	<h1><?php esc_html_e( 'Apollo Membership — Badges', 'apollo-membership' ); ?></h1>

	<p class="description">
		<?php esc_html_e( 'Badges de membership são atribuídas EXCLUSIVAMENTE pelo admin. Não têm relação com pontos de gamification.', 'apollo-membership' ); ?>
	</p>

	<?php if ( isset( $error_msg ) ) : ?>
		<div class="notice notice-error is-dismissible">
			<p><?php echo esc_html( $error_msg ); ?></p>
		</div>
	<?php endif; ?>
	<?php if ( isset( $success_msg ) ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php echo esc_html( $success_msg ); ?></p>
		</div>
	<?php endif; ?>

	<h2><?php esc_html_e( 'Tipos de Badge', 'apollo-membership' ); ?></h2>

	<!-- Referência de Ícones -->
	<div class="apollo-badge-reference" style="background:#f8f9fa; border:1px solid #c3c4c7; border-radius:4px; padding:16px; margin-bottom:20px;">
		<h4 style="margin:0 0 10px;"><?php esc_html_e( 'Referência de Ícones', 'apollo-membership' ); ?></h4>
		<table style="width:auto; border-collapse:collapse;">
			<tr>
				<td style="padding:4px 12px;"><i class="i-apollo-fill icon-apollo-s" title="Apollo team" data-apollo-icon="apollo-s" style="--apollo-mask: url('https://assets.apollo.rio.br/i/apollo-s.svg') !important; font-size:18px; color:#ffd700;"></i></td>
				<td style="padding:4px;">Apollo Team</td>
			</tr>
			<tr>
				<td style="padding:4px 12px;"><i class="ri-shield-check-fill" style="font-size:18px; color:#4caf50;"></i></td>
				<td style="padding:4px;">Usuário Verificado</td>
			</tr>
			<tr>
				<td style="padding:4px 12px;"><i class="ri-disc-line" style="font-size:18px; color:#00e5ff;"></i></td>
				<td style="padding:4px;">Cena::Rio — Indústria</td>
			</tr>
			<tr>
				<td style="padding:4px 12px;"><i class="ri-alert-fill" style="font-size:18px; color:#b71c1c;"></i></td>
				<td style="padding:4px;">Reportado — aguardando verificação admin</td>
			</tr>
		</table>
	</div>

	<div class="apollo-badges-grid">
		<?php foreach ( $badge_types as $key => $type ) : ?>
			<div class="apollo-badge-card" style="border-top: 3px solid <?php echo esc_attr( $type['color'] ); ?>">
				<div class="badge-icon" style="font-size:28px; color:<?php echo esc_attr( $type['color'] ); ?>; margin-bottom:8px;">
					<?php
					echo $type['html_icon']; // Already contains safe HTML
					?>
				</div>
				<span class="badge-label"><?php echo esc_html( $type['label'] ); ?></span>
				<span class="badge-count"><?php echo esc_html( $badge_counts[ $key ] ?? 0 ); ?> <?php esc_html_e( 'membros', 'apollo-membership' ); ?></span>
			</div>
		<?php endforeach; ?>
	</div>

	<h2><?php esc_html_e( 'Atribuir Badge', 'apollo-membership' ); ?></h2>
	<div class="settings-section" style="background:#fff; padding:20px; border:1px solid #c3c4c7; border-radius:4px; max-width:500px;">
		<form method="post" action="">
			<?php wp_nonce_field( 'apollo_assign_badge' ); ?>

			<table class="form-table">
				<tr>
					<th><label for="target_user_id"><?php esc_html_e( 'ID do Usuário', 'apollo-membership' ); ?></label></th>
					<td><input type="number" name="target_user_id" id="target_user_id" min="1" required class="apollo-input regular-text" /></td>
				</tr>
				<tr>
					<th><label for="target_badge"><?php esc_html_e( 'Badge', 'apollo-membership' ); ?></label></th>
					<td>
						<select name="target_badge" id="target_badge" required>
							<?php foreach ( $badge_types as $key => $type ) : ?>
								<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $type['label'] ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
			</table>

			<p class="submit">
				<input type="submit" name="apollo_assign_badge" class="button-primary" value="<?php esc_attr_e( 'Atribuir Badge', 'apollo-membership' ); ?>" />
			</p>
		</form>
	</div>

	<h2><?php esc_html_e( 'Membros Recentes com Badge', 'apollo-membership' ); ?></h2>
	<?php
	$recent = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT user_id, meta_value FROM {$wpdb->usermeta}
			 WHERE meta_key = %s AND meta_value != %s
			 ORDER BY umeta_id DESC LIMIT 20",
			'_apollo_membership',
			'nao-verificado'
		)
	);
	?>
	<?php if ( $recent ) : ?>
		<table class="widefat striped" style="max-width:600px;">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Usuário', 'apollo-membership' ); ?></th>
					<th><?php esc_html_e( 'Badge', 'apollo-membership' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $recent as $row ) : ?>
					<?php $user = get_user_by( 'ID', $row->user_id ); ?>
					<?php if ( $user ) : ?>
						<tr>
							<td>
								<?php echo get_avatar( $user->ID, 24 ); ?>
								<?php echo esc_html( $user->display_name ); ?>
								<small>(#<?php echo esc_html( $user->ID ); ?>)</small>
							</td>
							<td><?php echo apollo_membership_render_badge( $row->meta_value ); ?></td>
						</tr>
					<?php endif; ?>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php else : ?>
		<p><?php esc_html_e( 'Nenhuma badge atribuída ainda.', 'apollo-membership' ); ?></p>
	<?php endif; ?>

	<!-- ═══════════════════════════════════════════════════════════ -->
	<!-- REPORT BUTTON + MODAL (shared component)                   -->
	<!-- ═══════════════════════════════════════════════════════════ -->
	<hr style="margin:30px 0;">
	<span id="apolloTrigger" class="button button-secondary" style="cursor:pointer;" data-apollo-report-trigger>
		<i class="ri-mail-send-line"></i> <?php esc_html_e( 'Abrir Chamado de Suporte', 'apollo-membership' ); ?>
	</span>

	<?php
	// Modal now rendered globally via admin_footer hook in apollo-core
	?>
</div>