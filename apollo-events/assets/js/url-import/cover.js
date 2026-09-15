/**
 * Apollo URL Importer — cover preview + validation UI.
 *
 * @package apollo-events
 */
(function (global) {
  'use strict';

  const AUI = global.ApolloUrlImport = global.ApolloUrlImport || {};

  function coverLedClass(extract) {
    const diag = extract && extract.cover_diagnostics;
    if (!extract || !extract.cover) return 'off';
    if (diag && diag.valid > 0) return 'on';
    if (extract.cover_candidates && extract.cover_candidates.some(function (c) { return c.ok; })) return 'on';
    return extract.cover ? 'on' : 'off';
  }

  function renderCoverBlock(extract) {
    const host = AUI.dom.coverPreviewHost;
    if (!host) return;

    host.innerHTML = '';
    if (!extract || !extract.cover) {
      host.innerHTML = '<div class="imp-cover-empty">sem capa detectada</div>';
      return;
    }

    const wrap = document.createElement('div');
    wrap.className = 'imp-cover-preview';

    const thumb = document.createElement('div');
    thumb.className = 'imp-cover-thumb';
    thumb.style.backgroundImage = "url('" + String(extract.cover).replace(/'/g, "\\'") + "')";

    const meta = document.createElement('div');
    meta.className = 'imp-cover-meta';
    const diag = extract.cover_diagnostics || {};
    meta.innerHTML =
      '<div class="imp-cover-url">' + AUI.escapeHtml(extract.cover) + '</div>' +
      (diag.winner_src ? '<div class="tag">fonte: ' + AUI.escapeHtml(diag.winner_src) + '</div>' : '') +
      (typeof diag.valid === 'number' ? '<div class="tag">' + diag.valid + '/' + (diag.total || 0) + ' candidatos OK</div>' : '');

    wrap.appendChild(thumb);
    wrap.appendChild(meta);
    host.appendChild(wrap);

    if (Array.isArray(extract.cover_candidates) && extract.cover_candidates.length > 1) {
      const list = document.createElement('ul');
      list.className = 'imp-cover-candidates';
      extract.cover_candidates.forEach(function (c) {
        const li = document.createElement('li');
        li.className = c.ok ? 'ok' : 'bad';
        li.textContent = (c.source || '?') + ' — ' + (c.ok ? 'OK' : (c.error || 'inválida'));
        list.appendChild(li);
      });
      host.appendChild(list);
    }
  }

  function afterImportRow(row, body) {
    if (!body || !body.banner) return;
    row.importCover = body.banner;
    if (body.banner.local_url) {
      row.values.coverLocal = body.banner.local_url;
      row.values.coverAttachmentId = body.banner.attachment_id;
    }
  }

  AUI.Cover = {
    coverLedClass: coverLedClass,
    renderCoverBlock: renderCoverBlock,
    afterImportRow: afterImportRow
  };
})(window);
