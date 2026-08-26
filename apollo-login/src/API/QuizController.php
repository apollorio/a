<?php

/**
 * Quiz REST Controller
 *
 * @package Apollo\Login
 */

declare(strict_types=1);

namespace Apollo\Login\API;

use Apollo\Login\Quiz\QuizManager;
use Apollo\Login\Quiz\SimonGame;
use Apollo\Login\Security\Firewall;
use WP_REST_Controller;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

// Prevent direct access.
if (! defined('ABSPATH')) {
	exit;
}

/**
 * Quiz Controller class
 */
class QuizController extends WP_REST_Controller
{

	/**
	 * Namespace
	 *
	 * @var string
	 */
	protected $namespace = APOLLO_LOGIN_REST_NAMESPACE;

	/**
	 * Register routes
	 *
	 * @return void
	 */
	public function register_routes(): void
	{
		// GET /quiz/questions
		register_rest_route(
			$this->namespace,
			'/quiz/questions',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array($this, 'get_questions'),
				'permission_callback' => '__return_true',
				'args'                => array(
					'stage' => array(
						'required'          => true,
						'type'              => 'string',
						'enum'              => array('pattern', 'ethics', 'reaction'),
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		// POST /quiz/submit
		register_rest_route(
			$this->namespace,
			'/quiz/submit',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array($this, 'submit_quiz'),
				'permission_callback' => '__return_true', // Public by necessity — the quiz runs before an account exists. Rate-limited in submit_quiz() via check_quiz_rate_limit(), 30 req/min per IP.
				'args'                => array(
					'stage'   => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'answers' => array(
						'required'          => true,
						'type'              => 'array',
						'sanitize_callback' => 'rest_sanitize_array',
					),
					'token'   => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		// POST /simon/submit
		register_rest_route(
			$this->namespace,
			'/simon/submit',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array($this, 'submit_simon'),
				'permission_callback' => '__return_true', // Public by necessity — the game runs before an account exists. Rate-limited in submit_simon() via check_quiz_rate_limit(), 30 req/min per IP.
				'args'                => array(
					'level'    => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
					'sequence' => array(
						'required'          => true,
						'type'              => 'array',
						'sanitize_callback' => 'rest_sanitize_array',
					),
					'success'  => array(
						'required'          => false,
						'type'              => 'boolean',
						'sanitize_callback' => 'rest_sanitize_boolean',
					),
					'token'    => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		// GET /simon/highscores
		register_rest_route(
			$this->namespace,
			'/simon/highscores',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array($this, 'get_simon_highscores'),
				'permission_callback' => '__return_true',
				'args'                => array(
					'limit' => array(
						'required'          => false,
						'type'              => 'integer',
						'default'           => 10,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);
	}

	/**
	 * Get quiz questions
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function get_questions(WP_REST_Request $request): WP_REST_Response
	{
		$stage     = $request->get_param('stage');
		$questions = QuizManager::get_questions($stage);

		return new WP_REST_Response(
			array(
				'stage'     => $stage,
				'questions' => $questions,
			),
			200
		);
	}

	/**
	 * Submit quiz answers
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function submit_quiz(WP_REST_Request $request): WP_REST_Response
	{
		$stage   = $request->get_param('stage');
		$answers = $request->get_param('answers');
		$token   = $request->get_param('token') ?: wp_generate_password(32, false);

		// --- Rate limit: 30 requests/min per IP ---
		$client_ip    = Firewall::get_client_ip();
		$rate_limited = $this->check_quiz_rate_limit($client_ip);
		if ($rate_limited !== null) {
			return $rate_limited;
		}

		// --- IP binding: lock token to the originating IP ---
		$quiz_data = get_transient('apollo_quiz_' . $token) ?: array();

		if (empty($quiz_data)) {
			// First submission for this token — bind IP now.
			$quiz_data['_ip'] = $client_ip;
		} elseif (isset($quiz_data['_ip']) && $quiz_data['_ip'] !== $client_ip) {
			return new WP_REST_Response(
				array(
					'code'    => 'token_mismatch',
					'message' => __('Token inválido para este dispositivo.', 'apollo-login'),
				),
				403
			);
		}

		// Calculate score
		$score = $this->calculate_score($stage, $answers);

		$quiz_data[$stage] = array(
			'score'   => $score,
			'answers' => $answers,
		);
		set_transient('apollo_quiz_' . $token, $quiz_data, 60 * MINUTE_IN_SECONDS);

		return new WP_REST_Response(
			array(
				'success' => true,
				'stage'   => $stage,
				'score'   => $score,
				'token'   => $token,
			),
			200
		);
	}

	/**
	 * Submit Simon game result
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function submit_simon(WP_REST_Request $request): WP_REST_Response
	{
		$level    = $request->get_param('level');
		$sequence = $request->get_param('sequence');
		$token    = $request->get_param('token') ?: wp_generate_password(32, false);

		// --- Rate limit: 30 requests/min per IP ---
		$client_ip    = Firewall::get_client_ip();
		$rate_limited = $this->check_quiz_rate_limit($client_ip);
		if ($rate_limited !== null) {
			return $rate_limited;
		}

		// --- IP binding: reject if token was created from a different IP ---
		$quiz_data = get_transient('apollo_quiz_' . $token) ?: array();

		if (! empty($quiz_data) && isset($quiz_data['_ip']) && $quiz_data['_ip'] !== $client_ip) {
			return new WP_REST_Response(
				array(
					'code'    => 'token_mismatch',
					'message' => __('Token inválido para este dispositivo.', 'apollo-login'),
				),
				403
			);
		}

		// --- Server-side Simon validation ---
		// Accepted colors only — anything else is an invalid/fabricated submission.
		$valid_colors    = array('red', 'green', 'blue', 'yellow');
		$sequence_clean  = array_values(
			array_filter(
				array_map('sanitize_text_field', (array) $sequence),
				fn($c) => in_array($c, $valid_colors, true)
			)
		);

		// Server determines success: all submitted colors must be valid
		// and the sequence length must exactly match the declared level.
		$server_success = (count($sequence_clean) === $level && $level >= 1 && $level <= 4);

		// Ignore client-supplied $success entirely — use server-computed value.
		$score = $server_success ? $level * 25 : 0;

		// Save to transient (preserve existing quiz_data, including _ip binding)
		$quiz_data['simon'] = array(
			'score'    => $score,
			'level'    => $level,
			'sequence' => $sequence_clean,
			'success'  => $server_success,
		);
		set_transient('apollo_quiz_' . $token, $quiz_data, 60 * MINUTE_IN_SECONDS);

		return new WP_REST_Response(
			array(
				'success' => $server_success,
				'level'   => $level,
				'score'   => $score,
				'token'   => $token,
			),
			200
		);
	}

	/**
	 * Get Simon highscores
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function get_simon_highscores(WP_REST_Request $request): WP_REST_Response
	{
		$limit  = $request->get_param('limit');
		$scores = SimonGame::get_leaderboard($limit);

		return new WP_REST_Response(
			array(
				'scores' => $scores,
			),
			200
		);
	}

	/**
	 * Enforce per-IP rate limit on quiz/simon submission endpoints.
	 *
	 * 30 requests per minute per IP. Returns a WP_REST_Response with status 429
	 * on violation, or null when the request is within limits.
	 *
	 * @param string $ip Client IP address.
	 * @return WP_REST_Response|null
	 */
	private function check_quiz_rate_limit(string $ip): ?WP_REST_Response
	{
		$key  = 'apollo_rl_quiz_' . md5($ip);
		$hits = (int) get_transient($key);
		++$hits;
		set_transient($key, $hits, MINUTE_IN_SECONDS);

		if ($hits > 30) {
			return new WP_REST_Response(
				array(
					'code'    => 'rate_limited',
					'message' => __('Muitas tentativas. Aguarde um momento.', 'apollo-login'),
				),
				429
			);
		}

		return null;
	}

	/**
	 * Calculate quiz score
	 *
	 * @param string $stage   Stage name.
	 * @param array  $answers User answers.
	 * @return int
	 */
	private function calculate_score(string $stage, array $answers): int
	{
		// Reaction stage is a performance test (click-speed), not question-answer.
		if ('reaction' === $stage) {
			// Score is based on clicks completed within time limit.
			$clicks = isset($answers['clicks']) ? absint($answers['clicks']) : 0;
			$hits   = isset($answers['hits']) ? absint($answers['hits']) : 0;
			// Base score for completing the stage, plus bonus for accuracy.
			return ($hits > 0) ? min(100, $hits * 10) : 50;
		}

		$questions = QuizManager::get_questions($stage);
		$score     = 0;

		foreach ($questions as $question) {
			$question_id = $question['id'] ?? null;
			if (null === $question_id) {
				continue;
			}
			$user_answer = $answers[$question_id] ?? null;

			if (null !== $user_answer && isset($question['correct']) && $user_answer === $question['correct']) {
				$weight = $question['weight'] ?? 10;
				$score += $weight;
			}
		}

		return $score;
	}
}
