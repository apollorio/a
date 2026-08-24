<?php

/**
 * Dashboard Panel — Feed (Social Timeline V2)
 *
 * 2-column layout: feed-column + sidebar-column.
 * ap-card shape with badges, núcleos, resale/accommodation embeds.
 *
 * Variables expected: $user_id, $display_name, $username, $avatar_url, $current_user
 *
 * @package Apollo\Dashboard
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ── Load social posts from DB ──
$social_posts = array();
global $wpdb;
$social_table = $wpdb->prefix . 'apollo_activity';
$has_social   = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $social_table ) ) === $social_table;

if ( $has_social ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Template display query, no user input, all table names are trusted constants.
	$social_posts = $wpdb->get_results(
		"SELECT p.*, u.display_name, u.user_login,
                (SELECT meta_value FROM {$wpdb->usermeta} WHERE user_id = p.user_id AND meta_key = '_apollo_avatar_url' LIMIT 1) as author_avatar,
                (SELECT meta_value FROM {$wpdb->usermeta} WHERE user_id = p.user_id AND meta_key = '_apollo_social_name' LIMIT 1) as social_name
         FROM {$social_table} p
         LEFT JOIN {$wpdb->users} u ON u.ID = p.user_id
         ORDER BY p.created_at DESC
         LIMIT 20",
		ARRAY_A
	);
}

/**
 * Fallback — apollo-core should always provide apollo_time_ago().
 * Compact numeric format per Apollo standard (no 'atrás').
 */
if ( ! function_exists( 'apollo_time_ago' ) ) {
	function apollo_time_ago( string $datetime ): string {
		$diff = max( 0, current_time( 'timestamp' ) - (int) strtotime( $datetime ) );
		if ( $diff < 60 ) {
			return $diff . 's';
		}
		if ( $diff < 3600 ) {
			return floor( $diff / 60 ) . 'min';
		}
		if ( $diff < 86400 ) {
			return floor( $diff / 3600 ) . 'h';
		}
		if ( $diff < 604800 ) {
			return floor( $diff / 86400 ) . 'd';
		}
		if ( $diff < 31536000 ) {
			return floor( $diff / 604800 ) . 'w';
		}
		return floor( $diff / 31536000 ) . 'y';
	}
}
if ( ! function_exists( 'apollo_time_ago_html' ) ) {
	function apollo_time_ago_html( string $datetime ): string {
		$str = apollo_time_ago( $datetime );
		if ( '' === $str ) {
			return '';
		}
		preg_match( '/^(\d+)(\w+)$/', $str, $m );
		return '<i class="tempo-v"></i>&nbsp;<span class="time-ago">' . esc_html( $m[1] ?? $str ) . '</span><span class="when-ago">' . esc_html( $m[2] ?? '' ) . '</span>';
	}
}

// ── Sidebar data: upcoming events ──
$sidebar_events = array();
if ( post_type_exists( 'event' ) ) {
	$sidebar_events = get_posts(
		array(
			'post_type'      => 'event',
			'posts_per_page' => 3,
			'post_status'    => 'publish',
			'orderby'        => 'meta_value',
			'meta_key'       => '_event_start_date',
			'order'          => 'ASC',
			'meta_query'     => array(
				array(
					'key'     => '_event_start_date',
					'value'   => current_time( 'Y-m-d' ),
					'compare' => '>=',
					'type'    => 'DATE',
				),
			),
		)
	);
}

// ── Sidebar data: user's núcleos ──
$user_nucleos = array();
$nucleo_ids   = get_user_meta( $user_id, '_apollo_nucleos', true );
if ( is_array( $nucleo_ids ) && ! empty( $nucleo_ids ) ) {
	$groups_table = $wpdb->prefix . 'apollo_groups';
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $groups_table ) ) === $groups_table ) {
		$placeholders = implode( ',', array_fill( 0, count( $nucleo_ids ), '%d' ) );
		$user_nucleos = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, name, slug FROM {$groups_table} WHERE id IN ({$placeholders}) AND type = 'nucleo'",
				...$nucleo_ids
			),
			ARRAY_A
		);
	}
}

