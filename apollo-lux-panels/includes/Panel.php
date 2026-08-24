<?php
/**
 * Panel — abstract luxury metabox engine (WooCommerce single-metabox pattern).
 *
 * One high-priority metabox per CPT, rendered from a declarative schema with
 * internal tab navigation. Handles secure save (nonce + capability + per-type
 * sanitization) into the canonical Apollo meta keys.
 *
 * @package Apollo\LuxPanels
 */

declare(strict_types=1);

namespace Apollo\LuxPanels;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class Panel {

	/** @return string CPT (post type) this panel binds to. */
	abstract public function post_type(): string;

	/** @return array{title:string,subtitle:string,tabs:array} */
	abstract public function schema(): array;

	protected function nonce_action(): string { return 'apollo_lux_' . $this->post_type() . '_save'; }
	protected function nonce_field(): string { return 'apollo_lux_' . $this->post_type() . '_nonce'; }

	public function boot(): void {
		add_action( 'add_meta_boxes', array( $this, 'register' ) );
		add_action( 'save_post_' . $this->post_type(), array( $this, 'save' ), 10, 2 );
		$this->validate_schema();
	}

	/**
	 * Every field type this panel system can render and save.
	 *
	 * Declared once so validate_schema() and the render/save switches cannot
	 * drift — a type present here but missing from render_field() would fall to
	 * `default` and silently become a text input.
	 *
	 * @var string[]
	 */
	protected const KNOWN_TYPES = array(
		'text', 'url', 'email', 'number', 'date', 'time',
		'textarea', 'wysiwyg', 'select', 'toggle', 'taxonomy',
		'media_single', 'media_gallery', 'repeater', 'repeater_card',
		'hours', 'lineup', 'map', 'user_multiselect', 'color',
	);

	/**
	 * Fail loudly on a malformed schema, at registration, in WP_DEBUG only.
	 *
	 * WHY THIS EXISTS
	 * ---------------
	 * On 2026-08-17 an audit found FOUR malformed field declarations shipping
	 * simultaneously, and every one of them was invisible until a human opened
	 * the screen:
	 *
	 *   · _dj_name_lines and _local_rooms — `type => repeater` with no `cols`.
	 *     render_repeater() did count($f['opts']['cols']) on null, which is a
	 *     TypeError in PHP 8. The DJ and Local edit screens FATALED.
	 *   · _dj_media_kit_stats — repeater_card sub-fields keyed `key` where the
	 *     renderer reads `name`. Every input was named `[][]`, so the field
	 *     rendered perfectly and NEVER persisted a single value.
	 *   · _dj_booking_status — `select` whose opts were wrapped in a 'choices'
	 *     key. The renderer iterates opts as a flat value => label map, so it
	 *     emitted one <option value="choices"> with an array as its label.
	 *
	 * A schema is data. Data can be checked. All four are one-line rules below,
	 * and checking them at registration turns "fatal in production" into "notice
	 * on the dev box".
	 *
	 * Deliberately WP_DEBUG-gated and non-fatal: this folder deploys on save, so
	 * a validator that threw would be a worse outage than the bug it reports.
	 * $apollo_rule.data_flow — debug output is gated, never shipped.
	 *
	 * @return string[] Problems found (empty when the schema is sound).
	 */
	public function validate_schema(): array {
		$problems = array();
		$schema   = $this->schema();
		$cpt      = $this->post_type();

		foreach ( ( $schema['tabs'] ?? array() ) as $tid => $tab ) {
			foreach ( ( $tab['cards'] ?? array() ) as $card ) {
				foreach ( ( $card['fields'] ?? array() ) as $f ) {
					$key  = $f['key'] ?? '(missing key)';
					$type = $f['type'] ?? '';
					$at   = sprintf( '%s.%s.%s', $cpt, (string) $tid, $key );

					if ( ! isset( $f['key'] ) || '' === $f['key'] ) {
						$problems[] = $at . ' — field has no `key`';
					}
					if ( '' === $type ) {
						$problems[] = $at . ' — field has no `type`';
					} elseif ( ! in_array( $type, self::KNOWN_TYPES, true ) ) {
						$problems[] = $at . ' — unknown type "' . $type . '"; it will silently render as text';
					}

					// The two fatals.
					if ( 'repeater' === $type && empty( $f['opts']['cols'] ) ) {
						$problems[] = $at . ' — `repeater` needs opts.cols (an array of [name, ph]); `fields` is the repeater_card spelling and leaves cols null';
					}
					// The one that renders but never saves.
					if ( 'repeater_card' === $type ) {
						if ( empty( $f['opts']['fields'] ) ) {
							$problems[] = $at . ' — `repeater_card` needs opts.fields';
						} else {
							foreach ( (array) $f['opts']['fields'] as $sf ) {
								if ( empty( $sf['name'] ) ) {
									$problems[] = $at . ' — repeater_card sub-field uses `key`; the renderer and saver both read `name`, so this input would never persist';
									break;
								}
							}
						}
					}
					// The one that renders a nonsense <option>.
					if ( 'select' === $type ) {
						if ( ! isset( $f['opts'] ) || ! is_array( $f['opts'] ) ) {
							$problems[] = $at . ' — `select` needs opts as a flat value => label map';
						} elseif ( isset( $f['opts']['choices'] ) ) {
							$problems[] = $at . ' — `select` opts are wrapped in "choices"; opts IS the value => label map, unwrap it';
						}
					}
					if ( 'taxonomy' === $type && empty( $f['opts']['taxonomy'] ) ) {
						$problems[] = $at . ' — `taxonomy` needs opts.taxonomy';
					}
				}
			}
		}

		if ( $problems && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			foreach ( $problems as $p ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( 'Apollo Lux Panel schema: ' . $p );
			}
		}

		return $problems;
	}

