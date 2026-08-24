<?php
/**
 * Integrações com outros plugins Apollo
 *
 * - Apollo Fav (botão favorito + contagem)
 * - Apollo Wow (reações)
 * - Apollo Social (activity log)
 * - Apollo Notif (notificações de evento)
 * - Apollo Mod (moderação)
 * - Apollo Comment (comentários)
 *
 * @package Apollo\Event
 */

declare(strict_types=1);

namespace Apollo\Event;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Integrations {

	public function __construct() {
		// Registrar post type nos outros plugins
		add_action( 'init', array( $this, 'register_integrations' ), 20 );

		// Apollo Fav
		add_filter( 'apollo_fav_post_types', array( $this, 'add_to_fav' ) );
		add_action( 'apollo_event_after_card', array( $this, 'render_fav_button' ), 10, 2 );

		// Apollo Wow
		add_filter( 'apollo_wow_post_types', array( $this, 'add_to_wow' ) );
		add_action( 'apollo_event_after_card', array( $this, 'render_wow_reactions' ), 20, 2 );

		// Apollo Social
		add_action( 'apollo_event_rest_created', array( $this, 'log_event_created' ), 10, 2 );
		add_action( 'apollo_event_rest_updated', array( $this, 'log_event_updated' ), 10, 2 );
		add_action( 'apollo_event_gone', array( $this, 'log_event_gone' ) );

		// Apollo Notif
		add_action( 'apollo_event_rest_created', array( $this, 'notify_new_event' ), 10, 2 );
		add_action( 'apollo_event_dj_added', array( $this, 'notify_dj_added' ), 10, 3 );
		add_action( 'apollo_event_gone', array( $this, 'notify_event_gone' ) );

		// Apollo Mod
		add_filter( 'apollo_mod_post_types', array( $this, 'add_to_mod' ) );

		// Apollo Comment
		add_filter( 'apollo_comment_post_types', array( $this, 'add_to_comments' ) );

		// Warm-up Mural — notifica usuários com RSVP quando alguém posta no mural do evento
		add_action( 'apollo/depoimento/created', array( $this, 'on_warmup_post' ), 10, 3 );

		// Apollo Statistics
		add_filter( 'apollo_statistics_post_types', array( $this, 'add_to_statistics' ) );
		add_action( 'wp_head', array( $this, 'track_event_view' ) );
	}

	/**
	 * Registra integrações no init
	 */
	public function register_integrations(): void {
		/**
		 * Hook para plugins Apollo se integrarem com eventos
		 *
		 * @param string $cpt Post type slug.
		 */
		do_action( 'apollo_event_register_integrations', APOLLO_EVENT_CPT );
	}

	// ─── Apollo Fav ────────────────────────────────────────────────────

	/**
	 * Adiciona eventos ao sistema de favoritos
	 */
	public function add_to_fav( array $post_types ): array {
		$post_types[] = APOLLO_EVENT_CPT;
		return $post_types;
	}

	/**
	 * Renderiza botão de favorito após cada card
	 */
	public function render_fav_button( array $event_data, string $style ): void {
		if ( ! function_exists( 'apollo_fav_button' ) ) {
			return;
		}

		echo '<div class="a-eve-fav-wrap">';
		apollo_fav_button( $event_data['id'] );
		echo '</div>';
	}

	// ─── Apollo Wow ────────────────────────────────────────────────────

	/**
	 * Adiciona eventos ao sistema de reações
	 */
	public function add_to_wow( array $post_types ): array {
		$post_types[] = APOLLO_EVENT_CPT;
		return $post_types;
	}

	/**
	 * Renderiza reações após cada card
	 */
	public function render_wow_reactions( array $event_data, string $style ): void {
		if ( ! function_exists( 'apollo_wow_reactions' ) ) {
			return;
		}

		echo '<div class="a-eve-wow-wrap">';
		apollo_wow_reactions( $event_data['id'] );
		echo '</div>';
	}