// ── Sidebar data: user's comunas ──
$user_comunas  = array();
$members_table = $wpdb->prefix . 'apollo_group_members';
$groups_table  = $wpdb->prefix . 'apollo_groups';
$has_groups    = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $groups_table ) ) === $groups_table;
$has_members   = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $members_table ) ) === $members_table;

if ( $has_groups && $has_members ) {
	$user_comunas = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT g.name, g.slug FROM {$groups_table} g
         INNER JOIN {$members_table} gm ON gm.group_id = g.id AND gm.user_id = %d
         WHERE g.type = 'comuna'
         ORDER BY g.name ASC LIMIT 5",
			$user_id
		),
		ARRAY_A
	);
}

// ── Day name mapping for sidebar events ──
$day_names_pt = array( 'Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb' );
?>

<div class="tab-panel active" id="panel-feed">
	<div class="app-main">
		<section class="feed-column" id="feedColumn">

			<!-- ── Compose ── -->
			<div class="compose">
				<div class="av-sm"><img src="<?php echo esc_url( $avatar_url ); ?>" alt="<?php echo esc_attr( $display_name ); ?>"></div>
				<div class="compose-body">
					<textarea class="apollo-textarea compose-textarea" id="composeInput" placeholder="O que tá rolando na pista?" rows="2"></textarea>
					<div class="compose-toolbar">
						<div class="compose-actions">
							<button class="c-act" title="Imagem"><i class="ri-image-add-line"></i></button>
							<button class="c-act" title="SoundCloud"><i class="ri-soundcloud-line"></i></button>
							<button class="c-act" title="Evento"><i class="ri-calendar-event-line"></i></button>
							<button class="c-act" title="Revenda"><i class="ri-ticket-2-line"></i></button>
							<button class="c-act" title="Hospedagem"><i class="ri-home-heart-line"></i></button>
						</div>
						<div class="compose-right">
							<div class="char-ring"><svg width="24" height="24" viewBox="0 0 24 24">
									<circle class="char-ring-bg" cx="12" cy="12" r="10" fill="none" stroke-width="2" />
									<circle class="char-ring-fill" id="charRing" cx="12" cy="12" r="10" fill="none" stroke-width="2" stroke-dasharray="62.83" stroke-dashoffset="62.83" stroke-linecap="round" />
								</svg></div>
							<span class="char-count" id="charCount">280</span>
							<button class="btn-post" id="btnPost" disabled><span>Postar</span></button>
						</div>
					</div>
				</div>
			</div>

			<!-- ── Feed Posts ── -->
			<?php if ( ! empty( $social_posts ) ) : ?>
				<?php
				foreach ( $social_posts as $post_item ) :
					$author_name   = ! empty( $post_item['social_name'] ) ? $post_item['social_name'] : $post_item['display_name'];
					$author_handle = $post_item['user_login'];
					$author_avatar = ! empty( $post_item['author_avatar'] )
						? $post_item['author_avatar']
						: get_avatar_url( (int) $post_item['user_id'], array( 'size' => 100 ) );
					$time_ago      = apollo_time_ago( $post_item['created_at'] );
					$content       = esc_html( $post_item['content'] );
					$wow_count     = isset( $post_item['wow_count'] ) ? (int) $post_item['wow_count'] : 0;
					$reply_count   = isset( $post_item['reply_count'] ) ? (int) $post_item['reply_count'] : 0;
					$share_count   = isset( $post_item['share_count'] ) ? (int) $post_item['share_count'] : 0;
					?>
					<article class="post-article">
						<div class="ap-avatar" style="background-image:url('<?php echo esc_url( $author_avatar ); ?>')"></div>
						<div class="ap-card">
							<section class="ap-user">
								<p><span class="ap-username"><?php echo esc_html( $author_name ); ?></span> <span class="ap-badge apollo">Apollo</span></p>
								<p class="ap-handle"><span class="uid">@<?php echo esc_html( $author_handle ); ?></span> · <?php echo wp_kses_post( apollo_time_ago_html( $post_item['created_at'] ) ); ?></p>
							</section>
							<p class="ap-text"><?php echo $content; ?></p>
							<div class="ap-actions">
								<button class="ap-act wow" data-post-id="<?php echo esc_attr( $post_item['id'] ?? 0 ); ?>" onclick="toggleWow(this)"><i class="ri-brain-line"></i> <span><?php echo esc_html( $wow_count ); ?></span></button>
								<button class="ap-act"><i class="ri-chat-4-line"></i> <span><?php echo esc_html( $reply_count ); ?></span></button>
								<button class="ap-act"><i class="ri-share-forward-line"></i> <span><?php echo esc_html( $share_count ); ?></span></button>
							</div>
						</div>
					</article>
				<?php endforeach; ?>

			<?php else : ?>
				<!-- ── Sample feed (fresh install) ── -->
				<article class="post-article">
					<div class="ap-avatar" style="background-image:url('https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=100&q=80')"></div>
					<div class="ap-card">
						<section class="ap-user">
							<p><span class="ap-username">Vini</span> <span class="ap-badge apollo">Apollo</span> <span class="ap-badge dj">DJ</span></p>
							<p class="ap-nucleos"><span class="n-name">Núcleo Copacabana</span> <span class="sep">·</span> <span class="n-name">Núcleo Rio Noturno</span></p>
							<p class="ap-handle"><span class="uid">@vini.rj</span> · <i class="tempo-v"></i>&nbsp;<span class="time-ago">2</span><span class="when-ago">h</span></p>
						</section>
						<p class="ap-text">esse set do Leo Janeiro na Fabrika ficou absurdo, ouçam e me digam se não é o melhor techno saindo do Rio agora 🔊</p>
						<div class="ap-media sc"><iframe scrolling="no" frameborder="no" allow="autoplay" src="https://w.soundcloud.com/player/?url=https%3A//api.soundcloud.com/tracks/1293057640&color=%23121214&auto_play=false&hide_related=true&show_comments=false&show_user=true&show_reposts=false&show_teaser=false&visual=true"></iframe></div>
						<div class="ap-actions">
							<button class="ap-act wow" onclick="toggleWow(this)"><i class="ri-brain-line"></i> <span>142</span></button>
							<button class="ap-act"><i class="ri-chat-4-line"></i> <span>8</span></button>
							<button class="ap-act"><i class="ri-share-forward-line"></i> <span>23</span></button>
						</div>
					</div>
				</article>

				<article class="post-article">
					<div class="ap-avatar" style="background-image:url('https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=100&q=80')"></div>
					<div class="ap-card">
						<section class="ap-user">
							<p><span class="ap-username">Marta Supernova</span> <span class="ap-badge apollo">Apollo</span> <span class="ap-badge pink">Producer</span></p>
							<p class="ap-nucleos"><span class="n-name">Núcleo Music</span> <span class="sep">·</span> <span class="n-name">Núcleo Culture</span></p>
							<p class="ap-handle"><span class="uid">@martatechno</span> · <i class="tempo-v"></i>&nbsp;<span class="time-ago">5</span><span class="when-ago">h</span></p>
						</section>
						<p class="ap-text">vol. 04 confirmado. lineup pesadíssimo, som de alta fidelidade, iluminação imersiva. quem vai? 🖤</p>
						<div class="ap-event-banner" style="background-image:url('https://images.unsplash.com/photo-1516450360452-9312f5e86fc7?w=800&q=80')">
							<div>
								<div class="ap-event-title">Sunset Theory Vol.04 — Fabrika</div>
								<div class="ap-event-date">Sábado, 24 Fev · 23:00 · Techno / Industrial</div>
							</div>
						</div>
						<div class="ap-actions">
							<button class="ap-act wow active" onclick="toggleWow(this)"><i class="ri-brain-fill"></i> <span>310</span></button>
							<button class="ap-act"><i class="ri-chat-4-line"></i> <span>24</span></button>
							<button class="ap-act"><i class="ri-share-forward-line"></i> <span>67</span></button>
						</div>
					</div>
				</article>

				<article class="post-article">
					<div class="ap-avatar" style="background-image:url('https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?w=100&q=80')"></div>
					<div class="ap-card">
						<section class="ap-user">
							<p><span class="ap-username">Rafael Valle</span> <span class="ap-badge apollo">Apollo</span></p>
							<p class="ap-nucleos"><span class="n-name">Núcleo Copacabana</span></p>
							<p class="ap-handle"><span class="uid">@rafaelvalle</span> · <i class="tempo-v"></i>&nbsp;<span class="time-ago">6</span><span class="when-ago">h</span></p>
						</section>
						<p class="ap-text">não vou conseguir ir, alguém quer? preço abaixo do que paguei 🎫</p>
						<div class="ap-resale-embed">
							<div class="resale-badge">⟁ Revenda · Resale</div>
							<div class="resale-body">
								<img src="https://images.unsplash.com/photo-1516450360452-9312f5e86fc7?w=200&q=80" class="resale-thumb" alt="">
								<div class="resale-info">
									<div class="resale-title">Sunset Theory 04</div>
									<div class="resale-meta"><i class="ri-calendar-line"></i>24 Fev · 23h <span style="margin:0 4px;opacity:0.3">·</span> <i class="ri-map-pin-line"></i>Fabrika</div>
									<div class="resale-prices"><span class="resale-price">R$ 80</span><span class="resale-original">R$ 120</span></div>
								</div>
							</div>
							<div class="resale-rip">
								<div class="resale-rip-l"></div>
								<div class="resale-rip-r"></div>
							</div>
							<div class="resale-bottom">
								<div class="resale-seller">
									<div class="resale-seller-av">RV</div><span class="resale-seller-name">@rafaelvalle</span>
								</div>
								<button class="resale-chat-btn"><i class="ri-chat-3-line"></i> Chat</button>
							</div>
						</div>
						<div class="ap-actions">
							<button class="ap-act wow" onclick="toggleWow(this)"><i class="ri-brain-line"></i> <span>18</span></button>
							<button class="ap-act"><i class="ri-chat-4-line"></i> <span>5</span></button>
							<button class="ap-act"><i class="ri-share-forward-line"></i> <span>12</span></button>
						</div>
					</div>
				</article>

				<article class="post-article">
					<div class="ap-avatar" style="background-image:url('https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=100&q=80')"></div>
					<div class="ap-card">
						<section class="ap-user">
							<p><span class="ap-username">Ana Copacabana</span> <span class="ap-badge blue">Host</span></p>
							<p class="ap-nucleos"><span class="n-name">Núcleo Copacabana</span></p>
							<p class="ap-handle"><span class="uid">@ana.copa</span> · <i class="tempo-v"></i>&nbsp;<span class="time-ago">8</span><span class="when-ago">h</span></p>
						</section>
						<p class="ap-text">tô com quarto sobrando no apto de Copa pro fds do Sunset Theory. perto do metrô, vista pro mar ✨</p>
						<div class="ap-accom-embed">
							<div class="accom-badge"><i class="ri-home-heart-line" style="font-size:11px;margin-right:4px;vertical-align:-1px;"></i> Acomodação · Hospedagem</div>
							<div class="accom-gallery">
								<div class="accom-gallery-main"><img src="https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=600&q=80" alt="Quarto"></div>
								<div class="accom-gallery-side"><img src="https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?w=300&q=80" alt="Sala"><img src="https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=300&q=80" alt="Vista"></div>
							</div>
							<div class="accom-body">
								<div class="accom-type">Quarto privativo · Copacabana</div>
								<div class="accom-title">Quarto c/ vista pro mar, 2 min do metrô</div>
								<div class="accom-loc"><i class="ri-map-pin-line"></i> Copacabana, Rio de Janeiro</div>
								<div class="accom-details">
									<span class="accom-detail"><i class="ri-user-line"></i> 2 hóspedes</span>
									<span class="accom-detail"><i class="ri-temp-cold-line"></i> Ar cond.</span>
									<span class="accom-detail"><i class="ri-wifi-line"></i> Wi-Fi</span>
								</div>
								<div class="accom-footer">
									<div><span class="accom-price">R$ 120</span><span class="accom-price-sub"> /noite</span></div>
									<div class="accom-dates">23 — 25 Fev</div>
								</div>
							</div>
						</div>
						<div class="ap-actions">
							<button class="ap-act wow" onclick="toggleWow(this)"><i class="ri-brain-line"></i> <span>54</span></button>
							<button class="ap-act"><i class="ri-chat-4-line"></i> <span>11</span></button>
							<button class="ap-act"><i class="ri-share-forward-line"></i> <span>8</span></button>
						</div>
					</div>
				</article>

				<article class="post-article">
					<div class="ap-avatar" style="background-image:url('https://api.dicebear.com/7.x/avataaars/svg?seed=Host3&backgroundColor=e5e7eb')"></div>
					<div class="ap-card">
						<section class="ap-user">
							<p><span class="ap-username">Nayara</span> <span class="ap-badge apollo">Apollo</span> <span class="ap-badge mod">Moderação</span></p>
							<p class="ap-nucleos"><span class="n-name">Núcleo Rio Noturno</span></p>
							<p class="ap-handle"><span class="uid">@nay</span> · <i class="tempo-v"></i>&nbsp;<span class="time-ago">1</span><span class="when-ago">d</span></p>
						</section>
						<p class="ap-text">lembrete: qualquer situação de assédio em rolê citado aqui, manda relato pra moderação no privado. a cena se cuida assim. 🛡️</p>
						<div class="ap-actions">
							<button class="ap-act wow active" onclick="toggleWow(this)"><i class="ri-brain-fill"></i> <span>289</span></button>
							<button class="ap-act"><i class="ri-chat-4-line"></i> <span>3</span></button>
							<button class="ap-act"><i class="ri-share-forward-line"></i> <span>45</span></button>
						</div>
					</div>
				</article>

				<article class="post-article">
					<div class="ap-avatar" style="background-image:url('https://api.dicebear.com/7.x/avataaars/svg?seed=User3&backgroundColor=cbd5f5')"></div>
					<div class="ap-card">
						<section class="ap-user">
							<p><span class="ap-username">kaka.tech</span> <span class="ap-badge dj">DJ</span> <span class="ap-badge apollo">Apollo</span></p>
							<p class="ap-nucleos"><span class="n-name">Núcleo Music</span> <span class="sep">·</span> <span class="n-name">Núcleo Produtores RJ</span></p>
							<p class="ap-handle"><span class="uid">@kaka.tech</span> · <i class="tempo-v"></i>&nbsp;<span class="time-ago">2</span><span class="when-ago">d</span></p>
						</section>
						<p class="ap-text">frame do set de ontem no after. pista não parou até 10h da manhã. foto pela @ju_queer 📸</p>
						<figure class="ap-figure"><img src="https://images.unsplash.com/photo-1470225620780-dba8ba36b745?w=800&q=80" alt="Set"></figure>
						<div class="ap-actions">
							<button class="ap-act wow" onclick="toggleWow(this)"><i class="ri-brain-line"></i> <span>198</span></button>
							<button class="ap-act"><i class="ri-chat-4-line"></i> <span>11</span></button>
							<button class="ap-act"><i class="ri-share-forward-line"></i> <span>34</span></button>
						</div>
					</div>
				</article>
			<?php endif; ?>

		</section>

		<!-- ── SIDEBAR ── -->
		<aside class="sidebar-column">

			<!-- Próximos Eventos -->
			<div class="sb-card">
				<h2 class="sb-title">Próximos Eventos</h2>
				<?php if ( ! empty( $sidebar_events ) ) : ?>
					<?php
					foreach ( $sidebar_events as $sev ) :
						$ev_date = get_post_meta( $sev->ID, '_event_start_date', true );
						$day_num = $ev_date ? date_i18n( 'd', strtotime( $ev_date ) ) : '--';
						$day_w   = $ev_date ? $day_names_pt[ (int) date( 'w', strtotime( $ev_date ) ) ] : '';
						?>
						<a href="<?php echo esc_url( get_permalink( $sev->ID ) ); ?>" class="ev-mini">
							<div class="ev-date-box"><span class="day-name"><?php echo esc_html( strtoupper( $day_w ) ); ?></span><span class="day-num"><?php echo esc_html( $day_num ); ?></span></div>
							<div class="ev-info">
								<div class="ev-name"><?php echo esc_html( $sev->post_title ); ?></div>
								<div class="ev-attend">Evento Apollo</div>
							</div>
						</a>
					<?php endforeach; ?>
				<?php else : ?>
					<a class="ev-mini">
						<div class="ev-date-box"><span class="day-name">SEX</span><span class="day-num">27</span></div>
						<div class="ev-info">
							<div class="ev-name">Festa Rara</div>
							<div class="ev-attend">3 amigos vão</div>
						</div>
					</a>
					<a class="ev-mini">
						<div class="ev-date-box"><span class="day-name">SÁB</span><span class="day-num">30</span></div>
						<div class="ev-info">
							<div class="ev-name">Dismantle · Mainstage</div>
							<div class="ev-attend">12 amigos vão</div>
						</div>
					</a>
					<a class="ev-mini">
						<div class="ev-date-box"><span class="day-name">SAB</span><span class="day-num">07</span></div>
						<div class="ev-info">
							<div class="ev-name">Domply Mergulho</div>
							<div class="ev-attend">5 amigos vão</div>
						</div>
					</a>
				<?php endif; ?>
			</div>

			<!-- Núcleos -->
			<div class="sb-card">
				<h2 class="sb-title">Seus Núcleos</h2>
				<?php if ( ! empty( $user_nucleos ) ) : ?>
					<?php foreach ( $user_nucleos as $nuc ) : ?>
						<a href="<?php echo esc_url( home_url( '/nucleos/' . ( $nuc['slug'] ?? $nuc['id'] ) ) ); ?>" class="sb-link"><i class="ri-map-pin-2-line"></i> <?php echo esc_html( $nuc['name'] ); ?></a>
					<?php endforeach; ?>
				<?php else : ?>
					<a class="sb-link"><i class="ri-map-pin-2-line"></i> Núcleo Copacabana</a>
					<a class="sb-link"><i class="ri-moon-line"></i> Núcleo Rio Noturno</a>
					<a class="sb-link"><i class="ri-mic-line"></i> Núcleo Produtores RJ</a>
				<?php endif; ?>
			</div>

			<!-- Comunidades -->
			<div class="sb-card">
				<h2 class="sb-title">Comunidades</h2>
				<?php if ( ! empty( $user_comunas ) ) : ?>
					<?php foreach ( $user_comunas as $com ) : ?>
						<a href="<?php echo esc_url( home_url( '/comunas/' . ( $com['slug'] ?? '' ) ) ); ?>" class="sb-link"><i class="ri-group-line"></i> <?php echo esc_html( $com['name'] ); ?></a>
					<?php endforeach; ?>
				<?php else : ?>
					<a class="sb-link"><i class="ri-time-line"></i> After Lovers ZS</a>
					<a class="sb-link"><i class="ri-music-2-line"></i> Gay Techno RJ</a>
					<a class="sb-link"><i class="ri-headphone-line"></i> Clubbers Copacabana</a>
				<?php endif; ?>
			</div>

			<!-- Revenda em destaque -->
			<div class="sb-card">
				<h2 class="sb-title">Revenda em destaque</h2>
				<div style="display:flex;gap:10px;align-items:center;padding:8px;border-radius:8px;cursor:pointer;transition:0.2s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
					<div style="width:40px;height:40px;border-radius:8px;background:var(--primary);display:flex;align-items:center;justify-content:center;color:white;font-size:18px;flex-shrink:0;"><i class="ri-ticket-2-line"></i></div>
					<div style="flex:1;">
						<div style="font-size:12px;font-weight:700;color:var(--txt-color-hover);">3 ingressos</div>
						<div style="font-size:10px;color:var(--muted);font-family:var(--ff-mono);">SUNSET THEORY · R$80</div>
					</div>
				</div>
			</div>

		</aside>
	</div>
</div>