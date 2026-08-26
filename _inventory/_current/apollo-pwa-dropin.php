<?php
/**
 * Apollo PWA — the three things missing between this ecosystem and an installable app.
 *
 * NOT WIRED IN. This file is a drop-in, delivered 2026-08-20 with plan-002.
 * Land it only after the repository exists (plan-001 · P0-3), because the
 * folder deploys on save and this touches apollo-core, which ~31 plugins
 * depend on.
 *
 * ── What is already right, and why this file is small ──────────────────────
 * apollo-core/includes/document-head.php already emits every PWA meta tag:
 * viewport-fit=cover, interactive-widget=overlays-content, mobile-web-app-capable,
 * apple-mobile-web-app-capable, apple-mobile-web-app-status-bar-style,
 * apple-mobile-web-app-title, theme-color per colour scheme, format-detection.
 * The comment above them literally says "PWA metas for app-like behavior".
 *
 * Three things are missing, and only three:
 *   1. a manifest, and a <link rel="manifest"> to it   → no Android install prompt
 *   2. a root-scoped service worker                    → no offline, no app shell
 *   3. apple-touch-icon                                → iOS home screen shows a screenshot
 *
 * iOS half-works today off apple-mobile-web-app-capable, which is exactly why
 * nobody noticed Android offers nothing at all.
 *
 * ── Install ────────────────────────────────────────────────────────────────
 *   1. Move to apollo-core/includes/pwa.php
 *   2. require_once in apollo-core.php next to the other includes
 *   3. Bump APOLLO_CORE_VERSION *and* the docblock, in the same edit (D09)
 *   4. Save permalinks once — rewrite rules changed
 *   5. Retire apollo-remind/assets/js/push-sw.js: its scope is a plugin assets
 *      directory, so it can receive push but can never cache the shell (M02).
 *      The push handler below replaces it at root scope.
 *
 * ── Precondition this file cannot fix by itself ────────────────────────────
 * There are TWO head builders. apollo_render_document_head() fires
 * apollo/seo/head and apollo/canvas/head; apollo_render_blank_canvas_open()
 * fires NOTHING — no SEO head, no canvas head, and therefore no
 * apollo-statistics tracker either (it hooks apollo/canvas/head at 99).
 * Hooking apollo/canvas/head alone lands the manifest on only half the pages.
 * Until the two are unified (plan-002 · A-1), call apollo_pwa_head_tags()
 * directly from the second builder too. Both call sites are wired below.
 *
 * @package Apollo\Core
 * @since   plan-002
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ═══════════════════════════════════════════════════════════════════════════
   1 · The manifest
   ═══════════════════════════════════════════════════════════════════════════ */

if ( ! function_exists( 'apollo_pwa_manifest' ) ) {
	/**
	 * The web app manifest, as an array.
	 *
	 * Filter: apollo/pwa/manifest
	 *
	 * @return array<string,mixed>
	 */
	function apollo_pwa_manifest(): array {
		$cdn = defined( 'APOLLO_CDN_URL' ) ? rtrim( APOLLO_CDN_URL, '/' ) : 'https://cdn.apollo.rio.br/v1.0.0';

		$manifest = array(
			'id'          => '/',
			'name'        => 'apollo::rio',
			'short_name'  => 'apollo',
			'description' => 'A cena carioca — eventos, DJs, locais e a rede que liga tudo.',
			'lang'        => 'pt-BR',
			'dir'         => 'ltr',

			// standalone, not fullscreen: fullscreen hides the status bar and
			// on Android that costs you the clock and the battery, which people
			// notice and dislike more than they like the extra 24px.
			'display'          => 'standalone',
			'display_override' => array( 'standalone', 'minimal-ui' ),

			// The app opens on /casa, not /. A PWA that launches on the
			// marketing page is a bookmark, not an app.
			'start_url' => '/casa?source=pwa',
			'scope'     => '/',

			'orientation'      => 'portrait-primary',
			'background_color' => '#0F0F11',
			'theme_color'      => '#0F0F11',

			'icons' => array(
				array( 'src' => $cdn . '/img/pwa/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any' ),
				array( 'src' => $cdn . '/img/pwa/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any' ),
				// maskable must have ~20% padding or Android crops the logo.
				array( 'src' => $cdn . '/img/pwa/icon-maskable-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable' ),
			),

			// Long-press the installed icon. Keep to four; Android shows four.
			'shortcuts' => array(
				array( 'name' => 'Eventos',  'short_name' => 'Eventos',  'url' => '/eventos' ),
				array( 'name' => 'Mensagens','short_name' => 'Chat',     'url' => '/mensagens' ),
				array( 'name' => 'Mapa',     'short_name' => 'Mapa',     'url' => '/mapa' ),
				array( 'name' => 'Meu hub',  'short_name' => 'Hub',      'url' => '/hub' ),
			),

			'categories'  => array( 'music', 'social', 'events' ),
			'prefer_related_applications' => false,
		);

		return (array) apply_filters( 'apollo/pwa/manifest', $manifest );
	}
}

