<?php
/**
 * Depoimento — rendering helpers, query layer, template callbacks.
 *
 * Uses native WP comments but renders with Apollo's testimonial card
 * design, pulling avatar, membership badge, and user groups ("núcleos").
 *
 * @package Apollo\Comment
 */

namespace Apollo\Comment;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Depoimento {

	/**
	 * Register hooks.
	 */
	public static function init(): void {
		// Override default callback for wp_list_comments()
		add_filter( 'wp_list_comments_args', array( __CLASS__, 'override_comment_callback' ) );

		// Override comment form defaults
		add_filter( 'comment_form_defaults', array( __CLASS__, 'override_form_defaults' ) );

		// Add extra data to each comment (badge, groups)
		add_filter( 'get_comment_author', array( __CLASS__, 'enrich_comment_author' ), 10, 3 );

		// Allowed post types filter
		add_filter(
			'apollo/depoimento/allowed_post_types',
			function ( array $types ): array {
				return $types;
			}
		);
	}

	/**
	 * ── Override wp_list_comments callback ──
	 *
	 * @param array $args Default arguments.
	 * @return array
	 */
	public static function override_comment_callback( array $args ): array {
		$args['callback'] = array( __CLASS__, 'render_depoimento_card' );
		$args['style']    = 'div';
		return $args;
	}