	// ── Registration ─────────────────────────────────────────────────────────
	public function register(): void {
		add_meta_box(
			'apollo_lux_panel',
			'✦ ' . $this->schema()['title'],
			array( $this, 'render' ),
			$this->post_type(),
			'normal',
			'high'
		);
	}

	// ── Render ────────────────────────────────────────────────────────────────
	public function render( \WP_Post $post ): void {
		$s    = $this->schema();
		$tabs = $s['tabs'];
		wp_nonce_field( $this->nonce_action(), $this->nonce_field() );
		echo '<div class="apollo-lux" data-cpt="' . esc_attr( $this->post_type() ) . '">';

		// header
		echo '<div class="lux-head"><div>';
		echo '<div class="lux-title">' . esc_html( $s['title'] ) . '</div>';
		echo '<div class="lux-sub">' . esc_html( $s['subtitle'] ) . '</div>';
		echo '</div><div class="lux-brand"><b></b> Apollo · Luxury Panel</div></div>';

		// tab bar
		echo '<div class="lux-tabs" role="tablist">';
		$i = 0;
		foreach ( $tabs as $tid => $tab ) {
			printf(
				'<button type="button" class="lux-tab%s" data-tab="%s"><i class="%s"></i> %s</button>',
				0 === $i ? ' active' : '',
				esc_attr( (string) $tid ),
				esc_attr( $tab['icon'] ),
				esc_html( $tab['label'] )
			);
			$i++;
		}
		echo '</div>';

		// panels
		$i = 0;
		foreach ( $tabs as $tid => $tab ) {
			printf( '<div class="lux-panel%s" data-panel="%s">', 0 === $i ? ' active' : '', esc_attr( (string) $tid ) );
			foreach ( $tab['cards'] as $card ) {
				echo '<div class="card">';
				echo '<div class="tref-sec-lbl"><i class="' . esc_attr( $card['icon'] ) . '"></i> ' . esc_html( $card['label'] ) . '</div>';
				$this->render_fields( $post, $card['fields'] );
				echo '</div>';
			}
			echo '</div>';
			$i++;
		}

		echo '<div class="lux-foot"><span class="note">Apollo::Rio — dados salvos com o post</span>';
		echo '<span class="note">' . esc_html( strtoupper( $this->post_type() ) ) . '</span></div>';
		echo '</div>';
	}

	/** Render one grid of fields (auto grid-2 for paired rows via 'grid' flag). */
	protected function render_fields( \WP_Post $post, array $fields ): void {
		$buffer = array();
		foreach ( $fields as $f ) {
			$f = wp_parse_args( $f, array( 'grid' => 1, 'type' => 'text', 'label' => '', 'key' => '', 'opts' => array(), 'ph' => '', 'hint' => '', 'cap' => '' ) );

			/*
			 * Per-field capability — the render half of the gate enforced in
			 * save(). A control whose save will be ignored must not be shown as
			 * editable: it reads as a setting that did not stick, which is a
			 * worse bug than the field being absent.
			 */
			if ( '' !== $f['cap'] && ! current_user_can( $f['cap'] ) ) {
				continue;
			}

			if ( (int) $f['grid'] > 1 ) {
				$buffer[] = $f;
				if ( count( $buffer ) === (int) $f['grid'] ) {
					$this->render_grid( $post, $buffer );
					$buffer = array();
				}
			} else {
				if ( $buffer ) { $this->render_grid( $post, $buffer ); $buffer = array(); }
				$this->render_field( $post, $f );
			}
		}
		if ( $buffer ) { $this->render_grid( $post, $buffer ); }
	}

	protected function render_grid( \WP_Post $post, array $fields ): void {
		$cls = count( $fields ) >= 3 ? 'grid-3' : 'grid-2';
		echo '<div class="' . esc_attr( $cls ) . '">';
		foreach ( $fields as $f ) { $this->render_field( $post, $f ); }
		echo '</div>';
	}

