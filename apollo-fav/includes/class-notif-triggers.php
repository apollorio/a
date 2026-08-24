<?php
/**
 * Notif Triggers — O "Cérebro" das Notificações Inteligentes
 *
 * Implementa 3 fluxos lógicos de notificação baseados em favoritos:
 *
 * TRIGGER A: "Fanboy" — DJ → Evento
 *   Quando um evento é salvo com DJs vinculados, notifica quem favoritou aquele DJ.
 *
 * TRIGGER B: "Marketplace" — Classified → Sold/Closed
 *   Quando um anúncio muda status para vendido/fechado, notifica quem favoritou.
 *
 * TRIGGER C: "Hype" — Event Updates
 *   Quando data, venue ou lineup de um evento mudam, notifica quem favoritou.
 *
 * @package Apollo\Fav
 */

declare(strict_types=1);

namespace Apollo\Fav;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Notif_Triggers {

	/**
	 * Inicializa os triggers de notificação.
	 * Só ativa se apollo-notif estiver disponível.
	 */
	public function init(): void {
		// Verifica se a função de notificação do apollo-notif existe
		if ( ! function_exists( 'apollo_create_notification' ) ) {
			// O apollo-notif pode carregar depois — re-tenta no init
			add_action( 'init', array( $this, 'late_bind' ), 999 );
			return;
		}

		$this->register_hooks();
	}

	/**
	 * Tentativa tardia de binding caso apollo-notif carregue depois.
	 */
	public function late_bind(): void {
		if ( function_exists( 'apollo_create_notification' ) ) {
			$this->register_hooks();
		}
	}

	/**
	 * Registra todos os hooks de trigger.
	 */
	private function register_hooks(): void {
		// ═══════════════════════════════════════════════════
		// TRIGGER A: "Fanboy" — DJ favorito toca em evento
		// ═══════════════════════════════════════════════════
		add_action( 'save_post_event', array( $this, 'trigger_fanboy' ), 20, 3 );

		// ═══════════════════════════════════════════════════
		// TRIGGER B: "Marketplace" — Anúncio vendido/fechado
		// ═══════════════════════════════════════════════════
		add_action( 'save_post_classified', array( $this, 'trigger_marketplace' ), 20, 3 );

		// ═══════════════════════════════════════════════════
		// TRIGGER C: "Hype" — Evento atualizado
		// ═══════════════════════════════════════════════════
		add_action( 'post_updated', array( $this, 'trigger_hype_post' ), 20, 3 );
		add_action( 'updated_post_meta', array( $this, 'trigger_hype_meta' ), 20, 4 );
	}

	// ═══════════════════════════════════════════════════════════════
	// TRIGGER A: "FANBOY" — Seu DJ favorito vai tocar em um evento!
	// ═══════════════════════════════════════════════════════════════

