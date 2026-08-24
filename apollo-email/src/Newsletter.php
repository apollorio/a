<?php

/**
 * Apollo Native Newsletter
 *
 * Self-contained email newsletter system with NO external plugins.
 * Features: subscriber management, email campaigns, templates, scheduling.
 *
 * @package Apollo\Email
 * @since   1.0.0
 */

declare(strict_types=1);

namespace Apollo\Email;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Class Native_Newsletter
 *
 * Complete newsletter system without external dependencies.
 */
class Native_Newsletter
{


    /** @var string Database table name for subscribers (without prefix). */
    private const TABLE_SUBSCRIBERS = 'apollo_newsletter_subscribers';

    /** @var string Database table name for campaigns (without prefix). */
    private const TABLE_CAMPAIGNS = 'apollo_newsletter_campaigns';

    /** @var string Option name for settings. */
    private const SETTINGS_OPTION = 'apollo_newsletter_settings';

    /**
     * Initialize the newsletter system.
     */
    public static function init(): void
    {
        // Create database tables on activation.
        if (defined('APOLLO_EMAIL_FILE')) {
            register_activation_hook(APOLLO_EMAIL_FILE, array(__CLASS__, 'create_tables'));
        }

        // Ensure tables exist (create if missing).
        self::maybe_create_tables();

        // Ensure tables exist on admin init (auto-repair).
        add_action('admin_init', array(__CLASS__, 'maybe_create_tables'));

        // Admin menu.
        add_action('admin_menu', array(__CLASS__, 'add_admin_menu'));

        // REST API endpoints.
        add_action('rest_api_init', array(__CLASS__, 'register_rest_routes'));

        // Subscription form shortcode.
        add_shortcode('apollo_newsletter', array(__CLASS__, 'render_subscription_form'));

        // Widget registration.
        add_action('widgets_init', array(__CLASS__, 'register_widget'));

        // Enqueue styles.
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_admin_scripts'));

        // Cron for scheduled campaigns.
        add_action('apollo_newsletter_send_scheduled', array(__CLASS__, 'process_scheduled_campaigns'));
        if (! wp_next_scheduled('apollo_newsletter_send_scheduled')) {
            wp_schedule_event(time(), 'hourly', 'apollo_newsletter_send_scheduled');
        }

        // Handle unsubscribe links.
        add_action('init', array(__CLASS__, 'handle_unsubscribe'));

        // Show status notice.
        // add_action( 'admin_notices', array( __CLASS__, 'show_status_notice' ) ); // DISABLED - annoying popup
    }

    /**
     * Show status notice on Apollo admin pages.
     */
    public static function show_status_notice(): void
    {
        $screen = get_current_screen();
        if (! $screen || strpos($screen->id, 'apollo') === false) {
            return;
        }

        $count = self::get_subscriber_count();

        echo '<div class="notice notice-info is-dismissible apollo-newsletter-status">';
        echo '<p><span class="dashicons dashicons-email-alt" style="color:#0073aa;"></span> ';
        echo '<strong>' . esc_html__('Apollo Newsletter:', 'apollo-email') . '</strong> ';
        printf(
            /* translators: %d: number of subscribers */
            esc_html__('Native email system active with %d subscriber(s).', 'apollo-email'),
            $count
        );
        echo ' <span style="color:#46b450;">✓</span>';
        echo '</p></div>';
    }