	// ── Field renderers ────────────────────────────────────────────────────────
	protected function render_field( \WP_Post $post, array $f ): void {
		$key   = $f['key'];
		$type  = $f['type'];
		$label = $f['label'];
		$id    = 'lux_' . $key;
		$val   = $key ? get_post_meta( $post->ID, $key, true ) : '';

		$label_html = $label
			? '<label class="field-label" for="' . esc_attr( $id ) . '">' . esc_html( $label )
				. ( $f['hint'] ? ' <span class="hint">' . esc_html( $f['hint'] ) . '</span>' : '' ) . '</label>'
			: '';

		switch ( $type ) {
			case 'textarea':
				echo '<div class="field">' . $label_html; // phpcs:ignore WordPress.Security.EscapeOutput
				printf( '<textarea class="apollo-input" id="%s" name="%s" rows="4" placeholder="%s">%s</textarea>',
					esc_attr( $id ), esc_attr( $key ), esc_attr( $f['ph'] ), esc_textarea( (string) $val ) );
				echo '</div>';
				break;

			case 'wysiwyg':
				echo '<div class="field">' . $label_html; // phpcs:ignore WordPress.Security.EscapeOutput
				printf( '<textarea class="apollo-input" id="%s" name="%s" rows="5" placeholder="%s">%s</textarea>',
					esc_attr( $id ), esc_attr( $key ), esc_attr( $f['ph'] ), esc_textarea( (string) ( '' !== $val ? $val : get_post_field( 'post_content', $post->ID ) ) ) );
				echo '</div>';
				break;

			case 'select':
				echo '<div class="field">' . $label_html . '<select class="apollo-select" id="' . esc_attr( $id ) . '" name="' . esc_attr( $key ) . '">'; // phpcs:ignore WordPress.Security.EscapeOutput
				foreach ( $f['opts'] as $ov => $ol ) {
					printf( '<option value="%s"%s>%s</option>', esc_attr( (string) $ov ), selected( (string) $val, (string) $ov, false ), esc_html( $ol ) );
				}
				echo '</select></div>';
				break;

			case 'toggle':
				$on = ( '1' === (string) $val );
				echo '<div class="field"><label class="toggle-wrap"><input type="hidden" name="' . esc_attr( $key ) . '" value="0">';
				printf( '<input type="checkbox" class="toggle-input" name="%s" value="1"%s><span class="toggle-track"></span><span class="toggle-label">%s</span>',
					esc_attr( $key ), checked( $on, true, false ), esc_html( $label ) );
				echo '</label></div>';
				break;

			case 'taxonomy':
				$this->render_taxonomy( $post, $f );
				break;

			case 'media_single':
				echo '<div class="field">' . $label_html; // phpcs:ignore WordPress.Security.EscapeOutput
				$img = $val ? wp_get_attachment_image_url( (int) $val, 'medium' ) : '';
				printf( '<div class="cover-upload%s" data-lux-media="single" data-target="%s"><button type="button" class="cover-clear">&times;</button>%s<i class="ri-upload-cloud-2-line"></i><span>%s</span></div>',
					$img ? ' has-img' : '', esc_attr( $id ),
					$img ? '<img src="' . esc_url( $img ) . '" alt="">' : '',
					esc_html( $f['ph'] ?: 'Clique para anexar' ) );
				printf( '<input type="hidden" id="%s" name="%s" value="%s">', esc_attr( $id ), esc_attr( $key ), esc_attr( (string) $val ) );
				echo '</div>';
				break;

			case 'media_gallery':
				$ids = array_filter( array_map( 'intval', is_array( $val ) ? $val : explode( ',', (string) $val ) ) );
				$max = (int) ( $f['opts']['max'] ?? 3 );
				echo '<div class="field">' . $label_html . '<div class="venue-images" data-lux-media="gallery" data-target="' . esc_attr( $id ) . '">'; // phpcs:ignore WordPress.Security.EscapeOutput
				for ( $n = 0; $n < $max; $n++ ) {
					$gid = $ids[ $n ] ?? 0;
					$gurl = $gid ? wp_get_attachment_image_url( $gid, 'thumbnail' ) : '';
					printf( '<div class="frame%s"><button type="button" class="rm">&times;</button>%s<i class="ri-add-line"></i></div>',
						$gurl ? ' has-img' : '', $gurl ? '<img src="' . esc_url( $gurl ) . '" alt="">' : '' );
				}
				echo '</div>';
				printf( '<input type="hidden" id="%s" name="%s" value="%s"></div>', esc_attr( $id ), esc_attr( $key ), esc_attr( implode( ',', $ids ) ) );
				break;

			case 'repeater':
				$this->render_repeater( $post, $f );
				break;

			case 'repeater_card':
				$this->render_repeater_card( $post, $f );
				break;

			case 'hours':
				$this->render_hours( $post, $f );
				break;

			case 'lineup':
				$this->render_lineup( $post, $f );
				break;

			case 'map':
				echo '<div class="field">' . $label_html; // phpcs:ignore WordPress.Security.EscapeOutput
				echo '<div class="frame" style="aspect-ratio:21/9;overflow:hidden;"><div class="loc-map" data-lux-map data-lat="lux_' . esc_attr( $f['opts']['lat'] ) . '" data-lng="lux_' . esc_attr( $f['opts']['lng'] ) . '"></div></div></div>';
				break;

			case 'user_multiselect':
				$this->render_user_multiselect( $post, $f );
				break;

			case 'color':
				$hex = preg_match( '/^#[0-9a-fA-F]{3,6}$/', (string) $val ) ? (string) $val : '#0a0a0a';
				echo '<div class="field">' . $label_html; // phpcs:ignore WordPress.Security.EscapeOutput
				echo '<div class="flex-row" style="gap:10px;align-items:center;">';
				printf(
					'<input type="color" value="%1$s" style="width:44px;height:40px;padding:2px;border-radius:var(--r-xs);border:1px solid rgba(255,255,255,.06);background:var(--surface);cursor:pointer;" oninput="document.getElementById(\'%2$s\').value=this.value;">',
					esc_attr( $hex ),
					esc_attr( $id )
				);
				printf(
					'<input type="text" id="%1$s" name="%2$s" class="apollo-input" style="max-width:140px;" value="%3$s" placeholder="%4$s" oninput="if(/^#[0-9a-fA-F]{6}$/.test(this.value)) this.previousElementSibling.value=this.value;">',
					esc_attr( $id ),
					esc_attr( $key ),
					esc_attr( $hex ),
					esc_attr( $f['ph'] ?: '#0a0a0a' )
				);
				echo '</div></div>';
				break;

			default: // text / url / email / number / date / time
				$html_type = in_array( $type, array( 'url', 'email', 'number', 'date', 'time' ), true ) ? $type : 'text';
				echo '<div class="field">' . $label_html; // phpcs:ignore WordPress.Security.EscapeOutput
				printf( '<input type="%s" class="apollo-input" id="%s" name="%s" value="%s" placeholder="%s">',
					esc_attr( $html_type ), esc_attr( $id ), esc_attr( $key ), esc_attr( (string) $val ), esc_attr( $f['ph'] ) );
				echo '</div>';
		}
	}

