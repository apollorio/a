/**
 * Apollo Hub — Admin Blocks Manager (hub-admin-blocks.js)
 *
 * Interactive block management for the wp-admin metabox.
 * Handles: add/remove/reorder/toggle/edit blocks.
 *
 * @package Apollo\Hub
 */

(function () {
    'use strict';

    const container = document.getElementById('apollo-hub-blocks-admin');
    if (!container) return;

    let blocks = JSON.parse(container.dataset.blocks || '[]');
    const blockTypes = JSON.parse(container.dataset.blockTypes || '{}');
    const listEl = document.getElementById('ahb-blocks-list');
    const jsonField = document.getElementById('hub_blocks_json');
    const addMenu = document.getElementById('ahb-add-menu');
    const addBtn = document.getElementById('ahb-add-block-trigger');
    const migrateBtn = document.getElementById('ahb-migrate-trigger');

    // ── State sync ──────────────────────────────────────────────────────

    function syncJSON() {
        if (jsonField) jsonField.value = JSON.stringify(blocks);
        updateCounter();
    }

    function updateCounter() {
        const toolbar = container.querySelector('.ahb-toolbar strong');
        if (toolbar) toolbar.textContent = blocks.length;
    }

    function genId() {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
            var r = Math.random() * 16 | 0;
            return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16);
        });
    }

    // ── Render full list ────────────────────────────────────────────────

    function renderList() {
        listEl.innerHTML = '';
        if (blocks.length === 0) {
            listEl.innerHTML = '<div class="ahb-empty"><span class="dashicons dashicons-layout"></span><p>Nenhum bloco adicionado. Clique "Adicionar bloco" para começar.</p></div>';
            syncJSON();
            return;
        }
        blocks.forEach(function (block, idx) {
            listEl.appendChild(createCard(block, idx));
        });
        syncJSON();
        initDragDrop();
    }

    function createCard(block, idx) {
        var type = block.type || 'unknown';
        var bt = blockTypes[type] || { label: type, icon: 'dashicons-editor-code' };
        var active = block.active !== false;
        var preview = getPreview(type, block.data || {});

        var card = document.createElement('div');
        card.className = 'ahb-block-card' + (active ? '' : ' ahb-inactive');
        card.dataset.index = idx;
        card.dataset.id = block.id || '';
        card.dataset.type = type;
        card.draggable = true;

        card.innerHTML =
            '<div class="ahb-block-handle" title="Arrastar para reordenar"><span class="dashicons dashicons-move"></span></div>' +
            '<div class="ahb-block-icon"><i class="' + esc(bt.icon) + '"></i></div>' +
            '<div class="ahb-block-info"><span class="ahb-block-type-label">' + esc(bt.label) + '</span><span class="ahb-block-preview">' + esc(preview) + '</span></div>' +
            '<div class="ahb-block-actions">' +
            '<button type="button" class="ahb-toggle-btn" title="' + (active ? 'Desativar' : 'Ativar') + '"><span class="dashicons ' + (active ? 'dashicons-visibility' : 'dashicons-hidden') + '"></span></button>' +
            '<button type="button" class="ahb-edit-btn" title="Editar"><span class="dashicons dashicons-edit"></span></button>' +
            '<button type="button" class="ahb-dup-btn" title="Duplicar"><span class="dashicons dashicons-admin-page"></span></button>' +
            '<button type="button" class="ahb-delete-btn" title="Remover"><span class="dashicons dashicons-trash"></span></button>' +
            '</div>';

        // Toggle
        card.querySelector('.ahb-toggle-btn').addEventListener('click', function () {
            blocks[idx].active = !blocks[idx].active;
            renderList();
        });

        // Delete
        card.querySelector('.ahb-delete-btn').addEventListener('click', function () {
            if (!confirm('Remover este bloco?')) return;
            blocks.splice(idx, 1);
            renderList();
        });

        // Duplicate
        card.querySelector('.ahb-dup-btn').addEventListener('click', function () {
            var dup = JSON.parse(JSON.stringify(blocks[idx]));
            dup.id = genId();
            blocks.splice(idx + 1, 0, dup);
            renderList();
        });

        // Edit
        card.querySelector('.ahb-edit-btn').addEventListener('click', function () {
            openEditor(idx);
        });

        return card;
    }

    // ── Preview text ────────────────────────────────────────────────────

    function getPreview(type, data) {
        switch (type) {
            case 'header': return data.text || '';
            case 'link':
                var t = data.title || '';
                return t ? t + ' → ' + (data.url || '') : (data.url || '');
            case 'social':
                var c = (data.icons || []).length;
                return c + ' rede' + (c !== 1 ? 's' : '');
            case 'youtube': return data.url || '';
            case 'spotify': return (data.spotifyType || 'track') + ': ' + (data.url || '');
            case 'image': return data.alt || data.url || '';
            case 'text': return stripTags(data.content || '').substring(0, 80);
            case 'faq': var n = (data.items || []).length; return n + ' pergunta' + (n !== 1 ? 's' : '');
            case 'countdown': return data.target || '';
            case 'map': return data.embed || '';
            case 'divider': return (data.style || 'line') + ' (' + (data.height || 24) + 'px)';
            case 'embed': return stripTags(data.code || '').substring(0, 60);
            default: return '';
        }
    }

    // ── Add block ───────────────────────────────────────────────────────

    addBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        addMenu.style.display = addMenu.style.display === 'none' ? 'block' : 'none';
    });

    document.addEventListener('click', function (e) {
        if (!addMenu.contains(e.target) && e.target !== addBtn) {
            addMenu.style.display = 'none';
        }
    });

    addMenu.querySelectorAll('.ahb-add-type-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var type = btn.dataset.type;
            blocks.push({
                type: type,
                id: genId(),
                active: true,
                data: getDefaults(type)
            });
            addMenu.style.display = 'none';
            renderList();
            // Open editor for the new block
            openEditor(blocks.length - 1);
        });
    });

    // ── Block defaults ──────────────────────────────────────────────────

    function getDefaults(type) {
        switch (type) {
            case 'header': return { text: '' };
            case 'link': return { title: '', sub: '', url: '', icon: 'ri-link-m', variant: 'default', bgColor: '', textColor: '', iconBg: '', badge: '' };
            case 'social': return { icons: [], size: 'md', alignment: 'center' };
            case 'youtube': return { url: '', title: 'YouTube' };
            case 'spotify': return { url: '', spotifyType: 'track' };
            case 'image': return { url: '', alt: '', link: '', fit: 'cover', radius: '12px', attachment_id: 0 };
            case 'text': return { content: '', align: 'left' };
            case 'faq': return { items: [] };
            case 'countdown': return { target: '', label: '' };
            case 'map': return { embed: '', height: 250 };
            case 'divider': return { style: 'line', height: 24 };
            case 'embed': return { code: '' };
            default: return {};
        }
    }

    // ── Inline editor modal ─────────────────────────────────────────────

    function openEditor(idx) {
        var block = blocks[idx];
        if (!block) return;

        var overlay = document.createElement('div');
        overlay.className = 'ahb-editor-overlay';

        var bt = blockTypes[block.type] || { label: block.type, icon: '' };
        var modal = document.createElement('div');
        modal.className = 'ahb-editor-modal';
        modal.innerHTML =
            '<div class="ahb-editor-header">' +
            '<i class="' + esc(bt.icon) + '"></i> ' +
            '<strong>' + esc(bt.label) + '</strong>' +
            '<button type="button" class="ahb-editor-close">&times;</button>' +
            '</div>' +
            '<div class="ahb-editor-body"></div>' +
            '<div class="ahb-editor-footer">' +
            '<button type="button" class="button button-primary ahb-editor-save">Salvar</button>' +
            '<button type="button" class="button ahb-editor-cancel">Cancelar</button>' +
            '</div>';

        overlay.appendChild(modal);
        document.body.appendChild(overlay);

        var body = modal.querySelector('.ahb-editor-body');
        var fields = buildFields(block.type, JSON.parse(JSON.stringify(block.data || {})));
        body.appendChild(fields.el);

        // Close
        function close() { overlay.remove(); }
        modal.querySelector('.ahb-editor-close').addEventListener('click', close);
        modal.querySelector('.ahb-editor-cancel').addEventListener('click', close);
        overlay.addEventListener('click', function (e) { if (e.target === overlay) close(); });

        // Save
        modal.querySelector('.ahb-editor-save').addEventListener('click', function () {
            blocks[idx].data = fields.getData();
            close();
            renderList();
        });
    }

    // ── Field builders per type ─────────────────────────────────────────

    function buildFields(type, data) {
        var wrap = document.createElement('div');
        wrap.className = 'ahb-fields';
        var getters = [];

        function addField(label, inputHtml, getter) {
            var row = document.createElement('div');
            row.className = 'ahb-field-row';
            row.innerHTML = '<label>' + esc(label) + '</label>' + inputHtml;
            wrap.appendChild(row);
            getters.push(getter);
        }

        function textInput(name, val, placeholder) {
            var id = 'ahb-f-' + name + '-' + Math.random().toString(36).substr(2, 5);
            return { html: '<input type="text" id="' + id + '" value="' + esc(val || '') + '" placeholder="' + esc(placeholder || '') + '" class="widefat">', id: id };
        }

        function textArea(name, val, rows) {
            var id = 'ahb-f-' + name + '-' + Math.random().toString(36).substr(2, 5);
            return { html: '<textarea id="' + id + '" rows="' + (rows || 3) + '" class="widefat">' + esc(val || '') + '</textarea>', id: id };
        }

        function selectInput(name, val, options) {
            var id = 'ahb-f-' + name + '-' + Math.random().toString(36).substr(2, 5);
            var opts = Object.keys(options).map(function (k) {
                return '<option value="' + esc(k) + '"' + (val === k ? ' selected' : '') + '>' + esc(options[k]) + '</option>';
            }).join('');
            return { html: '<select id="' + id + '" class="widefat">' + opts + '</select>', id: id };
        }

        function numInput(name, val, min, max) {
            var id = 'ahb-f-' + name + '-' + Math.random().toString(36).substr(2, 5);
            return { html: '<input type="number" id="' + id + '" value="' + (val || 0) + '" min="' + (min || 0) + '"' + (max ? ' max="' + max + '"' : '') + ' class="small-text">', id: id };
        }

        switch (type) {
            case 'header': {
                var f = textInput('text', data.text, 'Texto do cabeçalho');
                addField('Texto', f.html, function () { return { text: document.getElementById(f.id).value }; });
                break;
            }
            case 'link': {
                var ft = textInput('title', data.title, 'Título do link');
                var fs = textInput('sub', data.sub, 'Subtítulo (opcional)');
                var fu = textInput('url', data.url, 'https://...');
                var fi = textInput('icon', data.icon, 'ri-link-m');
                var fv = selectInput('variant', data.variant || 'default', { 'default': 'Padrão', 'outline': 'Outline', 'glass': 'Glass', 'gradient': 'Gradiente' });
                var fb = textInput('badge', data.badge, 'Badge (opcional)');
                var fbg = textInput('bgColor', data.bgColor, '#hex cor de fundo');
                var ftc = textInput('textColor', data.textColor, '#hex cor do texto');

                addField('Título', ft.html, null);
                addField('Subtítulo', fs.html, null);
                addField('URL', fu.html, null);
                addField('Ícone (RemixIcon)', fi.html, null);
                addField('Variante', fv.html, null);
                addField('Badge', fb.html, null);
                addField('Cor de fundo', fbg.html, null);
                addField('Cor do texto', ftc.html, null);

                getters = [function () {
                    return {
                        title: document.getElementById(ft.id).value,
                        sub: document.getElementById(fs.id).value,
                        url: document.getElementById(fu.id).value,
                        icon: document.getElementById(fi.id).value || 'ri-link-m',
                        variant: document.getElementById(fv.id).value,
                        badge: document.getElementById(fb.id).value,
                        bgColor: document.getElementById(fbg.id).value,
                        textColor: document.getElementById(ftc.id).value,
                        iconBg: data.iconBg || ''
                    };
                }];
                break;
            }
            case 'social': {
                var fsize = selectInput('size', data.size || 'md', { sm: 'Pequeno', md: 'Médio', lg: 'Grande' });
                var falign = selectInput('alignment', data.alignment || 'center', { left: 'Esquerda', center: 'Centro', right: 'Direita' });
                addField('Tamanho', fsize.html, null);
                addField('Alinhamento', falign.html, null);

                // Social icons list (editable JSON)
                var ficons = textArea('icons', JSON.stringify(data.icons || [], null, 2), 6);
                addField('Ícones (JSON)', ficons.html, null);
                wrap.querySelector('label:last-of-type').insertAdjacentHTML('afterend', '<p class="description">Formato: [{"icon":"ri-instagram-line","url":"https://...","label":"Instagram"}]</p>');

                getters = [function () {
                    var parsed = [];
                    try { parsed = JSON.parse(document.getElementById(ficons.id).value); } catch (e) { }
                    return {
                        icons: Array.isArray(parsed) ? parsed : [],
                        size: document.getElementById(fsize.id).value,
                        alignment: document.getElementById(falign.id).value
                    };
                }];
                break;
            }
            case 'youtube': {
                var fy = textInput('url', data.url, 'https://youtube.com/watch?v=...');
                var fyt = textInput('title', data.title, 'Título');
                addField('URL do YouTube', fy.html, null);
                addField('Título', fyt.html, null);
                getters = [function () { return { url: document.getElementById(fy.id).value, title: document.getElementById(fyt.id).value }; }];
                break;
            }
            case 'spotify': {
                var fsp = textInput('url', data.url, 'https://open.spotify.com/...');
                var fspt = selectInput('spotifyType', data.spotifyType || 'track', { track: 'Faixa', album: 'Álbum', playlist: 'Playlist', artist: 'Artista' });
                addField('URL do Spotify', fsp.html, null);
                addField('Tipo', fspt.html, null);
                getters = [function () { return { url: document.getElementById(fsp.id).value, spotifyType: document.getElementById(fspt.id).value }; }];
                break;
            }
            case 'image': {
                var fiu = textInput('url', data.url, 'URL da imagem');
                var fia = textInput('alt', data.alt, 'Texto alternativo');
                var fil = textInput('link', data.link, 'Link ao clicar (opcional)');
                var fif = selectInput('fit', data.fit || 'cover', { cover: 'Cover', contain: 'Contain', fill: 'Fill' });
                var fir = textInput('radius', data.radius, '12px');
                var faid = numInput('attachment_id', data.attachment_id, 0);
                addField('URL da imagem', fiu.html, null);
                addField('Alt text', fia.html, null);
                addField('Link', fil.html, null);
                addField('Ajuste', fif.html, null);
                addField('Border radius', fir.html, null);
                addField('Attachment ID (WP)', faid.html, null);
                getters = [function () {
                    return {
                        url: document.getElementById(fiu.id).value,
                        alt: document.getElementById(fia.id).value,
                        link: document.getElementById(fil.id).value,
                        fit: document.getElementById(fif.id).value,
                        radius: document.getElementById(fir.id).value,
                        attachment_id: parseInt(document.getElementById(faid.id).value) || 0
                    };
                }];
                break;
            }
            case 'text': {
                var ftx = textArea('content', data.content, 5);
                var fta = selectInput('align', data.align || 'left', { left: 'Esquerda', center: 'Centro', right: 'Direita' });
                addField('Conteúdo (HTML permitido)', ftx.html, null);
                addField('Alinhamento', fta.html, null);
                getters = [function () { return { content: document.getElementById(ftx.id).value, align: document.getElementById(fta.id).value }; }];
                break;
            }
            case 'faq': {
                var ffaq = textArea('items', JSON.stringify(data.items || [], null, 2), 8);
                addField('Perguntas (JSON)', ffaq.html, null);
                wrap.querySelector('.ahb-field-row:last-child').insertAdjacentHTML('beforeend', '<p class="description">Formato: [{"question":"Pergunta?","answer":"Resposta."}]</p>');
                getters = [function () {
                    var parsed = [];
                    try { parsed = JSON.parse(document.getElementById(ffaq.id).value); } catch (e) { }
                    return { items: Array.isArray(parsed) ? parsed : [] };
                }];
                break;
            }
            case 'countdown': {
                var fct = textInput('target', data.target, '2025-12-31T23:59:59');
                var fcl = textInput('label', data.label, 'Evento especial');
                addField('Data/hora alvo (ISO)', fct.html, null);
                addField('Label', fcl.html, null);
                getters = [function () { return { target: document.getElementById(fct.id).value, label: document.getElementById(fcl.id).value }; }];
                break;
            }
            case 'map': {
                var fme = textInput('embed', data.embed, 'https://www.google.com/maps/embed?...');
                var fmh = numInput('height', data.height || 250, 100, 600);
                addField('URL embed do Google Maps', fme.html, null);
                addField('Altura (px)', fmh.html, null);
                getters = [function () { return { embed: document.getElementById(fme.id).value, height: parseInt(document.getElementById(fmh.id).value) || 250 }; }];
                break;
            }
            case 'divider': {
                var fds = selectInput('style', data.style || 'line', { line: 'Linha', space: 'Espaço' });
                var fdh = numInput('height', data.height || 24, 4, 200);
                addField('Estilo', fds.html, null);
                addField('Altura (px)', fdh.html, null);
                getters = [function () { return { style: document.getElementById(fds.id).value, height: parseInt(document.getElementById(fdh.id).value) || 24 }; }];
                break;
            }
            case 'embed': {
                var fem = textArea('code', data.code, 5);
                addField('Código embed (iframe)', fem.html, null);
                getters = [function () { return { code: document.getElementById(fem.id).value }; }];
                break;
            }
            default: {
                var fraw = textArea('raw', JSON.stringify(data, null, 2), 6);
                addField('Dados (JSON)', fraw.html, null);
                getters = [function () {
                    try { return JSON.parse(document.getElementById(fraw.id).value); } catch (e) { return data; }
                }];
            }
        }

        return {
            el: wrap,
            getData: function () {
                var result = {};
                getters.forEach(function (g) {
                    if (g) Object.assign(result, g());
                });
                return result;
            }
        };
    }

    // ── Drag & drop reorder ─────────────────────────────────────────────

    var dragSrcIdx = null;

    function initDragDrop() {
        var cards = listEl.querySelectorAll('.ahb-block-card');
        cards.forEach(function (card) {
            card.addEventListener('dragstart', function (e) {
                dragSrcIdx = parseInt(card.dataset.index);
                e.dataTransfer.effectAllowed = 'move';
                card.classList.add('ahb-dragging');
            });
            card.addEventListener('dragover', function (e) {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                card.classList.add('ahb-dragover');
            });
            card.addEventListener('dragleave', function () {
                card.classList.remove('ahb-dragover');
            });
            card.addEventListener('drop', function (e) {
                e.preventDefault();
                card.classList.remove('ahb-dragover');
                var targetIdx = parseInt(card.dataset.index);
                if (dragSrcIdx !== null && dragSrcIdx !== targetIdx) {
                    var moved = blocks.splice(dragSrcIdx, 1)[0];
                    blocks.splice(targetIdx, 0, moved);
                    renderList();
                }
            });
            card.addEventListener('dragend', function () {
                card.classList.remove('ahb-dragging');
            });
        });
    }

    // ── Migrate legacy links ────────────────────────────────────────────

    if (migrateBtn) {
        migrateBtn.addEventListener('click', function () {
            if (!confirm('Migrar links legados para o sistema de blocos? Os links atuais do campo JSON serão convertidos.')) return;

            // Read legacy links JSON from the textarea
            var linksEl = document.getElementById('hub_links');
            if (!linksEl) { alert('Campo de links não encontrado.'); return; }

            var legacyLinks = [];
            try { legacyLinks = JSON.parse(linksEl.value || '[]'); } catch (e) { alert('JSON de links inválido.'); return; }

            if (!Array.isArray(legacyLinks) || legacyLinks.length === 0) {
                alert('Nenhum link legado encontrado para migrar.');
                return;
            }

            // Convert each link to a block
            legacyLinks.forEach(function (link) {
                blocks.push({
                    type: 'link',
                    id: genId(),
                    active: link.active !== false,
                    data: {
                        title: link.title || '',
                        sub: '',
                        url: link.url || '',
                        icon: link.icon || 'ri-link-m',
                        variant: 'default',
                        bgColor: '',
                        textColor: '',
                        iconBg: '',
                        badge: ''
                    }
                });
            });

            renderList();
            alert(legacyLinks.length + ' link(s) migrado(s) para blocos com sucesso!');
        });
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    function esc(str) {
        var el = document.createElement('span');
        el.textContent = str || '';
        return el.innerHTML;
    }

    function stripTags(html) {
        var tmp = document.createElement('div');
        tmp.innerHTML = html;
        return tmp.textContent || tmp.innerText || '';
    }

    // ── Init ────────────────────────────────────────────────────────────

    renderList();

})();
