<?php
/**
 * Apollo Docs — File Manager Template
 * Admin page rendered by Admin\Controller::render_page()
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap" style="margin:0;padding:0;max-width:100%">
<div class="apollo-fm" id="apollo-file-manager">

	<!-- ═══ TOOLBAR ═══ -->
	<div class="fm-toolbar">
		<div class="fm-toolbar-title">
			<i class="ri-folder-3-fill"></i>
			<span>Arquivos</span>
		</div>

		<button class="fm-btn fm-btn-sidebar-toggle" style="display:none" title="Menu">
			<i class="ri-menu-line"></i>
		</button>

		<div class="fm-toolbar-search">
			<i class="ri-search-line"></i>
			<input type="text" placeholder="Buscar arquivos..." autocomplete="off" class="apollo-input">
			<button class="fm-search-clear" type="button"><i class="ri-close-line"></i></button>
		</div>

		<div class="fm-toolbar-actions">
			<button class="fm-btn fm-btn-primary fm-btn-new-doc">
				<i class="ri-file-add-line"></i> Novo
			</button>

			<button class="fm-btn fm-btn-upload" title="Upload">
				<i class="ri-upload-2-line"></i>
			</button>

			<div class="fm-view-toggle">
				<button class="fm-btn-icon active" data-mode="grid" title="Grade">
					<i class="ri-grid-fill"></i>
				</button>
				<button class="fm-btn-icon" data-mode="table" title="Lista">
					<i class="ri-list-check"></i>
				</button>
			</div>

			<button class="fm-btn-icon fm-btn-preview-toggle" title="Painel de informações">
				<i class="ri-side-bar-line"></i>
			</button>
		</div>
	</div>

	<!-- ═══ BODY ═══ -->
	<div class="fm-body">

		<!-- ── Sidebar ── -->
		<div class="fm-sidebar">
			<div class="fm-sidebar-header">
				<div class="fm-sidebar-add">
					<button class="fm-btn fm-sidebar-btn-doc" title="Novo Documento">
						<i class="ri-file-add-line"></i> Arquivo
					</button>
					<button class="fm-btn fm-sidebar-btn-folder" title="Nova Pasta">
						<i class="ri-folder-add-line"></i>
					</button>
				</div>
			</div>

			<div class="fm-tree">
				<!-- Rendered by JS -->
			</div>

			<div class="fm-storage">
				<div class="fm-storage-label">Armazenamento</div>
				<div class="fm-storage-bar">
					<div class="fm-storage-bar-fill" style="width: 0%"></div>
				</div>
				<div class="fm-storage-text">Calculando...</div>
			</div>
		</div>

		<!-- ── Content ── -->
		<div class="fm-content">
			<div class="fm-breadcrumbs">
				<!-- Rendered by JS -->
			</div>

			<div class="fm-files">
				<!-- Rendered by JS -->
			</div>
		</div>

		<!-- ── Preview Panel ── -->
		<div class="fm-preview">
			<div class="fm-preview-empty">
				<i class="ri-file-search-line"></i>
				<span>Selecione um arquivo para visualizar detalhes</span>
			</div>
		</div>

	</div>

	<!-- ═══ Drop Zone Overlay ═══ -->
	<div class="fm-dropzone">
		<div class="fm-dropzone-content">
			<i class="ri-upload-cloud-2-line"></i>
			<span>Solte os arquivos aqui para enviar</span>
		</div>
	</div>

	<!-- ═══ Context Menu ═══ -->
	<div class="fm-context-menu"></div>

	<!-- ═══ Modal Overlay ═══ -->
	<div class="fm-modal-overlay"></div>

	<!-- ═══ Hidden file input ═══ -->
	<input type="file" id="fm-file-input" multiple style="display:none">

</div>
</div>
<?php