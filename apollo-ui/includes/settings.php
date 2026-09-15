<?php
/**
 * Apollo UI — wp-admin settings.
 *
 * One screen: Appearance → Apollo UI. Picks the card style per post type and
 * says which post types open in the full-viewport lightbox.
 *
 * Everything is capability-checked (`manage_options`) and nonce-checked. The
 * ecosystem has 112 REST endpoints with `__return_true`; this screen is not
 * going to add to that pattern.
 *
 * @package Apollo\UI
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Register the screen. */
function apollo_ui_admin_menu(): void {
	add_theme_page(
		__( 'Apollo UI', 'apollo-ui' ),
		__( 'Apollo UI', 'apollo-ui' ),
		'manage_options',
		'apollo-ui',
		'apollo_ui_admin_page'
	);
}
add_action( 'admin_menu', 'apollo_ui_admin_menu' );

/** Handle the POST. */
function apollo_ui_admin_save(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Sem permissão.', 'apollo-ui' ) );
	}
	check_admin_referer( 'apollo_ui_save' );

	$d   = apollo_ui_defaults();
	$new = array(
		'enabled'       => isset( $_POST['enabled'] ),
		'lightbox'      => isset( $_POST['lightbox'] ),
		'default_type'  => $d['default_type'],
		'types'         => array(),
		'lightbox_cpts' => array(),
	);

	$dt = isset( $_POST['default_type'] ) ? sanitize_key( wp_unslash( (string) $_POST['default_type'] ) ) : '';
	if ( apollo_ui_is_type( $dt ) ) {
		$new['default_type'] = $dt;
	}

	$posted = isset( $_POST['types'] ) && is_array( $_POST['types'] ) ? wp_unslash( $_POST['types'] ) : array();
	foreach ( $posted as $cpt => $type ) {
		$cpt  = sanitize_key( (string) $cpt );
		$type = sanitize_key( (string) $type );
		if ( $cpt && apollo_ui_is_type( $type ) ) {
			$new['types'][ $cpt ] = $type;
		}
	}

	$lbc = isset( $_POST['lightbox_cpts'] ) && is_array( $_POST['lightbox_cpts'] ) ? wp_unslash( $_POST['lightbox_cpts'] ) : array();
	foreach ( $lbc as $cpt ) {
		$cpt = sanitize_key( (string) $cpt );
		if ( $cpt ) {
			$new['lightbox_cpts'][] = $cpt;
		}
	}

	update_option( APOLLO_UI_OPTION, $new );
	apollo_ui_flush_settings();

	wp_safe_redirect( add_query_arg( array( 'page' => 'apollo-ui', 'updated' => '1' ), admin_url( 'themes.php' ) ) );
	exit;
}
add_action( 'admin_post_apollo_ui_save', 'apollo_ui_admin_save' );

