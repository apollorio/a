/**
 * Apollo Admin — Main JavaScript
 *
 * @package Apollo\Admin
 */

( function( $ ) {
	'use strict';

	const Apollo = {

		/**
		 * Initialize
		 */
		init() {
			this.bindEvents();
			this.initColorPickers();
			this.highlightCurrentTab();
		},

		/**
		 * Bind DOM events
		 */
		bindEvents() {
			// Confirm before leaving with unsaved changes
			let dirty = false;

			$( '.apollo-settings-table' ).on( 'change input', 'input, select, textarea', function() {
				dirty = true;
			});

			$( window ).on( 'beforeunload', function( e ) {
				if ( dirty ) {
					e.preventDefault();
					e.returnValue = '';
				}
			});

			// Clear dirty flag on submit
			$( '.apollo-admin-content form' ).on( 'submit', function() {
				dirty = false;
			});

			// Auto-dismiss notices after 4s
			$( '.apollo-admin-wrap > .notice' ).each( function() {
				const $notice = $( this );
				setTimeout( function() {
					$notice.fadeOut( 300, function() {
						$notice.remove();
					});
				}, 4000 );
			});
		},

		/**
		 * Initialize WordPress color pickers
		 */
		initColorPickers() {
			if ( $.fn.wpColorPicker ) {
				$( '.apollo-color-field' ).wpColorPicker({
					change: function() {
						$( this ).trigger( 'change' );
					},
				});
			}
		},

		/**
		 * Scroll sidebar to show the active tab
		 */
		highlightCurrentTab() {
			const $active = $( '.apollo-tab-link.active' );
			if ( $active.length ) {
				const $sidebar = $( '.apollo-admin-sidebar' );
				const offset = $active.position().top - $sidebar.height() / 2;
				if ( offset > 0 ) {
					$sidebar.scrollTop( offset );
				}
			}
		},
	};

	// Boot on DOM ready
	$( function() {
		Apollo.init();
	});

})( jQuery );
