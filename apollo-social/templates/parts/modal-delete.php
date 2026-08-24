<?php

/**
 * Template Part: Delete Confirmation Modal
 *
 * Uses .modal / .modal-content per approved design.
 *
 * @package Apollo\Social
 * @since   2.1.0
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="modal" id="modal-delete" style="display:none;">
	<div class="modal-content">
		<div class="modal-header">
			<i class="ri-delete-bin-line"></i>
			<h3>Excluir post?</h3>
		</div>
		<p>Esta ação não pode ser desfeita.</p>
		<div class="modal-actions">
			<button class="btn-cancel" onclick="closeDeleteModal()">Cancelar</button>
			<button class="btn-modal-danger" id="modal-delete-confirm">Excluir</button>
		</div>
	</div>
</div>
