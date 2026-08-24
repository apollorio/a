<?php
/**
 * Funções globais (API pública) do Apollo Fav
 *
 * Outras plugins podem chamar estas funções para interagir com favoritos
 * sem precisar instanciar classes diretamente.
 *
 * @package Apollo\Fav
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ─────────────────────────────────────────────────────────────
// CRUD de Favoritos — API Global
// ─────────────────────────────────────────────────────────────

/**
 * Adiciona um post aos favoritos do usuário.
 *
 * @param int $user_id  ID do usuário.
 * @param int $post_id  ID do post/CPT.
 * @return int|false     ID do registro inserido ou false se já existia/erro.
 */
function apollo_add_fav( int $user_id, int $post_id, string $fav_type = '' ): int|false {
	global $wpdb;

	if ( $user_id <= 0 || $post_id <= 0 ) {
		return false;
	}

	// Determine the post_type to store
	if ( $fav_type === 'user' ) {
		// Favoriting a user — validate user exists, cannot fav yourself
		if ( $user_id === $post_id || ! get_userdata( $post_id ) ) {
			return false;
		}
		$stored_type = 'user';
	} else {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return false;
		}
		$stored_type = $post->post_type;
	}

	$table = $wpdb->prefix . APOLLO_FAV_TABLE;

	// Verifica se já existe (previne duplicata)
	$exists = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT id FROM {$table} WHERE user_id = %d AND post_id = %d",
			$user_id,
			$post_id
		)
	);

	if ( $exists ) {
		return false; // Já favoritado
	}

	// Insere novo favorito
	$inserted = $wpdb->insert(
		$table,
		array(
			'user_id'   => $user_id,
			'post_id'   => $post_id,
			'post_type' => $stored_type,
		),
		array( '%d', '%d', '%s' )
	);

	if ( ! $inserted ) {
		return false;
	}

	$fav_id = (int) $wpdb->insert_id;

	// Atualiza contagem no post meta (skip para user favs)
	if ( $stored_type !== 'user' ) {
		apollo_fav_update_count( $post_id );
	}

	// Dispara hook para outros módulos (membership triggers, etc.)
	do_action( 'apollo/fav/added', $fav_id, $user_id, $post_id, $stored_type );

	// Trigger do gamification — apollo-membership escuta este hook
	do_action( 'apollo_fav_added', $user_id, $post_id, $stored_type );

	return $fav_id;
}

/**
 * Remove um post dos favoritos do usuário.
 *
 * @param int $user_id  ID do usuário.
 * @param int $post_id  ID do post/CPT.
 * @return bool          true se removido, false se não encontrado.
 */
function apollo_remove_fav( int $user_id, int $post_id ): bool {
	global $wpdb;

	$table = $wpdb->prefix . APOLLO_FAV_TABLE;

	// Busca dados antes de deletar (para o hook)
	$row = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT id, post_type FROM {$table} WHERE user_id = %d AND post_id = %d",
			$user_id,
			$post_id
		)
	);

	if ( ! $row ) {
		return false;
	}

	// Dispara antes de remover
	do_action( 'apollo/fav/removing', (int) $row->id, $user_id, $post_id, $row->post_type );

	$deleted = $wpdb->delete(
		$table,
		array(
			'user_id' => $user_id,
			'post_id' => $post_id,
		),
		array( '%d', '%d' )
	);

	if ( $deleted ) {
		// Atualiza contagem
		apollo_fav_update_count( $post_id );

		// Dispara hook pós-remoção
		do_action( 'apollo/fav/removed', (int) $row->id, $user_id, $post_id, $row->post_type );
	}

	return (bool) $deleted;
}

/**
 * Toggle de favorito — adiciona se não existe, remove se já existe.
 * Padrão CBX Bookmark reimplementado para Apollo.
 *
 * @param int $user_id  ID do usuário.
 * @param int $post_id  ID do post.
 * @return array{action: string, fav_id: int|null, count: int}
 */
function apollo_toggle_fav( int $user_id, int $post_id ): array {
	if ( apollo_is_fav( $user_id, $post_id ) ) {
		apollo_remove_fav( $user_id, $post_id );
		return array(
			'action' => 'removed',
			'fav_id' => null,
			'count'  => apollo_get_fav_count( $post_id ),
		);
	}

	$fav_id = apollo_add_fav( $user_id, $post_id );
	return array(
		'action' => 'added',
		'fav_id' => $fav_id ?: null,
		'count'  => apollo_get_fav_count( $post_id ),
	);
}

