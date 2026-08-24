<?php

/**
 * BlockRenderer — Server-side block rendering engine.
 *
 * Renders blocks array into HTML for the public hub page.
 * Adapted from _library/hub/design apollo/blocks-hub.js (preview() methods).
 *
 * Registry: _hub_blocks meta key (array of typed block objects).
 * Each block: { type, id, active, data }
 *
 * @package Apollo\Hub
 */

declare(strict_types=1);

namespace Apollo\Hub;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BlockRenderer {


	/**
	 * Render full blocks array to HTML.
	 *
	 * @param  array  $blocks Ordered block array from _hub_blocks meta.
	 * @param  string $theme  Active theme slug.
	 * @return string HTML output.
	 */
	public static function render( array $blocks, string $theme = 'dark' ): string {
		if ( empty( $blocks ) ) {
			return '';
		}

		$html = '';

		foreach ( $blocks as $block ) {
			if ( empty( $block['type'] ) || empty( $block['active'] ) ) {
				continue;
			}

			$type = sanitize_key( $block['type'] );
			$data = (array) ( $block['data'] ?? array() );
			$id   = esc_attr( $block['id'] ?? wp_generate_uuid4() );

			$method = 'render_' . $type;
			if ( method_exists( self::class, $method ) ) {
				$inner = self::$method( $data );
				if ( $inner !== '' ) {
					$html .= sprintf(
						'<div class="hub-block hub-block--%s" data-block-id="%s">%s</div>' . "\n",
						$type,
						$id,
						$inner
					);
				}
			}
		}

		return $html;
	}

	/*
	═══════════════════════════════════════════════════════════
		BLOCK RENDERERS — one method per block type
		Adapted from _library/hub/design apollo/blocks-hub.js
	═══════════════════════════════════════════════════════════ */

	/**
	 * Section header block.
	 */
	private static function render_header( array $data ): string {
		$text = esc_html( $data['text'] ?? '' );
		if ( empty( $text ) ) {
			return '';
		}

		return sprintf( '<div class="hub-section-header">%s</div>', $text );
	}

	/**
	 * Link / CTA block — primary block type.
	 */
	private static function render_link( array $data ): string {
		$title   = esc_html( $data['title'] ?? '' );
		$sub     = esc_html( $data['sub'] ?? '' );
		$url     = esc_url( $data['url'] ?? '#' );
		$icon    = sanitize_text_field( $data['icon'] ?? 'ri-link-m' );
		$variant = sanitize_key( $data['variant'] ?? 'default' );
		$badge   = esc_html( $data['badge'] ?? '' );

		// Inline styles from block data
		$style_parts = array();
		if ( ! empty( $data['bgColor'] ) ) {
			$style_parts[] = 'background:' . esc_attr( $data['bgColor'] );
		}
		if ( ! empty( $data['textColor'] ) ) {
			$style_parts[] = 'color:' . esc_attr( $data['textColor'] );
		}
		$style = $style_parts ? ' style="' . implode( ';', $style_parts ) . '"' : '';

		$icon_style = '';
		if ( ! empty( $data['iconBg'] ) ) {
			$icon_style = ' style="background:' . esc_attr( $data['iconBg'] ) . '"';
		}

		$badge_html = $badge ? '<span class="hub-link-badge">' . $badge . '</span>' : '';

		return sprintf(
			'<a class="hub-link variant-%s" href="%s" target="_blank" rel="noopener noreferrer"%s>' .
				'<div class="hub-link-inner">' .
					'<span class="hub-link-icon"%s><i class="%s"></i></span>' .
					'<span class="hub-link-text">' .
						'<span class="hub-link-title">%s</span>' .
						'%s' .
					'</span>' .
					'%s' .
					'<span class="hub-link-arrow"><i class="ri-arrow-right-s-line"></i></span>' .
				'</div>' .
			'</a>',
			$variant,
			$url,
			$style,
			$icon_style,
			esc_attr( $icon ),
			$title,
			$sub ? '<span class="hub-link-sub">' . $sub . '</span>' : '',
			$badge_html
		);
	}