	protected function render_taxonomy( \WP_Post $post, array $f ): void {
		$tax   = $f['opts']['taxonomy'];
		$multi = ! empty( $f['opts']['multiple'] );
		$terms = get_terms( array( 'taxonomy' => $tax, 'hide_empty' => false ) );
		if ( is_wp_error( $terms ) ) { return; }
		$current = wp_get_post_terms( $post->ID, $tax, array( 'fields' => 'ids' ) );
		$current = is_wp_error( $current ) ? array() : $current;
		echo '<div class="field"><label class="field-label">' . esc_html( $f['label'] ) . '</label>';
		if ( $multi ) {
			// Search + capped(5) + chips — same pattern as co-authors, so long
			// term lists (Sons/Gêneros etc.) never dump everything on screen.
			$uid = 'lux_taxpick_' . $tax;
			echo '<div style="display:none;" aria-hidden="true">';
			foreach ( $terms as $t ) {
				printf( '<input type="checkbox" class="lux-tax-cb" data-tax="%s" name="lux_tax_%s[]" value="%d" data-label="%s"%s>',
					esc_attr( $tax ), esc_attr( $tax ), (int) $t->term_id, esc_attr( $t->name ), checked( in_array( $t->term_id, $current, true ), true, false ) );
			}
			echo '</div>';
			echo '<div class="lux-coauthors" data-lux-taxpick="' . esc_attr( $tax ) . '">';
			echo '<div class="lux-ca-search-wrap"><i class="ri-search-line lux-ca-search-ic"></i>';
			printf( '<input type="search" id="%s" class="apollo-input lux-ca-search" placeholder="Buscar…" autocomplete="off"></div>', esc_attr( $uid ) );
			echo '<div class="lux-ca-chips"></div><div class="lux-ca-list" role="listbox" aria-multiselectable="true"></div>';
			echo '</div>';
		} else {
			echo '<select class="apollo-select" name="lux_tax_' . esc_attr( $tax ) . '"><option value="">—</option>';
			foreach ( $terms as $t ) {
				printf( '<option value="%d"%s>%s</option>', (int) $t->term_id, selected( in_array( $t->term_id, $current, true ), true, false ), esc_html( $t->name ) );
			}
			echo '</select>';
		}
		echo '</div>';
	}

	/**
	 * Search + chips + listbox multi-select over all WP users (co-authors).
	 * Mirrors the frontend /novo-evento coauthors widget 1:1 (same markup
	 * intent, same hidden-JSON contract) so admin and frontend stay in sync.
	 */
	protected function render_user_multiselect( \WP_Post $post, array $f ): void {
		$key = $f['key'];
		$id  = 'lux_' . $key;

		if ( '_coauthors' === $key && function_exists( 'apollo_event_get_coauthor_ids' ) ) {
			$selected = apollo_event_get_coauthor_ids( $post->ID );
		} else {
			$raw      = get_post_meta( $post->ID, $key, true );
			$selected = is_array( $raw ) ? array_values( array_filter( array_map( 'absint', $raw ) ) ) : array();
		}

		echo '<div class="field lux-coauthors" data-lux-coauthors="' . esc_attr( $id ) . '">';
		echo '<label class="field-label" for="' . esc_attr( $id . '_search' ) . '">' . esc_html( $f['label'] )
			. ( $f['hint'] ? ' <span class="hint">' . esc_html( $f['hint'] ) . '</span>' : '' ) . '</label>'; // phpcs:ignore WordPress.Security.EscapeOutput

		printf(
			'<input type="hidden" id="%s" name="%s" value="%s">',
			esc_attr( $id ),
			esc_attr( $key ),
			esc_attr( (string) wp_json_encode( array_values( $selected ) ) )
		);

		echo '<div class="lux-ca-search-wrap">';
		echo '<i class="ri-search-line lux-ca-search-ic"></i>';
		printf(
			'<input type="search" id="%s" class="apollo-input lux-ca-search" placeholder="%s" autocomplete="off">',
			esc_attr( $id . '_search' ),
			esc_attr( $f['ph'] ?: 'Nome, login ou e-mail…' )
		);
		echo '</div>';

		echo '<div class="lux-ca-chips" aria-live="polite"></div>';
		echo '<div class="lux-ca-list" role="listbox" aria-multiselectable="true" aria-label="' . esc_attr( $f['label'] ) . '"></div>';
		echo '</div>';
	}

	protected function render_repeater( \WP_Post $post, array $f ): void {
		$key = $f['key'];
		/*
		 * `cols` IS REQUIRED, BUT A MISSING ONE MUST NOT BE FATAL (2026-08-17).
		 *
		 * This line was `$cols = $f['opts']['cols'];` — an unconditional array
		 * access followed by count() two lines down. Two shipped fields declared
		 * type => 'repeater' with an `opts` that had `fields` but no `cols`:
		 * _dj_name_lines (DjPanel) and _local_rooms (LocPanel), both added in the
		 * 2026-08-11 pass. $cols came out null, count(null) is a TypeError in
		 * PHP 8, and this plugin requires 8.1 — so the DJ and Local edit screens
		 * in wp-admin threw a fatal every time they loaded.
		 *
		 * Both declarations are fixed. This guard exists so the NEXT one is a
		 * missing field rather than a dead screen: a schema defect should
		 * degrade, not take wp-admin down. validate_schema() catches it properly
		 * at registration when WP_DEBUG is on.
		 *
		 * `fields` is accepted as an alias because that is the key both broken
		 * declarations reached for — the mistake was reasonable, the crash was
		 * not.
		 */
		$cols = $f['opts']['cols'] ?? $f['opts']['fields'] ?? array();
		if ( ! is_array( $cols ) || ! $cols ) {
			$cols = array( array( 'name' => 'value', 'ph' => '' ) );
		}
		$rows = get_post_meta( $post->ID, $key, true );
		$rows = is_array( $rows ) && $rows ? $rows : array( array() );
		$grid = 'cols-' . min( count( $cols ), 5 );
		echo '<div class="field"><label class="field-label">' . esc_html( $f['label'] )
			. ( $f['hint'] ? ' <span class="hint">' . esc_html( $f['hint'] ) . '</span>' : '' ) . '</label>';
		echo '<div class="lux-rep" data-lux-rep="' . esc_attr( $key ) . '">';
		foreach ( $rows as $row ) {
			echo '<div class="lux-rep-row ' . esc_attr( $grid ) . '">';
			foreach ( $cols as $c ) {
				$cv = is_array( $row ) ? ( $row[ $c['name'] ] ?? '' ) : '';
				if ( ( $c['type'] ?? '' ) === 'select' ) {
					echo '<select class="apollo-select" name="' . esc_attr( $key ) . '[' . esc_attr( $c['name'] ) . '][]">';
					foreach ( (array) ( $c['opts'] ?? array() ) as $ov => $ol ) {
						printf( '<option value="%s"%s>%s</option>', esc_attr( (string) $ov ), selected( (string) $cv, (string) $ov, false ), esc_html( $ol ) );
					}
					echo '</select>';
					continue;
				}
				printf( '<input type="%s" class="apollo-input" name="%s[%s][]" value="%s" placeholder="%s">',
					esc_attr( $c['type'] ?? 'text' ), esc_attr( $key ), esc_attr( $c['name'] ), esc_attr( (string) $cv ), esc_attr( $c['ph'] ) );
			}
			echo '<button type="button" class="lux-rep-rm">&times;</button></div>';
		}
		echo '</div><button type="button" class="btn btn-secondary btn-sm lux-rep-add" data-lux-rep-add><i class="ri-add-line"></i> ' . esc_html( $f['opts']['add'] ?? 'Adicionar' ) . '</button></div>';
	}

