/**
 * Apollo URL Importer — application bootstrap + import pipeline.
 *
 * Primary path (proven HTML flow): fetchHTML → client parse → paste fallback.
 * BlueTicket optional shortcut: server preview when api.js is loaded.
 *
 * @package apollo-events
 */
(function (global) {
  'use strict';

  const AUI = global.ApolloUrlImport = global.ApolloUrlImport || {};

  /** Inline fetchHTML — works even if api.js is stale/missing on CDN cache. */
  async function fetchHTMLBrutal(rawUrl) {
    if (AUI.Api && typeof AUI.Api.fetchHTML === 'function') {
      return AUI.Api.fetchHTML(rawUrl);
    }

    const proxyEl = AUI.$('cfgFetchProxy');
    const proxyTemplate = proxyEl ? proxyEl.value.trim() : '';

    try {
      AUI.UI.log('tentando fetch direto...', 'info');
      const res = await fetch(rawUrl, { mode: 'cors' });
      if (res.ok) {
        const text = await res.text();
        if (text && text.length > 200) {
          AUI.UI.log('fetch direto funcionou.', 'ok');
          return text;
        }
      }
    } catch (e) { /* CORS expected */ }

    if (proxyTemplate) {
      try {
        AUI.UI.log('tentando endpoint de fetch server-side configurado...', 'info');
        const proxyUrl = proxyTemplate.includes('{url}')
          ? proxyTemplate.replace('{url}', encodeURIComponent(rawUrl))
          : proxyTemplate + encodeURIComponent(rawUrl);
        const res = await fetch(proxyUrl);
        if (res.ok) {
          const raw = await res.text();
          try {
            const j = JSON.parse(raw);
            const html = j.html || j.contents || j.body || null;
            if (html) {
              AUI.UI.log('HTML recebido via proxy (JSON).', 'ok');
              return html;
            }
          } catch (e) { /* raw html */ }
          if (raw && raw.length > 200) {
            AUI.UI.log('HTML recebido via proxy.', 'ok');
            return raw;
          }
        }
        AUI.UI.log('proxy respondeu, mas sem HTML utilizável.', 'warn');
      } catch (e) {
        AUI.UI.log('proxy configurado falhou: ' + e.message, 'warn');
      }
    } else {
      AUI.UI.log('nenhum endpoint de fetch configurado.', 'warn');
    }

    return null;
  }

  async function tryServerPreview(rawUrl, platform) {
    if (platform !== 'blueticket') {
      return null;
    }
    const api = AUI.Api || {};
    const fn = api.previewImport || api.previewBlueTicket;
    if (typeof fn !== 'function') {
      return null;
    }
    AUI.UI.log('BlueTicket → tentando preview server-side…', 'info');
    const extract = await fn.call(api, rawUrl);
    extract.platform = platform;
    extract._server = true;
    return extract;
  }

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

    /* BlueTicket: server preview when available (never blocks Shotgun). */
    if (platform === 'blueticket') {
      try {
        const serverExtract = await tryServerPreview(rawUrl, platform);
        if (serverExtract) {
          finishPreview(serverExtract);
          return;
        }
      } catch (e) {
        AUI.UI.log('preview server indisponível: ' + e.message + ' — caindo para HTML.', 'warn');
      }
    }

    /* SSOT — same as standalone events-by-url.html */
    const html = await fetchHTMLBrutal(rawUrl);
    if (!html) {
      AUI.UI.log('busca automática indisponível — cole o HTML manualmente abaixo.', 'warn');
      AUI.dom.pasteBox.classList.add('show');
      AUI.dom.pasteBox.dataset.pendingUrl = rawUrl;
      AUI.dom.pasteBox.dataset.pendingPlatform = platform;
      return;
    }
    parseAndRender(html, rawUrl, platform);
  }

  function finishPreview(extract) {
    logPreview(extract);
    if (extract.ready && !extract.ready.ok) {
      if (!extract.ready.cover) {
        AUI.UI.log('contrato: capa ausente — import será recusado.', 'err');
      }
      if (!extract.ready.loc) {
        AUI.UI.log(
          'contrato: local não resolvido (slug ' + (extract.locSlug || '?') +
          ') — crie CPT local antes de confirmar.',
          'err'
        );
      }
    } else if (extract.matched_loc) {
      AUI.UI.log('local resolvido → loc_id ' + extract.matched_loc + ' (' + extract.locSlug + ')', 'ok');
    }
    if (extract.cover_diagnostics && extract.cover_diagnostics.valid > 0) {
      AUI.UI.log(
        'capa validada (' + extract.cover_diagnostics.valid + ' candidato(s))',
        'ok'
      );
    }
    AUI.UI.renderChecklist(extract);
    AUI.dom.checklistPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  function logPreview(extract) {
    AUI.UI.log('título → ' + (extract.title || '(vazio)'), extract.title ? 'ok' : 'warn');
    AUI.UI.log(
      'agenda → ' + (extract.start_date || '?') + ' ' + (extract.start_time || '') +
      ' → ' + (extract.end_date || '?') + ' ' + (extract.end_time || ''),
      extract.start_date ? 'ok' : 'warn'
    );
    AUI.UI.log(
      'capa → ' + (extract.cover || extract.video || '(vazia)'),
      (extract.cover || extract.video) ? 'ok' : 'warn'
    );
    AUI.UI.log('local → ' + (extract.locName || '(vazio)') + ' / ' + (extract.locSlug || ''), extract.locName ? 'ok' : 'warn');
    if (extract.bio) {
      AUI.UI.log('bio → ' + extract.bio.slice(0, 80) + (extract.bio.length > 80 ? '…' : ''), 'ok');
    }
    if (extract.ticket_price && extract.ticket_price !== 'Ingressos do Evento') {
      AUI.UI.log('preço (menor oferta) → ' + extract.ticket_price, 'ok');
    }
    if (extract.performers && extract.performers.length) {
      AUI.UI.log('performers → ' + extract.performers.length + ' (não linkados a DJ ainda)', 'info');
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

    if (!AUI.Parse) {
      AUI.UI.log('módulo parse.js não carregou.', 'err');
      return;
    }

    const extract = platform === 'blueticket'
      ? AUI.Parse.parseBlueTicket(doc, rawUrl)
      : AUI.Parse.parseShotgun(doc, rawUrl);

    extract.platform = platform;
    extract.sourceUrl = rawUrl || extract.ticketUrl || extract.sourceUrl || '';
    finishPreview(extract);
  }

  function confirmRow() {
    const extract = AUI.state.currentExtract;
    if (!extract) return;

    const values = AUI.UI.collectChecklistValues();
    values.sourceUrl = values.sourceUrl || extract.sourceUrl || extract.ticketUrl || '';
    values.ticketUrl = values.ticketUrl || extract.ticketUrl || extract.sourceUrl || '';
    if (!values.ticketUrl && AUI.dom.urlInput && AUI.dom.urlInput.value) {
      values.ticketUrl = AUI.dom.urlInput.value.trim();
    }
    if (/^https?:\/\//i.test(values.ticket_price || '')) {
      if (!values.ticketUrl) {
        values.ticketUrl = values.ticket_price;
      }
      values.ticket_price = extract.ticket_price || 'Ingressos do Evento';
    }
    if (!values.ticket_price) {
      values.ticket_price = extract.ticket_price || 'Ingressos do Evento';
    }

    /* Strict contract only for server-side BlueTicket import path. */
    if (extract._server && extract.platform === 'blueticket') {
      if (!values.cover) {
        AUI.UI.log('bloqueado: capa obrigatória (banner = featured).', 'err');
        return;
      }
      if (!values.locId && !extract.matched_loc) {
        AUI.UI.log('bloqueado: loc_id obrigatório (venue → CPT local).', 'err');
        return;
      }
      if (!values.locId && extract.matched_loc) {
        values.locId = String(extract.matched_loc);
      }
    }

    const payload = (AUI.Api && AUI.Api.buildPayload)
      ? AUI.Api.buildPayload(values, extract)
      : values;

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
    if (AUI.dom.diagnosticsHost) {
      AUI.dom.diagnosticsHost.innerHTML = '';
      AUI.dom.diagnosticsHost.style.display = 'none';
    }
    AUI.state.currentExtract = null;
    AUI.dom.resultsList.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  AUI.init = function () {
    AUI.dom.cache();

    const boot = AUI.boot || {};
    if (boot.restUrl && AUI.$('cfgWpBase') && !AUI.cfg('cfgWpBase')) {
      AUI.$('cfgWpBase').value = String(boot.restUrl).replace(/\/$/, '');
    }
    if (boot.importPath && AUI.$('cfgImportPath') && !AUI.cfg('cfgImportPath')) {
      AUI.$('cfgImportPath').value = boot.importPath;
    }
    if (boot.insertPath && AUI.$('cfgInsertPath') && !AUI.cfg('cfgInsertPath')) {
      AUI.$('cfgInsertPath').value = boot.insertPath;
    }
    if (boot.locPath && AUI.$('cfgLocPath') && !AUI.cfg('cfgLocPath')) {
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

    if (AUI.initRun) AUI.initRun();
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', AUI.init);
  } else {
    AUI.init();
  }
})(window);
