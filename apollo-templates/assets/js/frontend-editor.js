/**
 * Apollo Frontend Editor — JavaScript
 *
 * Handles:
 *   - AJAX form submission via admin-ajax.php
 *   - Image upload via AJAX (drag & drop + click)
 *   - Image deletion
 *   - Character counters on text/textarea fields
 *   - Client-side validation (required, URL, email, maxlength)
 *   - Toast notifications
 *   - Unsaved changes warning
 *   - Save bar state management
 *
 * Depends on: jQuery, apolloEditor (wp_localize_script)
 *
 * @package Apollo\Templates
 */

(function ($) {
	'use strict';

	/* ═══════════════════════════════════════════════════════════════════════
	   STATE
	   ═══════════════════════════════════════════════════════════════════════ */

	let isDirty = false;
	let isSaving = false;

	const config = window.apolloEditor || {};

	/* A debug beacon lived here (removed 2026-08-17). It defined
	   window.__apolloAgentDbg, which POSTed to http://127.0.0.1:7514 from every
	   visitor's browser that loaded the frontend editor — mixed content on
	   HTTPS, and a request that could only ever resolve on one laptop. */

	/* ═══════════════════════════════════════════════════════════════════════
	   INIT
	   ═══════════════════════════════════════════════════════════════════════ */

	$(function () {
		initCharCounters();
		initImageUploads();
		initFormSubmit();
		initDirtyTracking();
		initValidation();
	});

	/* ═══════════════════════════════════════════════════════════════════════
	   TOAST NOTIFICATION
	   ═══════════════════════════════════════════════════════════════════════ */

	/**
	 * Show a toast notification.
	 *
	 * @param {string} type - 'success' | 'error' | 'info'
	 * @param {string} message - Text to display
	 * @param {number} duration - Auto-hide ms (default 4000)
	 */
	function showToast(type, message, duration) {
		duration = duration || 4000;

		let $toast = $('#apollo-toast');

		if (!$toast.length) {
			$toast = $('<div id="apollo-toast"></div>').appendTo('body');
		}

		$toast
			.removeClass('success error info visible')
			.addClass(type)
			.text(message);

		// Force reflow for animation
		$toast[0].offsetHeight; // eslint-disable-line no-unused-expressions

		$toast.addClass('visible');

		clearTimeout($toast.data('timer'));
		$toast.data('timer', setTimeout(function () {
			$toast.removeClass('visible');
		}, duration));
	}

	/* ═══════════════════════════════════════════════════════════════════════
	   SAVE STATUS
	   ═══════════════════════════════════════════════════════════════════════ */

	/**
	 * Update the save bar status text.
	 *
	 * @param {string} state - 'idle' | 'saving' | 'saved' | 'error'
	 * @param {string} [text] - Optional custom text
	 */
	function setSaveStatus(state, text) {
		const $status = $('.apollo-save-status');
		const $btn = $('.apollo-save-btn');

		$status.removeClass('saving saved error');

		switch (state) {
			case 'saving':
				$status.addClass('saving').text(text || config.i18n?.saving || 'Salvando...');
				$btn.prop('disabled', true);
				break;
			case 'saved':
				$status.addClass('saved').text(text || config.i18n?.saved || 'Salvo!');
				$btn.prop('disabled', false);
				setTimeout(function () {
					$status.removeClass('saved').text('');
				}, 3000);
				break;
			case 'error':
				$status.addClass('error').text(text || config.i18n?.error || 'Erro');
				$btn.prop('disabled', false);
				break;
			default:
				$status.text('');
				$btn.prop('disabled', false);
		}
	}

	/* ═══════════════════════════════════════════════════════════════════════
	   CHARACTER COUNTERS
	   ═══════════════════════════════════════════════════════════════════════ */

	function initCharCounters() {
		$('.editable-input[data-maxlength]').on('input', function () {
			const $input = $(this);
			const name = $input.attr('name');
			const maxLength = parseInt($input.attr('data-maxlength'), 10);
			const currentLen = $input.val().length;
			const $counter = $('.char-counter[data-field="' + name + '"]');

			if ($counter.length) {
				$counter.text('(' + currentLen + '/' + maxLength + ')');

				if (currentLen > maxLength * 0.9) {
					$counter.css('color', 'var(--ae-danger, #dc2626)');
				} else {
					$counter.css('color', '');
				}
			}
		});
	}

	/* ═══════════════════════════════════════════════════════════════════════
	   DIRTY TRACKING (unsaved changes warning)
	   ═══════════════════════════════════════════════════════════════════════ */

	function initDirtyTracking() {
		$('#apollo-editor-form').on('input change', '.editable-input, select, input[type="checkbox"]', function () {
			if (!isDirty) {
				isDirty = true;
			}
		});

		// Warn on page leave
		$(window).on('beforeunload', function () {
			if (isDirty && !isSaving) {
				return config.i18n?.confirm || 'Descartar alterações?';
			}
		});
	}

	/* ═══════════════════════════════════════════════════════════════════════
	   VALIDATION
	   ═══════════════════════════════════════════════════════════════════════ */

	function initValidation() {
		// Clear error on input
		$('#apollo-editor-form').on('input', '.editable-input', function () {
			$(this).closest('.apollo-editor-field').removeClass('has-error');
			$(this).siblings('.apollo-field-error').remove();
		});
	}

	/**
	 * Validate form fields.
	 *
	 * @return {boolean} - True if valid
	 */
	function validateForm() {
		let valid = true;
		const $form = $('#apollo-editor-form');

		// Clear previous errors
		$form.find('.has-error').removeClass('has-error');
		$form.find('.apollo-field-error').remove();

		// Required fields
		$form.find('[data-required="1"]').each(function () {
			const $input = $(this);
			const val = $input.val();

			if (!val || (typeof val === 'string' && val.trim() === '')) {
				markError($input, config.i18n?.required || 'Campo obrigatório');
				valid = false;
			}
		});

		// URL validation
		$form.find('input[type="url"]').each(function () {
			const val = $(this).val();
			if (val && !isValidUrl(val)) {
				markError($(this), config.i18n?.invalidUrl || 'URL inválida');
				valid = false;
			}
		});

		// Email validation
		$form.find('input[type="email"]').each(function () {
			const val = $(this).val();
			if (val && !isValidEmail(val)) {
				markError($(this), config.i18n?.invalidEmail || 'E-mail inválido');
				valid = false;
			}
		});

		return valid;
	}

	/**
	 * Mark a field as having an error.
	 */
	function markError($input, message) {
		const $field = $input.closest('.apollo-editor-field');
		$field.addClass('has-error');
		$input.after('<span class="apollo-field-error">' + escHtml(message) + '</span>');

		// Scroll to first error
		if ($('.has-error').length === 1) {
			$('html, body').animate({
				scrollTop: $field.offset().top - 100
			}, 300);
		}
	}

	function isValidUrl(str) {
		try {
			new URL(str);
			return true;
		} catch (_) {
			return false;
		}
	}

	function isValidEmail(str) {
		return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(str);
	}

	function escHtml(str) {
		const div = document.createElement('div');
		div.appendChild(document.createTextNode(str));
		return div.innerHTML;
	}

	/* ═══════════════════════════════════════════════════════════════════════
	   FORM SUBMIT (AJAX)
	   ═══════════════════════════════════════════════════════════════════════ */

	function initFormSubmit() {
		$('#apollo-editor-form').on('submit', function (e) {
			e.preventDefault();
			submitForm();
		});

		// Save button click
		$(document).on('click', '.apollo-save-btn', function (e) {
			e.preventDefault();
			submitForm();
		});

		// Ctrl+S / Cmd+S shortcut
		$(document).on('keydown', function (e) {
			if ((e.ctrlKey || e.metaKey) && e.key === 's') {
				e.preventDefault();
				submitForm();
			}
		});
	}

	function submitForm() {
		if (isSaving) return;

		// Validate
		if (!validateForm()) {
			showToast('error', config.i18n?.required || 'Preencha os campos obrigatórios');
			return;
		}

		isSaving = true;
		setSaveStatus('saving');

		const $form = $('#apollo-editor-form');
		const formData = new FormData($form[0]);

		// Ensure action is set
		formData.set('action', 'apollo_frontend_save');

		$.ajax({
			url: config.ajaxUrl,
			type: 'POST',
			data: formData,
			processData: false,
			contentType: false,
			dataType: 'json',
			success: function (response) {
				isSaving = false;
				isDirty = false;

				if (response.success) {
					setSaveStatus('saved', response.data?.message);
					showToast('success', response.data?.message || config.i18n?.saved || 'Salvo!');
				} else {
					setSaveStatus('error', response.data?.message);
					showToast('error', response.data?.message || config.i18n?.error || 'Erro ao salvar');
				}
			},
			error: function (xhr) {
				isSaving = false;
				const msg = xhr.responseJSON?.data?.message || config.i18n?.error || 'Erro ao salvar';
				setSaveStatus('error', msg);
				showToast('error', msg);
			}
		});
	}

	/* ═══════════════════════════════════════════════════════════════════════
	   IMAGE UPLOAD
	   ═══════════════════════════════════════════════════════════════════════ */

	function initImageUploads() {
		// Click to upload — open the hidden file input from a real user gesture.
		$(document).on('click', '.apollo-image-upload-btn', function (e) {
			e.preventDefault();
			e.stopPropagation();
			const $upload = $(this).closest('.apollo-image-upload');
			const $file = $upload.find('.apollo-image-file-input').get(0);
			if ($file && typeof $file.click === 'function') {
				$file.click();
			}
		});

		// Also click on preview area (empty or with image overlay).
		$(document).on('click', '.apollo-image-preview', function (e) {
			if ($(e.target).closest('.apollo-image-delete-btn').length) {
				return;
			}
			const $upload = $(this).closest('.apollo-image-upload');
			const $file = $upload.find('.apollo-image-file-input').get(0);
			if ($file && typeof $file.click === 'function') {
				$file.click();
			}
		});

		// File selected
		$(document).on('change', '.apollo-image-file-input', function () {
			const $input = $(this);
			const $upload = $input.closest('.apollo-image-upload');
			const file = this.files[0];

			if (!file) return;

			// Validate file type
			const allowed = config.allowedMime || ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
			if (allowed.indexOf(file.type) === -1) {
				showToast('error', 'Formato não suportado. Use JPG, PNG, WebP ou GIF.');
				return;
			}

			// Validate file size
			const maxSize = config.maxUpload || 5242880; // 5MB default
			if (file.size > maxSize) {
				showToast('error', 'Arquivo muito grande. Máximo: ' + Math.round(maxSize / 1048576) + 'MB');
				return;
			}

			uploadImage($upload, file);
			// Allow re-selecting the same file later.
			$input.val('');
		});

		// Delete image
		$(document).on('click', '.apollo-image-delete-btn', function (e) {
			e.preventDefault();
			e.stopPropagation();

			const $upload = $(this).closest('.apollo-image-upload');
			deleteImage($upload);
		});

		// Drag & drop
		$(document).on('dragover', '.apollo-image-preview', function (e) {
			e.preventDefault();
			$(this).addClass('drag-over');
		});

		$(document).on('dragleave', '.apollo-image-preview', function () {
			$(this).removeClass('drag-over');
		});

		$(document).on('drop', '.apollo-image-preview', function (e) {
			e.preventDefault();
			$(this).removeClass('drag-over');

			const files = e.originalEvent.dataTransfer.files;
			if (files.length > 0) {
				const $upload = $(this).closest('.apollo-image-upload');
				uploadImage($upload, files[0]);
			}
		});
	}

	/**
	 * Upload an image via AJAX.
	 */
	function uploadImage($upload, file) {
		const metaKey = $upload.data('meta-key');
		const postId = $upload.data('post-id') || config.postId;

		if (!metaKey || !postId) {
			showToast('error', 'Configuração inválida para upload.');
			return;
		}

		$upload.addClass('uploading');
		showToast('info', config.i18n?.uploading || 'Enviando imagem...');

		const formData = new FormData();
		formData.append('file', file);
		formData.append('action', 'apollo_frontend_upload');
		formData.append('post_id', postId);
		formData.append('meta_key', metaKey);
		formData.append('_apollo_editor_nonce', $('input[name="_apollo_editor_nonce"]').val());

		$.ajax({
			url: config.ajaxUrl,
			type: 'POST',
			data: formData,
			processData: false,
			contentType: false,
			dataType: 'json',
			success: function (response) {
				$upload.removeClass('uploading');

				if (response.success) {
					const imageUrl = response.data.thumbnail || response.data.url;
					const $preview = $upload.find('.apollo-image-preview');

					// Update preview
					$preview.addClass('has-image');
					if ($preview.find('img').length) {
						$preview.find('img').attr('src', imageUrl);
					} else {
						$preview.prepend('<img src="' + imageUrl + '" alt="">');
					}

					// Add delete button if not present
					if (!$preview.find('.apollo-image-delete-btn').length) {
						$preview.find('.apollo-image-overlay').append(
							'<button type="button" class="apollo-image-delete-btn hero-edit-btn" title="Remover"><i class="ri-delete-bin-line"></i></button>'
						);
					}

					// Update hidden input — prefer attachment id (Apollo SSOT).
					const storeVal = (response.data.stored_as === 'url')
						? (response.data.url || '')
						: String(response.data.attachment_id || response.data.url || '');
					$upload.find('input[type="hidden"]').val(storeVal);

					showToast('success', config.i18n?.uploaded || 'Imagem enviada!');
					isDirty = true;
				} else {
					showToast('error', response.data?.message || config.i18n?.uploadError || 'Erro no upload');
				}
			},
			error: function (xhr) {
				$upload.removeClass('uploading');
				const msg = xhr.responseJSON?.data?.message || config.i18n?.uploadError || 'Erro no upload';
				showToast('error', msg);
			}
		});
	}

	/**
	 * Delete an image via AJAX.
	 */
	function deleteImage($upload) {
		const metaKey = $upload.data('meta-key');
		const postId = $upload.data('post-id') || config.postId;

		if (!metaKey || !postId) return;

		$.ajax({
			url: config.ajaxUrl,
			type: 'POST',
			data: {
				action: 'apollo_frontend_delete_image',
				post_id: postId,
				meta_key: metaKey,
				_apollo_editor_nonce: $('input[name="_apollo_editor_nonce"]').val()
			},
			dataType: 'json',
			success: function (response) {
				if (response.success) {
					const $preview = $upload.find('.apollo-image-preview');
					$preview.removeClass('has-image').find('img').remove();
					$preview.find('.apollo-image-delete-btn').remove();
					$upload.find('input[type="hidden"]').val('');

					showToast('success', config.i18n?.deleted || 'Imagem removida');
					isDirty = true;
				} else {
					showToast('error', response.data?.message || 'Erro ao remover');
				}
			},
			error: function () {
				showToast('error', 'Erro ao remover imagem');
			}
		});
	}

})(jQuery);
