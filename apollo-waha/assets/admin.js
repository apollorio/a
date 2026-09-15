/* apollo-waha admin — Testar button. Never puts the API key in the DOM or in this file. */
( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		var strip  = document.getElementById( 'apollo-wa-strip' );
		var button = document.getElementById( 'apollo-wa-test' );

		if ( ! strip || ! button || typeof window.ajaxurl === 'undefined' ) {
			return;
		}

		var dot   = strip.querySelector( '.apollo-wa-strip__dot' );
		var label = strip.querySelector( '.apollo-wa-strip__label' );

		function paint( state ) {
			strip.className = 'apollo-wa-strip apollo-wa-strip--' + state.toLowerCase();
			if ( label ) {
				label.textContent = state;
			}
		}

		button.addEventListener( 'click', function () {
			var nonce = strip.getAttribute( 'data-nonce' ) || '';

			button.disabled = true;
			button.setAttribute( 'aria-busy', 'true' );

			var body = new URLSearchParams();
			body.set( 'action', 'apollo_wa_test_connection' );
			body.set( 'nonce', nonce );

			fetch( window.ajaxurl, {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: body.toString()
			} )
				.then( function ( res ) { return res.json(); } )
				.then( function ( json ) {
					var data = ( json && json.data ) ? json.data : json;
					paint( ( data && data.status ) ? data.status : 'DOWN' );
				} )
				.catch( function () {
					paint( 'DOWN' );
				} )
				.finally( function () {
					button.disabled = false;
					button.removeAttribute( 'aria-busy' );
				} );
		} );
	} );
}() );