	/**
	 * Quando um evento é salvo, verifica se tem DJs vinculados.
	 * Para cada DJ, encontra os fãs (quem favoritou aquele DJ)
	 * e envia notificação.
	 *
	 * Hook: save_post_event
	 *
	 * @param int      $post_id  ID do evento.
	 * @param \WP_Post $post     Objeto do post.
	 * @param bool     $update   Se é atualização (true) ou inserção (false).
	 */
	public function trigger_fanboy( int $post_id, \WP_Post $post, bool $update ): void {
		// Ignora autosaves e revisions
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		// Só processa eventos publicados
		if ( $post->post_status !== 'publish' ) {
			return;
		}

		// Previne loops — marca que já processou este save
		$meta_key = '_apollo_fav_fanboy_notified';
		if ( get_post_meta( $post_id, $meta_key, true ) === APOLLO_FAV_VERSION ) {
			return;
		}

		// Busca os IDs dos DJs vinculados ao evento
		// Meta key conforme registry: _event_dj_ids (array de IDs de posts DJ)
		$dj_ids = get_post_meta( $post_id, '_event_dj_ids', true );

		if ( empty( $dj_ids ) || ! is_array( $dj_ids ) ) {
			return;
		}

		// Dados do evento para a mensagem
		$event_title = get_the_title( $post_id );
		$event_date  = get_post_meta( $post_id, '_event_start_date', true );
		$event_time  = get_post_meta( $post_id, '_event_start_time', true );
		$event_url   = get_permalink( $post_id );

		// Formata data para exibição amigável
		$date_display = '';
		if ( $event_date ) {
			$timestamp    = strtotime( $event_date );
			$date_display = wp_date( 'd \d\e M', $timestamp );

			if ( $event_time ) {
				$date_display .= ' às ' . substr( $event_time, 0, 5 ) . 'h';
			}
		}

		// Para cada DJ vinculado ao evento, busca os fãs e notifica
		$notified_users = array(); // Previne notificações duplicadas
		$event_author   = (int) $post->post_author;

		foreach ( $dj_ids as $dj_id ) {
			$dj_id = (int) $dj_id;

			if ( $dj_id <= 0 ) {
				continue;
			}

			$dj_name = get_the_title( $dj_id );

			// Busca todos os usuários que favoritaram este DJ
			$fans = apollo_get_dj_fans( $dj_id );

			foreach ( $fans as $fan_user_id ) {
				// Não notifica o próprio autor do evento
				if ( $fan_user_id === $event_author ) {
					continue;
				}

				// Evita duplicata se o usuário favoritou múltiplos DJs do mesmo evento
				if ( in_array( $fan_user_id, $notified_users, true ) ) {
					continue;
				}

				$notified_users[] = $fan_user_id;

				// Monta mensagem no formato solicitado
				$message = sprintf(
					'Seu DJ favorito %s vai tocar no evento %s%s! See you on the dancefloor? 🎉',
					$dj_name,
					$event_title,
					$date_display ? ' dia ' . $date_display : ''
				);

				// Envia via apollo-notif
				apollo_create_notification(
					user_id: $fan_user_id,
					type:    'fav_fanboy',
					title:   '🎧 DJ favorito confirmado!',
					message: $message,
					link:    $event_url ?: '',
					data:    array(
						'dj_id'    => $dj_id,
						'event_id' => $post_id,
						'trigger'  => 'fanboy',
					)
				);
			}
		}

		// Marca evento como processado para este save
		update_post_meta( $post_id, $meta_key, APOLLO_FAV_VERSION );

		// Hook para extensibilidade
		do_action( 'apollo/fav/fanboy_triggered', $post_id, $dj_ids, $notified_users );
	}

	// ═══════════════════════════════════════════════════════════════
	// TRIGGER B: "MARKETPLACE" — Anúncio vendido/fechado
	// ═══════════════════════════════════════════════════════════════

	/**
	 * Quando um classified muda de status para 'sold' ou 'closed',
	 * notifica todos os usuários que favoritaram aquele anúncio.
	 *
	 * Hook: save_post_classified
	 *
	 * @param int      $post_id  ID do classified.
	 * @param \WP_Post $post     Objeto do post.
	 * @param bool     $update   Se é atualização.
	 */
	public function trigger_marketplace( int $post_id, \WP_Post $post, bool $update ): void {
		// Ignora autosaves e revisions
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		// Só processa atualizações (não criações)
		if ( ! $update ) {
			return;
		}

		// Verifica se o status do classified mudou para 'sold' ou 'closed'
		// Meta key: _classified_status (não definida explicitamente no registry,
		// mas mencionada no prompt como campo a monitorar)
		$new_status = get_post_meta( $post_id, '_classified_status', true );

		if ( ! in_array( $new_status, array( 'sold', 'closed' ), true ) ) {
			return;
		}

		// Verifica se já notificamos para este status (previne spam)
		$notified_key = '_apollo_fav_market_notified_' . $new_status;
		if ( get_post_meta( $post_id, $notified_key, true ) ) {
			return;
		}

		// Busca todos os usuários que favoritaram este anúncio
		$fav_users = apollo_get_fav_users( $post_id );

		if ( empty( $fav_users ) ) {
			return;
		}

		$ad_title  = get_the_title( $post_id );
		$ad_url    = get_permalink( $post_id );
		$ad_author = (int) $post->post_author;

		// Determina texto de status
		$status_text = ( $new_status === 'sold' ) ? 'vendido' : 'fechado';

		foreach ( $fav_users as $fan_user_id ) {
			// Não notifica o próprio autor do anúncio
			if ( $fan_user_id === $ad_author ) {
				continue;
			}

			$message = sprintf(
				'O anúncio que você favoritou \'%s\' foi %s.',
				$ad_title,
				$status_text
			);

			apollo_create_notification(
				user_id: $fan_user_id,
				type:    'fav_marketplace',
				title:   '📦 Anúncio atualizado',
				message: $message,
				link:    $ad_url ?: '',
				data:    array(
					'classified_id' => $post_id,
					'new_status'    => $new_status,
					'trigger'       => 'marketplace',
				)
			);
		}

		// Marca como notificado para este status
		update_post_meta( $post_id, $notified_key, current_time( 'mysql' ) );

		// Hook para extensibilidade
		do_action( 'apollo/fav/marketplace_triggered', $post_id, $new_status, $fav_users );
	}

