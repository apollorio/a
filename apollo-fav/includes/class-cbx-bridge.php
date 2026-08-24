<?php
/**
 * CBX Bridge — Motor de Favoritos
 *
 * Ponte entre o ecossistema Apollo e a lógica de favoritos inspirada no CBX Bookmark.
 * Registra automaticamente suporte para TODOS os CPTs do Apollo:
 * event, dj, classified, loc, hub, supplier, doc
 *
 * NÃO usa Eloquent. Usa $wpdb direto para máxima compatibilidade e performance.
 *
 * @package Apollo\Fav
 */

declare(strict_types=1);

namespace Apollo\Fav;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CBX_Bridge {

	/**
	 * CPTs suportados pelo sistema de favoritos.
	 * Conforme apollo-registry.json → architecture → MASTER_REGISTRY → cpts
	 *
	 * @var string[]
	 */
	private array $supported_cpts = array(
		'event',
		'dj',
		'classified',
		'loc',
		'hub',
		'supplier',
		'doc',
		'user',
	);

	/**
	 * Inicializa o bridge.
	 */
	public function init(): void {
		// Registra AJAX handlers para toggle de favorito (frontend)
		add_action( 'wp_ajax_apollo_fav_toggle', array( $this, 'ajax_toggle' ) );
		add_action( 'wp_ajax_nopriv_apollo_fav_toggle', array( $this, 'ajax_nopriv' ) );

		// Injeta botão de favorito automaticamente nos templates de CPT
		add_action( 'apollo/template/after_title', array( $this, 'render_button_hook' ) );

		// Adiciona o botão via filtro the_content para single posts dos CPTs suportados
		add_filter( 'the_content', array( $this, 'inject_button_content' ), 99 );

		// Filtro para que outros módulos possam adicionar/remover CPTs suportados
		$this->supported_cpts = apply_filters( 'apollo/fav/supported_cpts', $this->supported_cpts );

		// Limpeza quando um post é deletado — remove favoritos órfãos
		add_action( 'before_delete_post', array( $this, 'cleanup_on_post_delete' ) );

		// Limpeza quando um usuário é deletado
		add_action( 'delete_user', array( $this, 'cleanup_on_user_delete' ) );
	}

	/**
	 * Retorna os CPTs suportados.
	 *
	 * @return string[]
	 */
	public function get_supported_cpts(): array {
		return $this->supported_cpts;
	}

	/**
	 * AJAX Toggle — usuário logado.
	 * Verifica nonce, valida post, executa toggle.
	 */
	public function ajax_toggle(): void {
		// Verifica nonce de segurança
		check_ajax_referer( 'apollo_fav_nonce', 'nonce' );

		// Verifica se o usuário está logado
		if ( ! is_user_logged_in() ) {
			wp_send_json_error(
				array(
					'message' => __( 'Você precisa estar logado para favoritar.', 'apollo-fav' ),
				),
				401
			);
		}

		$user_id = get_current_user_id();
		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;

		if ( $post_id <= 0 ) {
			wp_send_json_error(
				array(
					'message' => __( 'Post inválido.', 'apollo-fav' ),
				),
				400
			);
		}

		// User fav — valida como user ID ao invés de post
		$fav_type = isset( $_POST['fav_type'] ) ? sanitize_text_field( wp_unslash( $_POST['fav_type'] ) ) : '';
		if ( $fav_type === 'user' ) {
			if ( ! get_userdata( $post_id ) ) {
				wp_send_json_error(
					array(
						'message' => __( 'Usuário não encontrado.', 'apollo-fav' ),
					),
					404
				);
			}
		} else {
			// Verifica se o post existe e é de um tipo suportado
			$post = get_post( $post_id );
			if ( ! $post || ! in_array( $post->post_type, $this->supported_cpts, true ) ) {
				wp_send_json_error(
					array(
						'message' => __( 'Tipo de conteúdo não suportado.', 'apollo-fav' ),
					),
					400
				);
			}
		}

		// Verifica capability (qualquer subscriber pode favoritar)
		if ( ! current_user_can( 'read' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Permissão insuficiente.', 'apollo-fav' ),
				),
				403
			);
		}

