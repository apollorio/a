<?php
/**
 * Apollo WOW — Main Plugin Class
 *
 * Emoji-based reaction system replacing "like" with "wow".
 *
 * Registry compliance:
 *   REST: /wows (POST), /wows/{post_id} (GET, DELETE), /wows/types (GET)
 *   Shortcodes: [apollo_wow]
 *   Tables: apollo_wow_reactions (core)
 *   Meta: _wow_count, _wow_counts
 *
 * @package Apollo\Wow
 */

declare(strict_types=1);

namespace Apollo\Wow;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Plugin {

	private static ?Plugin $instance = null;

	public static function instance(): Plugin {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
		add_action( 'init', array( $this, 'register_shortcodes' ) );
	}

	// ─── REST API ──────────────────────────────────────────────────
	public function register_rest_routes(): void {
		$ns     = 'apollo/v1';
		$logged = function () {
			return is_user_logged_in();
		};

		register_rest_route(
			$ns,
			'/wows',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'rest_toggle_wow' ),
				'permission_callback' => $logged,
			)
		);

		register_rest_route(
			$ns,
			'/wows/(?P<post_id>\d+)',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'rest_get_wows' ),
					'permission_callback' => '__return_true',
				),
				array(
					'methods'             => 'DELETE',
					'callback'            => array( $this, 'rest_remove_wow' ),
					'permission_callback' => $logged,
				),
			)
		);

		register_rest_route(
			$ns,
			'/wows/types',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'rest_get_types' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			$ns,
			'/wows/chart/(?P<post_id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'rest_get_chart' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	public function rest_toggle_wow( \WP_REST_Request $request ): \WP_REST_Response {
		$object_type   = sanitize_text_field( $request->get_param( 'object_type' ) ?? 'post' );
		$object_id     = (int) ( $request->get_param( 'object_id' ) ?? $request->get_param( 'post_id' ) ?? 0 );
		$reaction_type = sanitize_text_field( $request->get_param( 'reaction_type' ) ?? 'wow' );

		if ( ! $object_id ) {
			return new \WP_REST_Response( array( 'error' => 'ID obrigatório' ), 400 );
		}

		$result = apollo_toggle_wow( get_current_user_id(), $object_type, $object_id, $reaction_type );
		$counts = apollo_get_wow_counts( $object_type, $object_id );

		return new \WP_REST_Response( array_merge( $result, array( 'counts' => $counts ) ), 200 );
	}

	public function rest_get_wows( \WP_REST_Request $request ): \WP_REST_Response {
		$post_id     = (int) $request->get_param( 'post_id' );
		$object_type = sanitize_text_field( $request->get_param( 'object_type' ) ?? 'post' );

		$counts    = apollo_get_wow_counts( $object_type, $post_id );
		$user_wows = is_user_logged_in() ? apollo_get_user_wows( get_current_user_id(), $object_type, $post_id ) : array();

		return new \WP_REST_Response(
			array(
				'counts'    => $counts,
				'user_wows' => $user_wows,
			),
			200
		);
	}

	public function rest_remove_wow( \WP_REST_Request $request ): \WP_REST_Response {
		$post_id       = (int) $request->get_param( 'post_id' );
		$reaction_type = sanitize_text_field( $request->get_param( 'reaction_type' ) ?? 'wow' );

		$removed = apollo_remove_wow( get_current_user_id(), 'post', $post_id, $reaction_type );
		return new \WP_REST_Response( array( 'removed' => $removed ), $removed ? 200 : 404 );
	}

	public function rest_get_types(): \WP_REST_Response {
		return new \WP_REST_Response( apollo_get_wow_types(), 200 );
	}

	public function rest_get_chart( \WP_REST_Request $request ): \WP_REST_Response {
		$post_id = (int) $request->get_param( 'post_id' );
		$data    = apollo_wow_chart_data( 'post', $post_id );
		return new \WP_REST_Response( $data, 200 );
	}

	// ─── Shortcodes ─────────────────────────────────────────────────
	public function register_shortcodes(): void {
		add_shortcode( 'apollo_wow', array( $this, 'shortcode_wow' ) );
		add_shortcode( 'apollo_wow_chart', array( $this, 'shortcode_wow_chart' ) );
	}

	/**
	 * [apollo_wow post_id=123] — Renders WOW reaction buttons.
	 */
	public function shortcode_wow( array $atts ): string {
		$a       = shortcode_atts( array( 'post_id' => get_the_ID() ), $atts );
		$post_id = (int) $a['post_id'];
		if ( ! $post_id ) {
			return '';
		}

		$types     = apollo_get_wow_types();
		$counts    = apollo_get_wow_counts( 'post', $post_id );
		$user_wows = is_user_logged_in() ? apollo_get_user_wows( get_current_user_id(), 'post', $post_id ) : array();
		$rest_url  = rest_url( 'apollo/v1/wows' );
		// Only emit a live nonce to authenticated users — the REST endpoint requires login,
		// and a nonce must never be baked into guest-visible (potentially cached) HTML.
		$nonce     = is_user_logged_in() ? wp_create_nonce( 'wp_rest' ) : '';

		ob_start();
		?>
		<div class="apollo-wow-buttons" data-post-id="<?php echo $post_id; ?>" data-rest="<?php echo esc_url( $rest_url ); ?>" data-nonce="<?php echo esc_attr( $nonce ); ?>">
			<?php
			foreach ( $types as $key => $info ) :
				$active = in_array( $key, $user_wows );
				$count  = $counts['types'][ $key ] ?? 0;
				?>
			<button class="wow-btn <?php echo $active ? 'active' : ''; ?>" data-type="<?php echo esc_attr( $key ); ?>" title="<?php echo esc_attr( $info['label'] ); ?>" onclick="toggleWow(this)">
				<span class="wow-emoji"><?php echo $info['emoji']; ?></span>
				<span class="wow-count"><?php echo $count > 0 ? $count : ''; ?></span>
			</button>
			<?php endforeach; ?>
		</div>
		<style>
		.apollo-wow-buttons{display:flex;gap:4px;flex-wrap:wrap}
		.wow-btn{background:var(--card-1,#fff);border:1px solid var(--glass-border,#e2e8f0);border-radius:99px;padding:4px 10px;cursor:pointer;display:flex;align-items:center;gap:4px;font-size:.85rem;transition:all .2s}
		.wow-btn:hover{transform:scale(1.1);box-shadow:0 2px 8px rgba(var(--rgb-d),.1)}
		.wow-btn.active{border-color:var(--ap-orange-500);background:rgba(255,105,37,.08)}
		.wow-count{font-size:.75rem;color:var(--ap-text-muted);font-weight:600}
		</style>
		<script>
		function toggleWow(btn){
			if(!btn.closest('.apollo-wow-buttons'))return;
			var wrap=btn.closest('.apollo-wow-buttons');
			fetch(wrap.dataset.rest,{
				method:'POST',
				headers:{'Content-Type':'application/json','X-WP-Nonce':wrap.dataset.nonce},
				body:JSON.stringify({object_type:'post',object_id:parseInt(wrap.dataset.postId),reaction_type:btn.dataset.type}),
				credentials:'same-origin'
			}).then(r=>r.json()).then(d=>{
				btn.classList.toggle('active',d.active);
				var c=btn.querySelector('.wow-count');
				var n=d.counts?.types?.[btn.dataset.type]||0;
				if(typeof ApolloTextFX!=='undefined'&&n!==parseFloat(c.textContent||'0')){
					ApolloTextFX.counter(c,n);
				}else{
					c.textContent=n>0?n:'';
				}
			});
		}
		</script>
		<?php
		return ob_get_clean();
	}

	/**
	 * [apollo_wow_chart post_id=123] — Renders WOW bar chart in pure PHP/CSS.
	 */
	public function shortcode_wow_chart( array $atts ): string {
		$a       = shortcode_atts( array( 'post_id' => get_the_ID() ), $atts );
		$post_id = (int) $a['post_id'];
		if ( ! $post_id ) {
			return '';
		}

		$data = apollo_wow_chart_data( 'post', $post_id );
		if ( $data['total'] <= 0 ) {
			return '<p style="color:var(--ap-text-muted);font-size:.85rem;">Sem reações ainda.</p>';
		}

		ob_start();
		?>
		<div class="apollo-wow-chart" style="max-width:400px;">
			<div style="font-weight:600;margin-bottom:.75rem;">Reações (<?php echo $data['total']; ?>)</div>
			<?php
			foreach ( $data['breakdown'] as $item ) :
				$pct = $data['total'] > 0 ? round( ( $item['count'] / $data['total'] ) * 100 ) : 0;
				if ( $item['count'] <= 0 ) {
					continue;
				}
				?>
			<div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
				<span style="width:24px;text-align:center;"><?php echo $item['emoji']; ?></span>
				<div style="flex:1;height:20px;background:var(--glass-border,#e2e8f0);border-radius:4px;overflow:hidden;">
					<div style="height:100%;width:<?php echo $pct; ?>%;background:linear-gradient(90deg,var(--ap-orange-500),var(--ap-orange-600));border-radius:4px;transition:width .5s;"></div>
				</div>
				<span style="font-size:.75rem;color:var(--ap-text-muted);min-width:40px;text-align:right;"><?php echo $item['count']; ?> (<?php echo $pct; ?>%)</span>
			</div>
			<?php endforeach; ?>
		</div>
		<?php
		return ob_get_clean();
	}
}
