<?php
/**
 * Router — registra todas as rotas REST do Apollo Local
 *
 * Cada rota delega para sua própria Endpoint class.
 * Nunca contém lógica de negócio.
 *
 * @package Apollo\Local\API
 */

declare(strict_types=1);

namespace Apollo\Local\API;

use Apollo\Core\API\RestBase;
use Apollo\Local\API\Endpoints\ListEndpoint;
use Apollo\Local\API\Endpoints\SingleEndpoint;
use Apollo\Local\API\Endpoints\CreateEndpoint;
use Apollo\Local\API\Endpoints\UpdateEndpoint;
use Apollo\Local\API\Endpoints\DeleteEndpoint;
use Apollo\Local\API\Endpoints\NearbyEndpoint;
use Apollo\Local\API\Schema\LocalSchema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Router extends RestBase {

	protected $rest_base = 'local';

	public function __construct() {
		parent::__construct();
	}

	public function register_routes(): void {
		$ns = $this->namespace;
		$rb = '/' . $this->rest_base;

		// ── Nearby (sempre antes do /{id} para evitar colisão) ────────────
		register_rest_route(
			$ns,
			$rb . '/proximos',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( new NearbyEndpoint(), 'handle' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'lat'      => array( 'type' => 'number', 'required' => true, 'sanitize_callback' => fn( $v ) => (float) $v ),
					'lng'      => array( 'type' => 'number', 'required' => true, 'sanitize_callback' => fn( $v ) => (float) $v ),
					'radius'   => array( 'type' => 'number', 'default' => 5, 'sanitize_callback' => fn( $v ) => (float) $v ),
					'per_page' => array( 'type' => 'integer', 'default' => 20, 'sanitize_callback' => 'absint' ),
				),
			)
		);

		// ── Collection: GET (list) / POST (create) ────────────────────────
		register_rest_route(
			$ns,
			$rb,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( new ListEndpoint(), 'handle' ),
					'permission_callback' => '__return_true',
					'args'                => LocalSchema::collection_params(),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( new CreateEndpoint(), 'handle' ),
					// Logged-in (not admin-only): the event form's "Cadastrar Local"
					// quick-add posts here as the organizer. Non-publishers create
					// 'pending' (moderated) — see CreateEndpoint. Update/Delete stay admin.
					'permission_callback' => array( $this, 'is_logged_in' ),
					'args'                => LocalSchema::write_params(),
				),
			)
		);

		// ── Single: GET / PUT / DELETE ────────────────────────────────────
		$id_arg = array(
			'id' => array(
				'type'              => 'integer',
				'required'          => true,
				'sanitize_callback' => 'absint',
				'validate_callback' => fn( $v ) => is_numeric( $v ) && (int) $v > 0,
			),
		);

		register_rest_route(
			$ns,
			$rb . '/(?P<id>[\d]+)',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( new SingleEndpoint(), 'handle' ),
					'permission_callback' => '__return_true',
					'args'                => $id_arg,
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( new UpdateEndpoint(), 'handle' ),
					'permission_callback' => array( $this, 'is_admin' ),
					'args'                => array_merge( $id_arg, LocalSchema::write_params() ),
				),
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( new DeleteEndpoint(), 'handle' ),
					'permission_callback' => array( $this, 'is_admin' ),
					'args'                => $id_arg,
				),
			)
		);
	}
}
