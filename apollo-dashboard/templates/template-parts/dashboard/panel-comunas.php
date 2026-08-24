<?php

/**
 * Dashboard Panel — Comunas (Communities V2)
 *
 * "Suas Comunidades" list + "Descubra" discovery section.
 * Uses li-item layout with avatars, counters, and action buttons.
 *
 * Variables expected: $user_id
 *
 * @package Apollo\Dashboard
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ── Load user's communities ──
global $wpdb;
$groups_table  = $wpdb->prefix . 'apollo_groups';
$members_table = $wpdb->prefix . 'apollo_group_members';
$has_groups    = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $groups_table ) ) === $groups_table;
$has_members   = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $members_table ) ) === $members_table;

$my_comunas = array();
$discover   = array();

if ( $has_groups && $has_members ) {
	// User's comunas
	$my_comunas = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT g.id, g.name, g.slug, g.description, g.avatar_url, g.type,
                (SELECT COUNT(*) FROM {$members_table} WHERE group_id = g.id) as member_count
         FROM {$groups_table} g
         INNER JOIN {$members_table} gm ON gm.group_id = g.id AND gm.user_id = %d
         ORDER BY g.name ASC",
			$user_id
		),
		ARRAY_A
	);

	// Community IDs user is already in
	$my_ids = array_map(
		function ( $c ) {
			return (int) $c['id'];
		},
		$my_comunas
	);

	// Discover (communities user is NOT in)
	if ( ! empty( $my_ids ) ) {
		$placeholders = implode( ',', array_fill( 0, count( $my_ids ), '%d' ) );
		$discover = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT g.id, g.name, g.slug, g.description, g.avatar_url, g.type,
                    (SELECT COUNT(*) FROM {$members_table} WHERE group_id = g.id) as member_count
             FROM {$groups_table} g
             WHERE g.id NOT IN ({$placeholders})
             AND g.type = 'comuna'
             ORDER BY member_count DESC
             LIMIT 6",
				...$my_ids
			),
			ARRAY_A
		);
	} else {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- No user input, literal string constants only.
		$discover = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT g.id, g.name, g.slug, g.description, g.avatar_url, g.type,
                    (SELECT COUNT(*) FROM {$members_table} WHERE group_id = g.id) as member_count
             FROM {$groups_table} g
             WHERE g.type = %s
             ORDER BY member_count DESC
             LIMIT 6",
				'comuna'
			),
			ARRAY_A
		);
	}
}

/**
 * Generate initials from a name
 */
if ( ! function_exists( 'apollo_initials' ) ) {
	function apollo_initials( string $name ): string {
		$parts = explode( ' ', trim( $name ) );
		if ( count( $parts ) >= 2 ) {
			return strtoupper( mb_substr( $parts[0], 0, 1 ) . mb_substr( $parts[1], 0, 1 ) );
		}
		return strtoupper( mb_substr( $name, 0, 2 ) );
	}
}

// ── Avatar colors for sample data ──
$avatar_colors = array( '#f97316', '#8b5cf6', '#3b82f6', '#10b981', '#ec4899', '#eab308' );
?>

<div class="tab-panel" id="panel-comunas">
	<div class="panel-inner">

		<h1 class="d-title">Comunidades</h1>

		<!-- ── Suas Comunidades ── -->
		<h2 class="d-subtitle">Suas comunidades</h2>

		<?php if ( ! empty( $my_comunas ) ) : ?>
			<?php
			foreach ( $my_comunas as $idx => $com ) :
				$color   = $avatar_colors[ $idx % count( $avatar_colors ) ];
				$initial = apollo_initials( $com['name'] );
				$members = (int) $com['member_count'];
				?>
				<div class="li-item">
					<div class="li-av" style="background:<?php echo esc_attr( $color ); ?>;"><?php echo esc_html( $initial ); ?></div>
					<div class="li-info">
						<div class="li-name"><?php echo esc_html( $com['name'] ); ?></div>
						<div class="li-meta"><?php echo esc_html( $members ); ?> membros<?php echo ! empty( $com['description'] ) ? ' · ' . esc_html( wp_trim_words( $com['description'], 6 ) ) : ''; ?></div>
					</div>
					<a href="<?php echo esc_url( home_url( '/comunas/' . ( $com['slug'] ?? $com['id'] ) ) ); ?>" class="li-btn">Ver</a>
				</div>
			<?php endforeach; ?>
		<?php else : ?>
			<!-- Sample for fresh install -->
			<div class="li-item">
				<div class="li-av" style="background:#f97316;">AL</div>
				<div class="li-info">
					<div class="li-name">After Lovers ZS</div>
					<div class="li-meta">124 membros · after até o sol nascer</div>
				</div>
				<a class="li-btn">Ver</a>
			</div>
			<div class="li-item">
				<div class="li-av" style="background:#8b5cf6;">GT</div>
				<div class="li-info">
					<div class="li-name">Gay Techno RJ</div>
					<div class="li-meta">87 membros · techno queer carioca</div>
				</div>
				<a class="li-btn">Ver</a>
			</div>
			<div class="li-item">
				<div class="li-av" style="background:#3b82f6;">CC</div>
				<div class="li-info">
					<div class="li-name">Clubbers Copacabana</div>
					<div class="li-meta">56 membros · rolê de copa</div>
				</div>
				<a class="li-btn">Ver</a>
			</div>
		<?php endif; ?>

		<!-- ── Descubra ── -->
		<h2 class="d-subtitle" style="margin-top:32px;">Descubra</h2>

		<?php if ( ! empty( $discover ) ) : ?>
			<?php
			foreach ( $discover as $idx => $disc ) :
				$color   = $avatar_colors[ ( $idx + 3 ) % count( $avatar_colors ) ];
				$initial = apollo_initials( $disc['name'] );
				$members = (int) $disc['member_count'];
				?>
				<div class="li-item">
					<div class="li-av" style="background:<?php echo esc_attr( $color ); ?>;"><?php echo esc_html( $initial ); ?></div>
					<div class="li-info">
						<div class="li-name"><?php echo esc_html( $disc['name'] ); ?></div>
						<div class="li-meta"><?php echo esc_html( $members ); ?> membros<?php echo ! empty( $disc['description'] ) ? ' · ' . esc_html( wp_trim_words( $disc['description'], 6 ) ) : ''; ?></div>
					</div>
					<button class="li-btn" onclick="joinComuna(<?php echo esc_attr( $disc['id'] ); ?>)">Entrar</button>
				</div>
			<?php endforeach; ?>
		<?php else : ?>
			<div class="li-item">
				<div class="li-av" style="background:#10b981;">RN</div>
				<div class="li-info">
					<div class="li-name">Rio Noturno</div>
					<div class="li-meta">210 membros · a noite é nostra</div>
				</div>
				<button class="li-btn">Entrar</button>
			</div>
			<div class="li-item">
				<div class="li-av" style="background:#ec4899;">BD</div>
				<div class="li-info">
					<div class="li-name">Bass District</div>
					<div class="li-meta">90 membros · drum&bass, jungle</div>
				</div>
				<button class="li-btn">Entrar</button>
			</div>
			<div class="li-item">
				<div class="li-av" style="background:#eab308;">MH</div>
				<div class="li-info">
					<div class="li-name">Minimal Heads</div>
					<div class="li-meta">45 membros · minimal e microhouse</div>
				</div>
				<button class="li-btn">Entrar</button>
			</div>
		<?php endif; ?>

	</div>
</div>
