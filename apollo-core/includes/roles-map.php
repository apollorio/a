<?php

/**
 * Apollo canonical USER-ROLE map — single source of truth.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * USER ROLE DEFINED EXCLUSIVELY (almost 100%) FOR CONTROL OF CAPABILITIES
 * AND PERMISSIONS, WHERE:
 *
 *  - Administrator  Full access (everything: users, plugins, themes,
 *                   settings, all content).
 *  - Editor         Publish/edit/delete any posts* & pages, moderate
 *                   comments, manage categories/tags. No site
 *                   settings/plugins/users.
 *  - Author         Publish/edit/delete own posts* only. Upload media.
 *  - Contributor    Write/edit own posts* only. Cannot publish or upload media.
 *  - Subscriber     Read content + manage own profile only.
 *
 *  (*) "post" = ONLY the CPT 'event'. !important
 *
 * Organization of user-role by just RELABELING frontend roles from WordPress
 * while keeping on backend the exact same words to avoid collapsing it.
 * Relation between frontend and backend:
 *
 *  | Backend         | Frontend (labeled) | Level |
 *  |-----------------|--------------------|-------|
 *  | `administrator` | **apollo**         | 10    |
 *  | `editor`        | **MOD**            | 7     |
 *  | `author`        | **cena+**          | 5     |
 *  | `contributor`   | **cena**           | 3     |
 *  | `subscriber`    | **clubber**        | 1     |
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * @package Apollo\Core
 * @since   6.3.0
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Canonical backend→frontend role map.
 *
 * @return array<string, array{label: string, level: int, backend: string, desc: string}>
 */
function apollo_roles_map(): array
{
	return array(
		'administrator' => array(
			'backend' => 'administrator',
			'label'   => 'apollo',
			'level'   => 10,
			'desc'    => 'Full access (everything: users, plugins, themes, settings, all content).',
		),
		'editor'        => array(
			'backend' => 'editor',
			'label'   => 'MOD',
			'level'   => 7,
			'desc'    => 'Publish/edit/delete any events & pages, moderate comments, manage categories/tags. No site settings/plugins/users.',
		),
		'author'        => array(
			'backend' => 'author',
			'label'   => 'cena+',
			'level'   => 5,
			'desc'    => 'Publish/edit/delete own events only. Upload media.',
		),
		'contributor'   => array(
			'backend' => 'contributor',
			'label'   => 'cena',
			'level'   => 3,
			'desc'    => 'Write/edit own events only. Cannot publish or upload media.',
		),
		'subscriber'    => array(
			'backend' => 'subscriber',
			'label'   => 'clubber',
			'level'   => 1,
			'desc'    => 'Read content + manage own profile only.',
		),
	);
}

/**
 * Frontend label for a backend role slug (falls back to the slug itself).
 */
function apollo_role_frontend_label(string $backend_role): string
{
	$map = apollo_roles_map();
	return isset($map[ $backend_role ]) ? $map[ $backend_role ]['label'] : $backend_role;
}

/**
 * Apollo level for a backend role slug (0 = unknown).
 */
function apollo_role_level(string $backend_role): int
{
	$map = apollo_roles_map();
	return isset($map[ $backend_role ]) ? $map[ $backend_role ]['level'] : 0;
}

/**
 * Highest-level backend role of a user (defaults to current user).
 *
 * @return string Backend role slug ('' when logged out / no mapped role).
 */
function apollo_user_top_role($user = null): string
{
	$user = $user instanceof \WP_User ? $user : ( $user ? get_userdata((int) $user) : wp_get_current_user() );
	if (! $user instanceof \WP_User || 0 === $user->ID) {
		return '';
	}

	$top       = '';
	$top_level = -1;
	foreach ((array) $user->roles as $role) {
		$level = apollo_role_level((string) $role);
		if ($level > $top_level) {
			$top_level = $level;
			$top       = (string) $role;
		}
	}
	return $top;
}

/**
 * Frontend label of a user's highest role ('' when none).
 */
function apollo_user_frontend_role($user = null): string
{
	$top = apollo_user_top_role($user);
	return '' === $top ? '' : apollo_role_frontend_label($top);
}

/**
 * Hidden HTML comment documenting the canonical role map.
 * Printed in wp-admin footer AND available for frontend shells (/modera).
 */
function apollo_roles_map_hidden_comment(): string
{
	$lines   = array();
	$lines[] = 'APOLLO USER-ROLE MAP — canonical (apollo-core/includes/roles-map.php)';
	$lines[] = 'User role defined exclusively (almost 100%) for control of capabilities and permissions.';
	$lines[] = '"post" = ONLY the CPT \'event\' !important';
	$lines[] = 'Backend slugs NEVER change — frontend is a relabel only, to avoid collapsing.';
	$lines[] = 'Backend        | Frontend | Level | Scope';
	$lines[] = 'administrator  | apollo   | 10    | Full access (users, plugins, themes, settings, all content)';
	$lines[] = 'editor         | MOD      | 7     | Any events & pages, comments, categories/tags. No settings/plugins/users';
	$lines[] = 'author         | cena+    | 5     | Own events only. Upload media';
	$lines[] = 'contributor    | cena     | 3     | Own events only. No publish, no media';
	$lines[] = 'subscriber     | clubber  | 1     | Read + own profile only';

	return "\n<!--\n" . implode("\n", $lines) . "\n-->\n";
}

/**
 * Echo the hidden comment (escaped-safe: static content only).
 */
function apollo_roles_map_print_comment(): void
{
	echo apollo_roles_map_hidden_comment(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static doc comment.
}

// wp-admin: hidden comment on every admin screen footer.
add_action('admin_footer', 'apollo_roles_map_print_comment', 99);
