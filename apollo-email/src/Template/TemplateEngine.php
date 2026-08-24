<?php

/**
 * Template Engine — renders email templates with merge tags and Apollo branding.
 *
 * @package Apollo\Email\Template
 * @since   1.0.0
 */

declare(strict_types=1);

namespace Apollo\Email\Template;

use Apollo\Email\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TemplateEngine {


	/** @var string[] Full HTML transactional templates — PHP source of truth, no colorful base.php wrapper. */
	private const STANDALONE_TEMPLATES = array(
		'verification',
		'password-reset',
		'welcome',
		'notification',
		'event-reminder',
		'digest',
		'task-deadline',
		'report',
	);

	/** @var string Path to email templates directory */
	private string $templates_path;

	public function __construct() {
		$this->templates_path = APOLLO_EMAIL_PATH . 'templates/emails/';
	}

	/**
	 * Render an email template with merge tags.
	 *
	 * @param string $template_slug Template slug (e.g., 'welcome', 'password-reset').
	 * @param array  $data          Merge tag data.
	 * @return string Rendered HTML.
	 */
	public function render( string $template_slug, array $data = array() ): string {
		$template_slug = $this->normalizeTemplateSlug( $template_slug );
		$standalone    = $this->isStandaloneTemplate( $template_slug );

		/**
		 * Filter template data before rendering.
		 *
		 * @param array  $data          Template data.
		 * @param string $template_slug Template slug.
		 */
		$data = apply_filters( 'apollo/email/template_data', $data, $template_slug );

		// Auto-resolve real WP/Apollo user data when user_id is present.
		// Must run before global merge so caller $data always wins over defaults.
		if ( ! empty( $data['user_id'] ) ) {
			$data = MergeTagRegistry::resolveForUser( (int) $data['user_id'], $data );
		}

		// Add global data (caller $data wins because it's merged last)
		$data = array_merge(
			array(
				'site_name'       => get_bloginfo( 'name' ),
				'site_url'        => home_url( '/' ),
				'brand_color'     => Plugin::setting( 'brand_color', 'FF9820' ),
				'brand_logo'      => function_exists( 'apollo_email_brand_logo_url' )
					? apollo_email_brand_logo_url( (string) Plugin::setting( 'brand_logo', '' ) )
					: Plugin::setting( 'brand_logo', '' ),
				'footer_text'     => Plugin::setting( 'footer_text', '' ),
				'footer_address'  => Plugin::setting( 'footer_address', '' ),
				'current_year'    => gmdate( 'Y' ),
				'unsubscribe_url' => '',
				'preferences_url' => home_url( '/?apollo_email_prefs=1' ),
				'header_tag'      => $this->getHeaderTag( $template_slug ),
			),
			$data
		);

        $content_html = $this->renderContentBlock( $template_slug, $data );

		// Standalone templates: PHP file is source of truth (never CPT / colorful base.php).
		if ( $standalone && $this->isFullHtmlDocument( $content_html ) ) {
			return $content_html;
		}

		// Try to get CPT template content first
		$cpt_content = $this->getFromCPT( $template_slug );

		// If CPT has custom content, use that instead (unless seed baked broken esc_url placeholders).
		if ( $cpt_content ) {
			$cpt_rendered = $this->replaceMergeTags( $cpt_content, $data );

			if ( ! $this->hasBrokenSeededMergeUrls( $cpt_rendered ) ) {
				$content_html = $cpt_rendered;
			}

			// Full HTML docs from seed/import should not be wrapped in base.php.
			if ( $this->isFullHtmlDocument( $content_html ) ) {
				return $content_html;
			}
		}

		// Wrap in base template
		$base_html = $this->renderBase( $data, $content_html );

		// Inline CSS for email client compatibility
		return StyleInliner::inline( $base_html );
	}

	/**
	 * Render a subject line with merge tags.
	 *
	 * @param string $template_slug Template slug.
	 * @param array  $data          Merge tag data.
	 * @return string Rendered subject.
	 */
	public function renderSubject( string $template_slug, array $data = array() ): string {
		$template_slug = $this->normalizeTemplateSlug( $template_slug );

		// Try CPT custom subject first
		$template = $this->getCPTTemplate( $template_slug );
		if ( $template ) {
			$subject = get_post_meta( $template->ID, '_email_subject', true );
			if ( $subject ) {
				return $this->replaceMergeTags( $subject, $data );
			}
		}

		// Fallback — return data subject or empty
		return $data['subject'] ?? '';
	}

	/**
	 * Get available templates from CPT.
	 *
	 * @return array List of template objects.
	 */
	public function getTemplates(): array {
		$posts = get_posts(
			array(
				'post_type'   => 'email_aprio',
				'post_status' => 'publish',
				'numberposts' => 100,
				'orderby'     => 'title',
				'order'       => 'ASC',
			)
		);

		return array_map(
			function ( $post ) {
				return array(
					'id'        => $post->ID,
					'slug'      => $post->post_name,
					'title'     => $post->post_title,
					'subject'   => get_post_meta( $post->ID, '_email_subject', true ),
					'type'      => get_post_meta( $post->ID, '_email_type', true ),
					'variables' => get_post_meta( $post->ID, '_email_variables', true ),
					'content'   => $post->post_content,
					'modified'  => $post->post_modified,
				);
			},
			$posts
		);
	}

	/**
	 * Get template object by slug.
	 */
	public function getTemplate( string $slug ): ?array {
		$slug     = $this->normalizeTemplateSlug( $slug );
		$template = $this->getCPTTemplate( $slug );
		if ( ! $template ) {
			return null;
		}

		return array(
			'id'        => $template->ID,
			'slug'      => $template->post_name,
			'title'     => $template->post_title,
			'subject'   => get_post_meta( $template->ID, '_email_subject', true ),
			'type'      => get_post_meta( $template->ID, '_email_type', true ),
			'variables' => get_post_meta( $template->ID, '_email_variables', true ),
			'content'   => $template->post_content,
			'modified'  => $template->post_modified,
		);
	}

	/**
	 * Replace merge tags in content.
	 *
	 * @param string $content Content with {{tags}}.
	 * @param array  $data    Key-value replacements.
	 * @return string Processed content.
	 */
	public function replaceMergeTags( string $content, array $data ): string {
		return preg_replace_callback(
			'/\{\{(\w+)\}\}/',
			function ( $matches ) use ( $data ) {
				$key = $matches[1];
				if ( isset( $data[ $key ] ) ) {
					if ( is_array( $data[ $key ] ) ) {
						return wp_json_encode( $data[ $key ] );
					}
					return (string) $data[ $key ];
				}
				return $matches[0]; // Keep unreplaced tags
			},
			$content
		) ?? $content;
	}

	/**
	 * Get content from CPT post.
	 */
	private function getFromCPT( string $slug ): ?string {
		$template = $this->getCPTTemplate( $slug );
		if ( $template && ! empty( $template->post_content ) ) {
			return $template->post_content;
		}
		return null;
	}

	/**
	 * Get CPT post by slug.
	 */
	private function getCPTTemplate( string $slug ): ?\WP_Post {
		$slug = $this->normalizeTemplateSlug( $slug );

		$slugs = array( $slug );
		if ( 'task-deadline' === $slug ) {
			$slugs[] = 'task-reminder';
		}

		foreach ( $slugs as $candidate ) {
			$posts = get_posts(
				array(
					'post_type'   => 'email_aprio',
					'name'        => $candidate,
					'post_status' => 'publish',
					'numberposts' => 1,
				)
			);

			if ( ! empty( $posts ) ) {
				return $posts[0];
			}
		}

		return null;
	}

	/**
	 * Render content block from PHP template file.
	 */
	private function renderContentBlock( string $template_slug, array $data ): string {
		$file = $this->templates_path . $template_slug . '.php';
		if ( ! file_exists( $file ) ) {
			// Fallback to notification template
			$file = $this->templates_path . 'notification.php';
		}

		if ( ! file_exists( $file ) ) {
			return '<p>' . esc_html( $data['message'] ?? '' ) . '</p>';
		}

		// Extract data for template scope (EXTR_SKIP prevents overwrites)
		extract( $data, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract

		ob_start();
		include $file;
		return ob_get_clean();
	}

	/**
	 * Render the base email wrapper template.
	 */
	private function renderBase( array $data, string $content ): string {
		$file = $this->templates_path . 'base.php';
		if ( ! file_exists( $file ) ) {
			return $content;
		}

		extract( $data, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		$email_content = $content;

		ob_start();
		include $file;
		return ob_get_clean();
	}

	/**
	 * Get contextual header tag text for the email type.
	 *
	 * @param string $template_slug Template slug.
	 * @return string Header tag text for the thin mono header.
	 */
	private function getHeaderTag( string $template_slug ): string {
		$template_slug = $this->normalizeTemplateSlug( $template_slug );

		$map = array(
			'welcome'        => 'Bem-vindo(a)',
			'verification'   => 'Verificação de Email',
			'password-reset' => 'Segurança da Conta',
			'notification'   => 'Notificação do Sistema',
			'event-reminder' => 'Lembrete de Evento',
			'digest'         => 'Resumo Semanal',
			'report'         => 'Mensagem de Contato',
			'task-deadline'  => 'Prazo de Tarefa',
		);

		return $map[ $template_slug ] ?? 'Notificação do Sistema';
	}

	/**
	 * Normalize legacy slugs to canonical template slugs.
	 */
	private function normalizeTemplateSlug( string $slug ): string {
		if ( 'task-reminder' === $slug ) {
			return 'task-deadline';
		}

		return $slug;
	}

	/**
	 * Detect whether template content is a full HTML document.
	 */
	private function isFullHtmlDocument( string $content ): bool {
		$trimmed = ltrim( $content );

		return 0 === stripos( $trimmed, '<!doctype html' ) || 0 === stripos( $trimmed, '<html' );
	}

	/**
	 * Templates rendered as a single self-contained HTML email.
	 */
	private function isStandaloneTemplate( string $slug ): bool {
		return in_array( $slug, self::STANDALONE_TEMPLATES, true );
	}

	/**
	 * Detect CPT HTML corrupted by esc_url() during template seeding.
	 *
	 * esc_url( '{{reset_url}}' ) becomes http://reset_url/ and breaks live emails.
	 */
	private function hasBrokenSeededMergeUrls( string $html ): bool {
		$broken_fragments = array(
			'http://reset_url/',
			'http://reset_url"',
			"http://reset_url'",
			'http://verify_url/',
			'http://verify_url"',
			"http://verify_url'",
			'http://action_url/',
			'http://profile_url/',
			'http://event_url/',
		);

		foreach ( $broken_fragments as $fragment ) {
			if ( str_contains( $html, $fragment ) ) {
				return true;
			}
		}

		return (bool) preg_match( '/\{\{(reset_url|verify_url|confirmation_link|action_url)\}\}/', $html );
	}
}