	/**
	 * Repeater whose ROW is a mini-card of several sub-fields (as opposed to
	 * render_repeater()'s single-line row of flat columns) — for schemas too
	 * wide for a flat grid, e.g. _dj_tracks v2 (14 fields/row). Reuses the exact
	 * same .field / .apollo-input / .cover-upload primitives as every other
	 * field, so it never falls back to unstyled wp-admin markup.
	 *
	 * opts:
	 *   fields             array of {name,label,type,ph} — type: text|url|number|date|media_single
	 *   add                "Adicionar…" button label
	 *   sanitize_callback  optional callable(array $rows): array — post-process
	 *                      the assembled rows before update_post_meta(). Lets a
	 *                      consuming plugin (e.g. apollo-djs) enforce its own
	 *                      required-field / at-least-one-of rules in one place,
	 *                      shared with the REST sanitize_callback registered in
	 *                      apollo-core's MetaRegistry.
	 */
	protected function render_repeater_card( \WP_Post $post, array $f ): void {
		$key = $f['key'];
		// Guarded for the same reason as render_repeater(): a schema defect
		// should render an empty field, never fatal the whole edit screen.
		$subfields = $f['opts']['fields'] ?? array();
		if ( ! is_array( $subfields ) || ! $subfields ) { return; }
		$rows      = get_post_meta( $post->ID, $key, true );
		$rows      = is_array( $rows ) && $rows ? $rows : array( array() );

		echo '<div class="field"><label class="field-label">' . esc_html( $f['label'] )
			. ( $f['hint'] ? ' <span class="hint">' . esc_html( $f['hint'] ) . '</span>' : '' ) . '</label>'; // phpcs:ignore WordPress.Security.EscapeOutput

		echo '<div class="lux-rep" data-lux-rep="' . esc_attr( $key ) . '">';
		foreach ( $rows as $row ) {
			$this->render_repeater_card_row( $key, is_array( $row ) ? $row : array(), $subfields );
		}
		echo '</div>';

		echo '<button type="button" class="btn btn-secondary btn-sm lux-rep-add" data-lux-rep-add>'
			. '<i class="ri-add-line"></i> ' . esc_html( $f['opts']['add'] ?? 'Adicionar' ) . '</button>';

		// Blank template so the JS "add row" clone is always empty — never a
		// duplicate of the last row's values (including any attached cover image).
		echo '<template data-lux-rep-tpl="' . esc_attr( $key ) . '">';
		$this->render_repeater_card_row( $key, array(), $subfields );
		echo '</template>';

		echo '</div>';
	}

	protected function render_repeater_card_row( string $key, array $row, array $subfields ): void {
		echo '<div class="lux-rep-row lux-rep-row-card"><div class="grid-3 lux-rep-card-grid">';
		foreach ( $subfields as $sf ) {
			$sf   = wp_parse_args( $sf, array( 'type' => 'text', 'label' => '', 'name' => '', 'ph' => '' ) );
			$name = $key . '[' . $sf['name'] . '][]';
			$val  = $row[ $sf['name'] ] ?? '';

			echo '<div class="field mb-0"><label class="field-label">' . esc_html( $sf['label'] ) . '</label>';

			if ( 'media_single' === $sf['type'] ) {
				$img = $val ? wp_get_attachment_image_url( (int) $val, 'thumbnail' ) : '';
				printf(
					'<div class="cover-upload%s" data-lux-media="single" style="min-height:84px;">%s<i class="ri-upload-cloud-2-line"></i><span>%s</span><button type="button" class="cover-clear">&times;</button></div>',
					$img ? ' has-img' : '',
					$img ? '<img src="' . esc_url( $img ) . '" alt="">' : '',
					esc_html( $sf['ph'] ?: 'Capa' )
				);
				printf( '<input type="hidden" class="lux-rep-media-input" name="%s" value="%s">', esc_attr( $name ), esc_attr( (string) $val ) );
			} else {
				$html_type = in_array( $sf['type'], array( 'url', 'number', 'date' ), true ) ? $sf['type'] : 'text';
				printf(
					'<input type="%s" class="apollo-input" name="%s" value="%s" placeholder="%s">',
					esc_attr( $html_type ),
					esc_attr( $name ),
					esc_attr( (string) $val ),
					esc_attr( $sf['ph'] )
				);
			}
			echo '</div>';
		}
		echo '</div><button type="button" class="lux-rep-rm">&times;</button></div>';
	}