/**
 * Verifica se o usuário já favoritou o post.
 *
 * @param int $user_id  ID do usuário.
 * @param int $post_id  ID do post.
 * @return bool
 */
function apollo_is_fav( int $user_id, int $post_id ): bool {
	global $wpdb;

	$table = $wpdb->prefix . APOLLO_FAV_TABLE;

	return (bool) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT id FROM {$table} WHERE user_id = %d AND post_id = %d LIMIT 1",
			$user_id,
			$post_id
		)
	);
}

/**
 * Retorna contagem total de favoritos de um post (COUNT DISTINCT user_id).
 *
 * @param int $post_id  ID do post.
 * @return int
 */
function apollo_get_fav_count( int $post_id ): int {
	global $wpdb;

	$table = $wpdb->prefix . APOLLO_FAV_TABLE;

	return (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(DISTINCT user_id) FROM {$table} WHERE post_id = %d",
			$post_id
		)
	);
}

/**
 * Retorna lista de favoritos do usuário, opcionalmente filtrada por post_type.
 *
 * @param int         $user_id    ID do usuário.
 * @param string|null $post_type  Filtro por tipo de conteúdo (event, dj, classified, etc.).
 * @param int         $limit      Limite de resultados.
 * @param int         $offset     Offset para paginação.
 * @return array                   Array de objetos {id, post_id, post_type, created_at}.
 */
function apollo_get_user_favs( int $user_id, ?string $post_type = null, int $limit = 20, int $offset = 0 ): array {
	global $wpdb;

	$table = $wpdb->prefix . APOLLO_FAV_TABLE;

	// Monta query com filtro opcional por tipo
	if ( $post_type ) {
		$sql = $wpdb->prepare(
			"SELECT f.id, f.post_id, f.post_type, f.created_at, p.post_title
             FROM {$table} f
             INNER JOIN {$wpdb->posts} p ON f.post_id = p.ID
             WHERE f.user_id = %d AND f.post_type = %s AND p.post_status = 'publish'
             ORDER BY f.created_at DESC
             LIMIT %d OFFSET %d",
			$user_id,
			$post_type,
			$limit,
			$offset
		);
	} else {
		$sql = $wpdb->prepare(
			"SELECT f.id, f.post_id, f.post_type, f.created_at, p.post_title
             FROM {$table} f
             INNER JOIN {$wpdb->posts} p ON f.post_id = p.ID
             WHERE f.user_id = %d AND p.post_status = 'publish'
             ORDER BY f.created_at DESC
             LIMIT %d OFFSET %d",
			$user_id,
			$limit,
			$offset
		);
	}

	return $wpdb->get_results( $sql );
}

/**
 * Retorna todos os user_ids que favoritaram um post específico.
 * Usado pelos triggers de notificação.
 *
 * @param int $post_id  ID do post.
 * @return int[]         Array de user IDs.
 */
function apollo_get_fav_users( int $post_id ): array {
	global $wpdb;

	$table = $wpdb->prefix . APOLLO_FAV_TABLE;

	$results = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT DISTINCT user_id FROM {$table} WHERE post_id = %d",
			$post_id
		)
	);

	return array_map( 'intval', $results );
}

/**
 * Retorna todos os user_ids que favoritaram posts de um determinado tipo
 * que contenham um valor específico em um meta key.
 * Exemplo: encontrar usuários que favoritaram um DJ específico.
 *
 * @param string $post_type   Tipo de post (ex: 'event').
 * @param string $meta_key    Chave meta (ex: '_event_dj_ids').
 * @param mixed  $meta_value  Valor procurado.
 * @return int[]               Array de user IDs únicos.
 */
function apollo_get_fav_users_by_meta( string $post_type, string $meta_key, mixed $meta_value ): array {
	global $wpdb;

	$table = $wpdb->prefix . APOLLO_FAV_TABLE;

	// Busca usuários que favoritaram posts deste tipo que têm o meta solicitado
	$results = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT DISTINCT f.user_id
         FROM {$table} f
         INNER JOIN {$wpdb->postmeta} pm ON f.post_id = pm.post_id
         WHERE f.post_type = %s AND pm.meta_key = %s AND pm.meta_value LIKE %s",
			$post_type,
			$meta_key,
			'%' . $wpdb->esc_like( (string) $meta_value ) . '%'
		)
	);

	return array_map( 'intval', $results );
}

/**
 * Retorna IDs de usuários que favoritaram um DJ específico (post_type = 'dj').
 *
 * @param int $dj_post_id  ID do post DJ.
 * @return int[]            Array de user IDs.
 */
