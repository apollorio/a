<?php

/**
 * Role-based Access Control for the Gestor admin page.
 *
 * Access matrix (WP role → Gestor access level):
 *  administrator  → level 10 (display "apollo")   — full edit
 *  editor         → level 7  (display "MOD")       — full edit
 *  author         → level 5  (display "cena+") — full edit
 *  (any other)    → level 1  (display "team")      — limited (tasks + mural only, if invited)
 *
 * Team-level users see the menu only when they belong to at least one event team.
 *
 * @package Apollo\Gestor
 */

declare(strict_types=1);

namespace Apollo\Gestor\Admin;

use Apollo\Gestor\Model\Team;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class RoleAccess {

	/**
	 * WP role → gestor access config.
	 * Roles not listed here get level 1 (team) if invited, else 0.
	 */
	private const ROLE_MAP = [
		'administrator' => [
			'display' => 'apollo',
			'level'   => 10,
		],
		'editor' => [
			'display' => 'MOD',
			'level'   => 7,
		],
		'author' => [
			'display' => 'cena+',
			'level'   => 5,
		],
	];

	/** Minimum level required for full editing access */
	public const LEVEL_FULL = 5;

	/** Team level — limited to tasks + mural */
	public const LEVEL_TEAM = 1;

	/** No access */
	public const LEVEL_NONE = 0;

	/**
	 * Resolve the current user's access context.
	 *
	 * @return array{level:int, display:string, can_edit:bool, is_team:bool, event_ids:int[]}
	 */
	public static function resolve(): array {
		$user = wp_get_current_user();

		if ( ! $user->exists() ) {
			return self::build( self::LEVEL_NONE, '', false, false, [] );
		}

		// Check mapped roles (highest wins)
		foreach ( self::ROLE_MAP as $role => $cfg ) {
			if ( in_array( $role, (array) $user->roles, true ) ) {
				return self::build(
					$cfg['level'],
					$cfg['display'],
					true,
					false,
					[]
				);
			}
		}

		// Not a mapped role → check team membership
		$event_ids = Team::get_user_event_ids( $user->ID );

		if ( ! empty( $event_ids ) ) {
			return self::build(
				self::LEVEL_TEAM,
				'team',
				false,
				true,
				$event_ids
			);
		}

		// No role match, no team membership → no access
		return self::build( self::LEVEL_NONE, '', false, false, [] );
	}

	/**
	 * Check if a user (by ID) can access the Gestor page at all.
	 *
	 * Used as a capability meta-check in register_menu.
	 *
	 * @param int $user_id
	 * @return bool
	 */
	public static function can_access( int $user_id = 0 ): bool {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		if ( ! $user_id ) {
			return false;
		}

		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return false;
		}

		// Mapped roles always have access
		foreach ( self::ROLE_MAP as $role => $cfg ) {
			if ( in_array( $role, (array) $user->roles, true ) ) {
				return true;
			}
		}

		// Team members have access
		$event_ids = Team::get_user_event_ids( $user_id );
		return ! empty( $event_ids );
	}

	/**
	 * Get the tabs a given access level can see.
	 *
	 * @param int $level
	 * @return string[]  Array of panel IDs
	 */
	public static function allowed_tabs( int $level ): array {
		if ( $level >= self::LEVEL_FULL ) {
			// Full access: all tabs
			return [
				'panel-overview',
				'panel-kanban',
				'panel-equipe',
				'panel-budget',
				'panel-financeiro',
				'panel-ctrl-financeiro',
				'panel-fornecedores',
				'panel-cronograma',
				'panel-doc',
				'panel-assina',
				'panel-mural',
			];
		}

		if ( $level >= self::LEVEL_TEAM ) {
			// Team: tasks (kanban) + mural only
			return [
				'panel-kanban',
				'panel-mural',
			];
		}

		return [];
	}

	/**
	 * Build the context array.
	 *
	 * @return array{level:int, display:string, can_edit:bool, is_team:bool, event_ids:int[]}
	 */
	private static function build( int $level, string $display, bool $can_edit, bool $is_team, array $event_ids ): array {
		return [
			'level'     => $level,
			'display'   => $display,
			'can_edit'  => $can_edit,
			'is_team'   => $is_team,
			'event_ids' => $event_ids,
		];
	}
}