	// ─── Apollo Social ─────────────────────────────────────────────────

	/**
	 * Log: evento criado
	 */
	public function log_event_created( int $post_id, array $data ): void {
		if ( ! function_exists( 'apollo_log_activity' ) ) {
			return;
		}

		apollo_log_activity(
			array(
				'user_id'   => get_current_user_id(),
				'type'      => 'event_created',
				'object_id' => $post_id,
				'content'   => sprintf(
				/* translators: %s: event title */
					__( 'criou o evento "%s"', 'apollo-events' ),
					get_the_title( $post_id )
				),
			)
		);
	}

	/**
	 * Log: evento atualizado
	 */
	public function log_event_updated( int $post_id, array $data ): void {
		if ( ! function_exists( 'apollo_log_activity' ) ) {
			return;
		}

		apollo_log_activity(
			array(
				'user_id'   => get_current_user_id(),
				'type'      => 'event_updated',
				'object_id' => $post_id,
				'content'   => sprintf(
				/* translators: %s: event title */
					__( 'atualizou o evento "%s"', 'apollo-events' ),
					get_the_title( $post_id )
				),
			)
		);
	}

	/**
	 * Log: evento expirou
	 */
	public function log_event_gone( int $post_id ): void {
		do_action( 'apollo/event/gone', $post_id );

		if ( ! function_exists( 'apollo_log_activity' ) ) {
			return;
		}

		apollo_log_activity(
			array(
				'user_id'   => 0,
				'type'      => 'event_gone',
				'object_id' => $post_id,
				'content'   => sprintf(
				/* translators: %s: event title */
					__( 'O evento "%s" expirou', 'apollo-events' ),
					get_the_title( $post_id )
				),
			)
		);
	}

	// ─── Apollo Notif ──────────────────────────────────────────────────

	/**
	 * Notificação: novo evento criado
	 */
	public function notify_new_event( int $post_id, array $data ): void {
		if ( ! function_exists( 'apollo_send_notification' ) ) {
			return;
		}

		// Notificar conexões do autor
		$author_id = get_post_field( 'post_author', $post_id );

		apollo_send_notification(
			array(
				'type'      => 'new_event',
				'from_user' => (int) $author_id,
				'object_id' => $post_id,
				'message'   => sprintf(
				/* translators: 1: author name, 2: event title */
					__( '%1$s criou o evento "%2$s"', 'apollo-events' ),
					get_the_author_meta( 'display_name', $author_id ),
					get_the_title( $post_id )
				),
			)
		);
	}

	/**
	 * Notificação: DJ adicionado ao evento
	 */
	public function notify_dj_added( int $event_id, int $dj_id, $slot ): void {
		do_action( 'apollo/event/dj-added', $event_id, $dj_id, $slot );

		if ( ! function_exists( 'apollo_send_notification' ) ) {
			return;
		}

		// Buscar o autor do DJ (se o DJ CPT tiver author)
		$dj_author = get_post_field( 'post_author', $dj_id );

		if ( $dj_author ) {
			apollo_send_notification(
				array(
					'type'      => 'dj_added_to_event',
					'to_user'   => (int) $dj_author,
					'from_user' => get_current_user_id(),
					'object_id' => $event_id,
					'message'   => sprintf(
					/* translators: 1: DJ name, 2: event title */
						__( '%1$s foi adicionado ao evento "%2$s"', 'apollo-events' ),
						get_the_title( $dj_id ),
						get_the_title( $event_id )
					),
				)
			);
		}
	}

	/**
	 * Notificação: evento expirou
	 */
	public function notify_event_gone( int $post_id ): void {
		if ( ! function_exists( 'apollo_send_notification' ) ) {
			return;
		}

		$author_id = get_post_field( 'post_author', $post_id );

		apollo_send_notification(
			array(
				'type'      => 'event_gone',
				'to_user'   => (int) $author_id,
				'object_id' => $post_id,
				'message'   => sprintf(
				/* translators: %s: event title */
					__( 'Seu evento "%s" expirou', 'apollo-events' ),
					get_the_title( $post_id )
				),
			)
		);
	}