function apollo_get_dj_fans( int $dj_post_id ): array {
	return apollo_get_fav_users( $dj_post_id );
}

/**
 * Atualiza a meta _fav_count de um post com a contagem real.
 *
 * @param int $post_id  ID do post.
 * @return void
 */
function apollo_fav_update_count( int $post_id ): void {
	$count = apollo_get_fav_count( $post_id );
	update_post_meta( $post_id, '_fav_count', $count );
}

/**
 * Contagem total de favoritos de um usuário (todos os tipos).
 *
 * @param int $user_id  ID do usuário.
 * @return int
 */
function apollo_get_user_fav_total( int $user_id ): int {
	global $wpdb;

	$table = $wpdb->prefix . APOLLO_FAV_TABLE;

	return (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(DISTINCT post_id) FROM {$table} WHERE user_id = %d",
			$user_id
		)
	);
}

/**
 * Contagem de favoritos do usuário filtrada por post_type.
 *
 * @param int    $user_id    ID do usuário.
 * @param string $post_type  Tipo (event, dj, classified, loc, hub).
 * @return int
 */
function apollo_get_user_fav_count_by_type( int $user_id, string $post_type ): int {
	global $wpdb;

	$table = $wpdb->prefix . APOLLO_FAV_TABLE;

	return (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$table} WHERE user_id = %d AND post_type = %s",
			$user_id,
			$post_type
		)
	);
}

/**
 * Posts mais favoritados (ranking global).
 *
 * @param string|null $post_type  Filtro por tipo (null = todos).
 * @param int         $limit      Quantidade.
 * @return array                   Array de {post_id, post_type, fav_count}.
 */
function apollo_get_most_faved( ?string $post_type = null, int $limit = 10 ): array {
	global $wpdb;

	$table = $wpdb->prefix . APOLLO_FAV_TABLE;

	if ( $post_type ) {
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT post_id, post_type, COUNT(DISTINCT user_id) AS fav_count
             FROM {$table}
             WHERE post_type = %s
             GROUP BY post_id, post_type
             ORDER BY fav_count DESC
             LIMIT %d",
				$post_type,
				$limit
			)
		);
	}

	return $wpdb->get_results(
		$wpdb->prepare(
			"SELECT post_id, post_type, COUNT(DISTINCT user_id) AS fav_count
         FROM {$table}
         GROUP BY post_id, post_type
         ORDER BY fav_count DESC
         LIMIT %d",
			$limit
		)
	);
}

// ─────────────────────────────────────────────────────────────
// Renderização do botão "Apollo Heart"
// ─────────────────────────────────────────────────────────────

/**
 * Renderiza o botão de favorito "Apollo Heart" para um post.
 * Pode ser chamado em qualquer template de CPT.
 *
 * @param int|null $post_id  ID do post (null = get_the_ID()).
 * @param bool     $echo     Se true, imprime. Se false, retorna HTML.
 * @return string|void
 */
function apollo_fav_button( ?int $post_id = null, bool $echo = true ): string|null {
	$post_id = $post_id ?: get_the_ID();

	if ( ! $post_id ) {
		return $echo ? null : '';
	}

	$is_fav = false;
	$count  = apollo_get_fav_count( $post_id );

	if ( is_user_logged_in() ) {
		$is_fav = apollo_is_fav( get_current_user_id(), $post_id );
	}

	$active_class = $is_fav ? 'apollo-fav--active' : '';

	// Only authenticated users can favorite; never bake a live nonce into guest/cached HTML.
	$fav_nonce = is_user_logged_in() ? wp_create_nonce( 'apollo_fav_nonce' ) : '';

	$html = sprintf(
		'<button class="apollo-fav-btn %s" data-post-id="%d" data-nonce="%s" aria-label="%s" title="%s">
            <span class="apollo-fav-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="%s" class="apollo-heart-svg">
                    <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                </svg>
            </span>
            <span class="apollo-fav-count">%d</span>
        </button>',
		esc_attr( $active_class ),
		(int) $post_id,
		esc_attr( $fav_nonce ),
		$is_fav ? esc_attr__( 'Remover dos favoritos', 'apollo-fav' ) : esc_attr__( 'Adicionar aos favoritos', 'apollo-fav' ),
		$is_fav ? esc_attr__( 'Remover dos favoritos', 'apollo-fav' ) : esc_attr__( 'Adicionar aos favoritos', 'apollo-fav' ),
		$is_fav ? 'currentColor' : 'none',
		(int) $count
	);

	if ( $echo ) {
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		return null;
	}

	return $html;
}
