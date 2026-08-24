<?php

/**
 * MergeTagRegistry — central catalogue of all {{placeholders}} and real-data resolver.
 *
 * Responsibilities:
 *   1) Define every known tag with description, context category, and source plugin.
 *   2) Resolve tags to real WP/Apollo data when given a user_id.
 *   3) Scan a HTML/text string and return which known tags it contains.
 *
 * @package Apollo\Email\Template
 * @since   1.1.0
 */

declare(strict_types=1);

namespace Apollo\Email\Template;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MergeTagRegistry {

	// ── Tag catalogue ─────────────────────────────────────────────

	/**
	 * Return the complete tag catalogue.
	 *
	 * Each entry: [tag, description, context, source, example]
	 *
	 * @return array<int, array{tag:string, description:string, context:string, source:string, example:string}>
	 */
	public static function getAllTags(): array {
		return array(
			// ── Site / Global ──────────────────────────────────────────
			array(
				'tag'         => 'site_name',
				'description' => 'Nome do site / plataforma',
				'context'     => 'global',
				'source'      => 'wordpress',
				'example'     => 'Apollo Rio',
			),
			array(
				'tag'         => 'site_url',
				'description' => 'URL base do site',
				'context'     => 'global',
				'source'      => 'wordpress',
				'example'     => 'https://apollo.rio.br',
			),
			array(
				'tag'         => 'current_year',
				'description' => 'Ano atual (rodapé)',
				'context'     => 'global',
				'source'      => 'system',
				'example'     => '2026',
			),
			array(
				'tag'         => 'brand_color',
				'description' => 'Cor principal da marca (#hex)',
				'context'     => 'global',
				'source'      => 'apollo-email settings',
				'example'     => 'FF9820',
			),
			array(
				'tag'         => 'footer_text',
				'description' => 'Texto do rodapé do e-mail',
				'context'     => 'global',
				'source'      => 'apollo-email settings',
				'example'     => '© 2026 Apollo Rio',
			),
			array(
				'tag'         => 'footer_address',
				'description' => 'Endereço físico no rodapé',
				'context'     => 'global',
				'source'      => 'apollo-email settings',
				'example'     => 'Rio de Janeiro, RJ — Brasil',
			),
			array(
				'tag'         => 'unsubscribe_url',
				'description' => 'Link para cancelar recebimento de e-mails',
				'context'     => 'global',
				'source'      => 'apollo-email',
				'example'     => 'https://apollo.rio.br/?apollo_unsubscribe=TOKEN',
			),
			array(
				'tag'         => 'preferences_url',
				'description' => 'Link para preferências de e-mail do usuário',
				'context'     => 'global',
				'source'      => 'apollo-email',
				'example'     => 'https://apollo.rio.br/?apollo_email_prefs=1',
			),

			// ── Usuário ────────────────────────────────────────────────
			array(
				'tag'         => 'user_name',
				'description' => 'Nome de exibição do usuário (social name ou display name)',
				'context'     => 'user',
				'source'      => 'apollo-users · _apollo_social_name',
				'example'     => 'Rafael Costa',
			),
			array(
				'tag'         => 'username',
				'description' => 'Login / @handle do usuário',
				'context'     => 'user',
				'source'      => 'wordpress · user_login',
				'example'     => 'rafaelcosta',
			),
			array(
				'tag'         => 'user_email',
				'description' => 'Endereço de e-mail do usuário',
				'context'     => 'user',
				'source'      => 'wordpress · user_email',
				'example'     => 'rafael@example.com',
			),
			array(
				'tag'         => 'profile_url',
				'description' => 'URL do perfil público do usuário (/id/username)',
				'context'     => 'user',
				'source'      => 'apollo-users',
				'example'     => 'https://apollo.rio.br/id/rafaelcosta',
			),
			array(
				'tag'         => 'plan_name',
				'description' => 'Nome do plano de membership do usuário',
				'context'     => 'user',
				'source'      => 'apollo-membership · _apollo_membership',
				'example'     => 'Apollo Gratuito',
			),
			array(
				'tag'         => 'member_since',
				'description' => 'Data de início de membro (registro)',
				'context'     => 'user',
				'source'      => 'wordpress · user_registered',
				'example'     => '13/03/2026',
			),
			array(
				'tag'         => 'registration_date',
				'description' => 'Data de cadastro formatada',
				'context'     => 'user',
				'source'      => 'wordpress · user_registered',
				'example'     => '13/03/2026',
			),

			// ── Auth / Segurança ───────────────────────────────────────
			array(
				'tag'         => 'verify_url',
				'description' => 'URL de verificação de e-mail (token único)',
				'context'     => 'auth',
				'source'      => 'apollo-login · _apollo_verification_token',
				'example'     => 'https://apollo.rio.br/verificar-email?token=ABC123',
			),
			array(
				'tag'         => 'confirmation_link',
				'description' => 'Link de confirmação de conta (alias de verify_url)',
				'context'     => 'auth',
				'source'      => 'apollo-login · _apollo_verification_token',
				'example'     => 'https://apollo.rio.br/verificar-email?token=ABC123',
			),
			array(
				'tag'         => 'verification_code',
				'description' => 'Código numérico de verificação (6 dígitos)',
				'context'     => 'auth',
				'source'      => 'apollo-login · _apollo_verification_token',
				'example'     => '847259',
			),
			array(
				'tag'         => 'reset_url',
				'description' => 'URL de redefinição de senha',
				'context'     => 'auth',
				'source'      => 'apollo-login · reset token',
				'example'     => 'https://apollo.rio.br/reset?key=XYZ&login=rafaelcosta',
			),
			array(
				'tag'         => 'expires_in',
				'description' => 'Validade do link/código (ex: "48 horas")',
				'context'     => 'auth',
				'source'      => 'apollo-login',
				'example'     => '48 horas',
			),
			array(
				'tag'         => 'expiration_time',
				'description' => 'Tempo de expiração do link (alias de expires_in)',
				'context'     => 'auth',
				'source'      => 'apollo-login',
				'example'     => '48 horas',
			),

			// ── Notificação / Genérico ─────────────────────────────────
			array(
				'tag'         => 'title',
				'description' => 'Título principal do e-mail',
				'context'     => 'notification',
				'source'      => 'caller data',
				'example'     => 'Nova mensagem recebida',
			),
			array(
				'tag'         => 'message',
				'description' => 'Corpo da mensagem principal',
				'context'     => 'notification',
				'source'      => 'caller data',
				'example'     => 'Você recebeu uma nova mensagem de João.',
			),
			array(
				'tag'         => 'action_url',
				'description' => 'URL do botão de ação (CTA)',
				'context'     => 'notification',
				'source'      => 'caller data',
				'example'     => 'https://apollo.rio.br/mensagens/42',
			),
			array(
				'tag'         => 'action_text',
				'description' => 'Texto do botão de ação (CTA)',
				'context'     => 'notification',
				'source'      => 'caller data',
				'example'     => 'Ver mensagem',
			),

			// ── Evento ────────────────────────────────────────────────
			array(
				'tag'         => 'event_title',
				'description' => 'Título do evento',
				'context'     => 'event',
				'source'      => 'apollo-events · post_title',
				'example'     => 'Noite de Drum & Bass — Lapa',
			),
			array(
				'tag'         => 'event_url',
				'description' => 'URL da página do evento',
				'context'     => 'event',
				'source'      => 'apollo-events · get_permalink()',
				'example'     => 'https://apollo.rio.br/evento/noite-drum-bass-lapa',
			),
			array(
				'tag'         => 'event_date',
				'description' => 'Data do evento (formatada)',
				'context'     => 'event',
				'source'      => 'apollo-events · _event_date',
				'example'     => 'sábado, 14 de março de 2026',
			),
			array(
				'tag'         => 'event_time',
				'description' => 'Horário do evento',
				'context'     => 'event',
				'source'      => 'apollo-events · _event_time',
				'example'     => '23:00',
			),
			array(
				'tag'         => 'event_location',
				'description' => 'Nome do local do evento',
				'context'     => 'event',
				'source'      => 'apollo-events · _event_loc_name',
				'example'     => 'Rio Scenarium',
			),
			array(
				'tag'         => 'loc_name',
				'description' => 'Nome do local (alias de event_location)',
				'context'     => 'event',
				'source'      => 'apollo-events · _event_loc_name',
				'example'     => 'Rio Scenarium',
			),

			// ── Digest ────────────────────────────────────────────────
			array(
				'tag'         => 'digest_title',
				'description' => 'Título da seção do digest',
				'context'     => 'digest',
				'source'      => 'apollo-email digest engine',
				'example'     => 'Digest de Notificações',
			),
			array(
				'tag'         => 'digest_intro',
				'description' => 'Texto de introdução do digest',
				'context'     => 'digest',
				'source'      => 'apollo-email digest engine',
				'example'     => 'Aqui está o que aconteceu esta semana:',
			),

			// ── Gestor / Tarefas ──────────────────────────────────────
			array(
				'tag'         => 'task_name',
				'description' => 'Nome/título da tarefa',
				'context'     => 'gestor',
				'source'      => 'apollo-gestor · apollo_gestor_tasks.title',
				'example'     => 'Finalizar arte do flyer',
			),
			array(
				'tag'         => 'task_date',
				'description' => 'Data de prazo da tarefa (dd/mm/yyyy)',
				'context'     => 'gestor',
				'source'      => 'apollo-gestor · apollo_gestor_tasks.due_date',
				'example'     => '15/03/2026',
			),
			array(
				'tag'         => 'task_deadline_label',
				'description' => 'Label relativo ao prazo (Hoje, Amanhã, em 3 dias, Atrasado)',
				'context'     => 'gestor',
				'source'      => 'apollo-gestor · TaskReminderCron::deadlineLabel()',
				'example'     => 'Amanhã',
			),
			array(
				'tag'         => 'task_priority',
				'description' => 'Prioridade da tarefa (Urgent, High, Medium, Low)',
				'context'     => 'gestor',
				'source'      => 'apollo-gestor · apollo_gestor_tasks.priority',
				'example'     => 'High',
			),
			array(
				'tag'         => 'task_project',
				'description' => 'Nome do projeto (evento) vinculado à tarefa',
				'context'     => 'gestor',
				'source'      => 'apollo-gestor · event CPT post_title',
				'example'     => 'Noite de Drum & Bass',
			),
			array(
				'tag'         => 'task_project_url',
				'description' => 'URL do projeto vinculado',
				'context'     => 'gestor',
				'source'      => 'apollo-gestor · get_permalink(event)',
				'example'     => 'https://apollo.rio.br/evento/noite-dnb',
			),
			array(
				'tag'         => 'task_assigned_by',
				'description' => 'Nome de quem atribuiu a tarefa',
				'context'     => 'gestor',
				'source'      => 'apollo-gestor · created_by user display_name',
				'example'     => 'Valle',
			),
			array(
				'tag'         => 'assigned_by_url',
				'description' => 'URL do perfil de quem atribuiu',
				'context'     => 'gestor',
				'source'      => 'apollo-gestor · /id/{user_login}',
				'example'     => 'https://apollo.rio.br/id/valle',
			),
			array(
				'tag'         => 'task_url',
				'description' => 'URL direta para abrir a tarefa no gestor',
				'context'     => 'gestor',
				'source'      => 'apollo-gestor · /gestor/?event=X&task=Y',
				'example'     => 'https://apollo.rio.br/gestor/?event=42&task=7',
			),
		);
	}

