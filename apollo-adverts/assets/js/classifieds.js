/**
 * Apollo Adverts — Frontend JavaScript
 *
 * Handles gallery slideshow, search form enhancements, confirm dialogs.
 *
 * Depends on: jQuery
 *
 * @package Apollo\Adverts
 */

(function( $ ) {
	'use strict';

	/**
	 * Gallery Slideshow — Single View
	 * Click thumbnail to change main image.
	 */
	$( document ).on( 'click', '.apollo-gallery-thumb', function( e ) {
		e.preventDefault();
		var $thumb = $( this );
		var src    = $thumb.data( 'full' );
		if ( ! src ) return;

		$( '#apollo-gallery-main-img' ).attr( 'src', src );
		$( '.apollo-gallery-thumb' ).removeClass( 'active' );
		$thumb.addClass( 'active' );
	});

	/**
	 * Toggle status via AJAX (manage page)
	 */
	$( document ).on( 'click', '.apollo-toggle-status', function( e ) {
		e.preventDefault();
		var $btn    = $( this );
		var postId  = $btn.data( 'id' );
		var status  = $btn.data( 'status' );
		var data    = window.apolloAdvertsData || {};

		if ( ! data.ajax_url || ! data.nonce ) return;

		$.post( data.ajax_url, {
			action:  'apollo_adverts_change_status',
			nonce:   data.nonce,
			post_id: postId,
			status:  status
		}).done( function( resp ) {
			if ( resp.success ) {
				location.reload();
			} else {
				alert( resp.data || 'Erro ao alterar status.' );
			}
		}).fail( function() {
			alert( 'Erro de conexão.' );
		});
	});

	/**
	 * Confirm delete
	 */
	$( document ).on( 'click', '.apollo-confirm-delete', function( e ) {
		if ( ! confirm( 'Tem certeza que deseja excluir este anúncio?' ) ) {
			e.preventDefault();
		}
	});

	/**
	 * Price mask — format as BRL while typing
	 */
	$( document ).on( 'input', '.apollo-adverts-form input[name="price"]', function() {
		var v = this.value.replace( /[^0-9]/g, '' );
		if ( v.length === 0 ) {
			this.value = '';
			return;
		}
		var num    = parseInt( v, 10 ) / 100;
		this.value = num.toLocaleString( 'pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 } );
	});

})( jQuery );