/* ═══════════════════════════════════════════════════════════════════════════
   2 · Root-scoped routes:  /manifest.webmanifest  and  /sw.js
   ═══════════════════════════════════════════════════════════════════════════ */

if ( ! function_exists( 'apollo_pwa_register_routes' ) ) {
	/**
	 * Both files must answer from the site ROOT.
	 *
	 * A service worker's scope is the directory it was served from. Served
	 * from /wp-content/plugins/…/assets/ it can never control a page — which
	 * is the state apollo-remind is in today (guard rule M02). Serving it from
	 * / is the fix; Service-Worker-Allowed is the belt to that braces.
	 */
	function apollo_pwa_register_routes(): void {
		add_rewrite_rule( '^manifest\.webmanifest$', 'index.php?apollo_pwa=manifest', 'top' );
		add_rewrite_rule( '^sw\.js$',                'index.php?apollo_pwa=sw',       'top' );
	}
	add_action( 'init', 'apollo_pwa_register_routes' );

	add_filter(
		'query_vars',
		static function ( array $vars ): array {
			$vars[] = 'apollo_pwa';
			return $vars;
		}
	);
}

if ( ! function_exists( 'apollo_pwa_serve' ) ) {
	/**
	 * Serve whichever of the two was asked for, then stop WordPress.
	 */
	function apollo_pwa_serve(): void {
		$what = get_query_var( 'apollo_pwa' );
		if ( '' === $what || null === $what ) {
			return;
		}

		nocache_headers();

		if ( 'manifest' === $what ) {
			header( 'Content-Type: application/manifest+json; charset=utf-8' );
			/* application/manifest+json has no ExpiresByType entry in the root
			 * .htaccess, so it would fall to ExpiresDefault "access plus 7 days"
			 * — an icon or name change would take a week to reach anyone. An
			 * explicit Expires makes mod_expires stand down. One hour is enough. */
			header( 'Cache-Control: public, max-age=3600' );
			header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', time() + 3600 ) . ' GMT' );
			echo wp_json_encode( apollo_pwa_manifest(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
			exit;
		}

		if ( 'sw' === $what ) {
			header( 'Content-Type: application/javascript; charset=utf-8' );
			// Without this the worker's scope is whatever directory served it.
			header( 'Service-Worker-Allowed: /' );

			/* THE WORKER MUST NEVER BE CACHED, AND THIS SITE FIGHTS THAT.
			 *
			 * The production root .htaccess (v3.1.0) does two things that would
			 * freeze this file permanently:
			 *   §5  mod_expires  ExpiresByType application/javascript "access plus 1 year"
			 *   §3.12 Header set Cache-Control "public, max-age=31536000, immutable"
			 *         on \.(js|mjs|css|…)$
			 * A stale service worker is the one bug that cannot be fixed by
			 * deploying — the fix itself is behind the stale worker.
			 *
			 * Three defences, because one is not enough here:
			 *   1. Cache-Control: no-store        — our intent, stated
			 *   2. Expires: 0                     — mod_expires SKIPS a response
			 *      that already carries an Expires header. This is the line that
			 *      actually beats §5; without it mod_expires wins.
			 *   3. updateViaCache: 'none' at the registration call site (below),
			 *      which tells the browser to bypass the HTTP cache for the
			 *      worker script regardless of what any header says.
			 */
			header( 'Cache-Control: no-cache, no-store, must-revalidate, max-age=0' );
			header( 'Expires: 0' );
			header( 'Pragma: no-cache' );
			apollo_pwa_print_sw();
			exit;
		}
	}
	add_action( 'template_redirect', 'apollo_pwa_serve', 0 );
}

/* ═══════════════════════════════════════════════════════════════════════════
   3 · Head tags
   ═══════════════════════════════════════════════════════════════════════════ */

if ( ! function_exists( 'apollo_pwa_head_tags' ) ) {
	/**
	 * Emit the three tags the head is missing. Idempotent — safe to call from
	 * both head builders while they are still two (see the note at the top).
	 */
	function apollo_pwa_head_tags(): void {
		static $done = false;
		if ( $done ) {
			return;
		}
		$done = true;

		$cdn = defined( 'APOLLO_CDN_URL' ) ? rtrim( APOLLO_CDN_URL, '/' ) : 'https://cdn.apollo.rio.br/v1.0.0';

		echo "\n<link rel=\"manifest\" href=\"/manifest.webmanifest\">\n";
		printf(
			"<link rel=\"apple-touch-icon\" sizes=\"180x180\" href=\"%s\">\n",
			esc_url( $cdn . '/img/pwa/apple-touch-icon-180.png' )
		);
		// iOS ignores the manifest's theme_color; it reads this pair instead,
		// and both are already emitted by document-head.php. Nothing to add.
	}
	add_action( 'apollo/canvas/head', 'apollo_pwa_head_tags', 5 );
}

if ( ! function_exists( 'apollo_pwa_register_sw_client' ) ) {
	/**
	 * Register the worker from the front end. One line of inline JS, CSP-nonced.
	 */
	function apollo_pwa_register_sw_client(): void {
		if ( is_admin() ) {
			return;
		}
		$nonce_attr = '';
		if ( function_exists( 'apollo_csp_nonce' ) ) {
			$n = (string) apollo_csp_nonce();
			if ( '' !== $n ) {
				$nonce_attr = ' nonce="' . esc_attr( $n ) . '"';
			}
		}
		/* updateViaCache:'none' — the browser bypasses its HTTP cache for the
		 * worker script itself on every update check. Belt to the Expires
		 * braces above: even if a CDN or a future .htaccess edit re-imposes a
		 * long TTL, the worker still updates. */
		echo '<script' . $nonce_attr . '>if("serviceWorker" in navigator){addEventListener("load",function(){navigator.serviceWorker.register("/sw.js",{scope:"/",updateViaCache:"none"}).catch(function(){})})}</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	add_action( 'wp_footer', 'apollo_pwa_register_sw_client', 99 );
	add_action( 'apollo/canvas/footer', 'apollo_pwa_register_sw_client', 99 );
}

/* ═══════════════════════════════════════════════════════════════════════════
   4 · The worker
   ═══════════════════════════════════════════════════════════════════════════ */

if ( ! function_exists( 'apollo_pwa_print_sw' ) ) {
	/**
	 * The service worker source.
	 *
	 * Strategy, deliberately conservative — this folder deploys on save and a
	 * bad worker is the one bug you cannot push a fix for:
	 *
	 *   documents      network-first, 4s timeout, cache fallback, then /offline
	 *   CDN assets     stale-while-revalidate  (cdn.apollo.rio.br is immutable)
	 *   same-origin    stale-while-revalidate for css/js/font/img
	 *   REST (apollo/v1)  never cached — the data is live or it is wrong
	 *   anything else  straight to network
	 *
	 * The cache name carries APOLLO_CORE_VERSION, so a version bump evicts
	 * everything. That is the manual escape hatch: bump core, old caches die.
	 */
	function apollo_pwa_print_sw(): void {
		$ver = defined( 'APOLLO_CORE_VERSION' ) ? APOLLO_CORE_VERSION : '0';
		$cdn = defined( 'APOLLO_CDN_URL' ) ? rtrim( APOLLO_CDN_URL, '/' ) : 'https://cdn.apollo.rio.br/v1.0.0';
		$cdn_host = wp_parse_url( $cdn, PHP_URL_HOST ) ?: 'cdn.apollo.rio.br';

		$cfg = wp_json_encode(
			array(
				'version'  => (string) $ver,
				'cdnHost'  => (string) $cdn_host,
				'shell'    => array( '/casa', '/offline' ),
				'restPath' => '/wp-json/apollo/v1/',
			),
			JSON_UNESCAPED_SLASHES
		);

		echo "const CFG = {$cfg};\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		?>
const SHELL = 'apollo-shell-' + CFG.version;
const RUNTIME = 'apollo-runtime-' + CFG.version;
const OFFLINE = '/offline';

self.addEventListener('install', (e) => {
  e.waitUntil(
    caches.open(SHELL)
      .then((c) => c.addAll(CFG.shell))
      // A failed precache must not block activation — a worker stuck in
      // "installing" is worse than a worker with a cold cache.
      .catch(() => null)
      .then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (e) => {
  e.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(
        keys.filter((k) => !k.endsWith(CFG.version)).map((k) => caches.delete(k))
      ))
      .then(() => self.clients.claim())
  );
});

function networkFirst(req) {
  return new Promise((resolve) => {
    let settled = false;
    const done = (r) => { if (!settled) { settled = true; resolve(r); } };
    const timer = setTimeout(() => {
      caches.match(req).then((hit) => done(hit || caches.match(OFFLINE)));
    }, 4000);

    fetch(req).then((res) => {
      clearTimeout(timer);
      if (res && res.ok) {
        const copy = res.clone();
        caches.open(RUNTIME).then((c) => c.put(req, copy)).catch(() => {});
      }
      done(res);
    }).catch(() => {
      clearTimeout(timer);
      caches.match(req).then((hit) => done(hit || caches.match(OFFLINE)));
    });
  });
}

function staleWhileRevalidate(req) {
  return caches.match(req).then((hit) => {
    const net = fetch(req).then((res) => {
      if (res && (res.ok || res.type === 'opaque')) {
        const copy = res.clone();
        caches.open(RUNTIME).then((c) => c.put(req, copy)).catch(() => {});
      }
      return res;
    }).catch(() => hit);
    return hit || net;
  });
}

self.addEventListener('fetch', (e) => {
  const req = e.request;
  if (req.method !== 'GET') return;

  const url = new URL(req.url);

  // Live data is live. Never serve a cached REST response — a stale event
  // line-up is worse than an error message.
  if (url.pathname.startsWith(CFG.restPath) || url.pathname.startsWith('/wp-admin')) return;

  if (req.mode === 'navigate') { e.respondWith(networkFirst(req)); return; }

  if (url.hostname === CFG.cdnHost) { e.respondWith(staleWhileRevalidate(req)); return; }

  if (url.origin === self.location.origin && /\.(css|js|mjs|woff2?|png|jpe?g|webp|avif|svg)$/.test(url.pathname)) {
    e.respondWith(staleWhileRevalidate(req));
  }
});

/* ── Push. Replaces apollo-remind/assets/js/push-sw.js, which is scope-locked
      to a plugin assets directory (guard M02). Same payload contract. ─────── */
self.addEventListener('push', (e) => {
  let d = {};
  try { d = e.data ? e.data.json() : {}; } catch (err) { d = {}; }
  const title = d.title || 'apollo::rio';
  e.waitUntil(self.registration.showNotification(title, {
    body: d.body || d.message || '',
    icon: d.icon || '/wp-content/uploads/apollo-notif-192.png',
    badge: d.badge,
    tag: d.tag || 'apollo',
    data: { url: d.url || d.link || '/casa' },
    renotify: !!d.renotify
  }));
});

self.addEventListener('notificationclick', (e) => {
  e.notification.close();
  const target = (e.notification.data && e.notification.data.url) || '/casa';
  e.waitUntil(
    self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((list) => {
      for (const c of list) {
        if ('focus' in c) { c.navigate(target); return c.focus(); }
      }
      return self.clients.openWindow(target);
    })
  );
});
		<?php
	}
}