		// Aplica filtro gate — outros plugins podem bloquear (ex: plano premium)
		$allow = apply_filters(
			'apollo/fav/allow_toggle',
			array(
				'allow' => true,
				'msg'   => '',
			),
			$user_id,
			$post_id,
			$post->post_type
		);

		if ( ! $allow['allow'] ) {
			wp_send_json_error(
				array(
					'message' => $allow['msg'] ?: __( 'Ação não permitida.', 'apollo-fav' ),
				),
				403
			);
		}

		// Executa toggle
		$result = apollo_toggle_fav( $user_id, $post_id );

		wp_send_json_success(
			array(
				'action' => $result['action'],
				'count'  => $result['count'],
				'fav_id' => $result['fav_id'],
			)
		);
	}

	/**
	 * AJAX para usuário não logado — retorna erro informativo.
	 */
	public function ajax_nopriv(): void {
		wp_send_json_error(
			array(
				'message' => __( 'Faça login para favoritar.', 'apollo-fav' ),
				'login'   => true,
			),
			401
		);
	}

	/**
	 * Renderiza botão quando chamado via hook apollo/template/after_title.
	 */
	public function render_button_hook(): void {
		$post_id = get_the_ID();

		if ( ! $post_id ) {
			return;
		}

		$post = get_post( $post_id );
		if ( $post && in_array( $post->post_type, $this->supported_cpts, true ) ) {
			apollo_fav_button( $post_id );
		}
	}

	/**
	 * Injeta o botão Apollo Heart no conteúdo de single posts dos CPTs suportados.
	 *
	 * @param string $content  Conteúdo do post.
	 * @return string          Conteúdo com botão anexado.
	 */
	public function inject_button_content( string $content ): string {
		// Só em single e no main query
		if ( ! is_singular() || ! is_main_query() ) {
			return $content;
		}

		$post = get_post();
		if ( ! $post || ! in_array( $post->post_type, $this->supported_cpts, true ) ) {
			return $content;
		}

		// Pega o HTML do botão sem imprimir
		$button = apollo_fav_button( $post->ID, false );

		// Insere no topo do conteúdo
		return '<div class="apollo-fav-wrapper">' . $button . '</div>' . $content;
	}

	/**
	 * Remove todos os favoritos quando um post é deletado.
	 * Previne registros órfãos na tabela apollo_favs.
	 *
	 * @param int $post_id  ID do post sendo deletado.
	 */
	public function cleanup_on_post_delete( int $post_id ): void {
		global $wpdb;

		$table = $wpdb->prefix . APOLLO_FAV_TABLE;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->delete( $table, array( 'post_id' => $post_id ), array( '%d' ) );
	}

	/**
	 * Remove todos os favoritos de um usuário ao deletá-lo.
	 *
	 * @param int $user_id  ID do usuário.
	 */
	public function cleanup_on_user_delete( int $user_id ): void {
		global $wpdb;

		$table = $wpdb->prefix . APOLLO_FAV_TABLE;

		// Busca posts que o usuário favoritou para recalcular contagem
		$post_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT post_id FROM {$table} WHERE user_id = %d",
				$user_id
			)
		);

		// Remove todos os favoritos do usuário
		$wpdb->delete( $table, array( 'user_id' => $user_id ), array( '%d' ) );

		// Recalcula contagem dos posts afetados
		foreach ( $post_ids as $pid ) {
			apollo_fav_update_count( (int) $pid );
		}
	}

	/**
	 * Retorna estatísticas gerais dos favoritos para o admin.
	 *
	 * @return array{total_favs: int, total_users: int, by_type: array}
	 */
	public function get_stats(): array {
		global $wpdb;

		$table = $wpdb->prefix . APOLLO_FAV_TABLE;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$total_favs = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i', $table ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$total_users = (int) $wpdb->get_var(
			$wpdb->prepare( 'SELECT COUNT(DISTINCT user_id) FROM %i', $table )
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$by_type = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT post_type, COUNT(*) AS total, COUNT(DISTINCT user_id) AS unique_users
             FROM %i
             GROUP BY post_type
             ORDER BY total DESC',
				$table
			),
			ARRAY_A
		);

		return array(
			'total_favs'  => $total_favs,
			'total_users' => $total_users,
			'by_type'     => $by_type ?: array(),
		);
	}
}
