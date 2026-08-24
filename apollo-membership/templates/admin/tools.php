<?php
/**
 * Admin Template: Tools
 *
 * Provides admin tools for recalculating points, achievements, ranks,
 * and other maintenance tasks.
 *
 * @package Apollo\Membership
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap apollo-tools-admin">
	<h1><?php esc_html_e( 'Apollo Membership — Ferramentas', 'apollo-membership' ); ?></h1>

	<p class="description">
		<?php esc_html_e( 'Ferramentas de manutenção para o sistema de gamification e membership.', 'apollo-membership' ); ?>
	</p>

	<div class="apollo-tools-grid">

		<!-- Recalculate Points -->
		<div class="apollo-tool-card">
			<h3>
				<span class="dashicons dashicons-chart-bar" style="color:#2271b1;"></span>
				<?php esc_html_e( 'Recalcular Pontos', 'apollo-membership' ); ?>
			</h3>
			<p><?php esc_html_e( 'Recalcula os pontos totais de todos os usuários a partir do histórico de transações.', 'apollo-membership' ); ?></p>
			<button type="button" id="apollo-recalc-points" class="button button-primary" data-nonce="<?php echo esc_attr( wp_create_nonce( 'apollo_membership_admin' ) ); ?>">
				<?php esc_html_e( 'Recalcular Pontos', 'apollo-membership' ); ?>
			</button>
			<span class="spinner" style="float:none;"></span>
			<div class="tool-result" style="margin-top:10px;"></div>
		</div>

		<!-- Recalculate Achievements -->
		<div class="apollo-tool-card">
			<h3>
				<span class="dashicons dashicons-awards" style="color:#dba617;"></span>
				<?php esc_html_e( 'Recalcular Achievements', 'apollo-membership' ); ?>
			</h3>
			<p><?php esc_html_e( 'Verifica e recalcula as conquistas de todos os usuários baseado nos triggers e regras atuais.', 'apollo-membership' ); ?></p>
			<button type="button" id="apollo-recalc-achievements" class="button button-primary" data-nonce="<?php echo esc_attr( wp_create_nonce( 'apollo_membership_admin' ) ); ?>">
				<?php esc_html_e( 'Recalcular Achievements', 'apollo-membership' ); ?>
			</button>
			<span class="spinner" style="float:none;"></span>
			<div class="tool-result" style="margin-top:10px;"></div>
		</div>

		<!-- Recalculate Ranks -->
		<div class="apollo-tool-card">
			<h3>
				<span class="dashicons dashicons-star-filled" style="color:#d63638;"></span>
				<?php esc_html_e( 'Recalcular Ranks', 'apollo-membership' ); ?>
			</h3>
			<p><?php esc_html_e( 'Recalcula a posição de rank de todos os usuários baseado nos pontos atuais.', 'apollo-membership' ); ?></p>
			<button type="button" id="apollo-recalc-ranks" class="button button-primary" data-nonce="<?php echo esc_attr( wp_create_nonce( 'apollo_membership_admin' ) ); ?>">
				<?php esc_html_e( 'Recalcular Ranks', 'apollo-membership' ); ?>
			</button>
			<span class="spinner" style="float:none;"></span>
			<div class="tool-result" style="margin-top:10px;"></div>
		</div>

		<!-- Reset Trigger Counts -->
		<div class="apollo-tool-card">
			<h3>
				<span class="dashicons dashicons-update" style="color:#00a32a;"></span>
				<?php esc_html_e( 'Resetar Contadores de Triggers', 'apollo-membership' ); ?>
			</h3>
			<p><?php esc_html_e( 'Zera os contadores de triggers de todos os usuários. Use com cuidado.', 'apollo-membership' ); ?></p>
			<button type="button" id="apollo-reset-triggers" class="button button-secondary" data-nonce="<?php echo esc_attr( wp_create_nonce( 'apollo_membership_admin' ) ); ?>">
				<?php esc_html_e( 'Resetar Triggers', 'apollo-membership' ); ?>
			</button>
			<span class="spinner" style="float:none;"></span>
			<div class="tool-result" style="margin-top:10px;"></div>
		</div>

		<!-- Seed Default Trigger Points -->
		<div class="apollo-tool-card">
			<h3>
				<span class="dashicons dashicons-database-add" style="color:#8c5e58;"></span>
				<?php esc_html_e( 'Re-seeder Pontos Default', 'apollo-membership' ); ?>
			</h3>
			<p><?php esc_html_e( 'Recarrega os valores padrão de pontos por trigger (não sobrescreve personalizados).', 'apollo-membership' ); ?></p>
			<button type="button" id="apollo-seed-defaults" class="button button-secondary" data-nonce="<?php echo esc_attr( wp_create_nonce( 'apollo_membership_admin' ) ); ?>">
				<?php esc_html_e( 'Re-seeder', 'apollo-membership' ); ?>
			</button>
			<span class="spinner" style="float:none;"></span>
			<div class="tool-result" style="margin-top:10px;"></div>
		</div>

	</div>

	<!-- System Info -->
	<h2 style="margin-top:30px;"><?php esc_html_e( 'Informações do Sistema', 'apollo-membership' ); ?></h2>
	<div class="settings-section" style="background:#fff; padding:20px; border:1px solid #c3c4c7; border-radius:4px; max-width:700px;">
		<?php
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Admin stats display, no user input.
		$total_users      = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->users}" );
		$users_with_badge = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT user_id) FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value != %s",
				'_apollo_membership',
				'nao-verificado'
			)
		);

		$ach_table      = APOLLO_TABLE_ACHIEVEMENTS;
		$points_table   = APOLLO_TABLE_POINTS;
		$ranks_table    = APOLLO_TABLE_RANKS;
		$triggers_table = APOLLO_TABLE_TRIGGERS;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Admin stats, table names from trusted constants.
		$total_achievements = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$ach_table}" );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$total_transactions = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$points_table}" );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$total_ranks        = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$ranks_table}" );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$total_triggers     = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$triggers_table}" );
		?>
		<table class="widefat" style="border:none; box-shadow:none;">
			<tbody>
				<tr>
					<th style="width:200px;"><?php esc_html_e( 'Total de Usuários', 'apollo-membership' ); ?></th>
					<td><?php echo esc_html( number_format_i18n( $total_users ) ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Usuários com Badge', 'apollo-membership' ); ?></th>
					<td><?php echo esc_html( number_format_i18n( $users_with_badge ) ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Achievements Registrados', 'apollo-membership' ); ?></th>
					<td><?php echo esc_html( number_format_i18n( $total_achievements ) ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Transações de Pontos', 'apollo-membership' ); ?></th>
					<td><?php echo esc_html( number_format_i18n( $total_transactions ) ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Ranks Atribuídos', 'apollo-membership' ); ?></th>
					<td><?php echo esc_html( number_format_i18n( $total_ranks ) ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Trigger Counts', 'apollo-membership' ); ?></th>
					<td><?php echo esc_html( number_format_i18n( $total_triggers ) ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Versão do Plugin', 'apollo-membership' ); ?></th>
					<td><?php echo esc_html( APOLLO_MEMBERSHIP_VERSION ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'DB Version', 'apollo-membership' ); ?></th>
					<td><?php echo esc_html( get_option( 'apollo_membership_db_version', 'N/A' ) ); ?></td>
				</tr>
			</tbody>
		</table>
	</div>
</div>
