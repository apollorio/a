<?php

/**
 * CENA-RIO Event Submissions
 *
 * Handles event submission flow for CENA-ROLE users.
 * Creates draft/pending events only, requiring mod.
 *
 * @package Apollo\Event
 * @since 3.1.0
 */

declare(strict_types=1);

namespace Apollo\Event;

use WP_Error;
use WP_Query;
use WP_REST_Request;
use WP_REST_Response;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * CENA-RIO Submissions class.
 */
class Cena_Rio_Submissions
{

    /**
     * Initialize
     */
    public static function init(): void
    {
        // Register shortcode for front-end submission form.
        add_shortcode('apollo_cena_submit_event', array(self::class, 'render_submission_form'));

        // Handle form submission.
        add_action('template_redirect', array(self::class, 'handle_submission'));

        // REST API endpoint for AJAX submissions.
        add_action('rest_api_init', array(self::class, 'register_rest_routes'));
    }

    /**
     * Register REST API routes
     */
    public static function register_rest_routes(): void
    {
        // Get CENA-RIO internal events (for industry calendar).
        register_rest_route(
            'apollo/v1',
            'cena-rio/agenda',
            array(
                'methods'             => 'GET',
                'callback'            => array(self::class, 'rest_get_events'),
                'permission_callback' => array(self::class, 'check_submission_permission'),
                // Only CENA members.
            )
        );

        // Submit new event (creates as 'expected').
        register_rest_route(
            'apollo/v1',
            'cena-rio/enviar',
            array(
                'methods'             => 'POST',
                'callback'            => array(__CLASS__, 'rest_submit_event'),
                'permission_callback' => array(__CLASS__, 'check_submission_permission'),
                'args'                => array(
                    'event_title'       => array(
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'event_description' => array(
                        'type'              => 'string',
                        'sanitize_callback' => 'wp_kses_post',
                    ),
                    'event_start_date'  => array(
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'event_end_date'    => array(
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'event_start_time'  => array(
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'event_end_time'    => array(
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'event_local'       => array(
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'event_lat'         => array(
                        'type' => 'number',
                    ),
                    'event_lng'         => array(
                        'type' => 'number',
                    ),
                ),
            )
        );

        // CENA-RIO internal confirm (industry confirms event → goes to MOD queue).
        register_rest_route(
            'apollo/v1',
            'cena-rio/confirmar/(?P<id>\d+)',
            array(
                'methods'             => 'POST',
                'callback'            => array(__CLASS__, 'rest_confirm_event'),
                'permission_callback' => array(__CLASS__, 'check_submission_permission'),
                'args'                => array(
                    'id' => array(
                        'required' => true,
                        'type'     => 'integer',
                    ),
                ),
            )
        );

        // CENA-RIO internal unconfirm (revert back to expected).
        register_rest_route(
            'apollo/v1',
            'cena-rio/cancelar/(?P<id>\d+)',
            array(
                'methods'             => 'POST',
                'callback'            => array(__CLASS__, 'rest_unconfirm_event'),
                'permission_callback' => array(__CLASS__, 'check_submission_permission'),
                'args'                => array(
                    'id' => array(
                        'required' => true,
                        'type'     => 'integer',
                    ),
                ),
            )
        );
    }

    /**
     * Check if user can submit events
     *
     * @return bool True if user has permission.
     */
    public static function check_submission_permission(): bool
    {
        // Check for namespaced or global class.
        if (class_exists('\Apollo_Core\Cena_Rio_Roles')) {
            return \Apollo_Core\Cena_Rio_Roles::user_can_submit();
        }
        if (class_exists('Apollo_Cena_Rio_Roles')) {
            return \Apollo_Cena_Rio_Roles::user_can_submit();
        }
        // Fallback to capability check.
        return current_user_can('apollo_cena_submit_events') || current_user_can('apollo_access_cena_rio') || current_user_can('manage_options');
    }

    /**
     * REST API: Get events for CENA-RIO internal calendar
     *
     * This returns ALL CENA-RIO events (expected + confirmed) for industry view.
     * These are NOT public - only visible to logged-in CENA-ROLE users.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response Response.
     */
    public static function rest_get_events(WP_REST_Request $request): WP_REST_Response
    {
        // CENA-RIO calendar shows ALL internal events (private + pending + draft).
        // These are NOT public events - only for industry members.
        $event_cpt = defined('APOLLO_CPT_EVENT') ? APOLLO_CPT_EVENT : 'event';

        $query = new WP_Query(
            array(
                'post_type'      => $event_cpt,
                'post_status'    => array('private', 'pending', 'draft', 'publish'),
                'posts_per_page' => -1,
                'meta_query'     => array(
                    array(
                        'key'   => '_apollo_source',
                        'value' => 'cena-rio',
                    ),
                ),
                'orderby'        => 'meta_value',
                'meta_key'       => '_event_start_date',
                'order'          => 'ASC',
            )
        );

        $events = array();
        foreach ($query->posts as $post) {
            $cena_status = get_post_meta($post->ID, '_apollo_cena_status', true);

            // Map CENA internal status to display status.
            // expected = pending internal approval from industry.
            // confirmed = approved by industry, waiting MOD approval for public.
            // published = approved by MOD, now public.
            $display_status = 'expected';
            if ('confirmed' === $cena_status || 'approved' === $cena_status) {
                $display_status = 'confirmed';
            }
            if ('publish' === $post->post_status) {
                $display_status = 'published';
                // Already public.
            }

            $events[] = array(
                'id'                             => $post->ID,
                'title'                          => get_the_title($post->ID),
                'description'                    => $post->post_content,
                'post_status'                    => $post->post_status,
                'cena_status'                    => $cena_status,
                'status'                         => $display_status,
                // expected | confirmed | published.
                'start_date' => get_post_meta($post->ID, '_event_start_date', true),
                'end_date'                       => get_post_meta($post->ID, '_event_end_date', true),
                'start_time'                     => get_post_meta($post->ID, '_event_start_time', true),
                'end_time'                       => get_post_meta($post->ID, '_event_end_time', true),
                'loc'                            => get_post_meta($post->ID, '_event_local_name', true),
                'lat'                            => get_post_meta($post->ID, '_event_lat', true),
                'lng'                            => get_post_meta($post->ID, '_event_lng', true),
                'author_id'                      => $post->post_author,
                'dateKey'                        => get_post_meta($post->ID, '_event_start_date', true),
                'is_public'                      => 'publish' === $post->post_status,
                'awaiting_mod'                   => 'confirmed' === $cena_status && 'draft' === $post->post_status,
            );
        } //end foreach

        return new WP_REST_Response(
            array(
                'success' => true,
                'total'   => count($events),
                'events'  => $events,
            ),
            200
        );
    }

    /**
     * REST API: Confirm event (industry internal confirmation)
     *
     * When CENA-RIO industry confirms an event:
     * 1. _apollo_cena_status changes from 'expected' to 'confirmed'
     * 2. post_status changes from 'private' to 'draft'
     * 3. Event now appears in MOD queue for public approval
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error Response.
     */
    public static function rest_confirm_event(WP_REST_Request $request)
    {
        $post_id = absint($request->get_param('id'));

        $post = get_post($post_id);
        $event_cpt = defined('APOLLO_CPT_EVENT') ? APOLLO_CPT_EVENT : 'event';
        if (! $post || $event_cpt !== $post->post_type) {
            return new WP_Error(
                'invalid_post',
                __('Evento não encontrado.', 'apollo-core'),
                array('status' => 404)
            );
        }

        // Verify this is a CENA-RIO event.
        $source = get_post_meta($post_id, '_apollo_source', true);
        if ('cena-rio' !== $source) {
            return new WP_Error(
                'invalid_source',
                __('Este evento não pertence ao CENA-RIO.', 'apollo-core'),
                array('status' => 400)
            );
        }

        // Check if already confirmed.
        $cena_status = get_post_meta($post_id, '_apollo_cena_status', true);
        if ('confirmed' === $cena_status) {
            return new WP_Error(
                'already_confirmed',
                __('Este evento já foi confirmado.', 'apollo-core'),
                array('status' => 400)
            );
        }

        // Update CENA status to confirmed.
        update_post_meta($post_id, '_apollo_cena_status', 'confirmed');
        update_post_meta($post_id, '_apollo_cena_confirmed_by', get_current_user_id());
        update_post_meta($post_id, '_apollo_cena_confirmed_at', current_time('mysql'));

        // Change post status to DRAFT - NOW it appears in MOD queue!
        wp_update_post(
            array(
                'ID'          => $post_id,
                'post_status' => 'draft',
            )
        );

        // Log action.
        if (function_exists('apollo_mod_log_action')) {
            apollo_mod_log_action(
                get_current_user_id(),
                'cena_event_confirmed',
                $event_cpt,
                $post_id,
                array(
                    'title'  => $post->post_title,
                    'action' => 'Industry confirmed - sent to MOD queue',
                )
            );
        }

        return new WP_REST_Response(
            array(
                'success' => true,
                'message' => __('Evento confirmado! Enviado para aprovação do MOD.', 'apollo-core'),
                'event'   => array(
                    'id'           => $post_id,
                    'cena_status'  => 'confirmed',
                    'post_status'  => 'draft',
                    'awaiting_mod' => true,
                ),
            ),
            200
        );
    }

    /**
     * REST API: Unconfirm event (revert to expected)
     *
     * Reverts a confirmed event back to expected status.
     * Removes from MOD queue.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error Response.
     */
    public static function rest_unconfirm_event(WP_REST_Request $request)
    {
        $post_id = absint($request->get_param('id'));

        $post     = get_post($post_id);
        $event_cpt = defined('APOLLO_CPT_EVENT') ? APOLLO_CPT_EVENT : 'event';
        if (! $post || $event_cpt !== $post->post_type) {
            return new WP_Error(
                'invalid_post',
                __('Evento não encontrado.', 'apollo-core'),
                array('status' => 404)
            );
        }

        // Can't unconfirm if already published.
        if ('publish' === $post->post_status) {
            return new WP_Error(
                'already_published',
                __('Não é possível reverter um evento já publicado.', 'apollo-core'),
                array('status' => 400)
            );
        }

        // Update CENA status back to expected.
        update_post_meta($post_id, '_apollo_cena_status', 'expected');
        delete_post_meta($post_id, '_apollo_cena_confirmed_by');
        delete_post_meta($post_id, '_apollo_cena_confirmed_at');

        // Change post status back to PRIVATE - removes from MOD queue.
        wp_update_post(
            array(
                'ID'          => $post_id,
                'post_status' => 'private',
            )
        );

        return new WP_REST_Response(
            array(
                'success' => true,
                'message' => __('Evento revertido para esperado.', 'apollo-core'),
                'event'   => array(
                    'id'          => $post_id,
                    'cena_status' => 'expected',
                    'post_status' => 'private',
                ),
            ),
            200
        );
    }

    /**
     * REST API: Submit event
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error Response.
     */
    public static function rest_submit_event(WP_REST_Request $request)
    {
        $event_data = array(
            'title'       => $request->get_param('event_title'),
            'description' => $request->get_param('event_description'),
            'start_date'  => $request->get_param('event_start_date'),
            'end_date'    => $request->get_param('event_end_date') ?? '',
            'start_time'  => $request->get_param('event_start_time') ?? '',
            'end_time'    => $request->get_param('event_end_time') ?? '',
            'loc'         => $request->get_param('event_local') ?? '',
            'lat'         => $request->get_param('event_lat'),
            'lng'         => $request->get_param('event_lng'),
        );

        $event_id = self::create_cena_event($event_data);

        if (is_wp_error($event_id)) {
            return $event_id;
        }

        return new WP_REST_Response(
            array(
                'success'  => true,
                'message'  => __('Evento enviado para moderação com sucesso!', 'apollo-core'),
                'event_id' => $event_id,
                'status'   => 'pending',
            ),
            201
        );
    }

    /**
     * Render submission form shortcode
     *
     * @return string HTML output.
     */
    public static function render_submission_form(): string
    {
        // Check if user has permission using namespace-aware check.
        if (! self::check_submission_permission()) {
            return '<div class="apollo-cena-notice" style="padding:20px;background:#fef2f2;border-left:4px solid #dc2626;border-radius:8px;color:#991b1b">
				<strong>Acesso Restrito</strong><br>
				Você precisa ter a role <strong>Cena::Rio Membro</strong> ou superior para enviar eventos.
			</div>';
        }

        // Check if form was submitted (sanitize GET parameter).
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This is a redirect confirmation parameter.
        $submitted = isset($_GET['cena_submitted']) ? sanitize_text_field(wp_unslash($_GET['cena_submitted'])) : '';
        if ('1' === $submitted) {
            return '<div class="apollo-cena-success" style="padding:20px;background:#f0fdf4;border-left:4px solid #10b981;border-radius:8px;color:#065f46">
				<strong>✓ Evento Enviado!</strong><br>
				Seu evento foi enviado para moderação. Aguarde a aprovação da equipe Cena::Rio.
			</div>';
        }

        ob_start();
?>
        <div class="apollo-cena-submission-form" id="apollo-cena-form-container">
            <form method="post" action="" id="apollo-cena-event-form" class="space-y-4">
                <?php wp_nonce_field('apollo_cena_submit_event', 'apollo_cena_nonce'); ?>
                <input type="hidden" name="action" value="apollo_cena_submit_event">

                <!-- Title -->
                <div>
                    <label for="event_title" class="block text-sm font-bold text-slate-700 mb-1">
                        Nome do Evento <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        id="event_title"
                        name="event_title"
                        required
                        class="apollo-input w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                        placeholder="Ex: Festival Tropicalis 2025" />
                </div>

                <!-- Description -->
                <div>
                    <label for="event_description" class="block text-sm font-bold text-slate-700 mb-1">
                        Descrição <span class="text-red-500">*</span>
                    </label>
                    <textarea
                        id="event_description"
                        name="event_description"
                        required
                        rows="4"
                        class="apollo-textarea w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                        placeholder="Descreva seu evento..."></textarea>
                </div>

                <!-- Date Range -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="event_start_date" class="block text-sm font-bold text-slate-700 mb-1">
                            Data Início <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="date"
                            id="event_start_date"
                            name="event_start_date"
                            required
                            class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"  data-apollo-date-picker="true">
                    </div>
                    <div>
                        <label for="event_end_date" class="block text-sm font-bold text-slate-700 mb-1">
                            Data Fim
                        </label>
                        <input
                            type="date"
                            id="event_end_date"
                            name="event_end_date"
                            class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"  data-apollo-date-picker="true">
                    </div>
                </div>

                <!-- Time Range -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="event_start_time" class="block text-sm font-bold text-slate-700 mb-1">
                            Horário Início
                        </label>
                        <input
                            type="time"
                            id="event_start_time"
                            name="event_start_time"
                            class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"  data-apollo-date-picker="true">
                    </div>
                    <div>
                        <label for="event_end_time" class="block text-sm font-bold text-slate-700 mb-1">
                            Horário Fim
                        </label>
                        <input
                            type="time"
                            id="event_end_time"
                            name="event_end_time"
                            class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"  data-apollo-date-picker="true">
                    </div>
                </div>

                <!-- Local -->
                <div>
                    <label for="event_local" class="block text-sm font-bold text-slate-700 mb-1">
                        Local do Evento
                    </label>
                    <input
                        type="text"
                        id="event_local"
                        name="event_local"
                        class="apollo-input w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                        placeholder="Ex: Copacabana, Rio de Janeiro" />
                </div>

                <!-- Coordinates (Optional - for map integration) -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="event_lat" class="block text-sm font-bold text-slate-700 mb-1">
                            Latitude (opcional)
                        </label>
                        <input
                            type="number"
                            id="event_lat"
                            name="event_lat"
                            step="0.0001"
                            class="apollo-input w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                            placeholder="-22.9068" />
                    </div>
                    <div>
                        <label for="event_lng" class="block text-sm font-bold text-slate-700 mb-1">
                            Longitude (opcional)
                        </label>
                        <input
                            type="number"
                            id="event_lng"
                            name="event_lng"
                            step="0.0001"
                            class="apollo-input w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                            placeholder="-43.1729" />
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="pt-4">
                    <button
                        type="submit"
                        class="w-full md:w-auto px-6 py-3 bg-slate-900 text-white font-bold rounded-lg hover:bg-slate-800 transition-colors flex items-center justify-center gap-2">
                        <i class="ri-send-plane-fill"></i>
                        Enviar para Moderação
                    </button>
                </div>

                <p class="text-sm text-slate-500 mt-2">
                    <i class="ri-information-line"></i>
                    Seu evento será revisado pela equipe de moderação antes de ser publicado no calendário público.
                </p>
            </form>
        </div>

        <style>
            .apollo-cena-submission-form {
                max-width: 800px;
                margin: 0 auto;
            }

            .apollo-cena-submission-form input:focus,
            .apollo-cena-submission-form textarea:focus {
                outline: none;
            }
        </style>
<?php
        $output = ob_get_clean();

        return false !== $output ? $output : '';
    }

    /**
     * Handle form submission (traditional POST)
     */
    public static function handle_submission(): void
    {
        // Check if this is our form submission.
        $action = isset($_POST['action']) ? sanitize_text_field(wp_unslash($_POST['action'])) : '';
        if ('apollo_cena_submit_event' !== $action) {
            return;
        }

        // Verify nonce.
        $nonce = isset($_POST['apollo_cena_nonce']) ? sanitize_text_field(wp_unslash($_POST['apollo_cena_nonce'])) : '';
        if (! wp_verify_nonce($nonce, 'apollo_cena_submit_event')) {
            wp_die(esc_html__('Security check failed.', 'apollo-core'));
        }

        // Check permission using namespace-aware check.
        if (! self::check_submission_permission()) {
            wp_die(esc_html__('You do not have permission to submit events.', 'apollo-core'));
        }

        // Sanitize input.
        $event_data = array(
            'title'       => isset($_POST['event_title']) ? sanitize_text_field(wp_unslash($_POST['event_title'])) : '',
            'description' => isset($_POST['event_description']) ? wp_kses_post(wp_unslash($_POST['event_description'])) : '',
            'start_date'  => isset($_POST['event_start_date']) ? sanitize_text_field(wp_unslash($_POST['event_start_date'])) : '',
            'end_date'    => isset($_POST['event_end_date']) ? sanitize_text_field(wp_unslash($_POST['event_end_date'])) : '',
            'start_time'  => isset($_POST['event_start_time']) ? sanitize_text_field(wp_unslash($_POST['event_start_time'])) : '',
            'end_time'    => isset($_POST['event_end_time']) ? sanitize_text_field(wp_unslash($_POST['event_end_time'])) : '',
            'loc'         => isset($_POST['event_local']) ? sanitize_text_field(wp_unslash($_POST['event_local'])) : '',
            'lat'         => isset($_POST['event_lat']) ? floatval($_POST['event_lat']) : null,
            'lng'         => isset($_POST['event_lng']) ? floatval($_POST['event_lng']) : null,
        );

        // Create event.
        $event_id = self::create_cena_event($event_data);

        if (is_wp_error($event_id)) {
            wp_die(esc_html($event_id->get_error_message()));
        }

        // Redirect with success message.
        $referer      = wp_get_referer();
        $redirect_url = $referer ? add_query_arg('cena_submitted', '1', $referer) : home_url('/cena-rio/?cena_submitted=1');
        wp_safe_redirect($redirect_url);
        exit;
    }

    /**
     * Create CENA event as PRIVATE (internal only - NOT in MOD queue)
     *
     * Events start as 'private' with _apollo_cena_status = 'expected'
     * They are ONLY visible in the CENA-RIO internal calendar.
     *
     * When industry confirms → status changes to 'confirmed' and post becomes 'draft'
     * Then it appears in MOD queue for public approval.
     *
     * @param array $event_data Event data.
     * @return int|WP_Error Post ID or error.
     */
    private static function create_cena_event(array $event_data)
    {
        // Validate required fields.
        if (empty($event_data['title']) || empty($event_data['start_date'])) {
            return new WP_Error(
                'missing_required_fields',
                __('Título e data de início são obrigatórios.', 'apollo-core')
            );
        }

        // Create post as PRIVATE - NOT visible to MOD yet!
        // Only visible in CENA-RIO internal calendar.
        $event_cpt = defined('APOLLO_CPT_EVENT') ? APOLLO_CPT_EVENT : 'event';

        $post_id = wp_insert_post(
            array(
                'post_type'    => $event_cpt,
                'post_title'   => $event_data['title'],
                'post_content' => $event_data['description'],
                'post_status'  => 'private',
                // Internal only - NOT in MOD queue.
                'post_author' => get_current_user_id(),
            ),
            true
        );

        if (is_wp_error($post_id)) {
            return $post_id;
        }

        // Add CENA-specific meta.
        update_post_meta($post_id, '_apollo_source', 'cena-rio');
        update_post_meta($post_id, '_apollo_cena_status', 'expected');
        // Expected = awaiting industry confirmation.
        update_post_meta($post_id, '_apollo_cena_submitted_by', get_current_user_id());
        update_post_meta($post_id, '_apollo_cena_submitted_at', current_time('mysql'));

        // Add event dates (apollo-events-manager format).
        update_post_meta($post_id, '_event_start_date', $event_data['start_date']);

        if (! empty($event_data['end_date'])) {
            update_post_meta($post_id, '_event_end_date', $event_data['end_date']);
        }

        if (! empty($event_data['start_time'])) {
            update_post_meta($post_id, '_event_start_time', $event_data['start_time']);
        }

        if (! empty($event_data['end_time'])) {
            update_post_meta($post_id, '_event_end_time', $event_data['end_time']);
        }

        // Add loc (local/venue).
        if (! empty($event_data['loc'])) {
            update_post_meta($post_id, '_event_local_name', $event_data['loc']);
        }

        // Add coordinates.
        if (null !== $event_data['lat'] && null !== $event_data['lng']) {
            update_post_meta($post_id, '_event_lat', $event_data['lat']);
            update_post_meta($post_id, '_event_lng', $event_data['lng']);
        }

        // Log submission.
        if (function_exists('apollo_mod_log_action')) {
            apollo_mod_log_action(
                get_current_user_id(),
                'cena_event_submitted',
                $event_cpt,
                $post_id,
                array(
                    'title'  => $event_data['title'],
                    'status' => 'pending',
                    'source' => 'cena-rio',
                )
            );
        }

        return $post_id;
    }
}