/** The screen. */
function apollo_ui_admin_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$s     = apollo_ui_settings();
	$types = apollo_ui_types();
	$cpts  = apollo_ui_post_types();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Apollo UI', 'apollo-ui' ); ?></h1>

		<?php if ( isset( $_GET['updated'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Salvo.', 'apollo-ui' ); ?></p></div>
		<?php endif; ?>

		<p class="description" style="max-width:70ch">
			<?php esc_html_e( 'Um estilo de card é uma aparência, não um tipo de conteúdo. type-1 significa "poster" para sempre — surfaces que fixam type-1 em shortcode não podem mudar de forma numa atualização. Para um visual novo, adicione type-7; nunca renumere.', 'apollo-ui' ); ?>
		</p>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="apollo_ui_save">
			<?php wp_nonce_field( 'apollo_ui_save' ); ?>

			<h2><?php esc_html_e( 'Geral', 'apollo-ui' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Ativar Apollo UI', 'apollo-ui' ); ?></th>
					<td><label><input type="checkbox" name="enabled" <?php checked( ! empty( $s['enabled'] ) ); ?>>
						<?php esc_html_e( 'Registrar os estilos de card e os shortcodes.', 'apollo-ui' ); ?></label></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Lightbox', 'apollo-ui' ); ?></th>
					<td><label><input type="checkbox" name="lightbox" <?php checked( ! empty( $s['lightbox'] ) ); ?>>
						<?php esc_html_e( 'Abrir o conteúdo em sobreposição de viewport inteiro ao clicar no card.', 'apollo-ui' ); ?></label></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Estilo padrão', 'apollo-ui' ); ?></th>
					<td>
						<select name="default_type">
							<?php foreach ( $types as $k => $t ) : ?>
								<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $s['default_type'], $k ); ?>>
									<?php echo esc_html( (string) $t['label'] ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php esc_html_e( 'Usado por qualquer tipo de post sem escolha explícita abaixo.', 'apollo-ui' ); ?></p>
					</td>
				</tr>
			</table>

			<h2><?php esc_html_e( 'Estilo por tipo de conteúdo', 'apollo-ui' ); ?></h2>
			<table class="widefat striped" style="max-width:900px">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Tipo de conteúdo', 'apollo-ui' ); ?></th>
						<th><?php esc_html_e( 'Estilo do card', 'apollo-ui' ); ?></th>
						<th><?php esc_html_e( 'Abre em lightbox', 'apollo-ui' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( $cpts as $slug => $label ) : ?>
					<tr>
						<td><strong><?php echo esc_html( (string) $label ); ?></strong><br><code><?php echo esc_html( (string) $slug ); ?></code></td>
						<td>
							<select name="types[<?php echo esc_attr( (string) $slug ); ?>]">
								<?php foreach ( $types as $k => $t ) : ?>
									<option value="<?php echo esc_attr( $k ); ?>" <?php selected( apollo_ui_type_for( (string) $slug ), $k ); ?>>
										<?php echo esc_html( (string) $t['label'] ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
						<td>
							<label><input type="checkbox" name="lightbox_cpts[]" value="<?php echo esc_attr( (string) $slug ); ?>"
								<?php checked( in_array( (string) $slug, (array) $s['lightbox_cpts'], true ) ); ?>>
								<?php esc_html_e( 'Sim', 'apollo-ui' ); ?></label>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>

			<h2><?php esc_html_e( 'Referência de estilos', 'apollo-ui' ); ?></h2>
			<table class="widefat striped" style="max-width:900px">
				<thead><tr>
					<th><?php esc_html_e( 'Chave', 'apollo-ui' ); ?></th>
					<th><?php esc_html_e( 'Nome', 'apollo-ui' ); ?></th>
					<th><?php esc_html_e( 'Descrição', 'apollo-ui' ); ?></th>
					<th><?php esc_html_e( 'Proporção', 'apollo-ui' ); ?></th>
				</tr></thead>
				<tbody>
				<?php foreach ( $types as $k => $t ) : ?>
					<tr>
						<td><code><?php echo esc_html( $k ); ?></code></td>
						<td><?php echo esc_html( (string) $t['label'] ); ?></td>
						<td><?php echo esc_html( (string) $t['desc'] ); ?></td>
						<td><code><?php echo esc_html( (string) $t['ratio'] ); ?></code></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>

			<h2><?php esc_html_e( 'Shortcodes', 'apollo-ui' ); ?></h2>
			<p><code>[apollo_card id="72" type="type-1"]</code> — <?php esc_html_e( 'um card.', 'apollo-ui' ); ?></p>
			<p><code>[apollo_cards cpt="event" count="8" type="type-2" columns="4"]</code> — <?php esc_html_e( 'uma grade.', 'apollo-ui' ); ?></p>
			<p class="description">
				<?php esc_html_e( 'Sem o atributo type, o estilo escolhido acima para aquele tipo de conteúdo é usado.', 'apollo-ui' ); ?>
			</p>

			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