	/**
	 * Social icons block.
	 */
	private static function render_social( array $data ): string {
		$icons     = (array) ( $data['icons'] ?? array() );
		$size      = sanitize_key( $data['size'] ?? 'md' );
		$alignment = sanitize_key( $data['alignment'] ?? 'center' );

		if ( empty( $icons ) ) {
			return '';
		}

		$items = '';
		foreach ( $icons as $ic ) {
			$icon_class = esc_attr( $ic['icon'] ?? 'ri-link-m' );
			$url        = esc_url( $ic['url'] ?? '#' );
			$label      = esc_attr( $ic['label'] ?? '' );

			// se o ícone for no formato "nome-s" (SVG mask), usar ri- equivalente
			if ( strpos( $icon_class, 'ri-' ) !== 0 ) {
				$icon_class = 'ri-' . str_replace( '-s', '-line', $icon_class );
			}

			$items .= sprintf(
				'<a class="hub-social-item" href="%s" target="_blank" rel="noopener noreferrer" aria-label="%s"><i class="%s"></i></a>',
				$url,
				$label,
				$icon_class
			);
		}

		return sprintf(
			'<div class="hub-social-row hub-social--%s" style="justify-content:%s;">%s</div>',
			$size,
			$alignment,
			$items
		);
	}

	/**
	 * YouTube embed block.
	 */
	private static function render_youtube( array $data ): string {
		$url = $data['url'] ?? '';
		if ( empty( $url ) ) {
			return '';
		}

		$embed_id = self::extract_youtube_id( $url );
		if ( ! $embed_id ) {
			return '';
		}

		$title = esc_attr( $data['title'] ?? 'YouTube' );

		return sprintf(
			'<div class="hub-youtube">' .
				'<div class="hub-embed-ratio">' .
					'<iframe src="https://www.youtube.com/embed/%s" title="%s" ' .
					'frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" ' .
					'allowfullscreen loading="lazy"></iframe>' .
				'</div>' .
			'</div>',
			esc_attr( $embed_id ),
			$title
		);
	}

	/**
	 * Spotify embed block.
	 */
	private static function render_spotify( array $data ): string {
		$url = $data['url'] ?? '';
		if ( empty( $url ) ) {
			return '';
		}

		$type   = sanitize_key( $data['spotifyType'] ?? 'track' );
		$height = $type === 'track' ? '80' : '380';

		// Convert open.spotify.com URL to embed
		$embed_url = str_replace( 'open.spotify.com/', 'open.spotify.com/embed/', $url );
		$embed_url = esc_url( $embed_url );

		if ( empty( $embed_url ) ) {
			return '';
		}

		return sprintf(
			'<div class="hub-spotify">' .
				'<iframe src="%s" height="%s" frameborder="0" ' .
				'allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture" ' .
				'loading="lazy" style="width:100%%;border-radius:12px;"></iframe>' .
			'</div>',
			$embed_url,
			esc_attr( $height )
		);
	}

	/**
	 * Image banner block.
	 */
	private static function render_image( array $data ): string {
		$url    = esc_url( $data['url'] ?? '' );
		$alt    = esc_attr( $data['alt'] ?? '' );
		$link   = esc_url( $data['link'] ?? '' );
		$fit    = sanitize_key( $data['fit'] ?? 'cover' );
		$radius = esc_attr( $data['radius'] ?? '12px' );

		if ( empty( $url ) ) {
			// Tentar usar attachment_id do WP
			$attachment_id = absint( $data['attachment_id'] ?? 0 );
			if ( $attachment_id ) {
				$url = wp_get_attachment_image_url( $attachment_id, 'large' ) ?: '';
			}
		}

		if ( empty( $url ) ) {
			return '';
		}

		$img = sprintf(
			'<img src="%s" alt="%s" class="hub-image-img" style="object-fit:%s;border-radius:%s;" loading="lazy">',
			$url,
			$alt,
			$fit,
			$radius
		);

		if ( $link ) {
			return sprintf(
				'<div class="hub-image"><a href="%s" target="_blank" rel="noopener noreferrer">%s</a></div>',
				$link,
				$img
			);
		}

		return sprintf( '<div class="hub-image">%s</div>', $img );
	}

	/**
	 * Text/paragraph block.
	 */
	private static function render_text( array $data ): string {
		$content = wp_kses_post( $data['content'] ?? '' );
		if ( empty( $content ) ) {
			return '';
		}

		$align = sanitize_key( $data['align'] ?? 'left' );

		return sprintf(
			'<div class="hub-text" style="text-align:%s;">%s</div>',
			$align,
			wpautop( $content )
		);
	}