	/**
	 * ── Render a single depoimento card ──
	 *
	 * This is the callback used by wp_list_comments(). It opens the
	 * container — WordPress closes it automatically.
	 *
	 * @param \WP_Comment $comment Comment object.
	 * @param array       $args    Display arguments.
	 * @param int         $depth   Nesting depth.
	 */
	public static function render_depoimento_card( \WP_Comment $comment, array $args, int $depth ): void {
		$user_id = (int) $comment->user_id;

		// Avatar URL
		$avatar_url = self::get_avatar_url( $user_id, $comment->comment_author_email );

		// Display name
		$display_name = $user_id
			? ( get_userdata( $user_id )->display_name ?? $comment->comment_author )
			: $comment->comment_author;

		// Membership badge HTML
		$badge_html = self::get_badge_html( $user_id );

		// User groups (núcleos)
		$groups     = self::get_user_groups( $user_id );
		$groups_str = ! empty( $groups ) ? implode( ' · ', wp_list_pluck( $groups, 'name' ) ) : '';

		// Profile URL
		$profile_url = $user_id ? home_url( '/id/' . get_userdata( $user_id )->user_login ) : '';

		// Date
		$date = wp_date( 'j M Y, H:i', strtotime( $comment->comment_date ) );

		// Approved?
		$is_pending = ( '0' === $comment->comment_approved );
		?>
		<div id="depoimento-<?php echo esc_attr( $comment->comment_ID ); ?>"
			class="depoimento-card<?php echo $is_pending ? ' depoimento--pending' : ''; ?>"
			data-depoimento-id="<?php echo esc_attr( $comment->comment_ID ); ?>">

			<!-- Avatar -->
			<div class="depoimento-avatar">
				<?php if ( $profile_url ) : ?>
					<a href="<?php echo esc_url( $profile_url ); ?>">
						<img src="<?php echo esc_url( $avatar_url ); ?>"
							alt="<?php echo esc_attr( $display_name ); ?>"
							class="depoimento-avatar-img" loading="lazy">
					</a>
				<?php else : ?>
					<img src="<?php echo esc_url( $avatar_url ); ?>"
						alt="<?php echo esc_attr( $display_name ); ?>"
						class="depoimento-avatar-img" loading="lazy">
				<?php endif; ?>
			</div>

			<!-- Content -->
			<div class="depoimento-body">
				<p class="depoimento-text"><?php echo wp_kses_post( $comment->comment_content ); ?></p>

				<div class="depoimento-meta">
					<span class="depoimento-author">
						<?php if ( $profile_url ) : ?>
							<a href="<?php echo esc_url( $profile_url ); ?>"><?php echo esc_html( $display_name ); ?></a>
						<?php else : ?>
							<?php echo esc_html( $display_name ); ?>
						<?php endif; ?>
						<?php echo $badge_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — pre-escaped ?>
					</span>

					<?php if ( $groups_str ) : ?>
						<span class="depoimento-groups"><?php echo esc_html( $groups_str ); ?></span>
					<?php endif; ?>

					<span class="depoimento-date"><?php echo esc_html( $date ); ?></span>
				</div>

				<?php if ( $is_pending ) : ?>
					<p class="depoimento-pending-notice">
						<i class="ri-time-line"></i> Seu depoimento está aguardando moderação.
					</p>
				<?php endif; ?>

				<?php
				// Reply link
				comment_reply_link(
					array_merge(
						$args,
						array(
							'reply_text' => '<i class="ri-reply-line"></i> Responder',
							'depth'      => $depth,
							'max_depth'  => $args['max_depth'] ?? 3,
							'before'     => '<div class="depoimento-reply">',
							'after'      => '</div>',
						)
					)
				);
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * ── Override comment form HTML ──
	 *
	 * @param array $defaults Default form arguments.
	 * @return array
	 */
	public static function override_form_defaults( array $defaults ): array {
		$defaults['title_reply']          = '<i class="ri-chat-quote-line"></i> Deixe um Depoimento';
		$defaults['title_reply_to']       = '<i class="ri-chat-quote-line"></i> Responder a %s';
		$defaults['label_submit']         = 'Enviar Depoimento';
		$defaults['comment_notes_before'] = '';
		$defaults['comment_notes_after']  = '';
		$defaults['class_form']           = 'depoimento-form';
		$defaults['class_submit']         = 'depoimento-submit-btn';

		// Textarea field
		$defaults['comment_field'] = '<div class="depoimento-field">'
			. '<textarea id="comment" name="comment" class="apollo-textarea depoimento-textarea" '
			. 'placeholder="Escreva seu depoimento…" rows="4" maxlength="1000" required></textarea>'
			. '</div>';

		return $defaults;
	}

	/**
	 * ── Avatar URL — uses Apollo helper with fallback ──
	 *
	 * @param int    $user_id User ID (0 for guests).
	 * @param string $email   Commenter email.
	 * @return string
	 */
	public static function get_avatar_url( int $user_id, string $email = '' ): string {
		if ( $user_id && function_exists( 'apollo_get_user_avatar_url' ) ) {
			return apollo_get_user_avatar_url( $user_id, 'thumb' );
		}
		return (string) get_avatar_url( $email ?: $user_id, array( 'size' => 96 ) );
	}

	/**
	 * ── Badge HTML — uses Apollo membership helper ──
	 *
	 * @param int $user_id User ID.
	 * @return string HTML or empty.
	 */
	public static function get_badge_html( int $user_id ): string {
		if ( $user_id && function_exists( 'apollo_get_membership_badge_html' ) ) {
			return apollo_get_membership_badge_html( $user_id, 'xs' );
		}
		return '';
	}

	/**
	 * ── User groups (núcleos) ──
	 *
	 * @param int $user_id User ID.
	 * @return array Array of groups [ { name, slug, ... }, ... ]
	 */
	public static function get_user_groups( int $user_id ): array {
		if ( $user_id && function_exists( 'apollo_get_user_groups' ) ) {
			return apollo_get_user_groups( $user_id, 5 );
		}
		return array();
	}

	/**
	 * ── Query depoimentos for a post ──
	 *
	 * @param int    $post_id Post ID.
	 * @param int    $limit   Max depoimentos.
	 * @param int    $offset  Offset.
	 * @param string $order   ASC or DESC.
	 * @return array { depoimentos: WP_Comment[], total: int }
	 */
	public static function query( int $post_id, int $limit = 10, int $offset = 0, string $order = 'DESC' ): array {
		$args = array(
			'post_id' => $post_id,
			'status'  => 'approve',
			'number'  => $limit,
			'offset'  => $offset,
			'orderby' => 'comment_date',
			'order'   => $order,
			'type'    => 'comment',
		);

		$args = apply_filters( 'apollo/depoimento/query_args', $args, $post_id );

		$comments = get_comments( $args );

		$count_args = $args;
		unset( $count_args['number'], $count_args['offset'] );
		$count_args['count'] = true;
		$total               = (int) get_comments( $count_args );

		return array(
			'depoimentos' => $comments,
			'total'       => $total,
		);
	}

	/**
	 * ── Enrich comment author (not HTML, just name) ──
	 *
	 * @param string $author     Comment author name.
	 * @param int    $comment_id Comment ID.
	 * @param object $comment    Comment object.
	 * @return string
	 */
	public static function enrich_comment_author( string $author, $comment_id, $comment ): string {
		// Keep original — enrichment happens in render_depoimento_card
		return $author;
	}

	/**
	 * ── Prepare depoimento data for REST API ──
	 *
	 * @param \WP_Comment $comment Comment object.
	 * @return array
	 */
	public static function prepare_for_rest( \WP_Comment $comment ): array {
		$user_id = (int) $comment->user_id;

		// Get complete user display data (name, badges, memberships, núcleos, handle, time)
		if ( $user_id && function_exists( 'apollo_get_user_display_data' ) ) {
			$user_data = apollo_get_user_display_data( $user_id );
			$author    = array(
				'id'           => $user_id,
				'name'         => $user_data['display_name'] ?? $comment->comment_author,
				'handle'       => $user_data['handle'] ?? '',
				'avatar'       => $user_data['avatar_url'] ?? self::get_avatar_url( $user_id, $comment->comment_author_email ),
				'badge'        => $user_data['badge'] ?? null,
				'membership'   => $user_data['membership'] ?? array(),
				'nucleos'      => $user_data['nucleos'] ?? array(),
				'member_for'   => $user_data['member_for'] ?? '',
				'member_since' => $user_data['member_since'] ?? '',
				'profile_url'  => $user_data['profile_url'] ?? '',
			);
		} else {
			// Fallback for non-logged users or missing function
			$groups = self::get_user_groups( $user_id );
			$author = array(
				'id'          => $user_id,
				'name'        => $comment->comment_author,
				'avatar'      => self::get_avatar_url( $user_id, $comment->comment_author_email ),
				'badge'       => $user_id && function_exists( 'apollo_get_user_badge_data' )
					? apollo_get_user_badge_data( $user_id )
					: null,
				'groups'      => array_map(
					function ( $g ) {
						return array(
							'name' => $g['name'] ?? '',
							'slug' => $g['slug'] ?? '',
						);
					},
					$groups
				),
				'profile_url' => $user_id
					? home_url( '/id/' . ( get_userdata( $user_id )->user_login ?? '' ) )
					: '',
			);
		}

		return array(
			'id'       => (int) $comment->comment_ID,
			'post_id'  => (int) $comment->comment_post_ID,
			'parent'   => (int) $comment->comment_parent,
			'author'   => $author,
			'content'  => wp_kses_post( $comment->comment_content ),
			'date'     => $comment->comment_date,
			'date_gmt' => $comment->comment_date_gmt,
			'status'   => $comment->comment_approved,
		);
	}
}