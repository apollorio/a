# W5 triage (coordinator)

FORBIDDEN_CONCEPTS from REG:
```json
[
    "follow button",
    "unfollow button",
    "followers count",
    "following count",
    "like button",
    "friend request",
    "friend count"
]
```

## TRUE_VIOLATION (67)
- D:\dev\_apollo.rio.br\plugins\apollo-admin\src\Settings.php:265:'enable_follow'    => array(
- D:\dev\_apollo.rio.br\plugins\apollo-admin\src\Settings\Schema\apollo-social.php:18:'enable_follow'    => array(
- D:\dev\_apollo.rio.br\plugins\apollo-admin\templates\frontend\pending.php:393:<span><?php echo esc_html(human_time_diff(\strtotime($p->post_date), current_time('timestamp'))); ?> atrás</span>
- D:\dev\_apollo.rio.br\plugins\apollo-admin\templates\partials\topbar.php:39:<button class="tab-btn" data-tab="email-unsub"><i class="ri-user-unfollow-line"></i><span class="tooltip"><?php esc_html_e('Unsubscribes', 'apollo-admin'); ?></span></button>
- D:\dev\_apollo.rio.br\plugins\apollo-admin\templates\partials\sections\email\subscribers.php:19:<div class="stat-card red"><div class="stat-icon red"><i class="ri-user-unfollow-line"></i></div><span class="stat-label"><?php esc_html_e( 'Unsubscribes', 'apollo-admin' ); ?></span><span class="stat-value">—</span></div>
- D:\dev\_apollo.rio.br\plugins\apollo-admin\templates\partials\sections\email\unsub.php:16:<div class="panel-header"><i class="ri-user-unfollow-line"></i> <?php esc_html_e( 'Unsubscribe Report', 'apollo-admin' ); ?></div>
- D:\dev\_apollo.rio.br\plugins\apollo-admin\templates\partials\sections\social\activity.php:26:<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[soc_follow]" value="1" <?php checked( $apollo['soc_follow'] ?? true ); ?>><span class="switch-track"></span></label>
- D:\dev\_apollo.rio.br\plugins\apollo-adverts\includes\cpt.php:19:* Adapted from WPAdverts adverts_register_post_type
- D:\dev\_apollo.rio.br\plugins\apollo-adverts\includes\cpt.php:42:register_post_type(
- D:\dev\_apollo.rio.br\plugins\apollo-adverts\includes\cpt.php:163:* This plugin must not call register_post_meta() directly.
- D:\dev\_apollo.rio.br\plugins\apollo-adverts\templates\parts\depoimentos.php:81:human_time_diff( strtotime( $depo->comment_date ), current_time( 'timestamp' ) )
- D:\dev\_apollo.rio.br\plugins\apollo-djs\src\Registry.php:56:register_post_type(
- D:\dev\_apollo.rio.br\plugins\apollo-djs\src\Registry.php:122:register_post_type(
- D:\dev\_apollo.rio.br\plugins\apollo-docs\src\Core\Registrar.php:31:register_post_type(
- D:\dev\_apollo.rio.br\plugins\apollo-email\src\Core\CPT.php:32:register_post_type(
- D:\dev\_apollo.rio.br\plugins\apollo-events\src\Registry.php:69:register_post_type(
- D:\dev\_apollo.rio.br\plugins\apollo-events\styles\base\template-parts\single\depoimentos.php:59:<span class="a-eve-depo__time"><?php echo esc_html( human_time_diff( strtotime( $depo->comment_date ), current_time( 'timestamp' ) ) ); ?></span>
- D:\dev\_apollo.rio.br\plugins\apollo-gestor\includes\modules\proj-board\backend\Proj_Board.php:67:$post['time_ago'] = human_time_diff( strtotime( $post['created_at'] ), current_time( 'timestamp' ) );
- D:\dev\_apollo.rio.br\plugins\apollo-hub\src\Activation.php:45:register_post_type(
- D:\dev\_apollo.rio.br\plugins\apollo-hub\src\Registry.php:58:register_post_type(
- D:\dev\_apollo.rio.br\plugins\apollo-hub\src\Registry.php:87:register_post_meta(
- D:\dev\_apollo.rio.br\plugins\apollo-hub\src\Registry.php:101:register_post_meta(
- D:\dev\_apollo.rio.br\plugins\apollo-hub\src\Registry.php:112:register_post_meta(
- D:\dev\_apollo.rio.br\plugins\apollo-hub\src\Registry.php:123:register_post_meta(
- D:\dev\_apollo.rio.br\plugins\apollo-hub\src\Registry.php:134:register_post_meta(
- D:\dev\_apollo.rio.br\plugins\apollo-hub\src\Registry.php:149:register_post_meta(
- D:\dev\_apollo.rio.br\plugins\apollo-hub\src\Registry.php:160:register_post_meta(
- D:\dev\_apollo.rio.br\plugins\apollo-hub\src\Registry.php:171:register_post_meta(
- D:\dev\_apollo.rio.br\plugins\apollo-journal\src\Plugin.php:117:register_post_type('journal_news', array(
- D:\dev\_apollo.rio.br\plugins\apollo-journal\src\Plugin.php:155:register_post_meta('journal_news', $key, array(
- D:\dev\_apollo.rio.br\plugins\apollo-journal\src\Plugin.php:168:register_post_type('journal_nota', array(
- D:\dev\_apollo.rio.br\plugins\apollo-journal\src\Plugin.php:205:register_post_meta('journal_nota', $key, array(
- D:\dev\_apollo.rio.br\plugins\apollo-journal\src\Shortcodes.php:340:<span class="aj-ng-item__time"><?php echo wp_kses_post( function_exists( 'apollo_time_ago_html' ) ? apollo_time_ago_html( $time_ago_dt ) : esc_html( human_time_diff( (int) strtotime( $time_ago_dt ), time() ) ) ); ?></span>
- D:\dev\_apollo.rio.br\plugins\apollo-journal\src\Shortcodes.php:381:<span><?php echo wp_kses_post( function_exists( 'apollo_time_ago_html' ) ? apollo_time_ago_html( $time_ago_dt ) : esc_html( human_time_diff( (int) strtotime( $time_ago_dt ), time() ) ) ); ?></span>
- D:\dev\_apollo.rio.br\plugins\apollo-journal\src\API\PostsController.php:124:: human_time_diff( get_post_time( 'U', false, $post ), time() );
- D:\dev\_apollo.rio.br\plugins\apollo-journal\src\API\PostsController.php:240:: human_time_diff( get_post_time( 'U', false, $post ), time() );
- D:\dev\_apollo.rio.br\plugins\apollo-journal\src\API\PostsController.php:309:: human_time_diff( get_post_time( 'U', false, $post ), time() );
- D:\dev\_apollo.rio.br\plugins\apollo-journal\templates\archive-journal.php:324:<span><?php echo wp_kses_post( function_exists( 'apollo_time_ago_html' ) ? apollo_time_ago_html( get_the_date( 'Y-m-d H:i:s' ) ) : esc_html( human_time_diff( get_the_time( 'U' ), time() ) ) ); ?></span>
- D:\dev\_apollo.rio.br\plugins\apollo-journal\templates\archive-journal.php:352:<span><?php echo wp_kses_post( function_exists( 'apollo_time_ago_html' ) ? apollo_time_ago_html( get_the_date( 'Y-m-d H:i:s' ) ) : esc_html( human_time_diff( get_the_time( 'U' ), time() ) ) ); ?></span>
- D:\dev\_apollo.rio.br\plugins\apollo-journal\templates\page-jornal.php:466:<span><?php echo wp_kses_post( function_exists( 'apollo_time_ago_html' ) ? apollo_time_ago_html( get_the_date( 'Y-m-d H:i:s' ) ) : esc_html( human_time_diff( get_the_time( 'U' ), time() ) ) ); ?></span>
- … +27 more

## CORE_OK (26)
- D:\dev\_apollo.rio.br\plugins\apollo-core\includes\blank-canvas-templates.php:26:* virtual-route branch forces `robots: index, follow` (wrong for private,
- D:\dev\_apollo.rio.br\plugins\apollo-core\includes\blank-canvas-templates.php:57:*     @type string $robots          Default 'index, follow'. Private panels MUST pass 'noindex, nofollow'.
- D:\dev\_apollo.rio.br\plugins\apollo-core\includes\blank-canvas-templates.php:71:'robots'         => 'index, follow',
- D:\dev\_apollo.rio.br\plugins\apollo-core\src\API\ShortcodesController.php:53:'apollo_follow_button',
- D:\dev\_apollo.rio.br\plugins\apollo-core\src\Config\ApolloRoute.php:63:public const FOLLOWERS   = '/followers';
- D:\dev\_apollo.rio.br\plugins\apollo-core\src\Config\ApolloRoute.php:64:public const FOLLOWING   = '/following';
- D:\dev\_apollo.rio.br\plugins\apollo-core\src\Config\ApolloTable.php:78:public const FOLLOWS       = 'apollo_follows';
- D:\dev\_apollo.rio.br\plugins\apollo-core\src\Config\ApolloTable.php:194:self::FOLLOWS,
- D:\dev\_apollo.rio.br\plugins\apollo-core\src\Core\ActivationHandler.php:255:$subscriber->add_cap( 'apollo_follow_users' );
- D:\dev\_apollo.rio.br\plugins\apollo-core\src\Core\CPTRegistry.php:153:* Note: WordPress forces `show_in_menu` to follow `show_ui` when the latter
- D:\dev\_apollo.rio.br\plugins\apollo-core\src\Core\CPTRegistry.php:180:register_post_type( $slug, $args );
- D:\dev\_apollo.rio.br\plugins\apollo-core\src\Core\DatabaseBuilder.php:267:'follows'             => array(
- D:\dev\_apollo.rio.br\plugins\apollo-core\src\Core\DatabaseBuilder.php:269:'table'  => $this->prefix . 'follows',
- D:\dev\_apollo.rio.br\plugins\apollo-core\src\Core\DatabaseBuilder.php:270:'sql'    => "CREATE TABLE {$this->prefix}follows (
- D:\dev\_apollo.rio.br\plugins\apollo-core\src\Core\DatabaseBuilder.php:272:follower_id BIGINT UNSIGNED NOT NULL,
- D:\dev\_apollo.rio.br\plugins\apollo-core\src\Core\DatabaseBuilder.php:273:following_id BIGINT UNSIGNED NOT NULL,
- D:\dev\_apollo.rio.br\plugins\apollo-core\src\Core\DatabaseBuilder.php:276:UNIQUE KEY follow_pair (follower_id, following_id),
- D:\dev\_apollo.rio.br\plugins\apollo-core\src\Core\DatabaseBuilder.php:277:KEY follower_id (follower_id),
- D:\dev\_apollo.rio.br\plugins\apollo-core\src\Core\DatabaseBuilder.php:278:KEY following_id (following_id)
- D:\dev\_apollo.rio.br\plugins\apollo-core\src\Core\MetaRegistry.php:2280:* - apollo_core_register_post_meta
- D:\dev\_apollo.rio.br\plugins\apollo-core\src\Core\MetaRegistry.php:2290:$post_meta = apply_filters( 'apollo_core_register_post_meta', $this->post_meta );
- D:\dev\_apollo.rio.br\plugins\apollo-core\src\Core\MetaRegistry.php:2313:$this->register_post_meta( $post_type, $key, $args );
- D:\dev\_apollo.rio.br\plugins\apollo-core\src\Core\MetaRegistry.php:2343:private function register_post_meta( string $post_type, string $key, array $args ): void {
- D:\dev\_apollo.rio.br\plugins\apollo-core\src\Core\MetaRegistry.php:2370:register_post_meta( $post_type, $key, $args );
- D:\dev\_apollo.rio.br\plugins\apollo-core\src\Core\ShortcodeRegistry.php:310:$this->register('apollo_follow_button', array(
- D:\dev\_apollo.rio.br\plugins\apollo-core\src\Core\ShortcodeRegistry.php:318:'examples' => array('[apollo_follow_button user_id="5"]'),

## FALSE_POSITIVE (47)
- D:\dev\_apollo.rio.br\plugins\apollo-admin\src\Frontend\ModeraData.php:7:* section is wired to REST in follow-up passes.
- D:\dev\_apollo.rio.br\plugins\apollo-admin\templates\modera\parts\head.php:11:* forces robots=index,follow — wrong for an authenticated admin panel).
- D:\dev\_apollo.rio.br\plugins\apollo-admin\templates\modera\parts\head.php:43:'robots'         => 'noindex, nofollow', // private, authenticated panel — never indexed.
- D:\dev\_apollo.rio.br\plugins\apollo-admin\templates\modera\parts\scripts.php:9:*                            section-by-section for REST in follow-ups)
- D:\dev\_apollo.rio.br\plugins\apollo-adverts\includes\integrations.php:281:' target="_blank" rel="noopener noreferrer nofollow">' .
- D:\dev\_apollo.rio.br\plugins\apollo-adverts\includes\safety-gate.php:339:* There is no Instagram endpoint that returns a follower list, so every
- D:\dev\_apollo.rio.br\plugins\apollo-adverts\src\Plugin.php:267:'robots'      => 'noindex, nofollow',
- D:\dev\_apollo.rio.br\plugins\apollo-adverts\src\Plugin.php:276:'robots'      => 'noindex, nofollow',
- D:\dev\_apollo.rio.br\plugins\apollo-adverts\src\Safety\NativeGraph.php:10:* follower list, so every "mutual friends" integration is a session scraper:
- D:\dev\_apollo.rio.br\plugins\apollo-adverts\templates\parts\safety\checks.php:37:API returns a follower list and every integration that claims to is a
- D:\dev\_apollo.rio.br\plugins\apollo-djs\includes\functions.php:360:* "known_gap" for the tracked follow-up.
- D:\dev\_apollo.rio.br\plugins\apollo-djs\styles\base\template-parts\single\sound.php:29:<a class="dj-btn dj-btn-line dj-pill" id="scFollow" href="#" target="_blank" rel="noopener"><i class="ri-soundcloud-line"></i> SoundCloud</a>
- D:\dev\_apollo.rio.br\plugins\apollo-djs\styles\base\template-parts\single\sound.php:30:<a class="dj-btn dj-btn-line dj-pill" id="bcFollow" href="#" target="_blank" rel="noopener"><i class="ri-bandcamp-line"></i> Bandcamp</a>
- D:\dev\_apollo.rio.br\plugins\apollo-djs\styles\base\template-parts\single\sound.php:31:<a class="dj-btn dj-btn-line dj-pill" id="spFollow" href="#" target="_blank" rel="noopener"><i class="ri-spotify-line"></i> Spotify</a>
- D:\dev\_apollo.rio.br\plugins\apollo-djs\templates\parts\dj-card\out-now.php:33:<a class="btn btn-line pill" id="scFollow" href="<?php echo esc_url( $sc ); ?>" target="_blank" rel="noopener"><i class="ri-soundcloud-line"></i> SoundCloud</a>
- D:\dev\_apollo.rio.br\plugins\apollo-djs\templates\parts\dj-card\out-now.php:36:<a class="btn btn-line pill" id="bcFollow" href="<?php echo esc_url( $bc ); ?>" target="_blank" rel="noopener"><i class="ri-bandcamp-line"></i> Bandcamp</a>
- D:\dev\_apollo.rio.br\plugins\apollo-djs\templates\parts\dj-card\out-now.php:39:<a class="btn btn-line pill" id="spFollow" href="<?php echo esc_url( $sp ); ?>" target="_blank" rel="noopener"><i class="ri-spotify-line"></i> Spotify</a>
- D:\dev\_apollo.rio.br\plugins\apollo-events\src\Plugin.php:406:* in a follow-up — the point of this pass was to stop the I/O, not to churn
- D:\dev\_apollo.rio.br\plugins\apollo-events\styles\base\archive-event.php:211:. '<meta name="robots" content="index, follow">' . "\n"
- D:\dev\_apollo.rio.br\plugins\apollo-hub\templates\edit-hub.php:43:<meta name="robots" content="noindex, nofollow">
- D:\dev\_apollo.rio.br\plugins\apollo-login\src\API\ActivityLogController.php:326:* Follows the same lazy-creation pattern as JWTAuth::maybe_create_table().
- D:\dev\_apollo.rio.br\plugins\apollo-login\src\Security\Firewall.php:453:echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Forbidden</title><meta name="robots" content="noindex,nofollow"></head>';
- D:\dev\_apollo.rio.br\plugins\apollo-login\src\Security\URLRewriter.php:158:<meta name="robots" content="noindex,nofollow">
- D:\dev\_apollo.rio.br\plugins\apollo-login\templates\profile.php:56:<meta name="robots" content="index,follow">
- D:\dev\_apollo.rio.br\plugins\apollo-login\templates\register.php:92:'extra_head' => '<meta name="robots" content="noindex,nofollow">',
- D:\dev\_apollo.rio.br\plugins\apollo-login\templates\reset.php:50:'extra_head' => '<meta name="robots" content="noindex,nofollow">',
- D:\dev\_apollo.rio.br\plugins\apollo-login\templates\verify-email.php:37:'extra_head' => '<meta name="robots" content="noindex,nofollow">',
- D:\dev\_apollo.rio.br\plugins\apollo-pane-engine\templates\page-casa.php:65:<meta name="robots" content="noindex, nofollow">
- D:\dev\_apollo.rio.br\plugins\apollo-seo\src\Meta.php:270:* touching meta directly, so it follows the same loc resolution the cards
- D:\dev\_apollo.rio.br\plugins\apollo-seo\src\Meta.php:515:'robots'           => 'index, follow',
- D:\dev\_apollo.rio.br\plugins\apollo-seo\src\Meta.php:940:if ( $id && Settings::get_post_meta( $id, 'nofollow' ) ) {
- D:\dev\_apollo.rio.br\plugins\apollo-seo\src\Meta.php:941:$directives[1] = 'nofollow';
- D:\dev\_apollo.rio.br\plugins\apollo-seo\src\Metabox.php:81:'nofollow'       => '',
- D:\dev\_apollo.rio.br\plugins\apollo-seo\src\Metabox.php:157:<input type="checkbox" name="<?php echo APOLLO_SEO_POST_META; ?>[nofollow]" value="1"
- D:\dev\_apollo.rio.br\plugins\apollo-seo\src\Metabox.php:158:<?php checked( $meta['nofollow'] ); ?>>
- D:\dev\_apollo.rio.br\plugins\apollo-seo\src\Metabox.php:159:<strong>nofollow</strong> — Não seguir links desta página
- D:\dev\_apollo.rio.br\plugins\apollo-seo\src\Metabox.php:255:'nofollow'       => ! empty( $raw['nofollow'] ) ? '1' : '',
- D:\dev\_apollo.rio.br\plugins\apollo-seo\src\Plugin.php:201:header( 'X-Robots-Tag: noindex, follow', true );
- D:\dev\_apollo.rio.br\plugins\apollo-seo\src\Plugin.php:514:'nofollow'       => ! empty( $meta['nofollow'] ),
- D:\dev\_apollo.rio.br\plugins\apollo-seo\src\Sitemap.php:112:header( 'X-Robots-Tag: noindex, follow' );
- … +7 more

## NEEDS_REVIEW (125)
- D:\dev\_apollo.rio.br\plugins\apollo-admin\src\Frontend\ModeraData.php:74:* Labels follow the canonical frontend relabel (apollo / MOD).
- D:\dev\_apollo.rio.br\plugins\apollo-admin\templates\partials\sections\email\settings.php:37:<div class="toggle-text"><span class="toggle-title"><?php esc_html_e('Weekly Social Digest', 'apollo-admin'); ?></span><span class="toggle-desc"><?php esc_html_e('Summary of social activity, new followers, and messages', 'apollo-admin'); ?></span></div>
- D:\dev\_apollo.rio.br\plugins\apollo-admin\templates\partials\sections\social\activity.php:27:<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Enable Follow System', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Allow users to follow/block other users', 'apollo-admin' ); ?></span></div>
- D:\dev\_apollo.rio.br\plugins\apollo-adverts\src\API\SafetyController.php:8:* cannot: the overlap needs two paginated follower calls against a private
- D:\dev\_apollo.rio.br\plugins\apollo-adverts\src\Safety\NativeGraph.php:16:* WHY apollo_activity AND THE SOCIAL FOLLOW TABLE ARE NOT USED
- D:\dev\_apollo.rio.br\plugins\apollo-adverts\src\Safety\NativeGraph.php:18:* "no friends/followers, everyone is connected". Intersecting that graph would
- D:\dev\_apollo.rio.br\plugins\apollo-adverts\src\Safety\NativeGraph.php:27:* way followers can. Two people who both have that edge with the same third
- D:\dev\_apollo.rio.br\plugins\apollo-adverts\templates\parts\safety\check.php:9:* follower fetches) and blocking the page on them would mean a member stares
- D:\dev\_apollo.rio.br\plugins\apollo-adverts\templates\parts\safety\rule.php:8:* a scammer follows back whoever follows him and the overlap fills itself.
- D:\dev\_apollo.rio.br\plugins\apollo-dashboard\templates\template-parts\dashboard\panel-events.php:25:$followed_djs = array();
- D:\dev\_apollo.rio.br\plugins\apollo-dashboard\templates\template-parts\dashboard\panel-events.php:84:// -- Followed DJs --
- D:\dev\_apollo.rio.br\plugins\apollo-dashboard\templates\template-parts\dashboard\panel-events.php:85:$follows_table = $wpdb->prefix . 'apollo_connections';
- D:\dev\_apollo.rio.br\plugins\apollo-dashboard\templates\template-parts\dashboard\panel-events.php:86:$has_follows   = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $follows_table ) ) === $follows_table;
- D:\dev\_apollo.rio.br\plugins\apollo-dashboard\templates\template-parts\dashboard\panel-events.php:88:if ( $has_follows ) {
- D:\dev\_apollo.rio.br\plugins\apollo-dashboard\templates\template-parts\dashboard\panel-events.php:89:$followed_djs = $wpdb->get_results(
- D:\dev\_apollo.rio.br\plugins\apollo-dashboard\templates\template-parts\dashboard\panel-events.php:94:FROM {$follows_table} f
- D:\dev\_apollo.rio.br\plugins\apollo-dashboard\templates\template-parts\dashboard\panel-events.php:95:INNER JOIN {$wpdb->users} u ON u.ID = f.followed_id
- D:\dev\_apollo.rio.br\plugins\apollo-dashboard\templates\template-parts\dashboard\panel-events.php:96:WHERE f.follower_id = %d
- D:\dev\_apollo.rio.br\plugins\apollo-dashboard\templates\template-parts\dashboard\panel-events.php:121:<div class="stat-item"><span class="stat-val"><?php echo esc_html( count( $followed_djs ) ); ?></span><span class="stat-label">DJs seguidos</span></div>
- D:\dev\_apollo.rio.br\plugins\apollo-dashboard\templates\template-parts\dashboard\panel-events.php:201:<?php if ( ! empty( $followed_djs ) ) : ?>
- D:\dev\_apollo.rio.br\plugins\apollo-dashboard\templates\template-parts\dashboard\panel-events.php:203:foreach ( $followed_djs as $dj ) :
- D:\dev\_apollo.rio.br\plugins\apollo-djs\styles\base\template-parts\single\dock.php:30:<button type="button" class="dj-icon-btn dj-pill" id="dockFollow" aria-label="Seguir" title="Seguir"><i class="ri-heart-3-line"></i></button>
- D:\dev\_apollo.rio.br\plugins\apollo-djs\styles\base\template-parts\single\scripts.php:85:q('#scFollow').href = APOLLO_DJ.soundcloud;
- D:\dev\_apollo.rio.br\plugins\apollo-djs\styles\base\template-parts\single\scripts.php:86:q('#bcFollow').href = APOLLO_DJ.bandcamp;
- D:\dev\_apollo.rio.br\plugins\apollo-djs\styles\base\template-parts\single\scripts.php:87:q('#spFollow').href = APOLLO_DJ.spotify;
- D:\dev\_apollo.rio.br\plugins\apollo-djs\styles\base\template-parts\single\scripts.php:219:var followed = false;
- D:\dev\_apollo.rio.br\plugins\apollo-djs\styles\base\template-parts\single\scripts.php:220:q('#dockFollow').addEventListener('click', function () {
- D:\dev\_apollo.rio.br\plugins\apollo-djs\styles\base\template-parts\single\scripts.php:221:followed = !followed;
- D:\dev\_apollo.rio.br\plugins\apollo-djs\styles\base\template-parts\single\scripts.php:222:this.classList.toggle('dj-is-active', followed);
- D:\dev\_apollo.rio.br\plugins\apollo-djs\styles\base\template-parts\single\scripts.php:223:this.querySelector('i').className = followed ? 'ri-heart-3-fill' : 'ri-heart-3-line';
- D:\dev\_apollo.rio.br\plugins\apollo-djs\styles\base\template-parts\single\scripts.php:224:this.setAttribute('aria-label', followed ? 'Seguindo' : 'Seguir');
- D:\dev\_apollo.rio.br\plugins\apollo-djs\styles\base\template-parts\single\scripts.php:225:this.setAttribute('title', followed ? 'Seguindo' : 'Seguir');
- D:\dev\_apollo.rio.br\plugins\apollo-djs\styles\base\template-parts\single\scripts.php:226:toast(followed ? 'Você segue ' + APOLLO_DJ.name + ' ?' : 'Deixou de seguir', 'ri-heart-3-line');
- D:\dev\_apollo.rio.br\plugins\apollo-djs\templates\parts\dj-card\dock.php:32:<button type="button" class="icon-btn pill" id="dockFollow" aria-label="<?php esc_attr_e( 'Seguir', 'apollo-djs' ); ?>" title="<?php esc_attr_e( 'Seguir', 'apollo-djs' ); ?>"><i class="ri-heart-3-line"></i></button>
- D:\dev\_apollo.rio.br\plugins\apollo-djs\templates\parts\dj-card\toast.php:4:* Status toast for share / follow feedback.
- D:\dev\_apollo.rio.br\plugins\apollo-events\src\API\EventsController.php:2103:/* Gallery entries follow the same int-or-URL rule as the banner. */
- D:\dev\_apollo.rio.br\plugins\apollo-events\styles\base\template-parts\archive\portal\bootstrap.php:132:(.ev-lb-scroll, overflow-y:auto). Two things follow, and neither
- D:\dev\_apollo.rio.br\plugins\apollo-events\styles\base\template-parts\archive\portal\styles-lightbox.php:194:out-specify it, not merely follow it. */
- D:\dev\_apollo.rio.br\plugins\apollo-fav\includes\class-statistics-merge.php:110:'get_follow_growth',
- D:\dev\_apollo.rio.br\plugins\apollo-loc\includes\surface.php:19:*   3. nothing else — the endpoint, card contract and lightbox follow.
- … +85 more

No PHP edits. Local agent may still refine.
