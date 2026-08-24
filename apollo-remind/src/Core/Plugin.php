<?php

namespace Apollo\Remind\Core;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Plugin singleton — wires everything together.
 */
final class Plugin {

    private static ?self $instance = null;

    public static function instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->maybe_upgrade_db();
        $this->load_includes();
        $this->init_hooks();
    }

    /* ── DB version check ───────────────────────────────────── */
    private function maybe_upgrade_db(): void {
        $installed = (int) get_option( 'apollo_remind_db_version', 0 );
        if ( $installed < APOLLO_REMIND_DB_VERSION ) {
            Activation::create_tables();
            update_option( 'apollo_remind_db_version', APOLLO_REMIND_DB_VERSION );
        }
    }

    /* ── Legacy includes ────────────────────────────────────── */
    private function load_includes(): void {
        require_once APOLLO_REMIND_PATH . 'includes/functions.php';
    }

    /* ── Hook wiring ────────────────────────────────────────── */
    private function init_hooks(): void {

        // REST API
        add_action( 'rest_api_init', [ new \Apollo\Remind\API\RemindersController(), 'register_routes' ] );
        add_action( 'rest_api_init', [ new \Apollo\Remind\API\TelegramWebhook(),     'register_routes' ] );
        add_action( 'rest_api_init', [ new \Apollo\Remind\API\PushController(),       'register_routes' ] );

        // Cron
        add_filter( 'cron_schedules', [ \Apollo\Remind\Cron\Scheduler::class, 'add_schedules' ] );
        add_action( 'apollo_remind_process_queue', [ \Apollo\Remind\Cron\Processor::class, 'run' ] );
        add_action( 'apollo_remind_daily_cleanup', [ \Apollo\Remind\Cron\Processor::class, 'cleanup' ] );

        // Cross-plugin hooks — listen for Apollo events
        // 3 args: (int $post_id, string $action, \WP_Post $post) — emitter contract.
        add_action( 'apollo/event/published',   [ $this, 'on_event_published' ], 10, 3 );
        add_action( 'apollo/event/rsvp',        [ $this, 'on_event_rsvp' ], 10, 3 );

        // Admin
        if ( is_admin() ) {
            add_action( 'admin_menu', [ $this, 'admin_menu' ], 20 );
        }

        // Frontend assets
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    /* ── Apollo event listeners ─────────────────────────────── */

    /**
     * Auto-create reminder when user RSVPs to an event.
     */
    public function on_event_rsvp( int $event_id, int $user_id, string $status ): void {
        if ( $status !== 'going' ) return;

        $start = get_post_meta( $event_id, '_event_start_date', true );
        $time  = get_post_meta( $event_id, '_event_start_time', true ) ?: '20:00';
        if ( ! $start ) return;

        $event_dt = strtotime( "$start $time" );
        // Remind 2 hours before
        $remind_at = $event_dt - ( 2 * HOUR_IN_SECONDS );
        if ( $remind_at <= time() ) return;

        $title = get_the_title( $event_id );
        apollo_remind_schedule( $user_id, [
            'title'       => sprintf( __( '🎵 %s starts in 2h!', 'apollo-remind' ), $title ),
            'message'     => sprintf( __( 'Get ready — %s is about to start.', 'apollo-remind' ), $title ),
            'due_at_gmt'  => gmdate( 'Y-m-d H:i:s', $remind_at ),
            'channels'    => [ 'push', 'telegram', 'notif' ],
            'context'     => 'event_rsvp',
            'ref_type'    => 'event',
            'ref_id'      => $event_id,
        ] );
    }

    /**
     * Hook: apollo/event/published (int $post_id, string $action, \WP_Post $post)
     *
     * BUGFIX (fatal): argument #2 was typed \WP_Post, but the emitter
     * (apollo-events Registry::on_status_transition) passes the STRING
     * 'published' — the post object is argument #3. PHP validates the
     * signature BEFORE entering the body, so even this empty stub threw a
     * TypeError and killed every draft→publish transition.
     *
     * @param int           $post_id Event post ID.
     * @param string        $action  Action name ('published').
     * @param \WP_Post|null $post    Event post object.
     */
    public function on_event_published( int $post_id, string $action = 'published', ?\WP_Post $post = null ): void {
        // Could auto-remind followers — hook reserved for future use
    }

    /* ── Admin ──────────────────────────────────────────────── */
    public function admin_menu(): void {
        add_submenu_page(
            'apollo',
            __( 'Reminders', 'apollo-remind' ),
            __( 'Reminders', 'apollo-remind' ),
            'manage_options',
            'apollo-reminders',
            [ $this, 'render_admin' ]
        );
    }

    public function render_admin(): void {
        require_once APOLLO_REMIND_PATH . 'templates/admin-dashboard.php';
    }

    /* ── Assets ─────────────────────────────────────────────── */
    public function enqueue_assets(): void {
        if ( ! is_user_logged_in() ) return;

        wp_enqueue_script(
            'apollo-remind-sw-register',
            APOLLO_REMIND_URL . 'assets/js/push-register.js',
            [],
            APOLLO_REMIND_VERSION,
            true
        );

        wp_localize_script( 'apollo-remind-sw-register', 'apolloRemind', [
            'rest'      => rest_url( 'apollo/v1/remind' ),
            'nonce'     => wp_create_nonce( 'wp_rest' ),
            'vapidKey'  => apollo_remind_get_vapid_public(),
            'swUrl'     => APOLLO_REMIND_URL . 'assets/js/push-sw.js',
        ] );
    }
}
