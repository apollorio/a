/**
 * Apollo URL Importer — application bootstrap + import pipeline.
 *
 * BlueTicket uses server preview/importar-url (strict CPT contract).
 * Shotgun keeps HTML scrape → POST /eventos until a PHP provider exists.
 *
 * @package apollo-events
 */
(function (global) {
  'use strict';

  const AUI = global.ApolloUrlImport = global.ApolloUrlImport || {};

  async function runImport() {
    const rawUrl = AUI.dom.urlInput.value.trim();
    AUI.UI.clearLog();
    AUI.dom.pasteBox.classList.remove('show');

    if (!rawUrl) {
      AUI.UI.log('cole uma URL antes de importar.', 'err');
      return;
    }

    let parsedUrl;
    try {
      parsedUrl = new URL(rawUrl);
    } catch (e) {
      AUI.UI.log('URL inválida.', 'err');
      return;
    }

    AUI.UI.log('detectando plataforma para ' + parsedUrl.hostname + '...', 'info');
    const platform = AUI.detectPlatform(rawUrl);
    if (!platform) {
      AUI.UI.log('plataforma não reconhecida — esperado blueticket.com.br ou shotgun.live.', 'err');
      return;
    }
    AUI.UI.log('plataforma detectada: ' + platform, 'ok');

    /* ── BlueTicket: server preview (API), never DOM scrape ── */
    if (platform === 'blueticket') {
      try {
        const extract = await AUI.Api.previewBlueTicket(rawUrl);
        logPreview(extract);
        if (extract.ready && !extract.ready.ok) {
          if (!extract.ready.cover) {
            AUI.UI.log('contrato: capa ausente — import será recusado.', 'err');
          }
          if (!extract.ready.loc) {
            AUI.UI.log(
              'contrato: local não resolvido (slug ' + (extract.locSlug || '?') +
              ') — crie CPT local "dedge" etc. antes de confirmar.',
              'err'
            );
          }
        } else if (extract.matched_loc) {
          AUI.UI.log('local resolvido → loc_id ' + extract.matched_loc + ' (' + extract.locSlug + ')', 'ok');
        }
        AUI.UI.renderChecklist(extract);
        AUI.dom.checklistPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
      } catch (e) {
        AUI.UI.log('preview BlueTicket falhou: ' + e.message, 'err');
      }
      return;
    }

    /* ── Shotgun (and others): HTML scrape fallback ── */
    const html = await AUI.Api.fetchHTML(rawUrl);
    if (!html) {
      AUI.UI.log('busca automática indisponível — cole o HTML manualmente abaixo.', 'warn');
      AUI.dom.pasteBox.classList.add('show');
      AUI.dom.pasteBox.dataset.pendingUrl = rawUrl;
      AUI.dom.pasteBox.dataset.pendingPlatform = platform;
      return;
    }
    parseAndRender(html, rawUrl, platform);
  }

  function logPreview(extract) {
    AUI.UI.log('título → ' + (extract.title || '(vazio)'), extract.title ? 'ok' : 'warn');
    AUI.UI.log(
      'agenda → ' + (extract.start_date || '?') + ' ' + (extract.start_time || '') +
      ' → ' + (extract.end_date || '?') + ' ' + (extract.end_time || ''),
      extract.start_date ? 'ok' : 'warn'
    );
    AUI.UI.log('capa → ' + (extract.cover || '(vazia)'), extract.cover ? 'ok' : 'err');
    AUI.UI.log('local → ' + (extract.locName || '(vazio)') + ' / ' + (extract.locSlug || ''), extract.locName ? 'ok' : 'warn');
    if (extract.bio) {
      AUI.UI.log('bio → ' + extract.bio.slice(0, 80) + (extract.bio.length > 80 ? '…' : ''), 'ok');
    }
    if (extract.coupon) {
      AUI.UI.log('cupom → ' + extract.coupon, 'ok');
    }
    if (extract.existing_id) {
      AUI.UI.log('re-import: evento existente #' + extract.existing_id, 'info');
    }
    AUI.UI.log('pronto — revise o console de validação abaixo.', 'ok');
  }

  function parseAndRender(html, rawUrl, platform) {
    AUI.UI.log('parseando HTML (' + html.length + ' chars)...', 'info');
    let doc;
    try {
      doc = new DOMParser().parseFromString(html, 'text/html');
    } catch (e) {
      AUI.UI.log('falha ao parsear HTML: ' + e.message, 'err');
      return;
    }

    const extract = platform === 'blueticket'
      ? AUI.Parse.parseBlueTicket(doc, rawUrl)
      : AUI.Parse.parseShotgun(doc, rawUrl);

    logPreview(extract);
    AUI.UI.renderChecklist(extract);
    AUI.dom.checklistPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  function confirmRow() {
    const extract = AUI.state.currentExtract;
    if (!extract) return;

    const values = AUI.UI.collectChecklistValues();
    /* Carry server / submitted source URL for importar-url + Shotgun POST. */
    if (extract.sourceUrl) {
      values.sourceUrl = extract.sourceUrl;
    }
    /* Ticket CTA MUST be the submitted page URL (never leave empty). */
    values.ticketUrl = values.ticketUrl || extract.ticketUrl || extract.sourceUrl || '';
    if (!values.ticketUrl && AUI.dom.urlInput && AUI.dom.urlInput.value) {
      values.ticketUrl = AUI.dom.urlInput.value.trim();
    }
    if (/^https?:\/\//i.test(values.ticket_price || '')) {
      if (!values.ticketUrl) {
        values.ticketUrl = values.ticket_price;
      }
      values.ticket_price = 'Ingressos do Evento';
    }
    if (!values.ticket_price) {
      values.ticket_price = extract.ticket_price || 'Ingressos do Evento';
    }
    if (/^https?:\/\//i.test(values.ticket_price)) {
      values.ticket_price = 'Ingressos do Evento';
    }

    if (extract._server || extract.platform === 'blueticket') {
      if (!values.cover) {
        AUI.UI.log('bloqueado: capa obrigatória (banner = featured).', 'err');
        return;
      }
      if (!values.locId) {
        AUI.UI.log('bloqueado: loc_id obrigatório (venue → CPT local).', 'err');
        return;
      }
    }

    const payload = AUI.Api.buildPayload(values, extract);

    AUI.state.counter += 1;
    AUI.state.rows.unshift({
      id: 'row-' + AUI.state.counter,
      platform: extract.platform,
      values: values,
      payload: payload,
      extract: extract,
      status: 'pending'
    });
    AUI.UI.renderResults();

    AUI.dom.checklistPanel.style.display = 'none';
    AUI.dom.urlInput.value = '';
    AUI.UI.clearLog();
    AUI.state.currentExtract = null;
    AUI.dom.resultsList.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  AUI.init = function () {
    AUI.dom.cache();

    const boot = AUI.boot;
    if (boot.restUrl && !AUI.cfg('cfgWpBase')) {
      AUI.$('cfgWpBase').value = String(boot.restUrl).replace(/\/$/, '');
    }
    /* Prefer server import path for BlueTicket; keep insertPath as Shotgun fallback. */
    if (boot.importPath && AUI.$('cfgImportPath') && !AUI.cfg('cfgImportPath')) {
      AUI.$('cfgImportPath').value = boot.importPath;
    }
    if (boot.insertPath && !AUI.cfg('cfgInsertPath')) {
      AUI.$('cfgInsertPath').value = boot.insertPath;
    }
    if (boot.locPath && !AUI.cfg('cfgLocPath')) {
      AUI.$('cfgLocPath').value = boot.locPath;
    }

    AUI.$('btnImport').addEventListener('click', runImport);
    AUI.dom.urlInput.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') runImport();
    });
    AUI.$('btnParsePasted').addEventListener('click', function () {
      const html = AUI.dom.pasteHtml.value;
      const rawUrl = AUI.dom.pasteBox.dataset.pendingUrl;
      const platform = AUI.dom.pasteBox.dataset.pendingPlatform;
      if (!html.trim()) {
        AUI.UI.log('cole o HTML antes de parsear.', 'err');
        return;
      }
      AUI.dom.pasteBox.classList.remove('show');
      parseAndRender(html, rawUrl, platform);
    });
    AUI.$('btnCancelPaste').addEventListener('click', function () {
      AUI.dom.pasteBox.classList.remove('show');
      AUI.dom.pasteHtml.value = '';
    });
    AUI.dom.btnConfirmRow.addEventListener('click', confirmRow);
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', AUI.init);
  } else {
    AUI.init();
  }
})(window);
