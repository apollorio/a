<?php
/**
 * Apollo Radar - Usage Examples
 *
 * Examples of how to use UserRadar helper class.
 *
 * P3: NOT loaded by production bootstrap. To load intentionally:
 *   define( 'APOLLO_LOAD_TEMPLATE_EXAMPLES', true );
 * before plugins load (e.g. wp-config.php for local demos only).
 *
 * @package Apollo
 * @subpackage Templates\Examples
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Apollo\Templates\Helpers\UserRadar;

// Hard stop: never register example REST/cron/admin hooks in production.
// Enable only via: define( 'APOLLO_LOAD_TEMPLATE_EXAMPLES', true );
if ( ! defined( 'APOLLO_LOAD_TEMPLATE_EXAMPLES' ) || ! APOLLO_LOAD_TEMPLATE_EXAMPLES ) {
	return;
}

// ============================================
// BASIC USAGE (demo only — not production)
// ============================================

// Update user badges
UserRadar::update_badges(
	1,
	array(
		'verified' => true,
		'team'     => true,
		'cenario'  => false,
	)
);

// Update user ranking
UserRadar::update_ranking( 1, 1 );

// Get user badges
$badges = UserRadar::get_badges( 1 );
// Returns: ['verified' => true, 'team' => true, 'cenario' => false]

// Check specific badge
if ( UserRadar::has_badge( 1, 'verified' ) ) {
	echo 'User is verified!';
}

// Get user ranking
$ranking = UserRadar::get_ranking( 1 );
echo "User is ranked #{$ranking}";

// ============================================
// ADVANCED USAGE
// ============================================

// Recalculate all rankings (run daily via cron)
$updated = UserRadar::recalculate_rankings();
echo "Updated {$updated} user rankings";

// Get top 10 users
$top_users = UserRadar::get_top_users( 10 );
foreach ( $top_users as $user ) {
	echo "{$user->display_name} - Ranking: " . UserRadar::get_ranking( $user->ID );
}

// Get all verified users
$verified_users = UserRadar::get_users_by_badge( 'verified' );
echo 'Total verified: ' . count( $verified_users );

// Get Apollo team members
$team_members = UserRadar::get_users_by_badge( 'team' );

// Get badge statistics
$stats = UserRadar::get_badge_stats();
print_r( $stats );
/*
Returns:
[
	'verified' => 5,
	'team' => 2,
	'cenario' => 3,
	'total_users' => 150
]
*/

// ============================================
// BULK OPERATIONS
// ============================================

// Verify multiple users at once
$user_ids = array( 1, 2, 3, 4, 5 );
UserRadar::bulk_assign_badge( $user_ids, 'verified', true );

// Remove team badge from users
UserRadar::bulk_assign_badge( array( 6, 7 ), 'team', false );

// Clear all radar meta for a user
UserRadar::clear_user_meta( 10 );

// ============================================
// WordPress CRON INTEGRATION
// ============================================

/**
 * Schedule daily ranking recalculation
 */
function apollo_schedule_ranking_update(): void {
	if ( ! wp_next_scheduled( 'apollo_daily_ranking_update' ) ) {
		wp_schedule_event( time(), 'daily', 'apollo_daily_ranking_update' );
	}
}
add_action( 'wp', 'apollo_schedule_ranking_update' );

/**
 * Execute ranking recalculation
 */
function apollo_execute_ranking_update(): void {
	UserRadar::recalculate_rankings();
}
add_action( 'apollo_daily_ranking_update', 'apollo_execute_ranking_update' );

// ============================================
// ADMIN ACTIONS
// ============================================

/**
 * Add quick action to verify user from admin
 */
function apollo_add_verify_user_action( $actions, $user_object ): array {
	$is_verified = UserRadar::has_badge( $user_object->ID, 'verified' );

	if ( ! $is_verified ) {
		$actions['verify'] = sprintf(
			'<a href="%s">%s</a>',
			wp_nonce_url(
				add_query_arg(
					array(
						'action'  => 'verify_user',
						'user_id' => $user_object->ID,
					)
				),
				'verify_user_' . $user_object->ID
			),
			__( 'Verificar', 'apollo' )
		);
	} else {
		$actions['unverify'] = sprintf(
			'<a href="%s">%s</a>',
			wp_nonce_url(
				add_query_arg(
					array(
						'action'  => 'unverify_user',
						'user_id' => $user_object->ID,
					)
				),
				'unverify_user_' . $user_object->ID
			),
			__( 'Remover Verificação', 'apollo' )
		);
	}

	return $actions;
}
add_filter( 'user_row_actions', 'apollo_add_verify_user_action', 10, 2 );