	/**
	 * FAQ / Accordion block.
	 */
	private static function render_faq( array $data ): string {
		$items = (array) ( $data['items'] ?? array() );
		if ( empty( $items ) ) {
			return '';
		}

		$html = '<div class="hub-faq">';
		foreach ( $items as $item ) {
			$q = esc_html( $item['question'] ?? '' );
			$a = wp_kses_post( $item['answer'] ?? '' );
			if ( empty( $q ) ) {
				continue;
			}
			$html .= sprintf(
				'<details class="hub-faq-item">' .
					'<summary class="hub-faq-q"><span>%s</span><i class="ri-arrow-down-s-line"></i></summary>' .
					'<div class="hub-faq-a">%s</div>' .
				'</details>',
				$q,
				$a
			);
		}
		$html .= '</div>';

		return $html;
	}

	/**
	 * Countdown timer block.
	 */
	private static function render_countdown( array $data ): string {
		$target = sanitize_text_field( $data['target'] ?? '' );
		$label  = esc_html( $data['label'] ?? '' );

		if ( empty( $target ) ) {
			return '';
		}

		return sprintf(
			'<div class="hub-countdown" data-target="%s">' .
				'%s' .
				'<div class="hub-countdown-grid">' .
					'<div class="hub-cd-cell"><span class="hub-cd-num" data-unit="days">00</span><span class="hub-cd-lbl">Dias</span></div>' .
					'<div class="hub-cd-cell"><span class="hub-cd-num" data-unit="hours">00</span><span class="hub-cd-lbl">Horas</span></div>' .
					'<div class="hub-cd-cell"><span class="hub-cd-num" data-unit="mins">00</span><span class="hub-cd-lbl">Min</span></div>' .
					'<div class="hub-cd-cell"><span class="hub-cd-num" data-unit="secs">00</span><span class="hub-cd-lbl">Seg</span></div>' .
				'</div>' .
			'</div>',
			esc_attr( $target ),
			$label ? '<div class="hub-countdown-label">' . $label . '</div>' : ''
		);
	}

	/**
	 * Map embed block.
	 */
	private static function render_map( array $data ): string {
		$embed_url = esc_url( $data['embed'] ?? '' );
		$height    = absint( $data['height'] ?? 250 );

		if ( empty( $embed_url ) ) {
			return '';
		}

		return sprintf(
			'<div class="hub-map">' .
				'<iframe src="%s" height="%d" style="width:100%%;border:0;border-radius:12px;" ' .
				'allowfullscreen loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>' .
			'</div>',
			$embed_url,
			$height
		);
	}

	/**
	 * Divider / spacer block.
	 */
	private static function render_divider( array $data ): string {
		$style  = sanitize_key( $data['style'] ?? 'line' );
		$height = absint( $data['height'] ?? 24 );

		if ( $style === 'space' ) {
			return sprintf( '<div class="hub-divider hub-divider--space" style="height:%dpx;"></div>', $height );
		}

		return '<div class="hub-divider hub-divider--line"><hr></div>';
	}

	/**
	 * Generic embed / HTML block (sanitized).
	 */
	private static function render_embed( array $data ): string {
		$code = $data['code'] ?? '';
		if ( empty( $code ) ) {
			return '';
		}

		// Allow iframes + basic HTML, strip scripts
		$allowed           = wp_kses_allowed_html( 'post' );
		$allowed['iframe'] = array(
			'src'             => true,
			'width'           => true,
			'height'          => true,
			'frameborder'     => true,
			'allow'           => true,
			'allowfullscreen' => true,
			'loading'         => true,
			'style'           => true,
			'title'           => true,
		);

		return sprintf(
			'<div class="hub-embed">%s</div>',
			wp_kses( $code, $allowed )
		);
	}

	/*
	═══════════════════════════════════════════════════════════
		HELPERS
	═══════════════════════════════════════════════════════════ */

	/**
	 * Extract YouTube video ID from various URL formats.
	 */
	private static function extract_youtube_id( string $url ): string {
		if ( preg_match( '/(?:youtube\.com\/(?:embed\/|watch\?v=|shorts\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $url, $m ) ) {
			return $m[1];
		}
		return '';
	}
}
