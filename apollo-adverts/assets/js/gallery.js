/**
 * Apollo Adverts — Gallery JavaScript
 *
 * Adapted from WPAdverts assets/js/gallery.js
 * Handles plupload, drag & drop sorting, delete, set featured.
 *
 * Depends on: jQuery, jQuery UI Sortable, plupload-all
 *
 * Localized data via `apolloAdvertsData`:
 *   ajax_url, nonce, max_images, i18n
 *
 * @package Apollo\Adverts
 */

(function( $, plupload ) {
	'use strict';

	var config = window.apolloAdvertsData || {};

	if ( ! config.ajax_url ) return;

	/**
	 * Init gallery on page load
	 */
	$( document ).ready( function() {
		$( '.apollo-adverts-gallery' ).each( function() {
			initGallery( $( this ) );
		});
	});

	/**
	 * Initialize a gallery container
	 */
	function initGallery( $gallery ) {
		var $images   = $gallery.find( '.apollo-gallery-images' );
		var $progress = $gallery.find( '.apollo-gallery-progress' );
		var $fill     = $progress.find( '.progress-fill' );
		var $btn      = $gallery.find( '.apollo-gallery-upload-btn' );
		var postId    = $gallery.data( 'post-id' ) || 0;

		// Sortable — reorder images
		$images.sortable({
			items:       '.apollo-gallery-item',
			placeholder: 'apollo-gallery-placeholder-item',
			tolerance:   'pointer',
			update: function() {
				var order = [];
				$images.find( '.apollo-gallery-item' ).each( function() {
					order.push( $( this ).data( 'id' ) );
				});

				$.post( config.ajax_url, {
					action:  'apollo_adverts_gallery_reorder',
					nonce:   config.nonce,
					post_id: postId,
					order:   order
				});
			}
		});

		// Plupload init
		var uploader = new plupload.Uploader({
			browse_button:  $btn[0],
			url:            config.ajax_url,
			multipart:      true,
			multipart_params: {
				action:  'apollo_adverts_gallery_upload',
				nonce:   config.nonce,
				post_id: postId
			},
			filters: {
				mime_types: [
					{ title: 'Images', extensions: 'jpg,jpeg,png,gif,webp' }
				],
				max_file_size: '5mb'
			}
		});

		uploader.init();

		uploader.bind( 'BeforeUpload', function() {
			var count = $images.find( '.apollo-gallery-item' ).length;
			var max   = parseInt( config.max_images, 10 ) || 8;
			if ( count >= max ) {
				alert( config.i18n.max_images_reached || 'Número máximo de imagens atingido.' );
				uploader.stop();
				return false;
			}
			$progress.show();
		});

		uploader.bind( 'UploadProgress', function( up, file ) {
			$fill.css( 'width', file.percent + '%' );
		});

		uploader.bind( 'FileUploaded', function( up, file, result ) {
			$progress.hide();
			$fill.css( 'width', '0%' );

			try {
				var resp = JSON.parse( result.response );
				if ( resp.success && resp.data ) {
					var item = resp.data;
					// Update post_id for subsequent uploads (temp post created on first upload)
					if ( item.post_id ) {
						postId = item.post_id;
						uploader.settings.multipart_params.post_id = postId;
						$gallery.data( 'post-id', postId );
						// Update hidden field if present
						$gallery.closest( 'form' ).find( 'input[name="post_id"]' ).val( postId );
					}
					appendImage( $images, item );
				} else {
					alert( resp.data || 'Erro ao enviar imagem.' );
				}
			} catch( e ) {
				alert( 'Erro ao processar resposta.' );
			}
		});

		uploader.bind( 'Error', function( up, err ) {
			$progress.hide();
			alert( err.message || 'Erro no upload.' );
		});

		// Delete image
		$gallery.on( 'click', '.apollo-gallery-delete', function( e ) {
			e.preventDefault();
			var $item = $( this ).closest( '.apollo-gallery-item' );
			var attachId = $item.data( 'id' );

			$.post( config.ajax_url, {
				action:        'apollo_adverts_gallery_delete',
				nonce:         config.nonce,
				post_id:       postId,
				attachment_id: attachId
			}).done( function( resp ) {
				if ( resp.success ) {
					$item.fadeOut( 200, function() { $item.remove(); } );
				}
			});
		});

		// Set featured (first image)
		$gallery.on( 'click', '.apollo-gallery-featured', function( e ) {
			e.preventDefault();
			var $item = $( this ).closest( '.apollo-gallery-item' );
			var attachId = $item.data( 'id' );

			$.post( config.ajax_url, {
				action:        'apollo_adverts_gallery_set_featured',
				nonce:         config.nonce,
				post_id:       postId,
				attachment_id: attachId
			}).done( function( resp ) {
				if ( resp.success ) {
					$images.find( '.apollo-gallery-item' ).removeClass( 'is-featured' );
					$item.addClass( 'is-featured' );
					// Move to first position
					$images.prepend( $item );
				}
			});
		});
	}

	/**
	 * Append uploaded image to gallery
	 */
	function appendImage( $container, item ) {
		var html = '<div class="apollo-gallery-item" data-id="' + item.attach_id + '">' +
			'<img src="' + item.thumb + '" alt="" />' +
			'<div class="apollo-gallery-item-actions">' +
				'<button type="button" class="apollo-gallery-featured" title="Destaque">&#9733;</button>' +
				'<button type="button" class="apollo-gallery-delete" title="Remover">&times;</button>' +
			'</div>' +
		'</div>';
		$container.append( html );
	}

})( jQuery, window.plupload );