	// ─── Apollo Mod ────────────────────────────────────────────────────

	/**
	 * Adiciona eventos ao sistema de moderação
	 */
	public function add_to_mod( array $post_types ): array {
		$post_types[] = APOLLO_EVENT_CPT;
		return $post_types;
	}

	// ─── Apollo Comment ────────────────────────────────────────────────

	/**
	 * Adiciona eventos ao sistema de comentários
	 */
	public function add_to_comments( array $post_types ): array {
		$post_types[] = APOLLO_EVENT_CPT;
		return $post_types;
	}

	// ─── Apollo Statistics ─────────────────────────────────────────────

	/**
	 * Adiciona eventos ao tracking de estatísticas
	 */
	public function add_to_statistics( array $post_types ): array {
		$post_types[] = APOLLO_EVENT_CPT;
		return $post_types;
	}

	/**
	 * Track view de single event
	 */
	public function track_event_view(): void {
		if ( ! is_singular( APOLLO_EVENT_CPT ) ) {
			return;
		}

		if ( ! function_exists( 'apollo_track_view' ) ) {
			return;
		}

		apollo_track_view( get_the_ID(), APOLLO_EVENT_CPT );
	}

	// ─── Warm-up Mural ────────────────────────────────────────────────

	/**
	 * Notifica todos os usuários com RSVP quando alguém posta no Warm-up
	 * de um evento que estão confirmados.
	 *
	 * @param int $comment_id  ID do depoimento criado.
	 * @param int $post_id     ID do post (deve ser CPT event).
	 * @param int $author_id   ID do usuário que postou.
	 */
	public function on_warmup_post( int $comment_id, int $post_id, int $author_id ): void {
		if ( get_post_type( $post_id ) !== APOLLO_EVENT_CPT ) {
			return;
		}

		if ( ! function_exists( 'apollo_create_notification' ) ) {
			return;
		}

		global $wpdb;

		$rsvp_uids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT user_id FROM {$wpdb->prefix}apollo_event_rsvp
				 WHERE event_id = %d
				   AND status IN ('going', 'interested')
				   AND user_id != %d",
				$post_id,
				$author_id
			)
		);

		if ( empty( $rsvp_uids ) ) {
			return;
		}

		/* Filtrar quem silenciou notificações do warm-up deste evento */
		$notifiable_uids = array();
		foreach ( $rsvp_uids as $rsvp_uid ) {
			$muted = get_user_meta( (int) $rsvp_uid, '_apollo_warmup_muted', true );
			if ( is_array( $muted ) && in_array( $post_id, $muted, true ) ) {
				continue;
			}
			$notifiable_uids[] = (int) $rsvp_uid;
		}

		if ( empty( $notifiable_uids ) ) {
			return;
		}

		$event_title = get_the_title( $post_id );
		$event_url   = get_permalink( $post_id );
		$author_data = get_userdata( $author_id );
		$author_name = $author_data ? $author_data->display_name : __( 'Alguém', 'apollo-events' );
		$avatar_url  = get_avatar_url( $author_id, array( 'size' => 64 ) );

		foreach ( $notifiable_uids as $uid ) {
			apollo_create_notification(
				(int) $uid,
				'warmup_post',
				sprintf(
					/* translators: 1: user name, 2: event title */
					__( '%1$s postou no Warm-up de "%2$s"', 'apollo-events' ),
					$author_name,
					$event_title
				),
				$avatar_url,
				$event_url . '#a-eve-warmup',
				array(
					'event_id'   => $post_id,
					'comment_id' => $comment_id,
					'from_user'  => $author_id,
				),
				array(
					'icon'    => 'ri-fire-line',
					'channel' => 'apollo/event',
				)
			);
		}
	}
}