	// ═══════════════════════════════════════════════════════════════
	// TRIGGER C: "HYPE" — Evento favorito foi atualizado!
	// ═══════════════════════════════════════════════════════════════

	/**
	 * Detecta mudanças no post do evento (título, conteúdo).
	 *
	 * Hook: post_updated
	 *
	 * @param int      $post_id    ID do post.
	 * @param \WP_Post $post_after  Post depois da atualização.
	 * @param \WP_Post $post_before Post antes da atualização.
	 */
	public function trigger_hype_post( int $post_id, \WP_Post $post_after, \WP_Post $post_before ): void {
		// Só processa eventos
		if ( $post_after->post_type !== 'event' ) {
			return;
		}

		// Ignora autosaves
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( $post_after->post_status !== 'publish' ) {
			return;
		}

		// Verifica se houve mudança significativa no conteúdo
		$title_changed   = $post_before->post_title !== $post_after->post_title;
		$content_changed = $post_before->post_content !== $post_after->post_content;

		if ( ! $title_changed && ! $content_changed ) {
			return;
		}

		// Chama a notificação com debounce (transiente de 5 minutos)
		$this->send_hype_notification( $post_id, 'content_update' );
	}

	/**
	 * Detecta mudanças em meta keys críticas do evento:
	 * - _event_start_date (data)
	 * - _event_loc_id (venue/local)
	 * - _event_dj_ids (lineup)
	 * - _event_start_time (horário)
	 * - _event_status (cancelado, adiado, etc.)
	 *
	 * Hook: updated_post_meta
	 *
	 * @param int    $meta_id    ID do meta.
	 * @param int    $post_id    ID do post.
	 * @param string $meta_key   Chave do meta.
	 * @param mixed  $meta_value Novo valor.
	 */
	public function trigger_hype_meta( int $meta_id, int $post_id, string $meta_key, mixed $meta_value ): void {
		// Meta keys consideradas "críticas" para eventos
		$critical_keys = array(
			'_event_start_date',
			'_event_end_date',
			'_event_start_time',
			'_event_end_time',
			'_event_loc_id',
			'_event_dj_ids',
			'_event_status',
			'_event_ticket_price',
		);

		if ( ! in_array( $meta_key, $critical_keys, true ) ) {
			return;
		}

		// Verifica se é um evento
		$post = get_post( $post_id );
		if ( ! $post || $post->post_type !== 'event' ) {
			return;
		}

		if ( $post->post_status !== 'publish' ) {
			return;
		}

		// Envia notificação com debounce
		$this->send_hype_notification( $post_id, $meta_key );
	}

	/**
	 * Envia notificações de "hype" para quem favoritou um evento atualizado.
	 * Usa transiente como debounce para evitar spam em saves múltiplos.
	 *
	 * @param int    $post_id  ID do evento.
	 * @param string $change   Tipo de mudança.
	 */
	private function send_hype_notification( int $post_id, string $change ): void {
		// Debounce: não notifica mais de 1x a cada 5 minutos por evento
		$transient_key = 'apollo_fav_hype_' . $post_id;

		if ( get_transient( $transient_key ) ) {
			return; // Já notificou recentemente
		}

		// Verifica se apollo-notif está disponível
		if ( ! function_exists( 'apollo_create_notification' ) ) {
			return;
		}

		// Busca fãs do evento
		$fav_users = apollo_get_fav_users( $post_id );

		if ( empty( $fav_users ) ) {
			return;
		}

		$event_title  = get_the_title( $post_id );
		$event_url    = get_permalink( $post_id );
		$event_author = (int) get_post_field( 'post_author', $post_id );

		foreach ( $fav_users as $fan_user_id ) {
			// Não notifica o autor
			if ( $fan_user_id === $event_author ) {
				continue;
			}

			$message = sprintf(
				'O evento que você favoritou \'%s\' foi atualizado! Veja as novidades.',
				$event_title
			);

			apollo_create_notification(
				user_id: $fan_user_id,
				type:    'fav_hype',
				title:   '🔥 Evento atualizado!',
				message: $message,
				link:    $event_url ?: '',
				data:    array(
					'event_id' => $post_id,
					'change'   => $change,
					'trigger'  => 'hype',
				)
			);
		}

		// Seta debounce de 5 minutos
		set_transient( $transient_key, true, 5 * MINUTE_IN_SECONDS );

		// Hook para extensibilidade
		do_action( 'apollo/fav/hype_triggered', $post_id, $change, $fav_users );
	}
}
