/**
 * Apollo Adverts — Admin JavaScript
 *
 * Admin list table: AJAX status change, toggle featured, dashboard stats.
 *
 * Depends on: jQuery
 * Localized data via `apolloAdvertsAdmin`:
 *   ajax_url, nonce
 *
 * @package Apollo\Adverts
 */

(function( $ ) {
	'use strict';

	var config = window.apolloAdvertsAdmin || {};

	if ( ! config.ajax_url ) return;

	/**
	 * Toggle featured star in admin list
	 */
	$( document ).on( 'click', '.apollo-admin-toggle-featured', function( e ) {
		e.preventDefault();
		var $el    = $( this );
		var postId = $el.data( 'post-id' );

		$.post( config.ajax_url, {
			action:  'apollo_adverts_toggle_featured',
			nonce:   config.nonce,
			post_id: postId
		}).done( function( resp ) {
			if ( resp.success ) {
				var $icon = $el.find( '.dashicons' );
				if ( resp.data.featured ) {
					$icon.removeClass( 'dashicons-star-empty' ).addClass( 'dashicons-star-filled' );
				} else {
					$icon.removeClass( 'dashicons-star-filled' ).addClass( 'dashicons-star-empty' );
				}
			}
		});
	});

	/**
	 * Dashboard — load stats via AJAX
	 */
	var $dashboard = $( '.apollo-adverts-dashboard' );
	if ( $dashboard.length ) {
		loadDashboardStats();
	}

	function loadDashboardStats() {
		$.post( config.ajax_url, {
			action: 'apollo_adverts_dashboard_stats',
			nonce:  config.nonce
		}).done( function( resp ) {
			if ( resp.success && resp.data ) {
				var d = resp.data;
				$dashboard.find( '.stat-active' ).text( d.active || 0 );
				$dashboard.find( '.stat-pending' ).text( d.pending || 0 );
				$dashboard.find( '.stat-expired' ).text( d.expired || 0 );
				$dashboard.find( '.stat-trash' ).text( d.trash || 0 );
			}
		});
	}

	/**
	 * Admin inline status change
	 */
	$( document ).on( 'click', '.apollo-admin-change-status', function( e ) {
		e.preventDefault();
		var $el    = $( this );
		var postId = $el.data( 'post-id' );
		var status = $el.data( 'status' );

		$.post( config.ajax_url, {
			action:  'apollo_adverts_change_status',
			nonce:   config.nonce,
			post_id: postId,
			status:  status
		}).done( function( resp ) {
			if ( resp.success ) {
				location.reload();
			} else {
				alert( resp.data || 'Erro ao alterar status.' );
			}
		});
	});

})( jQuery );