/**
 * Handle verify/unverify actions
 */
function apollo_handle_user_verification(): void {
	if ( ! isset( $_GET['action'] ) || ! isset( $_GET['user_id'] ) ) {
		return;
	}

	$user_id = (int) $_GET['user_id'];
	$action  = sanitize_text_field( $_GET['action'] );

	if ( $action === 'verify_user' ) {
		check_admin_referer( 'verify_user_' . $user_id );
		UserRadar::update_badges( $user_id, array( 'verified' => true ) );
		wp_redirect( add_query_arg( 'verified', '1', admin_url( 'users.php' ) ) );
		exit;
	}

	if ( $action === 'unverify_user' ) {
		check_admin_referer( 'unverify_user_' . $user_id );
		UserRadar::update_badges( $user_id, array( 'verified' => false ) );
		wp_redirect( add_query_arg( 'unverified', '1', admin_url( 'users.php' ) ) );
		exit;
	}
}
add_action( 'admin_init', 'apollo_handle_user_verification' );

// ============================================
// REST API ENDPOINTS
// ============================================

/**
 * Register custom REST endpoint for radar stats
 */
function apollo_register_radar_endpoints(): void {
	register_rest_route(
		'apollo/v1',
		'/radar/stats',
		array(
			'methods'             => 'GET',
			'callback'            => 'apollo_get_radar_stats',
			'permission_callback' => '__return_true',
		)
	);

	register_rest_route(
		'apollo/v1',
		'/radar/top',
		array(
			'methods'             => 'GET',
			'callback'            => 'apollo_get_top_users_api',
			'permission_callback' => '__return_true',
			'args'                => array(
				'limit' => array(
					'default'           => 10,
					'sanitize_callback' => 'absint',
				),
			),
		)
	);
}
add_action( 'rest_api_init', 'apollo_register_radar_endpoints' );

/**
 * Get radar statistics
 */
function apollo_get_radar_stats( \WP_REST_Request $request ): \WP_REST_Response {
	$stats = UserRadar::get_badge_stats();

	return new \WP_REST_Response(
		array(
			'success' => true,
			'data'    => $stats,
		)
	);
}

/**
 * Get top users via API
 */
function apollo_get_top_users_api( \WP_REST_Request $request ): \WP_REST_Response {
	$limit = $request->get_param( 'limit' );
	$users = UserRadar::get_top_users( $limit );

	$formatted_users = array_map(
		function ( $user ) {
			return array(
				'id'      => $user->ID,
				'name'    => $user->display_name,
				'avatar'  => get_avatar_url( $user->ID ),
				'ranking' => UserRadar::get_ranking( $user->ID ),
				'badges'  => UserRadar::get_badges( $user->ID ),
				'profile' => get_author_posts_url( $user->ID ),
			);
		},
		$users
	);

	return new \WP_REST_Response(
		array(
			'success' => true,
			'data'    => $formatted_users,
		)
	);
}

// ============================================
// SHORTCODES
// ============================================

/**
 * Shortcode: [apollo_user_badge user_id="1" badge="verified"]
 */
function apollo_user_badge_shortcode( $atts ): string {
	$atts = shortcode_atts(
		array(
			'user_id' => get_current_user_id(),
			'badge'   => 'verified',
		),
		$atts
	);

	$user_id = (int) $atts['user_id'];
	$badge   = sanitize_text_field( $atts['badge'] );

	if ( ! UserRadar::has_badge( $user_id, $badge ) ) {
		return '';
	}

	$icons = array(
		'verified' => '<i class="ri-shield-check-fill"></i> Verificado',
		'team'     => '<i class="i-apollo-fill"></i> Apollo Team',
		'cenario'  => '<i class="ri-disc-line"></i> Cena::rio',
	);

	return $icons[ $badge ] ?? '';
}
add_shortcode( 'apollo_user_badge', 'apollo_user_badge_shortcode' );

/**
 * Shortcode: [apollo_user_ranking user_id="1"]
 */
