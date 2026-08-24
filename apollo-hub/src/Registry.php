<?php

/**
 * Registry — CPT "hub", meta keys, metaboxes, admin columns
 *
 * CPT slug = "hub", rewrite = "hub", rest_base = "hubs", sem archive.
 * Cada usuário tem UM post hub cujo slug = seu username.
 * URL pública: /hub/{username}
 *
 * Meta keys conforme apollo-registry.json:
 * _hub_bio, _hub_links, _hub_socials, _hub_theme,
 * _hub_avatar, _hub_cover, _hub_custom_css
 *
 * @package Apollo\Hub
 */

declare(strict_types=1);

namespace Apollo\Hub;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Registry {


	public function __construct() {
		add_action( 'init', array( $this, 'register_cpt' ), 5 );
		add_action( 'init', array( $this, 'register_meta' ), 10 );
		add_action( 'add_meta_boxes', array( $this, 'add_metaboxes' ) );
		add_action( 'save_post_' . APOLLO_HUB_CPT, array( $this, 'save_metabox' ), 10, 2 );
		add_filter( 'manage_' . APOLLO_HUB_CPT . '_posts_columns', array( $this, 'admin_columns' ) );
		add_action( 'manage_' . APOLLO_HUB_CPT . '_posts_custom_column', array( $this, 'admin_column_content' ), 10, 2 );
	}

	/**
	 * Registra CPT "hub" — com fallback: se apollo-core já registrou, apenas conecta.
	 */
	public function register_cpt(): void {
		if ( post_type_exists( APOLLO_HUB_CPT ) ) {
			return; // apollo-core já registrou via MASTER_REGISTRY
		}

		$labels = array(
			'name'               => __( 'Hubs', 'apollo-hub' ),
			'singular_name'      => __( 'Hub', 'apollo-hub' ),
			'add_new'            => __( 'Novo Hub', 'apollo-hub' ),
			'add_new_item'       => __( 'Adicionar Novo Hub', 'apollo-hub' ),
			'edit_item'          => __( 'Editar Hub', 'apollo-hub' ),
			'new_item'           => __( 'Novo Hub', 'apollo-hub' ),
			'view_item'          => __( 'Ver Hub', 'apollo-hub' ),
			'search_items'       => __( 'Buscar Hubs', 'apollo-hub' ),
			'not_found'          => __( 'Nenhum Hub encontrado', 'apollo-hub' ),
			'not_found_in_trash' => __( 'Nenhum Hub na lixeira', 'apollo-hub' ),
		);

		register_post_type(
			APOLLO_HUB_CPT,
			array(
				'labels'              => $labels,
				'public'              => true,
				'has_archive'         => false,
				'rewrite'             => array(
					'slug'       => APOLLO_HUB_SLUG,
					'with_front' => false,
				),
				'rest_base'           => 'hubs',
				'show_in_rest'        => true,
				'supports'            => array( 'title', 'author' ),
				'menu_icon'           => 'dashicons-admin-links',
				'menu_position'       => 8,
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
				'show_in_admin_bar'   => true,
				'exclude_from_search' => true,
			)
		);
	}

	/**
	 * Registra meta keys conforme apollo-registry.json.
	 */
	public function register_meta(): void {
		$post_type = APOLLO_HUB_CPT;

		register_post_meta(
			$post_type,
			'_hub_bio',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'auth_callback'     => '__return_true',
				'sanitize_callback' => function ( $val ) {
					return substr( sanitize_textarea_field( (string) $val ), 0, APOLLO_HUB_BIO_MAX_LEN );
				},
			)
		);

		register_post_meta(
			$post_type,
			'_hub_blocks',
			array(
				'type'          => 'string', // JSON string
				'single'        => true,
				'show_in_rest'  => true,
				'auth_callback' => '__return_true',
			)
		);

		register_post_meta(
			$post_type,
			'_hub_links',
			array(
				'type'          => 'string', // JSON string
				'single'        => true,
				'show_in_rest'  => true,
				'auth_callback' => '__return_true',
			)
		);

		register_post_meta(
			$post_type,
			'_hub_socials',
			array(
				'type'          => 'string', // JSON string
				'single'        => true,
				'show_in_rest'  => true,
				'auth_callback' => '__return_true',
			)
		);

