/**
 * Apollo Docs — File Manager Controller
 * Full file manager: tree, grid/table views, preview, upload, context menu, hotkeys
 * Requires: jQuery, ApolloDocs (wp_localize_script)
 */
;(function ($) {
    'use strict';

    if (typeof ApolloDocs === 'undefined') return;

    /* ═══════════════════════════════════════════════════════════
       STATE
       ═══════════════════════════════════════════════════════════ */
    const S = {
        documents: [],
        folders: [],
        selected: [],
        clipboard: null,       // {action:'copy'|'cut', ids:[]}
        currentFolder: 0,
        breadcrumb: [{ id: 0, name: 'Todos os Arquivos' }],
        viewMode: 'grid',      // grid | table
        previewOpen: false,
        previewDoc: null,
        sortBy: 'updated_at',
        sortDir: 'DESC',
        searchQuery: '',
        page: 1,
        perPage: 40,
        totalPages: 1,
        totalItems: 0,
        loading: false,
        uploading: [],
    };

    /* ═══════════════════════════════════════════════════════════
       UTILITIES
       ═══════════════════════════════════════════════════════════ */
    function ajax(action, data = {}) {
        data.action = 'apollo_docs_' + action;
        data.nonce  = ApolloDocs.nonce;
        return $.post(ApolloDocs.ajax_url, data);
    }

    function formatSize(bytes) {
        if (!bytes) return '—';
        const u = ['B', 'KB', 'MB', 'GB'];
        let i = 0;
        while (bytes >= 1024 && i < u.length - 1) { bytes /= 1024; i++; }
        return bytes.toFixed(i ? 1 : 0) + ' ' + u[i];
    }

    function formatDate(d) {
        if (!d) return '—';
        const dt = new Date(d);
        const dd = String(dt.getDate()).padStart(2, '0');
        const mm = String(dt.getMonth() + 1).padStart(2, '0');
        const yy = String(dt.getFullYear()).slice(-2);
        const hh = String(dt.getHours()).padStart(2, '0');
        const mi = String(dt.getMinutes()).padStart(2, '0');
        return dd + '/' + mm + '/' + yy + ' ' + hh + ':' + mi;
    }

    function timeAgo(d) {
        if (!d) return '';
        const diff = Date.now() - new Date(d).getTime();
        const m = Math.floor(diff / 60000);
        if (m < 1)    return 'agora';
        if (m < 60)   return m + 'min';
        const h = Math.floor(m / 60);
        if (h < 24)   return h + 'h';
        const dd = Math.floor(h / 24);
        if (dd < 30)  return dd + 'd';
        const mo = Math.floor(dd / 30);
        return mo + 'mo';
    }

    function escHtml(s) {
        const d = document.createElement('div');
        d.textContent = s || '';
        return d.innerHTML;
    }

    function fileIcon(doc) {
        if (doc._is_folder) return { cls: 'type-folder', icon: 'ri-folder-3-fill' };
        const ext = (doc.title || '').split('.').pop().toLowerCase();
        const map = {
            pdf:  { cls: 'type-pdf',     icon: 'ri-file-pdf-2-fill' },
            jpg:  { cls: 'type-image',   icon: 'ri-image-fill' },
            jpeg: { cls: 'type-image',   icon: 'ri-image-fill' },
            png:  { cls: 'type-image',   icon: 'ri-image-fill' },
            gif:  { cls: 'type-image',   icon: 'ri-image-fill' },
            webp: { cls: 'type-image',   icon: 'ri-image-fill' },
            svg:  { cls: 'type-image',   icon: 'ri-image-fill' },
            mp4:  { cls: 'type-video',   icon: 'ri-video-fill' },
            mov:  { cls: 'type-video',   icon: 'ri-video-fill' },
            avi:  { cls: 'type-video',   icon: 'ri-video-fill' },
            webm: { cls: 'type-video',   icon: 'ri-video-fill' },
            mp3:  { cls: 'type-audio',   icon: 'ri-music-fill' },
            wav:  { cls: 'type-audio',   icon: 'ri-music-fill' },
            ogg:  { cls: 'type-audio',   icon: 'ri-music-fill' },
            flac: { cls: 'type-audio',   icon: 'ri-music-fill' },
            doc:  { cls: 'type-doc',     icon: 'ri-file-word-fill' },
            docx: { cls: 'type-doc',     icon: 'ri-file-word-fill' },
            xls:  { cls: 'type-sheet',   icon: 'ri-file-excel-fill' },
            xlsx: { cls: 'type-sheet',   icon: 'ri-file-excel-fill' },
            csv:  { cls: 'type-sheet',   icon: 'ri-file-excel-fill' },
            ppt:  { cls: 'type-doc',     icon: 'ri-file-ppt-fill' },
            pptx: { cls: 'type-doc',     icon: 'ri-file-ppt-fill' },
            zip:  { cls: 'type-archive', icon: 'ri-file-zip-fill' },
            rar:  { cls: 'type-archive', icon: 'ri-file-zip-fill' },
            '7z': { cls: 'type-archive', icon: 'ri-file-zip-fill' },
            js:   { cls: 'type-code',    icon: 'ri-file-code-fill' },
            ts:   { cls: 'type-code',    icon: 'ri-file-code-fill' },
            php:  { cls: 'type-code',    icon: 'ri-file-code-fill' },
            css:  { cls: 'type-code',    icon: 'ri-file-code-fill' },
            html: { cls: 'type-code',    icon: 'ri-file-code-fill' },
            json: { cls: 'type-code',    icon: 'ri-file-code-fill' },
            txt:  { cls: 'type-file',    icon: 'ri-file-text-fill' },
        };
        return map[ext] || { cls: 'type-file', icon: 'ri-file-fill' };
    }

    function statusLabel(s) {
        const m = {
            draft:     'Rascunho',
            locked:    'Bloqueado',
            finalized: 'Finalizado',
            signed:    'Assinado',
        };
        return m[s] || s;
    }

    /* ═══════════════════════════════════════════════════════════
       DATA FETCHING
       ═══════════════════════════════════════════════════════════ */
    function loadDocuments() {
        if (S.loading) return;
        S.loading = true;
        showSkeleton();

        ajax('load_documents', {
            folder_id: S.currentFolder || '',
            search:    S.searchQuery,
            status:    '',
            per_page:  S.perPage,
            page:      S.page,
        }).done(function (r) {
            if (r.success) {
                S.documents  = r.data.items || [];
                S.totalItems = r.data.total || 0;
                S.totalPages = r.data.pages || 1;
            } else {
                S.documents = [];
            }
        }).fail(function () {
            S.documents = [];
            toast('Erro ao carregar documentos', 'error');
        }).always(function () {
            S.loading = false;
            S.selected = [];
            render();
        });
    }

    function loadFolders() {
        ajax('load_folders').done(function (r) {
            if (r.success) {
                S.folders = r.data || [];
            }
            renderTree();
        });
    }

    /* ═══════════════════════════════════════════════════════════
       RENDERING — TREE SIDEBAR
       ═══════════════════════════════════════════════════════════ */
    function renderTree() {
        const $tree = $('.fm-tree');
        if (!$tree.length) return;

        // Build hierarchy
        const roots = S.folders.filter(f => !f.parent);
        const childMap = {};
        S.folders.forEach(f => {
            if (f.parent) {
                if (!childMap[f.parent]) childMap[f.parent] = [];
                childMap[f.parent].push(f);
            }
        });

        let html = '';

        // "All Files" root
        html += '<div class="fm-tree-item' + (S.currentFolder === 0 ? ' active' : '') + '" data-folder="0">';
        html += '<i class="ri-home-4-line fm-tree-icon" style="color:var(--docs-text-muted)"></i>';
        html += '<span class="fm-tree-label">Todos os Arquivos</span>';
        html += '</div>';

        // Separator
        html += '<div style="height:1px;background:var(--docs-border);margin:4px 16px"></div>';

        // Category shortcuts
        const cats = [
            { icon: 'ri-file-pdf-2-fill',   color: 'var(--docs-type-pdf)',   label: 'Documentos',  filter: 'doc' },
            { icon: 'ri-image-fill',         color: 'var(--docs-type-image)', label: 'Imagens',     filter: 'image' },
            { icon: 'ri-video-fill',         color: 'var(--docs-type-video)', label: 'Vídeos',      filter: 'video' },
            { icon: 'ri-music-fill',         color: 'var(--docs-type-audio)', label: 'Áudio',       filter: 'audio' },
        ];
        cats.forEach(c => {
            html += '<div class="fm-tree-item" data-type-filter="' + c.filter + '">';
            html += '<i class="' + c.icon + ' fm-tree-icon" style="color:' + c.color + '"></i>';
            html += '<span class="fm-tree-label">' + c.label + '</span>';
            html += '</div>';
        });

        html += '<div style="height:1px;background:var(--docs-border);margin:4px 16px"></div>';

        // Folder tree
        function renderFolder(folder, depth) {
            const children = childMap[folder.id] || [];
            const hasChildren = children.length > 0;
            const isActive = S.currentFolder === folder.id;
            const pad = depth * 20;

            let h = '<div class="fm-tree-item' + (isActive ? ' active' : '') + '" data-folder="' + folder.id + '" style="padding-left:' + (16 + pad) + 'px">';
            if (hasChildren) {
                h += '<span class="fm-tree-chevron"><i class="ri-arrow-right-s-line"></i></span>';
            } else {
                h += '<span style="width:16px;flex-shrink:0"></span>';
            }
            h += '<i class="ri-folder-3-fill fm-tree-icon"></i>';
            h += '<span class="fm-tree-label">' + escHtml(folder.name) + '</span>';
            if (folder.count > 0) {
                h += '<span class="fm-tree-count">' + folder.count + '</span>';
            }
            h += '</div>';

            if (hasChildren) {
                h += '<div class="fm-tree-children">';
                children.forEach(ch => { h += renderFolder(ch, depth + 1); });
                h += '</div>';
            }

            return h;
        }

        roots.forEach(f => { html += renderFolder(f, 0); });

        $tree.html(html);
    }

    /* ═══════════════════════════════════════════════════════════
       RENDERING — BREADCRUMB
       ═══════════════════════════════════════════════════════════ */
    function renderBreadcrumb() {
        const $bc = $('.fm-breadcrumbs');
        if (!$bc.length) return;

        let html = '';
        S.breadcrumb.forEach((crumb, i) => {
            if (i > 0) html += '<span class="fm-breadcrumb-sep"><i class="ri-arrow-right-s-line"></i></span>';
            const isCurrent = i === S.breadcrumb.length - 1;
            html += '<span class="fm-breadcrumb-item' + (isCurrent ? ' current' : '') + '" data-folder="' + crumb.id + '">';
            html += escHtml(crumb.name);
            html += '</span>';
        });

        // Refresh button
        html += '<button class="fm-breadcrumb-refresh" title="Atualizar"><i class="ri-refresh-line"></i></button>';

        // Item count
        html += '<span style="margin-left:8px;font-size:11px;color:var(--docs-text-dim)">' + S.totalItems + ' itens</span>';

        $bc.html(html);
    }

    /* ═══════════════════════════════════════════════════════════
       RENDERING — GRID VIEW
       ═══════════════════════════════════════════════════════════ */
    function renderGrid() {
        const $files = $('.fm-files');
        if (!$files.length) return;

        // Merge folders-as-cards with docs
        const items = buildItemList();

        if (items.length === 0) {
            $files.html(renderEmpty());
            return;
        }

        let html = '<div class="fm-grid">';

        items.forEach(item => {
            const ic = fileIcon(item);
            const isSelected = S.selected.includes(item.id);
            html += '<div class="fm-card' + (isSelected ? ' selected' : '') + '" data-id="' + item.id + '" data-type="' + (item._is_folder ? 'folder' : 'doc') + '">';

            // Card actions
            html += '<div class="fm-card-actions"><button class="fm-card-more" data-id="' + item.id + '"><i class="ri-more-fill"></i></button></div>';

            // Preview/icon area
            html += '<div class="fm-card-preview"><span class="' + ic.cls + '"><i class="' + ic.icon + '"></i></span></div>';

            // Name
            html += '<div class="fm-card-name">' + escHtml(item.title || item.name) + '</div>';

            // Meta
            if (!item._is_folder) {
                html += '<div class="fm-card-meta">' + timeAgo(item.updated_at) + '</div>';
            }

            html += '</div>';
        });

        html += '</div>';
        $files.html(html);
    }

    /* ═══════════════════════════════════════════════════════════
       RENDERING — TABLE VIEW
       ═══════════════════════════════════════════════════════════ */
    function renderTable() {
        const $files = $('.fm-files');
        if (!$files.length) return;

        const items = buildItemList();

        if (items.length === 0) {
            $files.html(renderEmpty());
            return;
        }

        let html = '<table class="fm-table"><thead><tr>';
        html += '<th class="sortable" data-sort="title">Nome <span class="sort-icon"><i class="ri-arrow-up-down-line"></i></span></th>';
        html += '<th>Status</th>';
        html += '<th class="sortable" data-sort="version">Versão</th>';
        html += '<th>Acesso</th>';
        html += '<th class="sortable" data-sort="updated_at">Modificado <span class="sort-icon"><i class="ri-arrow-up-down-line"></i></span></th>';
        html += '<th>Autor</th>';
        html += '</tr></thead><tbody>';

        items.forEach(item => {
            const ic = fileIcon(item);
            const isSelected = S.selected.includes(item.id);
            html += '<tr class="' + (isSelected ? 'selected' : '') + '" data-id="' + item.id + '" data-type="' + (item._is_folder ? 'folder' : 'doc') + '">';

            // Name cell
            html += '<td><div class="fm-cell-name"><i class="' + ic.icon + '" style="color:var(--docs-' + ic.cls.replace('type-', 'type-') + ')"></i>';
            html += '<span>' + escHtml(item.title || item.name) + '</span></div></td>';

            if (item._is_folder) {
                html += '<td>—</td><td>—</td><td>—</td><td>—</td><td>—</td>';
            } else {
                // Status
                html += '<td><span class="fm-cell-status ' + (item.status || '') + '">' + statusLabel(item.status) + '</span></td>';
                // Version
                html += '<td class="fm-cell-size">v' + escHtml(item.version || '1.0') + '</td>';
                // Access
                html += '<td class="fm-cell-type">' + escHtml(item.access || 'private') + '</td>';
                // Modified
                html += '<td class="fm-cell-date">' + formatDate(item.updated_at) + '</td>';
                // Author
                html += '<td class="fm-cell-date">' + escHtml(item.author_name || '') + '</td>';
            }

            html += '</tr>';
        });

        html += '</tbody></table>';
        $files.html(html);
    }

    /* ═══════════════════════════════════════════════════════════
       RENDERING — PREVIEW PANE
       ═══════════════════════════════════════════════════════════ */
    function renderPreview() {
        const $p = $('.fm-preview');
        if (!$p.length) return;

        if (!S.previewOpen || !S.previewDoc) {
            $p.removeClass('open');
            return;
        }

        $p.addClass('open');
        const doc = S.previewDoc;
        const ic = fileIcon(doc);

        let html = '<div class="fm-preview-header"><h3>' + escHtml(doc.title) + '</h3>';
        html += '<button class="fm-preview-close"><i class="ri-close-line"></i></button></div>';

        // Thumb
        html += '<div class="fm-preview-thumb"><i class="' + ic.icon + '" style="color:var(--docs-' + ic.cls.replace('type-', 'type-') + ')"></i></div>';

        // Info rows
        html += '<div class="fm-preview-info">';
        const rows = [
            ['Tipo', doc.type ? doc.type.name : fileIcon(doc).cls.replace('type-', '').toUpperCase()],
            ['Versão', 'v' + (doc.version || '1.0')],
            ['Status', statusLabel(doc.status)],
            ['Acesso', doc.access || 'private'],
            ['Downloads', String(doc.downloads || 0)],
            ['Autor', doc.author_name || '—'],
            ['Criado', formatDate(doc.created_at)],
            ['Modificado', formatDate(doc.updated_at)],
        ];
        if (doc.checksum) {
            rows.push(['Checksum', doc.checksum.substring(0, 12) + '…']);
        }

        rows.forEach(r => {
            html += '<div class="fm-info-row"><span class="fm-info-label">' + r[0] + '</span><span class="fm-info-value">' + escHtml(r[1]) + '</span></div>';
        });
        html += '</div>';

        // Actions
        html += '<div class="fm-preview-actions">';
        if (doc.status === 'draft') {
            html += '<button class="fm-btn fm-btn-primary fm-action-edit" data-id="' + doc.id + '"><i class="ri-edit-line"></i> Editar</button>';
        }
        html += '<button class="fm-btn fm-action-download" data-id="' + doc.id + '"><i class="ri-download-line"></i></button>';
        html += '<button class="fm-btn fm-action-versions" data-id="' + doc.id + '"><i class="ri-history-line"></i></button>';
        if (doc.status === 'draft') {
            html += '<button class="fm-btn fm-action-lock" data-id="' + doc.id + '" title="Bloquear"><i class="ri-lock-line"></i></button>';
        }
        if (doc.status === 'finalized' && doc.author_id === parseInt(ApolloDocs.userId, 10)) {
            html += '<button class="fm-btn fm-btn-sign fm-action-sign" data-id="' + doc.id + '" title="Solicitar Assinatura"><i class="ri-pen-nib-line"></i> Assinar</button>';
        }
        html += '</div>';

        $p.html(html);
    }

    /* ═══════════════════════════════════════════════════════════
       RENDERING — HELPERS
       ═══════════════════════════════════════════════════════════ */
    function buildItemList() {
        // Convert child folders of current folder into card items
        const subfolders = S.folders
            .filter(f => f.parent === S.currentFolder || (S.currentFolder === 0 && !f.parent))
            .map(f => ({ ...f, _is_folder: true, title: f.name }));

        // Combine: folders first, then docs
        return [...subfolders, ...S.documents.map(d => ({ ...d, _is_folder: false }))];
    }

    function renderEmpty() {
        return '<div class="fm-empty">' +
            '<i class="ri-folder-open-line"></i>' +
            '<h4>Nenhum arquivo encontrado</h4>' +
            '<p>Crie um novo documento ou arraste arquivos para fazer upload.</p>' +
            '</div>';
    }

    function showSkeleton() {
        const $files = $('.fm-files');
        if (!$files.length) return;
        let html = '';
        if (S.viewMode === 'grid') {
            html = '<div class="fm-grid">';
            for (let i = 0; i < 8; i++) html += '<div class="fm-skeleton fm-skeleton-card"></div>';
            html += '</div>';
        } else {
            for (let i = 0; i < 6; i++) html += '<div class="fm-skeleton fm-skeleton-row"></div>';
        }
        $files.html(html);
    }

    /* ═══════════════════════════════════════════════════════════
       MAIN RENDER
       ═══════════════════════════════════════════════════════════ */
    function render() {
        renderBreadcrumb();
        if (S.viewMode === 'grid') {
            renderGrid();
        } else {
            renderTable();
        }
        renderPreview();
        updateSelectionUI();
    }

    function updateSelectionUI() {
        const $count = $('.fm-select-count');
        if (S.selected.length > 0) {
            if (!$count.length) {
                $('.fm-toolbar-actions').prepend('<span class="fm-select-count">' + S.selected.length + ' selecionados</span>');
            } else {
                $count.text(S.selected.length + ' selecionados');
            }
        } else {
            $count.remove();
        }
    }

    /* ═══════════════════════════════════════════════════════════
       CONTEXT MENU
       ═══════════════════════════════════════════════════════════ */
    function showContextMenu(e, itemId, itemType) {
        e.preventDefault();
        e.stopPropagation();

        const $menu = $('.fm-context-menu');
        let html = '';

        if (itemType === 'folder') {
            html += ctxItem('ri-folder-open-line', 'Abrir', 'open');
            html += '<div class="fm-context-sep"></div>';
            html += ctxItem('ri-edit-line', 'Renomear', 'rename');
            if (ApolloDocs.is_admin) {
                html += ctxItem('ri-delete-bin-line', 'Excluir', 'delete', true);
            }
        } else {
            html += ctxItem('ri-eye-line', 'Visualizar', 'preview', false, 'Espaço');
            html += ctxItem('ri-edit-line', 'Editar', 'edit');
            html += '<div class="fm-context-sep"></div>';
            html += ctxItem('ri-download-line', 'Download', 'download', false, 'Ctrl+D');
            html += ctxItem('ri-file-copy-line', 'Copiar', 'copy', false, 'Ctrl+C');
            html += ctxItem('ri-scissors-line', 'Recortar', 'cut', false, 'Ctrl+X');
            html += '<div class="fm-context-sep"></div>';
            html += ctxItem('ri-lock-line', 'Bloquear', 'lock');
            html += ctxItem('ri-shield-check-line', 'Finalizar', 'finalize');
            html += ctxItem('ri-history-line', 'Versões', 'versions');
            html += '<div class="fm-context-sep"></div>';
            html += ctxItem('ri-delete-bin-line', 'Excluir', 'delete', true, 'Del');
        }

        $menu.html(html).attr('data-target-id', itemId).attr('data-target-type', itemType);

        // Position
        let x = e.pageX, y = e.pageY;
        const mw = $menu.outerWidth() || 200;
        const mh = $menu.outerHeight() || 300;
        if (x + mw > $(window).width()) x = $(window).width() - mw - 8;
        if (y + mh > $(window).height()) y = $(window).height() - mh - 8;
        $menu.css({ left: x, top: y }).addClass('visible');
    }

    function ctxItem(icon, label, action, danger, hotkey) {
        let h = '<div class="fm-context-item' + (danger ? ' danger' : '') + '" data-action="' + action + '">';
        h += '<i class="' + icon + '"></i>' + label;
        if (hotkey) h += '<span class="hotkey">' + hotkey + '</span>';
        h += '</div>';
        return h;
    }

    function hideContextMenu() {
        $('.fm-context-menu').removeClass('visible');
    }

    /* ═══════════════════════════════════════════════════════════
       MODALS
       ═══════════════════════════════════════════════════════════ */
    function showModal(title, bodyHtml, onConfirm, confirmLabel, isDanger) {
        const $overlay = $('.fm-modal-overlay');
        let html = '<div class="fm-modal">';
        html += '<div class="fm-modal-header"><h3>' + escHtml(title) + '</h3><button class="fm-modal-close"><i class="ri-close-line"></i></button></div>';
        html += '<div class="fm-modal-body">' + bodyHtml + '</div>';
        html += '<div class="fm-modal-footer">';
        html += '<button class="fm-btn fm-modal-cancel">Cancelar</button>';
        html += '<button class="fm-btn ' + (isDanger ? 'fm-btn-danger' : 'fm-btn-primary') + ' fm-modal-confirm">' + (confirmLabel || 'Confirmar') + '</button>';
        html += '</div></div>';

        $overlay.html(html).addClass('visible');
        $overlay.find('input:first').focus();

        $overlay.find('.fm-modal-confirm').off('click').on('click', function () {
            if (onConfirm) onConfirm();
            closeModal();
        });
        $overlay.find('.fm-modal-cancel, .fm-modal-close').off('click').on('click', closeModal);
    }

    function closeModal() {
        $('.fm-modal-overlay').removeClass('visible').empty();
    }

    /* ═══════════════════════════════════════════════════════════
       TOAST NOTIFICATIONS
       ═══════════════════════════════════════════════════════════ */
    function toast(msg, type) {
        type = type || 'success';
        const icon = type === 'success' ? 'ri-check-line' : 'ri-error-warning-line';
        const $toast = $('<div class="fm-toast ' + type + '"><i class="' + icon + '"></i><span>' + escHtml(msg) + '</span></div>');
        $('body').append($toast);
        setTimeout(() => $toast.addClass('visible'), 10);
        setTimeout(() => {
            $toast.removeClass('visible');
            setTimeout(() => $toast.remove(), 400);
        }, 3500);
    }

    /* ═══════════════════════════════════════════════════════════
       ACTIONS
       ═══════════════════════════════════════════════════════════ */

    // Navigate into folder
    function navigateToFolder(folderId) {
        S.currentFolder = folderId;
        S.page = 1;
        S.selected = [];
        S.previewDoc = null;
        S.previewOpen = false;

        // Rebuild breadcrumb
        if (folderId === 0) {
            S.breadcrumb = [{ id: 0, name: 'Todos os Arquivos' }];
        } else {
            const path = buildFolderPath(folderId);
            S.breadcrumb = [{ id: 0, name: 'Todos os Arquivos' }, ...path];
        }

        renderTree();
        loadDocuments();
    }

    function buildFolderPath(folderId) {
        const path = [];
        let current = S.folders.find(f => f.id === folderId);
        while (current) {
            path.unshift({ id: current.id, name: current.name });
            current = current.parent ? S.folders.find(f => f.id === current.parent) : null;
        }
        return path;
    }

    // Create Document
    function createDocumentAction() {
        const body = '<label>Título do Documento</label>' +
            '<input type="text" class="fm-modal-input" id="fm-new-title" placeholder="Título do documento...">' +
            '<label>Acesso</label>' +
            '<select class="fm-modal-input" id="fm-new-access">' +
            '<option value="private">Privado</option>' +
            '<option value="public">Público</option>' +
            '<option value="group">Grupo</option>' +
            '<option value="industry">Indústria</option>' +
            '</select>';

        showModal('Novo Documento', body, function () {
            const title = $('#fm-new-title').val().trim();
            if (!title) { toast('Título obrigatório', 'error'); return; }

            ajax('create_document', {
                title: title,
                access: $('#fm-new-access').val(),
                folder_id: S.currentFolder || '',
                content: '',
            }).done(function (r) {
                if (r.success) {
                    toast('Documento criado');
                    loadDocuments();
                    loadFolders();
                } else {
                    toast(r.data?.message || 'Erro ao criar', 'error');
                }
            });
        }, 'Criar');
    }

    // Create Folder
    function createFolderAction() {
        const body = '<label>Nome da Pasta</label>' +
            '<input type="text" class="fm-modal-input" id="fm-folder-name" placeholder="Nome da pasta...">';

        showModal('Nova Pasta', body, function () {
            const name = $('#fm-folder-name').val().trim();
            if (!name) { toast('Nome obrigatório', 'error'); return; }

            ajax('create_folder', {
                name: name,
                parent: S.currentFolder || 0,
            }).done(function (r) {
                if (r.success) {
                    toast('Pasta criada');
                    loadFolders();
                    loadDocuments();
                } else {
                    toast(r.data?.message || 'Erro', 'error');
                }
            });
        }, 'Criar');
    }

    // Delete Document
    function deleteDocumentAction(docId) {
        const doc = S.documents.find(d => d.id === docId);
        const body = '<div class="fm-modal-confirm-text">Deseja realmente excluir este documento? Esta ação não pode ser desfeita.</div>' +
            '<ul class="fm-modal-confirm-list"><li><i class="ri-file-text-fill"></i>' + escHtml(doc ? doc.title : 'Documento #' + docId) + '</li></ul>';

        showModal('Excluir Documento', body, function () {
            ajax('delete_document', { doc_id: docId }).done(function (r) {
                if (r.success) {
                    toast('Documento excluído');
                    loadDocuments();
                    loadFolders();
                } else {
                    toast(r.data?.message || 'Erro ao excluir', 'error');
                }
            });
        }, 'Excluir', true);
    }

    // Delete Folder
    function deleteFolderAction(folderId) {
        const folder = S.folders.find(f => f.id === folderId);
        const body = '<div class="fm-modal-confirm-text">Excluir esta pasta? Os documentos dentro dela serão movidos para a raiz.</div>' +
            '<ul class="fm-modal-confirm-list"><li><i class="ri-folder-3-fill" style="color:var(--docs-type-folder)"></i>' + escHtml(folder ? folder.name : 'Pasta') + '</li></ul>';

        showModal('Excluir Pasta', body, function () {
            ajax('delete_folder', { folder_id: folderId }).done(function (r) {
                if (r.success) {
                    toast('Pasta excluída');
                    if (S.currentFolder === folderId) navigateToFolder(0);
                    else { loadFolders(); loadDocuments(); }
                } else {
                    toast(r.data?.message || 'Erro', 'error');
                }
            });
        }, 'Excluir', true);
    }

    // Lock Document
    function lockDocumentAction(docId) {
        ajax('lock_document', { doc_id: docId }).done(function (r) {
            if (r.success) {
                toast('Documento bloqueado');
                loadDocuments();
                if (S.previewDoc && S.previewDoc.id === docId) {
                    S.previewDoc.status = 'locked';
                    renderPreview();
                }
            } else {
                toast(r.data?.message || 'Erro ao bloquear', 'error');
            }
        });
    }

    // Finalize Document
    function finalizeDocumentAction(docId) {
        ajax('finalize_document', { doc_id: docId }).done(function (r) {
            if (r.success) {
                toast('Documento finalizado');
                loadDocuments();
            } else {
                toast(r.data?.message || 'Erro ao finalizar', 'error');
            }
        });
    }

    // Show Versions
    function showVersionsAction(docId) {
        ajax('load_versions', { doc_id: docId }).done(function (r) {
            if (!r.success || !r.data.length) {
                toast('Nenhuma versão encontrada', 'error');
                return;
            }
            let body = '<div style="max-height:300px;overflow-y:auto">';
            r.data.forEach(v => {
                body += '<div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--docs-border)">';
                body += '<div><strong>v' + escHtml(v.version) + '</strong>';
                body += '<br><span style="font-size:11px;color:var(--docs-text-dim)">' + escHtml(v.author_name || '') + ' · ' + formatDate(v.created_at) + '</span>';
                if (v.changelog) body += '<br><span style="font-size:11px;color:var(--docs-text-muted)">' + escHtml(v.changelog) + '</span>';
                body += '</div>';
                body += '<span style="font-size:10px;color:var(--docs-text-dim);font-family:monospace">' + (v.checksum || '').substring(0, 8) + '</span>';
                body += '</div>';
            });
            body += '</div>';
            showModal('Histórico de Versões', body, closeModal, 'Fechar');
        });
    }

    // Request Signature — opens modal to add signers and sends POST /signatures/request
    function requestSignatureAction(docId) {
        // Load platform users for the dropdown (may fail if sign plugin absent — handled in fail())
        $.ajax({
            url:     ApolloDocs.restUrl + 'signatures/users',
            method:  'GET',
            headers: { 'X-WP-Nonce': ApolloDocs.restNonce },
        }).done(function (users) {
            let optHtml = '<option value="">\u2014 Selecione um usu\u00e1rio \u2014</option>';
            (users || []).forEach(function (u) {
                optHtml += '<option value="' + u.id + '" data-email="' + escHtml(u.email) + '" data-name="' + escHtml(u.name) + '">' +
                    escHtml(u.name) + ' (' + escHtml(u.email) + ')</option>';
            });

            _openSignModal(docId, optHtml);
        }).fail(function () {
            _openSignModal(docId, ''); // fallback: no user dropdown
        });
    }

    function _openSignModal(docId, usersOptHtml) {
        const signers = [];

        let body = '<div id="fm-sign-queue" style="max-height:120px;overflow-y:auto;margin-bottom:12px">' +
            '<p style="color:var(--docs-text-dim);font-size:12px;margin:0" id="fm-sign-empty">Nenhum signat\u00e1rio adicionado.</p>' +
            '</div>';

        body += '<div style="padding:12px;background:var(--docs-panel-bg,#1a1a1a);border:1px solid var(--docs-border);border-radius:8px;margin-bottom:12px">';
        body += '<label style="font-size:12px;color:var(--docs-text-muted)">Email do signat\u00e1rio</label>';
        body += '<input type="email" class="fm-modal-input" id="fm-sign-email" placeholder="email@exemplo.com" style="margin-bottom:8px">';
        body += '<label style="font-size:12px;color:var(--docs-text-muted)">Nome (opcional)</label>';
        body += '<input type="text" class="fm-modal-input" id="fm-sign-name" placeholder="Nome..." style="margin-bottom:8px">';

        if (usersOptHtml) {
            body += '<label style="font-size:12px;color:var(--docs-text-muted)">Ou selecione usu\u00e1rio da plataforma</label>';
            body += '<select class="fm-modal-input" id="fm-sign-user" style="margin-bottom:8px">' + usersOptHtml + '</select>';
        }

        body += '<button type="button" class="fm-btn" id="fm-sign-add"><i class="ri-add-line"></i> Adicionar</button>';
        body += '</div>';

        function renderQueue() {
            if (!signers.length) {
                $('#fm-sign-queue').html('<p style="color:var(--docs-text-dim);font-size:12px;margin:0" id="fm-sign-empty">Nenhum signat\u00e1rio adicionado.</p>');
                return;
            }
            let h = '';
            signers.forEach(function (s, i) {
                h += '<div style="display:flex;align-items:center;gap:8px;padding:6px 0;border-bottom:1px solid var(--docs-border)">';
                h += '<span style="flex:1;font-size:13px">' + (i + 1) + '. <strong>' + escHtml(s.name || s.email) + '</strong>';
                if (s.name && s.email !== s.name) h += ' <span style="color:var(--docs-text-dim);font-size:11px">' + escHtml(s.email) + '</span>';
                h += '</span>';
                h += '<button type="button" class="fm-btn fm-sign-remove" data-idx="' + i + '" style="padding:2px 8px;font-size:11px"><i class="ri-delete-bin-line"></i></button>';
                h += '</div>';
            });
            $('#fm-sign-queue').html(h);
        }

        showModal('Solicitar Assinatura', body, function () {
            if (!signers.length) {
                toast('Adicione pelo menos um signat\u00e1rio', 'error');
                return;
            }
            $.ajax({
                url:         ApolloDocs.restUrl + 'signatures/request',
                method:      'POST',
                contentType: 'application/json',
                headers:     { 'X-WP-Nonce': ApolloDocs.restNonce },
                data:        JSON.stringify({ doc_id: docId, signers: signers }),
            }).done(function (r) {
                if (r && r.success) {
                    toast('Convite enviado para ' + (signers[0] ? signers[0].email : 'signat\u00e1rio'));
                    loadDocuments();
                } else {
                    toast((r && r.message) || 'Erro ao enviar convite', 'error');
                }
            }).fail(function (xhr) {
                const msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Erro ao enviar convite';
                toast(msg, 'error');
            });
        }, 'Enviar para Assinatura');

        // Populate email/name from user dropdown selection
        $(document).off('change.sign').on('change.sign', '#fm-sign-user', function () {
            const $opt = $(this).find(':selected');
            if ($(this).val()) {
                $('#fm-sign-email').val($opt.data('email'));
                $('#fm-sign-name').val($opt.data('name'));
            }
        });

        // Add to queue
        $(document).off('click.sign-add').on('click.sign-add', '#fm-sign-add', function () {
            const email = $.trim($('#fm-sign-email').val());
            const name  = $.trim($('#fm-sign-name').val());
            const uid   = parseInt($('#fm-sign-user').val(), 10) || 0;
            if (!email) { toast('Informe o email do signat\u00e1rio', 'error'); return; }
            // Prevent duplicates
            if (signers.some(function (s) { return s.email === email; })) {
                toast('Este email j\u00e1 foi adicionado', 'error'); return;
            }
            signers.push({ email: email, name: name || email, user_id: uid });
            $('#fm-sign-email').val('');
            $('#fm-sign-name').val('');
            if ($('#fm-sign-user').length) $('#fm-sign-user').val('');
            renderQueue();
        });

        // Remove from queue
        $(document).off('click.sign-remove').on('click.sign-remove', '.fm-sign-remove', function () {
            signers.splice(parseInt($(this).data('idx'), 10), 1);
            renderQueue();
        });

        renderQueue();
    }

    // Rename Document
    function renameDocumentAction(docId) {
        const doc = S.documents.find(d => d.id === docId);
        const body = '<label>Novo nome</label>' +
            '<input type="text" class="fm-modal-input" id="fm-rename-val" value="' + escHtml(doc ? doc.title : '') + '">';

        showModal('Renomear', body, function () {
            const title = $('#fm-rename-val').val().trim();
            if (!title) { toast('Nome obrigatório', 'error'); return; }

            ajax('update_document', { doc_id: docId, title: title }).done(function (r) {
                if (r.success) {
                    toast('Renomeado');
                    loadDocuments();
                } else {
                    toast(r.data?.message || 'Erro', 'error');
                }
            });
        }, 'Salvar');
    }

    /* ═══════════════════════════════════════════════════════════
       FILE UPLOAD (Drag & Drop + Button)
       ═══════════════════════════════════════════════════════════ */
    function handleUpload(files) {
        if (!files || !files.length) return;

        Array.from(files).forEach(file => {
            const formData = new FormData();
            formData.append('action', 'apollo_docs_create_document');
            formData.append('nonce', ApolloDocs.nonce);
            formData.append('title', file.name);
            formData.append('folder_id', S.currentFolder || '');
            formData.append('access', 'private');
            formData.append('content', '');

            const uploadId = Date.now() + '_' + Math.random().toString(36).substr(2, 5);
            S.uploading.push({ id: uploadId, name: file.name, progress: 0 });
            renderUploadProgress();

            $.ajax({
                url: ApolloDocs.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                xhr: function () {
                    const xhr = new XMLHttpRequest();
                    xhr.upload.addEventListener('progress', function (e) {
                        if (e.lengthComputable) {
                            const pct = Math.round((e.loaded / e.total) * 100);
                            const item = S.uploading.find(u => u.id === uploadId);
                            if (item) item.progress = pct;
                            renderUploadProgress();
                        }
                    });
                    return xhr;
                },
                success: function (r) {
                    const item = S.uploading.find(u => u.id === uploadId);
                    if (item) { item.progress = 100; item.done = true; }
                    renderUploadProgress();
                    setTimeout(() => {
                        S.uploading = S.uploading.filter(u => u.id !== uploadId);
                        renderUploadProgress();
                    }, 2000);
                    if (r.success) toast(file.name + ' enviado');
                    loadDocuments();
                    loadFolders();
                },
                error: function () {
                    S.uploading = S.uploading.filter(u => u.id !== uploadId);
                    renderUploadProgress();
                    toast('Erro ao enviar ' + file.name, 'error');
                },
            });
        });
    }

    function renderUploadProgress() {
        let $area = $('.fm-upload-list');
        if (!$area.length) {
            $('body').append('<div class="fm-upload-list"></div>');
            $area = $('.fm-upload-list');
        }

        if (!S.uploading.length) { $area.empty(); return; }

        let html = '';
        S.uploading.forEach(u => {
            html += '<div class="fm-upload-item">';
            html += '<i class="ri-file-upload-line" style="color:var(--docs-primary)"></i>';
            html += '<span class="fm-upload-name">' + escHtml(u.name) + '</span>';
            html += '<div class="fm-upload-progress"><div class="fm-upload-bar" style="width:' + u.progress + '%"></div></div>';
            html += '<span class="fm-upload-status' + (u.done ? ' done' : '') + '">' + (u.done ? '✓' : u.progress + '%') + '</span>';
            html += '</div>';
        });
        $area.html(html);
    }

    /* ═══════════════════════════════════════════════════════════
       EVENT BINDINGS
       ═══════════════════════════════════════════════════════════ */
    function bindEvents() {
        const $wrap = $('.apollo-fm');

        // ── View mode toggle ──
        $wrap.on('click', '.fm-view-toggle .fm-btn-icon', function () {
            const mode = $(this).data('mode');
            if (mode === S.viewMode) return;
            S.viewMode = mode;
            $('.fm-view-toggle .fm-btn-icon').removeClass('active');
            $(this).addClass('active');
            render();
        });

        // ── Search ──
        let searchTimer;
        $wrap.on('input', '.fm-toolbar-search input', function () {
            const val = $(this).val();
            const $container = $(this).closest('.fm-toolbar-search');
            $container.toggleClass('has-value', val.length > 0);
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => {
                S.searchQuery = val;
                S.page = 1;
                loadDocuments();
            }, 350);
        });

        $wrap.on('click', '.fm-search-clear', function () {
            $(this).closest('.fm-toolbar-search').find('input').val('').trigger('input');
        });

        // ── Tree navigation ──
        $wrap.on('click', '.fm-tree-item[data-folder]', function () {
            navigateToFolder(parseInt($(this).data('folder'), 10));
        });

        // Type filter tree items
        $wrap.on('click', '.fm-tree-item[data-type-filter]', function () {
            // TODO: implement type filtering
            toast('Filtro por tipo em breve', 'success');
        });

        // ── Tree chevron toggle ──
        $wrap.on('click', '.fm-tree-chevron', function (e) {
            e.stopPropagation();
            $(this).toggleClass('open');
            $(this).closest('.fm-tree-item').next('.fm-tree-children').slideToggle(200);
        });

        // ── Breadcrumb ──
        $wrap.on('click', '.fm-breadcrumb-item:not(.current)', function () {
            navigateToFolder(parseInt($(this).data('folder'), 10));
        });

        // ── Refresh ──
        $wrap.on('click', '.fm-breadcrumb-refresh', function () {
            $(this).addClass('spinning');
            loadDocuments();
            loadFolders();
            setTimeout(() => $(this).removeClass('spinning'), 800);
        });

        // ── Card / Row click ─ select ──
        $wrap.on('click', '.fm-card, .fm-table tbody tr', function (e) {
            if ($(e.target).closest('.fm-card-more').length) return;

            const id   = parseInt($(this).data('id'), 10);
            const type = $(this).data('type');

            if (e.ctrlKey || e.metaKey) {
                // Multi-select
                const idx = S.selected.indexOf(id);
                if (idx > -1) S.selected.splice(idx, 1);
                else S.selected.push(id);
                render();
                return;
            }

            if (type === 'folder') {
                navigateToFolder(id);
            } else {
                // Preview
                S.selected = [id];
                S.previewDoc = S.documents.find(d => d.id === id) || null;
                S.previewOpen = true;
                render();
            }
        });

        // ── Double-click card — edit ──
        $wrap.on('dblclick', '.fm-card[data-type="doc"], .fm-table tbody tr[data-type="doc"]', function () {
            const id = parseInt($(this).data('id'), 10);
            renameDocumentAction(id);
        });

        // ── Card more button → context menu ──
        $wrap.on('click', '.fm-card-more', function (e) {
            const $card = $(this).closest('.fm-card');
            showContextMenu(e, parseInt($card.data('id'), 10), $card.data('type'));
        });

        // ── Right-click context ──
        $wrap.on('contextmenu', '.fm-card, .fm-table tbody tr', function (e) {
            showContextMenu(e, parseInt($(this).data('id'), 10), $(this).data('type'));
        });

        // ── Background right-click ──
        $wrap.on('contextmenu', '.fm-files', function (e) {
            if (!$(e.target).closest('.fm-card, .fm-table tbody tr').length) {
                e.preventDefault();
                const $menu = $('.fm-context-menu');
                let html = ctxItem('ri-file-add-line', 'Novo Documento', 'new-doc');
                html += ctxItem('ri-folder-add-line', 'Nova Pasta', 'new-folder');
                html += '<div class="fm-context-sep"></div>';
                html += ctxItem('ri-upload-line', 'Upload', 'upload');
                if (S.clipboard) {
                    html += '<div class="fm-context-sep"></div>';
                    html += ctxItem('ri-clipboard-line', 'Colar', 'paste', false, 'Ctrl+V');
                }
                html += '<div class="fm-context-sep"></div>';
                html += ctxItem('ri-refresh-line', 'Atualizar', 'refresh', false, 'F5');
                $menu.html(html).attr('data-target-id', '').attr('data-target-type', 'bg');

                let x = e.pageX, y = e.pageY;
                if (x + 200 > $(window).width()) x = $(window).width() - 208;
                if (y + 250 > $(window).height()) y = $(window).height() - 258;
                $menu.css({ left: x, top: y }).addClass('visible');
            }
        });

        // ── Context menu actions ──
        $(document).on('click', '.fm-context-item', function () {
            const action = $(this).data('action');
            const targetId = parseInt($('.fm-context-menu').attr('data-target-id'), 10);
            const targetType = $('.fm-context-menu').attr('data-target-type');
            hideContextMenu();

            switch (action) {
                case 'open':     navigateToFolder(targetId); break;
                case 'preview':
                    S.previewDoc = S.documents.find(d => d.id === targetId) || null;
                    S.previewOpen = true;
                    render();
                    break;
                case 'edit':     renameDocumentAction(targetId); break;
                case 'rename':
                    if (targetType === 'folder') {
                        // Simple rename for folder — reuse as doc rename for now
                        toast('Renomear pasta em breve', 'success');
                    } else {
                        renameDocumentAction(targetId);
                    }
                    break;
                case 'download': window.open(ApolloDocs.rest_url + '/' + targetId + '/download', '_blank'); break;
                case 'copy':     S.clipboard = { action: 'copy', ids: [targetId] }; toast('Copiado para área de transferência'); break;
                case 'cut':      S.clipboard = { action: 'cut', ids: [targetId] }; toast('Recortado'); break;
                case 'paste':    if (S.clipboard) toast('Colar: em breve', 'success'); break;
                case 'lock':     lockDocumentAction(targetId); break;
                case 'finalize': finalizeDocumentAction(targetId); break;
                case 'versions': showVersionsAction(targetId); break;
                case 'delete':
                    if (targetType === 'folder') deleteFolderAction(targetId);
                    else deleteDocumentAction(targetId);
                    break;
                case 'new-doc':    createDocumentAction(); break;
                case 'new-folder': createFolderAction(); break;
                case 'upload':     $('#fm-file-input').trigger('click'); break;
                case 'refresh':    loadDocuments(); loadFolders(); break;
            }
        });

        // ── Hide context menu on click elsewhere ──
        $(document).on('click', function (e) {
            if (!$(e.target).closest('.fm-context-menu').length) hideContextMenu();
        });

        // ── Preview close ──
        $wrap.on('click', '.fm-preview-close', function () {
            S.previewOpen = false;
            S.previewDoc = null;
            render();
        });

        // ── Preview actions ──
        $wrap.on('click', '.fm-action-edit', function () { renameDocumentAction(parseInt($(this).data('id'), 10)); });
        $wrap.on('click', '.fm-action-download', function () { window.open(ApolloDocs.rest_url + '/' + $(this).data('id') + '/download', '_blank'); });
        $wrap.on('click', '.fm-action-versions', function () { showVersionsAction(parseInt($(this).data('id'), 10)); });
        $wrap.on('click', '.fm-action-lock', function () { lockDocumentAction(parseInt($(this).data('id'), 10)); });
        $wrap.on('click', '.fm-action-sign', function () { requestSignatureAction(parseInt($(this).data('id'), 10)); });

        // ── Toolbar buttons ──
        $wrap.on('click', '.fm-btn-new-doc', createDocumentAction);
        $wrap.on('click', '.fm-btn-new-folder', createFolderAction);
        $wrap.on('click', '.fm-btn-upload', function () { $('#fm-file-input').trigger('click'); });

        // ── File input for upload ──
        $wrap.on('change', '#fm-file-input', function () {
            handleUpload(this.files);
            $(this).val('');
        });

        // ── Preview toggle button ──
        $wrap.on('click', '.fm-btn-preview-toggle', function () {
            S.previewOpen = !S.previewOpen;
            $(this).toggleClass('active', S.previewOpen);
            render();
        });

        // ── Sidebar toggle (mobile) ──
        $wrap.on('click', '.fm-btn-sidebar-toggle', function () {
            $('.fm-sidebar').toggleClass('open');
        });

        // ── Table sort ──
        $wrap.on('click', '.fm-table thead th.sortable', function () {
            const col = $(this).data('sort');
            if (S.sortBy === col) {
                S.sortDir = S.sortDir === 'ASC' ? 'DESC' : 'ASC';
            } else {
                S.sortBy = col;
                S.sortDir = 'ASC';
            }
            // Client-side sort
            S.documents.sort((a, b) => {
                let va = a[S.sortBy] || '', vb = b[S.sortBy] || '';
                if (typeof va === 'string') va = va.toLowerCase();
                if (typeof vb === 'string') vb = vb.toLowerCase();
                if (va < vb) return S.sortDir === 'ASC' ? -1 : 1;
                if (va > vb) return S.sortDir === 'ASC' ? 1 : -1;
                return 0;
            });
            render();
        });

        // ── Drag & Drop ──
        let dragCounter = 0;
        $wrap.on('dragenter', function (e) {
            e.preventDefault();
            dragCounter++;
            $('.fm-dropzone').addClass('active');
        });
        $wrap.on('dragleave', function (e) {
            e.preventDefault();
            dragCounter--;
            if (dragCounter <= 0) {
                dragCounter = 0;
                $('.fm-dropzone').removeClass('active');
            }
        });
        $wrap.on('dragover', function (e) { e.preventDefault(); });
        $wrap.on('drop', function (e) {
            e.preventDefault();
            dragCounter = 0;
            $('.fm-dropzone').removeClass('active');
            const files = e.originalEvent.dataTransfer.files;
            if (files.length) handleUpload(files);
        });

        // ── Keyboard shortcuts ──
        $(document).on('keydown', function (e) {
            if ($('.fm-modal-overlay.visible').length) {
                if (e.key === 'Escape') closeModal();
                return;
            }

            if ($(e.target).is('input, textarea, select')) return;

            switch (e.key) {
                case 'Delete':
                    if (S.selected.length === 1) deleteDocumentAction(S.selected[0]);
                    break;
                case 'F5':
                    e.preventDefault();
                    loadDocuments();
                    loadFolders();
                    break;
                case 'Escape':
                    hideContextMenu();
                    if (S.previewOpen) { S.previewOpen = false; S.previewDoc = null; render(); }
                    break;
                case ' ':
                    if (S.selected.length === 1) {
                        e.preventDefault();
                        S.previewDoc = S.documents.find(d => d.id === S.selected[0]) || null;
                        S.previewOpen = !S.previewOpen;
                        render();
                    }
                    break;
                case 'a':
                    if (e.ctrlKey || e.metaKey) {
                        e.preventDefault();
                        S.selected = S.documents.map(d => d.id);
                        render();
                    }
                    break;
            }
        });

        // ── Sidebar add buttons ──
        $wrap.on('click', '.fm-sidebar-btn-doc', createDocumentAction);
        $wrap.on('click', '.fm-sidebar-btn-folder', createFolderAction);
    }

    /* ═══════════════════════════════════════════════════════════
       INIT
       ═══════════════════════════════════════════════════════════ */
    $(function () {
        if (!$('.apollo-fm').length) return;
        bindEvents();
        loadFolders();
        loadDocuments();
    });

})(jQuery);