function apollo_user_ranking_shortcode( $atts ): string {
	$atts = shortcode_atts(
		array(
			'user_id' => get_current_user_id(),
		),
		$atts
	);

	$user_id = (int) $atts['user_id'];
	$ranking = UserRadar::get_ranking( $user_id );

	if ( ! $ranking ) {
		return '';
	}

	return '<span class="apollo-ranking">#' . $ranking . '</span>';
}
add_shortcode( 'apollo_user_ranking', 'apollo_user_ranking_shortcode' );

// ============================================
// WIDGET EXAMPLE
// ============================================

/**
 * Widget: Top 10 Users
 */
class Apollo_Top_Users_Widget extends \WP_Widget {

	public function __construct() {
		parent::__construct(
			'apollo_top_users',
			'Apollo - Top Usuários',
			array( 'description' => 'Exibe os usuários com melhor ranking' )
		);
	}

	public function widget( $args, $instance ): void {
		$limit = $instance['limit'] ?? 10;
		$users = UserRadar::get_top_users( $limit );

		echo $args['before_widget'];
		echo $args['before_title'] . 'Top Usuários' . $args['after_title'];

		echo '<ul class="apollo-top-users">';
		foreach ( $users as $user ) {
			$ranking     = UserRadar::get_ranking( $user->ID );
			$profile_url = get_author_posts_url( $user->ID );

			printf(
				'<li><a href="%s">#%d - %s</a></li>',
				esc_url( $profile_url ),
				$ranking,
				esc_html( $user->display_name )
			);
		}
		echo '</ul>';

		echo $args['after_widget'];
	}

	public function form( $instance ): void {
		$limit = $instance['limit'] ?? 10;
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'limit' ); ?>">
				Número de usuários:
			</label>
			<input
				type="number"
				id="<?php echo $this->get_field_id( 'limit' ); ?>"
				name="<?php echo $this->get_field_name( 'limit' ); ?>"
				value="<?php echo esc_attr( $limit ); ?>"
				min="1"
				max="50"
			/>
		</p>
		<?php
	}

	public function update( $new_instance, $old_instance ): array {
		return array(
			'limit' => absint( $new_instance['limit'] ),
		);
	}
}

function apollo_register_top_users_widget(): void {
	register_widget( 'Apollo_Top_Users_Widget' );
}
add_action( 'widgets_init', 'apollo_register_top_users_widget' );

// ============================================
// NOTIFICATIONS INTEGRATION
// ============================================

/**
 * Send notification when user gets verified
 */
function apollo_notify_user_verified( int $user_id, array $badges ): void {
	if ( ! isset( $badges['verified'] ) || ! $badges['verified'] ) {
		return;
	}

	// Check if user wasn't verified before
	$was_verified = UserRadar::has_badge( $user_id, 'verified' );

	if ( ! $was_verified && class_exists( '\\Apollo\\Notif\\NotificationManager' ) ) {
		\Apollo\Notif\NotificationManager::send(
			$user_id,
			'verified',
			array(
				'title'   => 'Parabéns! Você foi verificadx!',
				'message' => 'Seu perfil agora tem o selo de verificação Apollo.',
				'link'    => get_author_posts_url( $user_id ),
			)
		);
	}
}
add_action( 'apollo/radar/badges_updated', 'apollo_notify_user_verified', 10, 2 );

/**
 * Send notification when user ranks up
 */
function apollo_notify_ranking_updated( int $user_id, int $new_position ): void {
	$old_position = (int) get_transient( 'apollo_ranking_' . $user_id );

	if ( $old_position && $new_position < $old_position ) {
		// User improved ranking
		$improvement = $old_position - $new_position;

		if ( class_exists( '\\Apollo\\Notif\\NotificationManager' ) ) {
			\Apollo\Notif\NotificationManager::send(
				$user_id,
				'ranking_up',
				array(
					'title'   => 'Você subiu no ranking!',
					'message' => "Parabéns! Você subiu {$improvement} posições e agora está em #{$new_position}",
					'link'    => home_url( '/radar-de-usuarios/' ),
				)
			);
		}
	}

	set_transient( 'apollo_ranking_' . $user_id, $new_position, WEEK_IN_SECONDS );
}
add_action( 'apollo/radar/ranking_updated', 'apollo_notify_ranking_updated', 10, 2 );