		register_post_meta(
			$post_type,
			'_hub_theme',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'auth_callback'     => '__return_true',
				'sanitize_callback' => function ( $val ) {
					$allowed = array_keys( APOLLO_HUB_THEMES );
					return in_array( $val, $allowed, true ) ? $val : 'dark';
				},
			)
		);

		register_post_meta(
			$post_type,
			'_hub_avatar',
			array(
				'type'          => 'integer',
				'single'        => true,
				'show_in_rest'  => true,
				'auth_callback' => '__return_true',
			)
		);

		register_post_meta(
			$post_type,
			'_hub_cover',
			array(
				'type'          => 'integer',
				'single'        => true,
				'show_in_rest'  => true,
				'auth_callback' => '__return_true',
			)
		);

		register_post_meta(
			$post_type,
			'_hub_custom_css',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => false, // segurança: não expor CSS via REST público
				'auth_callback'     => '__return_true',
				'sanitize_callback' => 'wp_strip_all_tags',
			)
		);
	}

	/**
	 * Metaboxes no admin para gestão visual completa.
	 */
	public function add_metaboxes(): void {
		add_meta_box(
			'apollo_hub_data',
			__( 'Dados do Hub', 'apollo-hub' ),
			array( $this, 'render_metabox' ),
			APOLLO_HUB_CPT,
			'normal',
			'high'
		);

		add_meta_box(
			'apollo_hub_blocks',
			__( 'Blocos do Hub', 'apollo-hub' ),
			array( $this, 'render_blocks_metabox' ),
			APOLLO_HUB_CPT,
			'normal',
			'default'
		);
	}

	/**
	 * Renderiza metabox.
	 *
	 * @param \WP_Post $post Post atual.
	 */
	public function render_metabox( \WP_Post $post ): void {
		wp_nonce_field( 'apollo_hub_metabox', 'apollo_hub_nonce' );
		$data = apollo_hub_get_data( $post->ID );

		echo '<table class="form-table"><tbody>';

		// Bio
		echo '<tr><th><label for="hub_bio">' . esc_html__( 'Bio (max 280 chars)', 'apollo-hub' ) . '</label></th>';
		echo '<td><textarea id="hub_bio" name="hub_bio" rows="3" style="width:100%;" maxlength="280">' . esc_textarea( $data['bio'] ) . '</textarea></td></tr>';

		// Tema
		echo '<tr><th><label for="hub_theme">' . esc_html__( 'Tema', 'apollo-hub' ) . '</label></th>';
		echo '<td><select id="hub_theme" name="hub_theme">';
		foreach ( APOLLO_HUB_THEMES as $key => $label ) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $key ),
				selected( $data['theme'], $key, false ),
				esc_html( $label )
			);
		}
		echo '</select></td></tr>';

		// Avatar attachment ID
		echo '<tr><th><label for="hub_avatar">' . esc_html__( 'Avatar (attachment ID)', 'apollo-hub' ) . '</label></th>';
		echo '<td><input type="number" id="hub_avatar" name="hub_avatar" value="' . esc_attr( (string) $data['avatar'] ) . '" /></td></tr>';

		// Cover attachment ID
		echo '<tr><th><label for="hub_cover">' . esc_html__( 'Cover (attachment ID)', 'apollo-hub' ) . '</label></th>';
		echo '<td><input type="number" id="hub_cover" name="hub_cover" value="' . esc_attr( (string) $data['cover'] ) . '" /></td></tr>';

		// Links (JSON) — legacy
		echo '<tr><th><label for="hub_links">' . esc_html__( 'Links (JSON) — Legacy', 'apollo-hub' ) . '</label></th>';
		echo '<td><textarea id="hub_links" name="hub_links" rows="4" style="width:100%;font-family:monospace;">' . esc_textarea( get_post_meta( $post->ID, '_hub_links', true ) ) . '</textarea>';
		echo '<p class="description">' . esc_html__( 'Dados legados. Use o metabox "Blocos do Hub" abaixo para gerenciar conteúdo.', 'apollo-hub' ) . '</p>';
		echo '</td></tr>';

		// Socials (JSON) — legacy
		$socials_raw     = get_post_meta( $post->ID, '_hub_socials', true );
		$socials_display = '';
		if ( $socials_raw ) {
			$decoded         = is_string( $socials_raw ) ? json_decode( $socials_raw, true ) : $socials_raw;
			$socials_display = is_array( $decoded ) ? wp_json_encode( $decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) : $socials_raw;
		}
		echo '<tr><th><label for="hub_socials">' . esc_html__( 'Redes Sociais (JSON)', 'apollo-hub' ) . '</label></th>';
		echo '<td><textarea id="hub_socials" name="hub_socials" rows="4" style="width:100%;font-family:monospace;">' . esc_textarea( $socials_display ) . '</textarea>';
		echo '<p class="description">' . esc_html__( 'Array JSON de redes sociais. Ex: [{"platform":"instagram","url":"https://..."}]', 'apollo-hub' ) . '</p>';
		echo '</td></tr>';

		// Custom CSS
		$custom_css = get_post_meta( $post->ID, '_hub_custom_css', true );
		echo '<tr><th><label for="hub_custom_css">' . esc_html__( 'CSS Customizado', 'apollo-hub' ) . '</label></th>';
		echo '<td><textarea id="hub_custom_css" name="hub_custom_css" rows="6" style="width:100%;font-family:monospace;font-size:12px;" placeholder=".hub-page { background: #111; }">' . esc_textarea( $custom_css ) . '</textarea>';
		echo '<p class="description">' . esc_html__( 'CSS customizado aplicado apenas a este Hub.', 'apollo-hub' ) . '</p>';
		echo '</td></tr>';

		echo '</tbody></table>';
	}

	/**
	 * Renderiza metabox de blocos com UI interativa.
	 *
	 * @param \WP_Post $post Post atual.
	 */
	public function render_blocks_metabox( \WP_Post $post ): void {
		$blocks      = apollo_hub_get_blocks( $post->ID );
		$block_types = APOLLO_HUB_BLOCK_TYPES;
		?>
		<div id="apollo-hub-blocks-admin" data-blocks="<?php echo esc_attr( wp_json_encode( $blocks ) ); ?>" data-block-types="<?php echo esc_attr( wp_json_encode( $block_types ) ); ?>">

			<div class="ahb-toolbar">
				<strong><?php echo esc_html( count( $blocks ) ); ?></strong> <?php esc_html_e( 'bloco(s)', 'apollo-hub' ); ?>
				<span class="ahb-toolbar-sep">|</span>
				<button type="button" class="button ahb-add-block-btn" id="ahb-add-block-trigger">
					<span class="dashicons dashicons-plus-alt2"></span>
					<?php esc_html_e( 'Adicionar bloco', 'apollo-hub' ); ?>
				</button>
				<button type="button" class="button ahb-migrate-btn" id="ahb-migrate-trigger" title="<?php esc_attr_e( 'Migrar links legados para blocos', 'apollo-hub' ); ?>">
					<span class="dashicons dashicons-update"></span>
					<?php esc_html_e( 'Migrar links', 'apollo-hub' ); ?>
				</button>
			</div>

			<!-- Add block dropdown -->
			<div class="ahb-add-menu" id="ahb-add-menu" style="display:none;">
				<?php
				$groups = array();
				foreach ( $block_types as $key => $def ) {
					$groups[ $def['group'] ][ $key ] = $def;
				}
				foreach ( $groups as $group => $types ) :
					?>
					<div class="ahb-add-group">
						<span class="ahb-add-group-label"><?php echo esc_html( ucfirst( $group ) ); ?></span>
						<?php foreach ( $types as $key => $def ) : ?>
							<button type="button" class="ahb-add-type-btn" data-type="<?php echo esc_attr( $key ); ?>">
								<i class="<?php echo esc_attr( $def['icon'] ); ?>"></i>
								<?php echo esc_html( $def['label'] ); ?>
							</button>
						<?php endforeach; ?>
					</div>
				<?php endforeach; ?>
			</div>

			<!-- Block list -->
			<div class="ahb-blocks-list" id="ahb-blocks-list">
				<?php if ( empty( $blocks ) ) : ?>
					<div class="ahb-empty">
						<span class="dashicons dashicons-layout"></span>
						<p><?php esc_html_e( 'Nenhum bloco adicionado. Clique "Adicionar bloco" para começar.', 'apollo-hub' ); ?></p>
					</div>
				<?php else : ?>
					<?php foreach ( $blocks as $idx => $block ) : ?>
						<?php $this->render_block_card( $block, $idx ); ?>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>

			<textarea name="hub_blocks" id="hub_blocks_json" style="display:none;"><?php echo esc_textarea( wp_json_encode( $blocks ) ); ?></textarea>
		</div>
		<?php
	}

	/**
	 * Renderiza card de bloco individual no admin.
	 *
	 * @param array $block Block data.
	 * @param int   $index Index.
	 */
	private function render_block_card( array $block, int $index ): void {
		$type    = $block['type'] ?? 'unknown';
		$active  = $block['active'] ?? true;
		$data    = $block['data'] ?? array();
		$id      = $block['id'] ?? '';
		$bt      = APOLLO_HUB_BLOCK_TYPES[ $type ] ?? array(
			'label' => $type,
			'icon'  => 'ri-question-line',
		);
		$preview = $this->get_block_preview_text( $type, $data );
		?>
		<div class="ahb-block-card<?php echo $active ? '' : ' ahb-inactive'; ?>" data-index="<?php echo esc_attr( (string) $index ); ?>" data-id="<?php echo esc_attr( $id ); ?>" data-type="<?php echo esc_attr( $type ); ?>">
			<div class="ahb-block-handle" title="<?php esc_attr_e( 'Arrastar para reordenar', 'apollo-hub' ); ?>">
				<span class="dashicons dashicons-move"></span>
			</div>
			<div class="ahb-block-icon">
				<i class="<?php echo esc_attr( $bt['icon'] ); ?>"></i>
			</div>
			<div class="ahb-block-info">
				<span class="ahb-block-type-label"><?php echo esc_html( $bt['label'] ); ?></span>
				<span class="ahb-block-preview"><?php echo esc_html( $preview ); ?></span>
			</div>
			<div class="ahb-block-actions">
				<button type="button" class="ahb-toggle-btn" title="<?php echo $active ? esc_attr__( 'Desativar', 'apollo-hub' ) : esc_attr__( 'Ativar', 'apollo-hub' ); ?>">
					<span class="dashicons <?php echo $active ? 'dashicons-visibility' : 'dashicons-hidden'; ?>"></span>
				</button>
				<button type="button" class="ahb-edit-btn" title="<?php esc_attr_e( 'Editar', 'apollo-hub' ); ?>">
					<span class="dashicons dashicons-edit"></span>
				</button>
				<button type="button" class="ahb-delete-btn" title="<?php esc_attr_e( 'Remover', 'apollo-hub' ); ?>">
					<span class="dashicons dashicons-trash"></span>
				</button>
			</div>
		</div>
		<?php
	}

	/**
	 * Preview text resumido do bloco para o admin.
	 *
	 * @param string $type Block type.
	 * @param array  $data Block data.
	 * @return string
	 */
	private function get_block_preview_text( string $type, array $data ): string {
		switch ( $type ) {
			case 'header':
				return $data['text'] ?? '';
			case 'link':
				$title = $data['title'] ?? '';
				$url   = $data['url'] ?? '';
				return $title ? $title . ' → ' . $url : $url;
			case 'social':
				$count = count( $data['icons'] ?? array() );
				return sprintf( _n( '%d rede', '%d redes', $count, 'apollo-hub' ), $count );
			case 'youtube':
				return $data['url'] ?? '';
			case 'spotify':
				return ( $data['spotifyType'] ?? 'track' ) . ': ' . ( $data['url'] ?? '' );
			case 'image':
				return $data['alt'] ?? ( $data['url'] ?? '' );
			case 'text':
				return wp_trim_words( wp_strip_all_tags( $data['content'] ?? '' ), 10 );
			case 'faq':
				$count = count( $data['items'] ?? array() );
				return sprintf( _n( '%d pergunta', '%d perguntas', $count, 'apollo-hub' ), $count );
			case 'countdown':
				return $data['target'] ?? '';
			case 'map':
				return $data['embed'] ?? '';
			case 'divider':
				return ( $data['style'] ?? 'line' ) . ' (' . ( $data['height'] ?? 24 ) . 'px)';
			case 'embed':
				return mb_substr( wp_strip_all_tags( $data['code'] ?? '' ), 0, 60 );
			default:
				return '';
		}
	}

	/**
	 * Salva metabox.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post.
	 */
	public function save_metabox( int $post_id, \WP_Post $post ): void {
		if ( ! isset( $_POST['apollo_hub_nonce'] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( sanitize_key( $_POST['apollo_hub_nonce'] ), 'apollo_hub_metabox' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( isset( $_POST['hub_bio'] ) ) {
			update_post_meta(
				$post_id,
				'_hub_bio',
				substr( sanitize_textarea_field( wp_unslash( $_POST['hub_bio'] ) ), 0, APOLLO_HUB_BIO_MAX_LEN )
			);
		}

		if ( isset( $_POST['hub_theme'] ) ) {
			$theme = sanitize_key( $_POST['hub_theme'] );
			if ( array_key_exists( $theme, APOLLO_HUB_THEMES ) ) {
				update_post_meta( $post_id, '_hub_theme', $theme );
			}
		}

		if ( isset( $_POST['hub_avatar'] ) ) {
			update_post_meta( $post_id, '_hub_avatar', absint( $_POST['hub_avatar'] ) );
		}

		if ( isset( $_POST['hub_cover'] ) ) {
			update_post_meta( $post_id, '_hub_cover', absint( $_POST['hub_cover'] ) );
		}

		if ( isset( $_POST['hub_links'] ) ) {
			$links_raw = wp_unslash( $_POST['hub_links'] );
			$decoded   = json_decode( $links_raw, true );
			if ( is_array( $decoded ) ) {
				update_post_meta( $post_id, '_hub_links', wp_json_encode( $decoded ) );
			}
		}

		// Blocks — via hidden textarea with full JSON
		if ( isset( $_POST['hub_blocks'] ) ) {
			$blocks_raw = wp_unslash( $_POST['hub_blocks'] );
			$decoded    = json_decode( $blocks_raw, true );
			if ( is_array( $decoded ) ) {
				apollo_hub_save_blocks( $post_id, $decoded );
			}
		}

		// Socials (JSON)
		if ( isset( $_POST['hub_socials'] ) ) {
			$socials_raw = wp_unslash( $_POST['hub_socials'] );
			$decoded     = json_decode( $socials_raw, true );
			if ( is_array( $decoded ) ) {
				update_post_meta( $post_id, '_hub_socials', wp_json_encode( $decoded ) );
			} elseif ( '' === trim( $socials_raw ) ) {
				delete_post_meta( $post_id, '_hub_socials' );
			}
		}

		// Custom CSS
		if ( isset( $_POST['hub_custom_css'] ) ) {
			$css = wp_strip_all_tags( wp_unslash( $_POST['hub_custom_css'] ) );
			if ( '' !== $css ) {
				update_post_meta( $post_id, '_hub_custom_css', $css );
			} else {
				delete_post_meta( $post_id, '_hub_custom_css' );
			}
		}
	}

	/**
	 * Colunas no admin.
	 *
	 * @param array $columns Colunas.
	 * @return array
	 */
	public function admin_columns( array $columns ): array {
		unset( $columns['date'] );
		$columns['hub_username']    = __( 'Username', 'apollo-hub' );
		$columns['hub_theme']       = __( 'Tema', 'apollo-hub' );
		$columns['hub_block_count'] = __( 'Blocos', 'apollo-hub' );
		$columns['hub_link_count']  = __( 'Links', 'apollo-hub' );
		$columns['hub_public_url']  = __( 'URL Pública', 'apollo-hub' );
		$columns['date']            = __( 'Data', 'apollo-hub' );
		return $columns;
	}

	/**
	 * Conteúdo das colunas.
	 *
	 * @param string $column  Coluna.
	 * @param int    $post_id Post ID.
	 */
	public function admin_column_content( string $column, int $post_id ): void {
		$post = get_post( $post_id );
		$data = apollo_hub_get_data( $post_id );

		switch ( $column ) {
			case 'hub_username':
				echo esc_html( $post->post_name );
				break;
			case 'hub_theme':
				$themes = APOLLO_HUB_THEMES;
				echo esc_html( $themes[ $data['theme'] ] ?? $data['theme'] );
				break;
			case 'hub_block_count':
				$blocks = $data['blocks'] ?? array();
				echo esc_html( (string) count( $blocks ) );
				break;
			case 'hub_link_count':
				echo esc_html( (string) count( $data['links'] ) );
				break;
			case 'hub_public_url':
				$url = get_permalink( $post_id );
				printf(
					'<a href="%s" target="_blank">%s</a>',
					esc_url( $url ),
					esc_html( $url )
				);
				break;
		}
	}
}