    /**
     * Create database tables.
     */
    public static function create_tables(): void
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Subscribers table.
        $table_subscribers = $wpdb->prefix . self::TABLE_SUBSCRIBERS;
        $sql_subscribers   = "CREATE TABLE $table_subscribers (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			email varchar(255) NOT NULL,
			name varchar(255) DEFAULT '',
			status enum('active','pending','unsubscribed') DEFAULT 'pending',
			token varchar(64) NOT NULL,
			lists text DEFAULT '',
			ip_address varchar(45) DEFAULT '',
			subscribed_at datetime DEFAULT CURRENT_TIMESTAMP,
			confirmed_at datetime DEFAULT NULL,
			unsubscribed_at datetime DEFAULT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY email (email),
			KEY status (status),
			KEY token (token)
		) $charset_collate;";

        // Campaigns table.
        $table_campaigns = $wpdb->prefix . self::TABLE_CAMPAIGNS;
        $sql_campaigns   = "CREATE TABLE $table_campaigns (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			subject varchar(255) NOT NULL,
			content longtext NOT NULL,
			template varchar(50) DEFAULT 'default',
			status enum('draft','scheduled','sending','sent') DEFAULT 'draft',
			lists text DEFAULT '',
			scheduled_at datetime DEFAULT NULL,
			sent_at datetime DEFAULT NULL,
			sent_count int(11) DEFAULT 0,
			open_count int(11) DEFAULT 0,
			click_count int(11) DEFAULT 0,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			created_by bigint(20) unsigned DEFAULT 0,
			PRIMARY KEY (id),
			KEY status (status)
		) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_subscribers);
        dbDelta($sql_campaigns);
    }

    /**
     * Check and create tables if they don't exist.
     * Auto-repair mechanism for sites where activation hook didn't run.
     */
    public static function maybe_create_tables(): void
    {
        global $wpdb;

        // Check if tables exist using option flag.
        $tables_version = get_option('apollo_newsletter_db_version', '');
        if ('1.0' === $tables_version) {
            return;
        }

        // Verify tables exist.
        $table_subscribers = $wpdb->prefix . self::TABLE_SUBSCRIBERS;
        $table_campaigns   = $wpdb->prefix . self::TABLE_CAMPAIGNS;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $subscribers_exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table_subscribers));
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $campaigns_exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table_campaigns));

        if ($table_subscribers !== $subscribers_exists || $table_campaigns !== $campaigns_exists) {
            self::create_tables();
            update_option('apollo_newsletter_db_version', '1.0');
        } else {
            // Tables exist, mark version.
            update_option('apollo_newsletter_db_version', '1.0');
        }
    }

    /**
     * Get default settings.
     *
     * @return array
     */
    public static function get_settings(): array
    {
        return wp_parse_args(
            get_option(self::SETTINGS_OPTION, array()),
            array(
                'from_name'        => get_bloginfo('name'),
                'from_email'       => get_bloginfo('admin_email'),
                'reply_to'         => get_bloginfo('admin_email'),
                'double_optin'     => true,
                'welcome_email'    => true,
                'welcome_subject'  => __('Welcome to our newsletter!', 'apollo-email'),
                'welcome_content'  => __('Thank you for subscribing. You will now receive our latest updates.', 'apollo-email'),
                'confirm_subject'  => __('Please confirm your subscription', 'apollo-email'),
                'confirm_content'  => __('Click the link below to confirm your subscription:', 'apollo-email'),
                'unsubscribe_page' => '',
                'gdpr_enabled'     => true,
                'gdpr_text'        => __('I agree to receive newsletters and accept the privacy policy.', 'apollo-email'),
                'lists'            => array('default' => __('General Newsletter', 'apollo-email')),
            )
        );
    }

    /**
     * Get subscriber count.
     *
     * @param string $status Optional status filter.
     * @return int
     */
    public static function get_subscriber_count(string $status = 'active'): int
    {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE_SUBSCRIBERS;

        if ('all' === $status) {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is trusted constant
            return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
        }

        return (int) $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM $table WHERE status = %s", $status)
        );
    }

    /**
     * Get subscribers.
     *
     * @param array $args Query arguments.
     * @return array
     */
    public static function get_subscribers(array $args = array()): array
    {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE_SUBSCRIBERS;

        $defaults = array(
            'status'  => 'active',
            'limit'   => 100,
            'offset'  => 0,
            'orderby' => 'subscribed_at',
            'order'   => 'DESC',
            'list'    => '',
        );

        $args  = wp_parse_args($args, $defaults);
        $where = array();

        if ('all' !== $args['status']) {
            $where[] = $wpdb->prepare('status = %s', $args['status']);
        }

        if (! empty($args['list'])) {
            $where[] = $wpdb->prepare('lists LIKE %s', '%' . $wpdb->esc_like($args['list']) . '%');
        }

        $where_sql = ! empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $order_sql = sprintf(
            'ORDER BY %s %s',
            sanitize_sql_orderby($args['orderby']) ?: 'subscribed_at',
            'ASC' === strtoupper($args['order']) ? 'ASC' : 'DESC'
        );
        $limit_sql = $wpdb->prepare('LIMIT %d OFFSET %d', $args['limit'], $args['offset']);

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        return $wpdb->get_results("SELECT * FROM $table $where_sql $order_sql $limit_sql", ARRAY_A) ?: array();
    }

    /**
     * Add subscriber.
     *
     * @param string $email Email address.
     * @param string $name  Optional name.
     * @param array  $lists Optional lists.
     * @return array{success: bool, message: string, subscriber_id?: int}
     */
    public static function add_subscriber(string $email, string $name = '', array $lists = array()): array
    {
        global $wpdb;

        $email = sanitize_email($email);
        if (! is_email($email)) {
            return array(
                'success' => false,
                'message' => __('Invalid email address.', 'apollo-email'),
            );
        }

        $table    = $wpdb->prefix . self::TABLE_SUBSCRIBERS;
        $existing = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE email = %s", $email), ARRAY_A);

        if ($existing) {
            if ('active' === $existing['status']) {
                return array(
                    'success' => false,
                    'message' => __('You are already subscribed.', 'apollo-email'),
                );
            }

            // Reactivate.
            $wpdb->update(
                $table,
                array(
                    'status'          => 'pending',
                    'token'           => wp_generate_password(32, false),
                    'unsubscribed_at' => null,
                ),
                array('id' => $existing['id']),
                array('%s', '%s', null),
                array('%d')
            );

            $subscriber_id = (int) $existing['id'];
        } else {
            $token = wp_generate_password(32, false);

            $wpdb->insert(
                $table,
                array(
                    'email'         => $email,
                    'name'          => sanitize_text_field($name),
                    'status'        => 'pending',
                    'token'         => $token,
                    'lists'         => wp_json_encode($lists ?: array('default')),
                    'ip_address'    => self::get_client_ip(),
                    'subscribed_at' => current_time('mysql'),
                ),
                array('%s', '%s', '%s', '%s', '%s', '%s', '%s')
            );

            $subscriber_id = (int) $wpdb->insert_id;
        }

        $settings = self::get_settings();

        if ($settings['double_optin']) {
            self::send_confirmation_email($subscriber_id);
            return array(
                'success'       => true,
                'message'       => __('Please check your email to confirm your subscription.', 'apollo-email'),
                'subscriber_id' => $subscriber_id,
            );
        } else {
            // Direct activation.
            $wpdb->update(
                $table,
                array(
                    'status'       => 'active',
                    'confirmed_at' => current_time('mysql'),
                ),
                array('id' => $subscriber_id),
                array('%s', '%s'),
                array('%d')
            );

            if ($settings['welcome_email']) {
                self::send_welcome_email($subscriber_id);
            }

            return array(
                'success'       => true,
                'message'       => __('You have been subscribed successfully!', 'apollo-email'),
                'subscriber_id' => $subscriber_id,
            );
        }
    }

    /**
     * Confirm subscriber.
     *
     * @param string $token Confirmation token.
     * @return bool
     */
    public static function confirm_subscriber(string $token): bool
    {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE_SUBSCRIBERS;

        $subscriber = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table WHERE token = %s AND status = 'pending'", $token),
            ARRAY_A
        );

        if (! $subscriber) {
            return false;
        }

        $wpdb->update(
            $table,
            array(
                'status'       => 'active',
                'confirmed_at' => current_time('mysql'),
                'token'        => wp_generate_password(32, false), // Regenerate token.
            ),
            array('id' => $subscriber['id']),
            array('%s', '%s', '%s'),
            array('%d')
        );

        $settings = self::get_settings();
        if ($settings['welcome_email']) {
            self::send_welcome_email((int) $subscriber['id']);
        }

        do_action('apollo_newsletter_subscriber_confirmed', $subscriber);

        return true;
    }

    /**
     * Unsubscribe.
     *
     * @param string $token Unsubscribe token.
     * @return bool
     */
    public static function unsubscribe(string $token): bool
    {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE_SUBSCRIBERS;

        $result = $wpdb->update(
            $table,
            array(
                'status'          => 'unsubscribed',
                'unsubscribed_at' => current_time('mysql'),
            ),
            array('token' => $token),
            array('%s', '%s'),
            array('%s')
        );

        return $result > 0;
    }

    /**
     * Send confirmation email.
     *
     * @param int $subscriber_id Subscriber ID.
     */
    private static function send_confirmation_email(int $subscriber_id): void
    {
        global $wpdb;
        $table      = $wpdb->prefix . self::TABLE_SUBSCRIBERS;
        $subscriber = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $subscriber_id), ARRAY_A);

        if (! $subscriber) {
            return;
        }

        $settings     = self::get_settings();
        $confirm_link = add_query_arg(
            array(
                'apollo_newsletter' => 'confirm',
                'token'             => $subscriber['token'],
            ),
            home_url()
        );

        $subject = $settings['confirm_subject'];
        $message = $settings['confirm_content'] . "\n\n" . $confirm_link;

        self::send_email($subscriber['email'], $subject, $message);
    }

    /**
     * Send welcome email.
     *
     * @param int $subscriber_id Subscriber ID.
     */
    private static function send_welcome_email(int $subscriber_id): void
    {
        global $wpdb;
        $table      = $wpdb->prefix . self::TABLE_SUBSCRIBERS;
        $subscriber = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $subscriber_id), ARRAY_A);

        if (! $subscriber) {
            return;
        }

        $settings = self::get_settings();

        self::send_email(
            $subscriber['email'],
            $settings['welcome_subject'],
            $settings['welcome_content']
        );
    }

    /**
     * Send email using WordPress mail.
     *
     * @param string $to      Recipient email.
     * @param string $subject Email subject.
     * @param string $message Email content (HTML supported).
     * @param array  $headers Additional headers.
     * @return bool
     */
    public static function send_email(string $to, string $subject, string $message, array $headers = array()): bool
    {
        // Route through apollo-email for logging/templating if available
        if (function_exists('apollo_send_email')) {
            return apollo_send_email(
                $to,
                $subject,
                'notification',
                array(
                    'user_name'   => '',
                    'title'       => $subject,
                    'message'     => $message,
                    'action_url'  => '',
                    'action_text' => '',
                    'site_name'   => get_bloginfo('name'),
                )
            );
        }

        $settings = self::get_settings();

        $default_headers = array(
            'Content-Type: text/html; charset=UTF-8',
            sprintf('From: %s <%s>', $settings['from_name'], $settings['from_email']),
        );

        if ($settings['reply_to']) {
            $default_headers[] = sprintf('Reply-To: %s', $settings['reply_to']);
        }

        $headers = array_merge($default_headers, $headers);

        // Wrap in basic template.
        $html = self::get_email_template($message);

        return wp_mail($to, $subject, $html, $headers);
    }

    /**
     * Get email template.
     *
     * @param string $content Email content.
     * @return string
     */
    private static function get_email_template(string $content): string
    {
        $site_name = get_bloginfo('name');
        $logo_url  = get_site_icon_url(128);

        $logo_html = $logo_url ? '<img src="' . esc_url($logo_url) . '" alt="' . esc_attr($site_name) . '" style="max-width: 150px; height: auto;">' : '<h1 style="margin: 0; color: #333;">' . esc_html($site_name) . '</h1>';

        return '<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; font-family: system-ui, -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif; background-color: #f5f5f5;">
	<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f5f5f5;">
		<tr>
			<td align="center" style="padding: 40px 20px;">
				<table role="presentation" width="600" cellspacing="0" cellpadding="0" style="background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 8px rgba(var(--rgb-d),0.1);">
					<!-- Header -->
					<tr>
						<td align="center" style="padding: 30px 40px; border-bottom: 1px solid #eee;">
							' . $logo_html . '
						</td>
					</tr>
					<!-- Content -->
					<tr>
						<td style="padding: 40px; color: #333; font-size: 16px; line-height: 1.6;">
							' . wpautop($content) . '
						</td>
					</tr>
					<!-- Footer -->
					<tr>
						<td align="center" style="padding: 20px 40px; background-color: #f9f9f9; border-top: 1px solid #eee; font-size: 12px; color: #666;">
							<p style="margin: 0 0 10px;">
								' . esc_html($site_name) . ' - ' . esc_html(home_url()) . '
							</p>
							<p style="margin: 0;">
								<a href="{unsubscribe_url}" style="color: #666;">' . esc_html__('Unsubscribe', 'apollo-email') . '</a>
							</p>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</body>
</html>';
    }

    /**
     * Send campaign to subscribers.
     *
     * @param int $campaign_id Campaign ID.
     * @return array{sent: int, failed: int}
     */
    public static function send_campaign(int $campaign_id): array
    {
        global $wpdb;
        $campaigns_table = $wpdb->prefix . self::TABLE_CAMPAIGNS;

        $campaign = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $campaigns_table WHERE id = %d", $campaign_id),
            ARRAY_A
        );

        if (! $campaign) {
            return array(
                'sent'   => 0,
                'failed' => 0,
            );
        }

        // Update status to sending.
        $wpdb->update(
            $campaigns_table,
            array('status' => 'sending'),
            array('id' => $campaign_id),
            array('%s'),
            array('%d')
        );

        $lists       = json_decode($campaign['lists'], true) ?: array();
        $subscribers = self::get_subscribers(
            array(
                'status' => 'active',
                'limit'  => 10000,
                'list'   => ! empty($lists) ? $lists[0] : '',
            )
        );

        $sent   = 0;
        $failed = 0;

        foreach ($subscribers as $subscriber) {
            // Replace placeholders.
            $content = str_replace(
                array('{name}', '{email}', '{unsubscribe_url}'),
                array(
                    $subscriber['name'] ?: __('Subscriber', 'apollo-email'),
                    $subscriber['email'],
                    add_query_arg(
                        array(
                            'apollo_newsletter' => 'unsubscribe',
                            'token'             => $subscriber['token'],
                        ),
                        home_url()
                    ),
                ),
                $campaign['content']
            );

            $result = self::send_email($subscriber['email'], $campaign['subject'], $content);

            if ($result) {
                ++$sent;
            } else {
                ++$failed;
            }

            // Small delay to avoid overwhelming mail server.
            usleep(50000); // 50ms.
        }

        // Update campaign status.
        $wpdb->update(
            $campaigns_table,
            array(
                'status'     => 'sent',
                'sent_at'    => current_time('mysql'),
                'sent_count' => $sent,
            ),
            array('id' => $campaign_id),
            array('%s', '%s', '%d'),
            array('%d')
        );

        return array(
            'sent'   => $sent,
            'failed' => $failed,
        );
    }

    /**
     * Process scheduled campaigns.
     */
    public static function process_scheduled_campaigns(): void
    {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE_CAMPAIGNS;

        $scheduled = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id FROM $table WHERE status = 'scheduled' AND scheduled_at <= %s",
                current_time('mysql')
            ),
            ARRAY_A
        );

        foreach ($scheduled as $campaign) {
            self::send_campaign((int) $campaign['id']);
        }
    }

    /**
     * Handle unsubscribe and confirm actions.
     */
    public static function handle_unsubscribe(): void
    {
        if (! isset($_GET['apollo_newsletter'])) {
            return;
        }

        $action = sanitize_text_field(wp_unslash($_GET['apollo_newsletter']));
        $token  = isset($_GET['token']) ? sanitize_text_field(wp_unslash($_GET['token'])) : '';

        if (empty($token)) {
            return;
        }

        switch ($action) {
            case 'confirm':
                $success = self::confirm_subscriber($token);
                $message = $success
                    ? __('Your subscription has been confirmed. Thank you!', 'apollo-email')
                    : __('Invalid or expired confirmation link.', 'apollo-email');
                break;

            case 'unsubscribe':
                $success = self::unsubscribe($token);
                $message = $success
                    ? __('You have been unsubscribed successfully.', 'apollo-email')
                    : __('Invalid unsubscribe link.', 'apollo-email');
                break;

            default:
                return;
        }

        // Display simple message page.
        wp_die(
            '<div style="text-align: center; padding: 50px; font-family: sans-serif;">
				<h2>' . esc_html($message) . '</h2>
				<p><a href="' . esc_url(home_url()) . '">' . esc_html__('Return to homepage', 'apollo-email') . '</a></p>
			</div>',
            esc_html(get_bloginfo('name')),
            array('response' => 200)
        );
    }

    /**
     * Register REST API routes.
     */
    public static function register_rest_routes(): void
    {
        register_rest_route(
            'apollo/v1',
            '/newsletter/subscribe',
            array(
                'methods'             => 'POST',
                'callback'            => array(__CLASS__, 'rest_subscribe'),
                'permission_callback' => '__return_true',
            )
        );

        register_rest_route(
            'apollo/v1',
            '/newsletter/subscribers',
            array(
                'methods'             => 'GET',
                'callback'            => array(__CLASS__, 'rest_get_subscribers'),
                'permission_callback' => function () {
                    return current_user_can('manage_options');
                },
            )
        );
    }

    /**
     * REST: Subscribe.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public static function rest_subscribe(\WP_REST_Request $request): \WP_REST_Response
    {
        $email = sanitize_email($request->get_param('email'));
        $name  = sanitize_text_field($request->get_param('name') ?? '');
        $lists = $request->get_param('lists') ?? array();

        // Verify nonce — either REST nonce or form nonce must be valid.
        $nonce = $request->get_header('X-WP-Nonce');
        $nonce_valid = $nonce && wp_verify_nonce($nonce, 'wp_rest');

        if (! $nonce_valid) {
            $form_nonce = $request->get_param('_wpnonce');
            $nonce_valid = $form_nonce && wp_verify_nonce($form_nonce, 'apollo_newsletter_subscribe');
        }

        if (! $nonce_valid) {
            return new \WP_REST_Response(
                array('success' => false, 'message' => __('Security check failed. Please refresh the page and try again.', 'apollo-email')),
                403
            );
        }

        // IP-based rate limiting — prevents email-harvest / enumeration abuse, which the
        // per-email throttle below cannot stop (an attacker simply varies the address).
        $ip = self::get_request_ip();
        if ('' !== $ip) {
            $ip_key  = 'apollo_nl_sub_ip_' . md5($ip);
            $ip_hits = (int) get_transient($ip_key);
            if ($ip_hits >= 5) {
                return new \WP_REST_Response(
                    array('success' => false, 'message' => __('Too many subscription attempts. Please try again later.', 'apollo-email')),
                    429
                );
            }
            set_transient($ip_key, $ip_hits + 1, 10 * MINUTE_IN_SECONDS);
        }

        // Basic rate limiting — 1 subscribe per email per minute.
        $rate_key = 'apollo_nl_sub_' . md5($email);
        if (get_transient($rate_key)) {
            return new \WP_REST_Response(
                array('success' => false, 'message' => __('Please wait before trying again.', 'apollo-email')),
                429
            );
        }
        set_transient($rate_key, 1, MINUTE_IN_SECONDS);

        $result = self::add_subscriber($email, $name, (array) $lists);

        return new \WP_REST_Response($result, $result['success'] ? 200 : 400);
    }

    /**
     * Resolve the client IP for rate limiting.
     *
     * Prefers apollo-login's Firewall (proxy-aware) when present, otherwise falls
     * back to a validated REMOTE_ADDR. Returns '' when no valid IP is available.
     *
     * @return string
     */
    private static function get_request_ip(): string
    {
        if (is_callable(array('\Apollo\Login\Security\Firewall', 'get_client_ip'))) {
            return (string) \Apollo\Login\Security\Firewall::get_client_ip();
        }
        $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '';
    }

    /**
     * REST: Get subscribers.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public static function rest_get_subscribers(\WP_REST_Request $request): \WP_REST_Response
    {
        $args = array(
            'status' => $request->get_param('status') ?? 'active',
            'limit'  => min(100, absint($request->get_param('limit') ?? 50)),
            'offset' => absint($request->get_param('offset') ?? 0),
        );

        return new \WP_REST_Response(
            array(
                'subscribers' => self::get_subscribers($args),
                'total'       => self::get_subscriber_count($args['status']),
            )
        );
    }

    /**
     * Render subscription form shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public static function render_subscription_form($atts = array()): string
    {
        $atts = shortcode_atts(
            array(
                'title'       => __('Subscribe to our Newsletter', 'apollo-email'),
                'button'      => __('Subscribe', 'apollo-email'),
                'placeholder' => __('Your email address', 'apollo-email'),
                'show_name'   => 'false',
                'list'        => 'default',
                'style'       => 'default',
            ),
            $atts
        );

        $form_id = 'apollo-newsletter-' . wp_unique_id();

        ob_start();
?>
        <div class="apollo-newsletter-form <?php echo esc_attr('style-' . $atts['style']); ?>" id="<?php echo esc_attr($form_id); ?>">
            <?php if ($atts['title']) : ?>
                <h3 class="apollo-newsletter-title"><?php echo esc_html($atts['title']); ?></h3>
            <?php endif; ?>

            <form class="apollo-newsletter-subscribe" data-list="<?php echo esc_attr($atts['list']); ?>">
                <?php wp_nonce_field('apollo_newsletter_subscribe', '_wpnonce'); ?>

                <?php if ('true' === $atts['show_name']) : ?>
                    <div class="apollo-newsletter-field">
                        <input type="text" name="name" placeholder="<?php esc_attr_e('Your name', 'apollo-email'); ?>" class="apollo-newsletter-input">
                    </div>
                <?php endif; ?>

                <div class="apollo-newsletter-field apollo-newsletter-email-field">
                    <input type="email" name="email" placeholder="<?php echo esc_attr($atts['placeholder']); ?>" required class="apollo-newsletter-input">
                    <button type="submit" class="apollo-newsletter-button"><?php echo esc_html($atts['button']); ?></button>
                </div>

                <?php
                $settings = self::get_settings();
                if ($settings['gdpr_enabled']) :
                ?>
                    <div class="apollo-newsletter-field apollo-newsletter-gdpr">
                        <label>
                            <input type="checkbox" name="gdpr" required>
                            <span><?php echo esc_html($settings['gdpr_text']); ?></span>
                        </label>
                    </div>
                <?php endif; ?>

                <div class="apollo-newsletter-message" style="display: none;"></div>
            </form>
        </div>

        <style>
            .apollo-newsletter-form {
                max-width: 500px;
                padding: 20px;
                background: #f9f9f9;
                border-radius: 8px;
            }

            .apollo-newsletter-title {
                margin: 0 0 15px;
                font-size: 18px;
            }

            .apollo-newsletter-field {
                margin-bottom: 10px;
            }

            .apollo-newsletter-email-field {
                display: flex;
                gap: 10px;
            }

            .apollo-newsletter-input {
                flex: 1;
                padding: 12px 15px;
                border: 1px solid #ddd;
                border-radius: 4px;
                font-size: 14px;
            }

            .apollo-newsletter-button {
                padding: 12px 24px;
                background: #0073aa;
                color: white;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                font-size: 14px;
                white-space: nowrap;
            }

            .apollo-newsletter-button:hover {
                background: #005177;
            }

            .apollo-newsletter-gdpr {
                font-size: 12px;
                color: #666;
            }

            .apollo-newsletter-gdpr label {
                display: flex;
                align-items: flex-start;
                gap: 8px;
            }

            .apollo-newsletter-message {
                padding: 10px;
                border-radius: 4px;
                margin-top: 10px;
                font-size: 14px;
            }

            .apollo-newsletter-message.success {
                background: #d4edda;
                color: #155724;
            }

            .apollo-newsletter-message.error {
                background: #f8d7da;
                color: #721c24;
            }
        </style>

        <script>
            (function() {
                var form = document.querySelector('#<?php echo esc_js($form_id); ?> form');
                if (!form) return;

                form.addEventListener('submit', function(e) {
                    e.preventDefault();

                    var formData = new FormData(form);
                    var data = {
                        email: formData.get('email'),
                        name: formData.get('name') || '',
                        lists: [form.dataset.list || 'default'],
                        _wpnonce: formData.get('_wpnonce')
                    };

                    var button = form.querySelector('button');
                    var message = form.querySelector('.apollo-newsletter-message');

                    button.disabled = true;
                    button.textContent = '<?php echo esc_js(__('Subscribing...', 'apollo-email')); ?>';

                    fetch('<?php echo esc_url(rest_url('apollo/v1/newsletter/subscribe')); ?>', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-WP-Nonce': '<?php echo esc_js(wp_create_nonce('wp_rest')); ?>'
                            },
                            body: JSON.stringify(data)
                        })
                        .then(function(r) {
                            return r.json();
                        })
                        .then(function(result) {
                            message.textContent = result.message;
                            message.className = 'apollo-newsletter-message ' + (result.success ? 'success' : 'error');
                            message.style.display = 'block';

                            if (result.success) {
                                form.reset();
                            }

                            button.disabled = false;
                            button.textContent = '<?php echo esc_js($atts['button']); ?>';
                        })
                        .catch(function() {
                            message.textContent = '<?php echo esc_js(__('An error occurred. Please try again.', 'apollo-email')); ?>';
                            message.className = 'apollo-newsletter-message error';
                            message.style.display = 'block';
                            button.disabled = false;
                            button.textContent = '<?php echo esc_js($atts['button']); ?>';
                        });
                });
            })();
        </script>
    <?php
        return ob_get_clean();
    }

    /**
     * Register widget.
     */
    public static function register_widget(): void
    {
        register_widget('Apollo\Email\Newsletter_Widget');
    }

    /**
     * Enqueue scripts.
     */
    public static function enqueue_scripts(): void
    {
        // Scripts are inline in shortcode for now.
    }

    /**
     * Enqueue admin scripts.
     *
     * @param string $hook Admin page hook.
     */
    public static function enqueue_admin_scripts(string $hook): void
    {
        if (strpos($hook, 'apollo-newsletter') === false) {
            return;
        }

        wp_enqueue_style('wp-codemirror');
        wp_enqueue_script('wp-codemirror');
    }

    /**
     * Add admin menu.
     */
    public static function add_admin_menu(): void
    {
        add_submenu_page(
            'apollo-control',
            __('Newsletter', 'apollo-email'),
            __('Newsletter', 'apollo-email'),
            'manage_options',
            'apollo-newsletter',
            array(__CLASS__, 'render_admin_page')
        );
    }

    /**
     * Render admin page.
     */
    public static function render_admin_page(): void
    {
        $tab          = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'subscribers';
        $settings     = self::get_settings();
        $active_count = self::get_subscriber_count('active');
        $total_count  = self::get_subscriber_count('all');

        // Handle settings save.
        if (isset($_POST['apollo_newsletter_settings']) && check_admin_referer('apollo_newsletter_settings')) {
            $new_settings = array();
            foreach ($_POST['settings'] as $key => $value) {
                if (is_array($value)) {
                    $new_settings[$key] = array_map('sanitize_text_field', $value);
                } else {
                    $new_settings[$key] = sanitize_textarea_field($value);
                }
            }
            $new_settings['double_optin']  = isset($_POST['settings']['double_optin']);
            $new_settings['welcome_email'] = isset($_POST['settings']['welcome_email']);
            $new_settings['gdpr_enabled']  = isset($_POST['settings']['gdpr_enabled']);

            update_option(self::SETTINGS_OPTION, array_merge($settings, $new_settings));
            $settings = self::get_settings();
            echo '<div class="notice notice-success"><p>' . esc_html__('Settings saved.', 'apollo-email') . '</p></div>';
        }
    ?>
        <div class="wrap">
            <h1><?php esc_html_e('Apollo Newsletter', 'apollo-email'); ?></h1>

            <div class="card" style="max-width: 400px; margin-bottom: 20px;">
                <p>
                    <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
                    <strong><?php esc_html_e('Native Newsletter: Active', 'apollo-email'); ?></strong>
                </p>
                <p><?php esc_html_e('No external plugins required.', 'apollo-email'); ?></p>
                <table>
                    <tr>
                        <td><strong><?php esc_html_e('Active Subscribers:', 'apollo-email'); ?></strong></td>
                        <td><?php echo esc_html($active_count); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e('Total:', 'apollo-email'); ?></strong></td>
                        <td><?php echo esc_html($total_count); ?></td>
                    </tr>
                </table>
            </div>

            <nav class="nav-tab-wrapper">
                <a href="?page=apollo-newsletter&tab=subscribers" class="nav-tab <?php echo 'subscribers' === $tab ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Subscribers', 'apollo-email'); ?>
                </a>
                <a href="?page=apollo-newsletter&tab=campaigns" class="nav-tab <?php echo 'campaigns' === $tab ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Campaigns', 'apollo-email'); ?>
                </a>
                <a href="?page=apollo-newsletter&tab=settings" class="nav-tab <?php echo 'settings' === $tab ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Settings', 'apollo-email'); ?>
                </a>
            </nav>

            <div class="tab-content" style="margin-top: 20px;">
                <?php
                switch ($tab) {
                    case 'campaigns':
                        self::render_campaigns_tab();
                        break;
                    case 'settings':
                        self::render_settings_tab($settings);
                        break;
                    default:
                        self::render_subscribers_tab();
                        break;
                }
                ?>
            </div>

            <div class="card" style="margin-top: 20px; max-width: 600px;">
                <h3><?php esc_html_e('Shortcode Usage', 'apollo-email'); ?></h3>
                <code>[apollo_newsletter]</code>
                <p style="margin-top: 10px;"><?php esc_html_e('Options:', 'apollo-email'); ?></p>
                <ul style="list-style: disc; margin-left: 20px;">
                    <li><code>title=""</code> - <?php esc_html_e('Form title', 'apollo-email'); ?></li>
                    <li><code>button=""</code> - <?php esc_html_e('Button text', 'apollo-email'); ?></li>
                    <li><code>show_name="true"</code> - <?php esc_html_e('Show name field', 'apollo-email'); ?></li>
                    <li><code>list=""</code> - <?php esc_html_e('Target list', 'apollo-email'); ?></li>
                </ul>
            </div>
        </div>
    <?php
    }

    /**
     * Render subscribers tab.
     */
    private static function render_subscribers_tab(): void
    {
        $subscribers = self::get_subscribers(
            array(
                'status' => 'all',
                'limit'  => 100,
            )
        );
    ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Email', 'apollo-email'); ?></th>
                    <th><?php esc_html_e('Name', 'apollo-email'); ?></th>
                    <th><?php esc_html_e('Status', 'apollo-email'); ?></th>
                    <th><?php esc_html_e('Lists', 'apollo-email'); ?></th>
                    <th><?php esc_html_e('Subscribed', 'apollo-email'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($subscribers)) : ?>
                    <tr>
                        <td colspan="5"><?php esc_html_e('No subscribers yet.', 'apollo-email'); ?></td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($subscribers as $sub) : ?>
                        <tr>
                            <td><?php echo esc_html($sub['email']); ?></td>
                            <td><?php echo esc_html($sub['name'] ?: '—'); ?></td>
                            <td>
                                <?php
                                $status_colors = array(
                                    'active'       => '#46b450',
                                    'pending'      => '#ffb900',
                                    'unsubscribed' => '#dc3232',
                                );
                                $color         = $status_colors[$sub['status']] ?? '#666';
                                ?>
                                <span style="color: <?php echo esc_attr($color); ?>; font-weight: 600;">
                                    <?php echo esc_html(ucfirst($sub['status'])); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html(implode(', ', json_decode($sub['lists'], true) ?: array())); ?></td>
                            <td><?php echo esc_html($sub['subscribed_at']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    <?php
    }

    /**
     * Render campaigns tab.
     */
    private static function render_campaigns_tab(): void
    {
        global $wpdb;
        $table     = $wpdb->prefix . self::TABLE_CAMPAIGNS;
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is trusted constant
        $campaigns = $wpdb->get_results("SELECT * FROM {$table} ORDER BY created_at DESC LIMIT 50", ARRAY_A);
    ?>
        <p>
            <a href="?page=apollo-newsletter&tab=campaigns&action=new" class="button button-primary">
                <?php esc_html_e('New Campaign', 'apollo-email'); ?>
            </a>
        </p>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Subject', 'apollo-email'); ?></th>
                    <th><?php esc_html_e('Status', 'apollo-email'); ?></th>
                    <th><?php esc_html_e('Sent', 'apollo-email'); ?></th>
                    <th><?php esc_html_e('Created', 'apollo-email'); ?></th>
                    <th><?php esc_html_e('Actions', 'apollo-email'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($campaigns)) : ?>
                    <tr>
                        <td colspan="5"><?php esc_html_e('No campaigns yet.', 'apollo-email'); ?></td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($campaigns as $campaign) : ?>
                        <tr>
                            <td><?php echo esc_html($campaign['subject']); ?></td>
                            <td><?php echo esc_html(ucfirst($campaign['status'])); ?></td>
                            <td><?php echo esc_html($campaign['sent_count']); ?></td>
                            <td><?php echo esc_html($campaign['created_at']); ?></td>
                            <td>
                                <?php if ('draft' === $campaign['status']) : ?>
                                    <a href="?page=apollo-newsletter&tab=campaigns&action=send&id=<?php echo absint($campaign['id']); ?>" class="button button-small">
                                        <?php esc_html_e('Send', 'apollo-email'); ?>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    <?php
    }

    /**
     * Render settings tab.
     *
     * @param array $settings Current settings.
     */
    private static function render_settings_tab(array $settings): void
    {
    ?>
        <form method="post" action="">
            <?php wp_nonce_field('apollo_newsletter_settings'); ?>
            <input type="hidden" name="apollo_newsletter_settings" value="1">

            <table class="form-table">
                <tr>
                    <th><label for="from_name"><?php esc_html_e('From Name', 'apollo-email'); ?></label></th>
                    <td><input type="text" id="from_name" name="settings[from_name]" value="<?php echo esc_attr($settings['from_name']); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="from_email"><?php esc_html_e('From Email', 'apollo-email'); ?></label></th>
                    <td><input type="email" id="from_email" name="settings[from_email]" value="<?php echo esc_attr($settings['from_email']); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="reply_to"><?php esc_html_e('Reply-To Email', 'apollo-email'); ?></label></th>
                    <td><input type="email" id="reply_to" name="settings[reply_to]" value="<?php echo esc_attr($settings['reply_to']); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Double Opt-in', 'apollo-email'); ?></th>
                    <td><label><input type="checkbox" name="settings[double_optin]" value="1" <?php checked($settings['double_optin']); ?>> <?php esc_html_e('Require email confirmation', 'apollo-email'); ?></label></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Welcome Email', 'apollo-email'); ?></th>
                    <td><label><input type="checkbox" name="settings[welcome_email]" value="1" <?php checked($settings['welcome_email']); ?>> <?php esc_html_e('Send welcome email after confirmation', 'apollo-email'); ?></label></td>
                </tr>
                <tr>
                    <th><label for="welcome_subject"><?php esc_html_e('Welcome Subject', 'apollo-email'); ?></label></th>
                    <td><input type="text" id="welcome_subject" name="settings[welcome_subject]" value="<?php echo esc_attr($settings['welcome_subject']); ?>" class="large-text"></td>
                </tr>
                <tr>
                    <th><label for="welcome_content"><?php esc_html_e('Welcome Content', 'apollo-email'); ?></label></th>
                    <td><textarea id="welcome_content" name="settings[welcome_content]" class="large-text" rows="4"><?php echo esc_textarea($settings['welcome_content']); ?></textarea></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('GDPR Consent', 'apollo-email'); ?></th>
                    <td><label><input type="checkbox" name="settings[gdpr_enabled]" value="1" <?php checked($settings['gdpr_enabled']); ?>> <?php esc_html_e('Require GDPR consent checkbox', 'apollo-email'); ?></label></td>
                </tr>
                <tr>
                    <th><label for="gdpr_text"><?php esc_html_e('GDPR Text', 'apollo-email'); ?></label></th>
                    <td><input type="text" id="gdpr_text" name="settings[gdpr_text]" value="<?php echo esc_attr($settings['gdpr_text']); ?>" class="large-text"></td>
                </tr>
            </table>

            <p class="submit">
                <button type="submit" class="button button-primary"><?php esc_html_e('Save Settings', 'apollo-email'); ?></button>
            </p>
        </form>
    <?php
    }

    /**
     * Get client IP address.
     *
     * @return string
     */
    private static function get_client_ip(): string
    {
        $ip_keys = array('HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR');

        foreach ($ip_keys as $key) {
            if (! empty($_SERVER[$key])) {
                $ip = sanitize_text_field(wp_unslash($_SERVER[$key]));
                // Handle comma-separated IPs.
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '';
    }
}

/**
 * Newsletter Widget
 */
class Newsletter_Widget extends \WP_Widget
{


    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct(
            'apollo_newsletter',
            __('Apollo Newsletter', 'apollo-email'),
            array('description' => __('Newsletter subscription form.', 'apollo-email'))
        );
    }

    /**
     * Widget output.
     *
     * @param array $args     Widget arguments.
     * @param array $instance Widget instance.
     */
    public function widget($args, $instance): void
    {
        echo $args['before_widget'];

        $shortcode_atts = array(
            'title'     => $instance['title'] ?? '',
            'button'    => $instance['button'] ?? __('Subscribe', 'apollo-email'),
            'show_name' => ! empty($instance['show_name']) ? 'true' : 'false',
        );

        echo Native_Newsletter::render_subscription_form($shortcode_atts);

        echo $args['after_widget'];
    }

    /**
     * Widget form.
     *
     * @param array $instance Widget instance.
     */
    public function form($instance): void
    {
        $title     = $instance['title'] ?? '';
        $button    = $instance['button'] ?? __('Subscribe', 'apollo-email');
        $show_name = ! empty($instance['show_name']);
    ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php esc_html_e('Title:', 'apollo-email'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('button')); ?>"><?php esc_html_e('Button Text:', 'apollo-email'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('button')); ?>" name="<?php echo esc_attr($this->get_field_name('button')); ?>" type="text" value="<?php echo esc_attr($button); ?>">
        </p>
        <p>
            <input type="checkbox" id="<?php echo esc_attr($this->get_field_id('show_name')); ?>" name="<?php echo esc_attr($this->get_field_name('show_name')); ?>" <?php checked($show_name); ?>>
            <label for="<?php echo esc_attr($this->get_field_id('show_name')); ?>"><?php esc_html_e('Show name field', 'apollo-email'); ?></label>
        </p>
<?php
    }

    /**
     * Update widget.
     *
     * @param array $new_instance New instance.
     * @param array $old_instance Old instance.
     * @return array
     */
    public function update($new_instance, $old_instance): array
    {
        return array(
            'title'     => sanitize_text_field($new_instance['title'] ?? ''),
            'button'    => sanitize_text_field($new_instance['button'] ?? __('Subscribe', 'apollo-email')),
            'show_name' => ! empty($new_instance['show_name']),
        );
    }
}

// Initialization handled by Plugin.php — do NOT call init() here.