	/**
	 * Return all tags grouped by context category.
	 *
	 * @return array<string, array>
	 */
	public static function getTagsByContext(): array {
		$grouped = array();
		foreach ( self::getAllTags() as $tag ) {
			$grouped[ $tag['context'] ][] = $tag;
		}
		return $grouped;
	}

	/**
	 * Scan content string and return all {{tag}} names found therein.
	 *
	 * @param string $content HTML or text content.
	 * @return string[]
	 */
	public static function extractFromContent( string $content ): array {
		preg_match_all( '/\{\{(\w+)\}\}/', $content, $matches );
		return array_unique( $matches[1] ?? array() );
	}

	/**
	 * Resolve all known tags to real values for a given WP user.
	 *
	 * Returns a flat key→value array ready to merge into $data before rendering.
	 *
	 * @param int   $user_id WP user ID.
	 * @param array $data    Caller-supplied data (will not overwrite existing keys).
	 * @return array
	 */
	public static function resolveForUser( int $user_id, array $data = array() ): array {
		if ( $user_id <= 0 ) {
			return $data;
		}

		$user = get_userdata( $user_id );
		if ( ! $user instanceof \WP_User ) {
			return $data;
		}

		// ── User identity ────────────────────────────────────────────
		$social_name = get_user_meta( $user_id, '_apollo_social_name', true );
		$display     = ! empty( $social_name ) ? $social_name : $user->display_name;

		$defaults = array(
			'user_name'    => $display,
			'username'     => $user->user_login,
			'user_email'   => $user->user_email,
			'email'        => $user->user_email,
			'profile_url'  => home_url( '/id/' . rawurlencode( $user->user_login ) ),
		);

		// ── Plan / membership ────────────────────────────────────────
		$membership = get_user_meta( $user_id, '_apollo_membership', true );
		if ( empty( $data['plan_name'] ) ) {
			$defaults['plan_name'] = ! empty( $membership['plan'] )
				? sanitize_text_field( $membership['plan'] )
				: __( 'Apollo Gratuito', 'apollo-email' );
		}

		// ── Dates ────────────────────────────────────────────────────
		$reg_date = ! empty( $user->user_registered )
			? wp_date( 'd/m/Y', strtotime( $user->user_registered ) )
			: '';

		$defaults['member_since']      = $reg_date;
		$defaults['registration_date'] = $reg_date;

		// ── Verification token / code ────────────────────────────────
		if ( empty( $data['verify_url'] ) && empty( $data['confirmation_link'] ) ) {
			$token = get_user_meta( $user_id, '_apollo_verification_token', true );
			if ( ! empty( $token ) ) {
				$vurl = add_query_arg(
					array(
						'apollo_verify' => '1',
						'token'         => $token,
						'user'          => $user_id,
					),
					home_url( '/verificar-email' )
				);
				$defaults['verify_url']        = $vurl;
				$defaults['confirmation_link'] = $vurl;

				// Derive 6-digit numeric code from token (last 6 digits of sha1)
				$defaults['verification_code'] = strtoupper( substr( sha1( $token ), -6 ) );
			}
		}

		// ── Expiry fallback ──────────────────────────────────────────
		if ( empty( $data['expires_in'] ) ) {
			$defaults['expires_in']       = __( '48 horas', 'apollo-email' );
			$defaults['expiration_time']  = __( '48 horas', 'apollo-email' );
		}

		// Caller $data takes priority over resolved defaults.
		return array_merge( $defaults, $data );
	}
}