	protected function render_hours( \WP_Post $post, array $f ): void {
		$key  = $f['key'];
		$days = array( 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado', 'Domingo' );
		$raw  = get_post_meta( $post->ID, $key, true );
		if ( is_string( $raw ) && '' !== $raw ) { $d = json_decode( $raw, true ); $raw = is_array( $d ) ? $d : array(); }
		if ( ! is_array( $raw ) ) { $raw = array(); }
		echo '<div class="field"><label class="field-label">' . esc_html( $f['label'] ) . ' <span class="hint">(vazio = Fechado)</span></label>';
		foreach ( $days as $idx => $day ) {
			$dv = $raw[ $idx ] ?? '';
			$dv = is_array( $dv ) ? trim( ( $dv['open'] ?? '' ) . ' - ' . ( $dv['close'] ?? '' ), ' -' ) : (string) $dv;
			echo '<div class="lux-day"><span>' . esc_html( $day ) . '</span>';
			printf( '<input type="text" class="apollo-input" name="%s[%d]" value="%s" placeholder="Ex: 23h – 08h"></div>',
				esc_attr( $key ), (int) $idx, esc_attr( $dv ) );
		}
		echo '</div>';
	}

	protected function render_lineup( \WP_Post $post, array $f ): void {
		$ids   = (array) get_post_meta( $post->ID, '_event_dj_ids', true );
		$slots = get_post_meta( $post->ID, '_event_dj_slots', true );
		$slots = is_array( $slots ) ? $slots : array();
		$slot_of = function ( $dj_id ) use ( $slots ) {
			foreach ( $slots as $s ) { if ( (int) ( $s['dj_id'] ?? 0 ) === (int) $dj_id ) { return $s; } }
			return array();
		};
		$djs = get_posts( array( 'post_type' => 'dj', 'posts_per_page' => 300, 'orderby' => 'title', 'order' => 'ASC', 'post_status' => 'publish' ) );
		$rows = $ids ? $ids : array( 0 );
		echo '<div class="field"><label class="field-label">' . esc_html( $f['label'] ) . ' <span class="hint">DJ · início · fim · badge</span></label>';
		echo '<div class="lux-rep" data-lux-rep="lineup">';
		foreach ( $rows as $dj_id ) {
			$slot = $slot_of( $dj_id );
			echo '<div class="lux-rep-row lineup">';
			echo '<select class="apollo-select" name="lux_lineup_dj[]"><option value="">— DJ —</option>';
			foreach ( $djs as $dj ) {
				printf( '<option value="%d"%s>%s</option>', (int) $dj->ID, selected( (int) $dj_id, (int) $dj->ID, false ), esc_html( $dj->post_title ) );
			}
			echo '</select>';
			printf( '<input type="time" class="apollo-input" name="lux_lineup_start[]" value="%s">', esc_attr( (string) ( $slot['start_time'] ?? '' ) ) );
			printf( '<input type="time" class="apollo-input" name="lux_lineup_end[]" value="%s">', esc_attr( (string) ( $slot['end_time'] ?? '' ) ) );
			printf( '<input type="text" class="apollo-input" name="lux_lineup_badge[]" maxlength="24" placeholder="Badge" value="%s">', esc_attr( (string) ( $slot['badge'] ?? '' ) ) );
			echo '<button type="button" class="lux-rep-rm">&times;</button></div>';
		}
		echo '</div><button type="button" class="btn btn-secondary btn-sm lux-rep-add" data-lux-rep-add><i class="ri-add-line"></i> Adicionar DJ</button></div>';
	}

	// ── Save ────────────────────────────────────────────────────────────────────
	public function save( int $post_id, \WP_Post $post ): void {
		if ( ! isset( $_POST[ $this->nonce_field() ] ) ||
			! wp_verify_nonce( sanitize_key( $_POST[ $this->nonce_field() ] ), $this->nonce_action() ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { return; }
		if ( wp_is_post_revision( $post_id ) ) { return; }
		if ( ! current_user_can( 'edit_post', $post_id ) ) { return; }
		if ( $post->post_type !== $this->post_type() ) { return; }

		foreach ( $this->schema()['tabs'] as $tab ) {
			foreach ( $tab['cards'] as $card ) {
				foreach ( $card['fields'] as $f ) {
					$f = wp_parse_args( $f, array( 'type' => 'text', 'key' => '', 'opts' => array(), 'cap' => '' ) );

					/*
					 * PER-FIELD CAPABILITY (added 2026-08-17).
					 *
					 * Until now this class had ONE check — edit_post — and then
					 * wrote everything in the schema. There was no way to declare
					 * a field as staff-only, which is why editorial switches like
					 * apollo-adverts' _classified_hostel had to be guarded by hand
					 * in a bespoke metabox, and why that guard was missing for two
					 * years on the wp-admin path while REST had it.
					 *
					 * Declare `'cap' => 'manage_options'` on a field and it is
					 * skipped on save for anyone without it — no overwrite, no
					 * error, the stored value simply stands.
					 */
					if ( '' !== $f['cap'] && ! current_user_can( $f['cap'] ) ) {
						continue;
					}

					$this->save_field( $post_id, $f );
				}
			}
		}
		do_action( 'apollo/lux/saved', $post_id, $this->post_type() );
	}

	/**
	 * Apply a field's own sanitize_callback, when it declares one.
	 *
	 * WAS HONOURED FOR EXACTLY ONE TYPE until 2026-08-17: save_repeater_card()
	 * read opts.sanitize_callback and nothing else did. So a sanitize_callback on
	 * a text, select or repeater field was accepted, ignored, and read like
	 * protection that was not there. Declaring one must either work or be
	 * rejected; silently doing nothing is the worst of the three.
	 *
	 * @param array<string,mixed> $f     Field definition.
	 * @param mixed               $value Sanitised-by-type value.
	 * @return mixed
	 */
	protected function apply_field_callback( array $f, $value ) {
		$cb = $f['opts']['sanitize_callback'] ?? null;
		return ( $cb && is_callable( $cb ) ) ? call_user_func( $cb, $value ) : $value;
	}

	protected function save_field( int $post_id, array $f ): void {
		$key  = $f['key'];
		$type = $f['type'];

		switch ( $type ) {
			case 'select':
				/*
				 * A select is a CLOSED set, and the saver never enforced that —
				 * it ran sanitize_text_field and stored whatever arrived, so any
				 * value could be posted into an enum field regardless of what the
				 * dropdown offered. Validated against the field's own opts now;
				 * an unknown value falls back to the first declared option.
				 */
				if ( isset( $_POST[ $key ] ) ) {
					$val    = sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
					$allowed = array_keys( (array) $f['opts'] );
					if ( $allowed && ! in_array( $val, $allowed, true ) ) {
						$val = (string) reset( $allowed );
					}
					update_post_meta( $post_id, $key, $this->apply_field_callback( $f, $val ) );
				}
				break;
			case 'text': case 'date': case 'time':
				if ( isset( $_POST[ $key ] ) ) {
					update_post_meta(
						$post_id,
						$key,
						$this->apply_field_callback( $f, sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) )
					);
				}
				break;
			case 'url':
				if ( isset( $_POST[ $key ] ) ) { update_post_meta( $post_id, $key, esc_url_raw( wp_unslash( (string) $_POST[ $key ] ) ) ); }
				break;
			case 'email':
				if ( isset( $_POST[ $key ] ) ) { update_post_meta( $post_id, $key, sanitize_email( wp_unslash( $_POST[ $key ] ) ) ); }
				break;
			case 'number': case 'media_single':
				if ( isset( $_POST[ $key ] ) ) { update_post_meta( $post_id, $key, absint( $_POST[ $key ] ) ); }
				break;
			case 'textarea':
				if ( isset( $_POST[ $key ] ) ) { update_post_meta( $post_id, $key, sanitize_textarea_field( wp_unslash( $_POST[ $key ] ) ) ); }
				break;
			case 'wysiwyg':
				if ( isset( $_POST[ $key ] ) ) {
					if ( '_post_content' === $key ) {
						remove_action( 'save_post_' . $this->post_type(), array( $this, 'save' ), 10 );
						wp_update_post( array( 'ID' => $post_id, 'post_content' => wp_kses_post( wp_unslash( $_POST[ $key ] ) ) ) );
						add_action( 'save_post_' . $this->post_type(), array( $this, 'save' ), 10, 2 );
					} else {
						update_post_meta( $post_id, $key, wp_kses_post( wp_unslash( $_POST[ $key ] ) ) );
					}
				}
				break;
			case 'toggle':
				update_post_meta( $post_id, $key, isset( $_POST[ $key ] ) && '1' === (string) $_POST[ $key ] ? '1' : '' );
				break;
			case 'media_gallery':
				if ( isset( $_POST[ $key ] ) ) {
					$ids = array_values( array_filter( array_map( 'absint', explode( ',', sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) ) ) ) );
					update_post_meta( $post_id, $key, $ids );
				}
				break;
			case 'taxonomy':
				$tax    = $f['opts']['taxonomy'];
				$field  = 'lux_tax_' . $tax;
				$raw    = isset( $_POST[ $field ] ) ? (array) wp_unslash( $_POST[ $field ] ) : array();
				$ids    = array_values( array_filter( array_map( 'absint', $raw ) ) );
				if ( taxonomy_exists( $tax ) ) {
					wp_set_post_terms( $post_id, $ids, $tax );
				}
				/*
				 * A debug beacon lived here (removed 2026-08-17). On every save
				 * of a `sound` taxonomy field it appended JSON to
				 * D:/dev/_livro.rvalle.com.br/… — an absolute path belonging to
				 * a DIFFERENT project on one developer's machine — plus
				 * WP_CONTENT_DIR/debug-c3f157.log on the live server.
				 *
				 * $apollo_rule.data_flow is explicit: no debug output in
				 * production, server log or admin panel only, gated on WP_DEBUG.
				 * Harness assertion E27 now fails the build if any beacon of
				 * this shape reappears — it had already been removed once from
				 * apollo-events (see apollo-events.json $portal_header_swap) and
				 * came back.
				 */
				break;
			case 'hours':
				if ( isset( $_POST[ $key ] ) && is_array( $_POST[ $key ] ) ) {
					$out = array();
					foreach ( (array) wp_unslash( $_POST[ $key ] ) as $idx => $v ) { $out[ (int) $idx ] = sanitize_text_field( $v ); }
					ksort( $out );
					update_post_meta( $post_id, $key, $out );
				}
				break;
			case 'repeater':
				$this->save_repeater( $post_id, $f );
				break;
			case 'repeater_card':
				$this->save_repeater_card( $post_id, $f );
				break;
			case 'lineup':
				$this->save_lineup( $post_id );
				break;
			case 'user_multiselect':
				$this->save_user_multiselect( $post_id, $f );
				break;
			case 'color':
				if ( isset( $_POST[ $key ] ) ) {
					$hex = sanitize_hex_color( wp_unslash( (string) $_POST[ $key ] ) );
					update_post_meta( $post_id, $key, $hex ?: '#0a0a0a' );
				}
				break;
		}
	}

	/**
	 * Decode the hidden-JSON user-id array and persist it. `_coauthors` routes
	 * through the shared apollo_event_save_coauthors() helper so admin saves
	 * stay identical to the frontend/REST save path (author exclusion, valid
	 * user check, etc.) — any other key falls back to a plain postmeta array.
	 */
	protected function save_user_multiselect( int $post_id, array $f ): void {
		$key = $f['key'];
		if ( ! isset( $_POST[ $key ] ) ) {
			return;
		}
		$decoded = json_decode( (string) wp_unslash( $_POST[ $key ] ), true );
		$ids     = is_array( $decoded ) ? array_values( array_filter( array_map( 'absint', $decoded ) ) ) : array();

		if ( '_coauthors' === $key && function_exists( 'apollo_event_save_coauthors' ) ) {
			apollo_event_save_coauthors( $post_id, $ids );
			return;
		}
		update_post_meta( $post_id, $key, $ids );
	}

	protected function save_repeater( int $post_id, array $f ): void {
		$key  = $f['key'];
		if ( ! isset( $_POST[ $key ] ) || ! is_array( $_POST[ $key ] ) ) { return; }
		// Same guard as render_repeater(), and for the same reason — a missing
		// `cols` fataled here too, on save rather than on load.
		$cols = $f['opts']['cols'] ?? $f['opts']['fields'] ?? array();
		if ( ! is_array( $cols ) || ! $cols ) { return; }
		$data = wp_unslash( $_POST[ $key ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$count = 0;
		foreach ( $cols as $c ) { $count = max( $count, count( (array) ( $data[ $c['name'] ] ?? array() ) ) ); }
		$out = array();
		for ( $i = 0; $i < $count; $i++ ) {
			$row = array(); $has = false;
			foreach ( $cols as $c ) {
				$raw = (string) ( $data[ $c['name'] ][ $i ] ?? '' );
				if ( 'url' === ( $c['type'] ?? '' ) ) {
					$val = esc_url_raw( $raw );
				} elseif ( 'select' === ( $c['type'] ?? '' ) ) {
					$allowed = array_map( 'strval', array_keys( (array) ( $c['opts'] ?? array() ) ) );
					$val     = in_array( $raw, $allowed, true ) ? $raw : (string) ( $allowed[0] ?? '' );
				} else {
					$val = sanitize_text_field( $raw );
				}
				$row[ $c['name'] ] = $val;
				if ( '' !== $val && ( $c['type'] ?? '' ) !== 'select' ) { $has = true; }
			}
			if ( $has ) { $out[] = $row; }
		}
		update_post_meta( $post_id, $key, $out );
	}

	/**
	 * Save counterpart of render_repeater_card(). Each sub-field posts as
	 * key[subname][] (parallel arrays, row index = array position) — same
	 * convention as save_repeater(), just one dimension deeper.
	 */
	protected function save_repeater_card( int $post_id, array $f ): void {
		$key = $f['key'];
		if ( ! isset( $_POST[ $key ] ) || ! is_array( $_POST[ $key ] ) ) {
			return;
		}
		$subfields = $f['opts']['fields'] ?? array();
		if ( ! is_array( $subfields ) || ! $subfields ) { return; }
		$data      = wp_unslash( $_POST[ $key ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

		$count = 0;
		foreach ( $subfields as $sf ) {
			$count = max( $count, count( (array) ( $data[ $sf['name'] ] ?? array() ) ) );
		}

		$out = array();
		for ( $i = 0; $i < $count; $i++ ) {
			$row = array();
			$has = false;
			foreach ( $subfields as $sf ) {
				$name = $sf['name'];
				$type = $sf['type'] ?? 'text';
				$raw  = (string) ( $data[ $name ][ $i ] ?? '' );

				switch ( $type ) {
					case 'url':
						$val = esc_url_raw( $raw );
						break;
					case 'number':
					case 'media_single':
						$val = absint( $raw );
						break;
					default:
						$val = sanitize_text_field( $raw );
				}

				$row[ $name ] = $val;
				if ( '' !== (string) $val ) {
					$has = true;
				}
			}
			if ( $has ) {
				$out[] = $row;
			}
		}

		$callback = $f['opts']['sanitize_callback'] ?? null;
		if ( $callback && is_callable( $callback ) ) {
			$out = call_user_func( $callback, $out );
		}

		update_post_meta( $post_id, $key, $out );
	}

	protected function save_lineup( int $post_id ): void {
		if ( ! isset( $_POST['lux_lineup_dj'] ) || ! is_array( $_POST['lux_lineup_dj'] ) ) { return; }
		$djs    = array_map( 'absint', (array) wp_unslash( $_POST['lux_lineup_dj'] ) );
		$starts = isset( $_POST['lux_lineup_start'] ) ? array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['lux_lineup_start'] ) ) : array();
		$ends   = isset( $_POST['lux_lineup_end'] ) ? array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['lux_lineup_end'] ) ) : array();
		$badges = isset( $_POST['lux_lineup_badge'] ) ? array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['lux_lineup_badge'] ) ) : array();
		$ids = array(); $slots = array();
		foreach ( $djs as $i => $dj_id ) {
			if ( ! $dj_id ) { continue; }
			$ids[]   = $dj_id;
			$slots[] = array(
				'dj_id'      => $dj_id,
				'start_time' => (string) ( $starts[ $i ] ?? '' ),
				'end_time'   => (string) ( $ends[ $i ] ?? '' ),
				'badge'      => (string) ( $badges[ $i ] ?? '' ),
			);
		}
		update_post_meta( $post_id, '_event_dj_ids', array_values( array_unique( $ids ) ) );
		update_post_meta( $post_id, '_event_dj_slots', $slots );
	}
}
