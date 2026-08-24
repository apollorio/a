/**
 * Apollo URL Importer — REST / fetch API layer.
 *
 * BlueTicket → server preview + importar-url (strict CPT contract).
 * Shotgun   → client HTML scrape → POST /eventos (until PHP provider exists).
 *
 * @package apollo-events
 */
(function (global) {
  'use strict';

  const AUI = global.ApolloUrlImport = global.ApolloUrlImport || {};

  function logMsg(msg, level) {
    if (AUI.UI && AUI.UI.log) {
      AUI.UI.log(msg, level);
    }
  }

  function restHeaders(extra) {
    const headers = Object.assign({ 'Content-Type': 'application/json' }, extra || {});
    if (AUI.boot.nonce) {
      headers['X-WP-Nonce'] = AUI.boot.nonce;
    }
    const auth = AUI.cfg('cfgAuth');
    if (auth) {
      headers.Authorization = auth;
    }
    return headers;
  }

  function restBase() {
    return (AUI.$('cfgWpBase') && AUI.$('cfgWpBase').value.trim())
      || (AUI.boot.restUrl || '').replace(/\/$/, '');
  }

  async function fetchHTML(rawUrl) {
    const proxyTemplate = AUI.$('cfgFetchProxy').value.trim();

    try {
      logMsg('tentando fetch direto...', 'info');
      const res = await fetch(rawUrl, { mode: 'cors' });
      if (res.ok) {
        const text = await res.text();
        if (text && text.length > 200) {
          logMsg('fetch direto funcionou.', 'ok');
          return text;
        }
      }
    } catch (e) { /* expected on cross-origin */ }

    if (proxyTemplate) {
      try {
        logMsg('tentando endpoint de fetch server-side configurado...', 'info');
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
              logMsg('HTML recebido via proxy (JSON).', 'ok');
              return html;
            }
          } catch (e) { /* not JSON */ }
          if (raw && raw.length > 200) {
            logMsg('HTML recebido via proxy.', 'ok');
            return raw;
          }
        }
        logMsg('proxy respondeu, mas sem HTML utilizável.', 'warn');
      } catch (e) {
        logMsg('proxy configurado falhou: ' + e.message, 'warn');
      }
    } else {
      logMsg('nenhum endpoint de fetch configurado.', 'warn');
    }

    return null;
  }

  /**
   * Map server preview payload → checklist extract shape.
   */
  function extractFromServerPreview(data, rawUrl) {
    const loc = data.loc || {};
    const matched = data.matched_loc ? String(data.matched_loc) : '';
    /* Submitted URL wins for the ticket CTA — provider may only append ?c=. */
    const ticketUrl = rawUrl || data.ticket_url || data.source_url || '';
    return {
      platform: data.provider || 'blueticket',
      title: data.title || '',
      start_date: data.start_date || '',
      start_time: data.start_time || '',
      end_date: data.end_date || '',
      end_time: data.end_time || '07:00',
      cover: data.cover || '',
      video: data.video_url || '',
      locName: loc.name || '',
      locSlug: loc.slug || '',
      locId: matched,
      bio: data.about || '',
      ticket_price: data.ticket_price || 'Ingressos do Evento',
      coupon: data.coupon || (AUI.$('cfgCoupon') && AUI.$('cfgCoupon').value) || 'apollo',
      ticketUrl: ticketUrl,
      sourceUrl: rawUrl || data.source_url || ticketUrl,
      performers: [],
      matched_loc: data.matched_loc || 0,
      existing_id: data.existing_id || 0,
      ready: data.ready || null,
      _server: true
    };
  }

  async function previewBlueTicket(rawUrl) {
    const base = restBase();
    const path = (AUI.boot.importPreviewPath || 'apollo/v1/eventos/importar-url/preview').replace(/^\//, '');
    if (!base) {
      throw new Error('configure a base da REST API primeiro.');
    }
    const coupon = (AUI.$('cfgCoupon') && AUI.$('cfgCoupon').value.trim()) || (AUI.boot.defaultCoupon || 'apollo');
    logMsg('BlueTicket → preview server-side (' + path + ')…', 'info');
    const res = await fetch(base.replace(/\/$/, '') + '/' + path, {
      method: 'POST',
      headers: restHeaders(),
      credentials: 'same-origin',
      body: JSON.stringify({ url: rawUrl, coupon: coupon })
    });
    const body = await res.json().catch(function () { return null; });
    if (!res.ok) {
      const msg = (body && (body.message || (body.data && body.data.message))) || ('HTTP ' + res.status);
      throw new Error(msg);
    }
    if (!body || !body.ok || !body.data) {
      throw new Error('preview sem data');
    }
    return extractFromServerPreview(body.data, rawUrl);
  }

  function buildPayload(values, extract) {
    const status = AUI.$('cfgStatus').value || 'draft';
    /* Submitted page URL is always the ticket CTA — never drop it. */
    let ticketUrl = (values.ticketUrl || values.sourceUrl || (extract && (extract.ticketUrl || extract.sourceUrl)) || '').trim();
    let ticketTitle = (values.ticket_price || '').trim();
    /* Guard: offer scrape / paste mistakes put the page URL into ticket_price. */
    if (!ticketUrl && /^https?:\/\//i.test(ticketTitle)) {
      ticketUrl = ticketTitle;
      ticketTitle = '';
    }
    if (/^https?:\/\//i.test(ticketTitle)) {
      ticketTitle = '';
    }
    if (!ticketTitle) {
      ticketTitle = 'Ingressos do Evento';
    }
    const payload = {
      title: values.title || '',
      content: values.bio || '',
      post_status: status,
      start_date: values.start_date || '',
      start_time: values.start_time || '',
      end_date: values.end_date || '',
      end_time: values.end_time || '',
      banner: values.cover || '',
      ticket_url: ticketUrl,
      coupon_code: values.coupon || '',
      video_url: values.video || '',
      ticket_price: ticketTitle,
      loc_id: values.locId || ''
    };
    Object.keys(payload).forEach(function (k) {
      if (payload[k] === '' && k !== 'title' && k !== 'post_status') {
        delete payload[k];
      }
    });
    return payload;
  }

  async function resolveLoc(row, card) {
    const base = AUI.$('cfgWpBase').value.trim();
    const locPath = AUI.$('cfgLocPath').value.trim() || 'apollo/v1/local';
    const locSlug = (row.values.locSlug || '').trim();
    const locName = (row.values.locName || '').trim();
    if (!base) {
      alert('configure a base da REST API primeiro.');
      return;
    }
    if (!locSlug && !locName) {
      alert('não há slug/nome de local para buscar.');
      return;
    }
    try {
      const path = base.replace(/\/$/, '') + '/' + locPath.replace(/^\//, '');
      let url = locSlug
        ? path + '?slug=' + encodeURIComponent(locSlug)
        : path + '?search=' + encodeURIComponent(locName) + '&per_page=10';
      let res = await fetch(url, { headers: restHeaders(), credentials: 'same-origin' });
      if (!res.ok) throw new Error('HTTP ' + res.status);
      let data = await res.json();
      let items = Array.isArray(data) ? data : (Array.isArray(data && data.data) ? data.data : []);

      if (!items.length && locSlug && locName) {
        url = path + '?search=' + encodeURIComponent(locName) + '&per_page=10';
        res = await fetch(url, { headers: restHeaders(), credentials: 'same-origin' });
        if (!res.ok) throw new Error('HTTP ' + res.status);
        data = await res.json();
        items = Array.isArray(data) ? data : (Array.isArray(data && data.data) ? data.data : []);
      }

      if (!items.length) {
        alert('nenhum local encontrado para slug "' + (locSlug || locName) + '" — crie o CPT local no WP ou deixe loc_id vazio.');
        return;
      }

      let match = null;
      if (locSlug) {
        const want = locSlug.toLowerCase();
        match = items.find(function (i) { return String(i.slug || '').toLowerCase() === want; })
          || items.find(function (i) { return AUI.slugify(i.slug || '') === AUI.slugify(locSlug); });
      }
      if (!match && locName) {
        const want = AUI.slugify(locName);
        match = items.find(function (i) { return AUI.slugify(i.title || i.slug || '') === want; })
          || items.find(function (i) {
            const t = AUI.slugify(i.title || '');
            return t.includes(want) || want.includes(t);
          });
      }
      if (!match) match = items[0];

      const id = match && match.id != null ? String(match.id) : '';
      if (id) {
        row.payload.loc_id = id;
        row.values.locId = id;
        if (match.slug) {
          row.values.locSlug = String(match.slug);
        }
        card.querySelector('[data-locid]').value = id;
      } else {
        alert('endpoint respondeu, mas sem ID — preencha manualmente.');
      }
    } catch (e) {
      alert('falha ao buscar local: ' + e.message);
    }
  }

  async function sendViaImportarUrl(row, card) {
    const base = restBase();
    const path = (
      (AUI.$('cfgImportPath') && AUI.$('cfgImportPath').value.trim())
      || AUI.boot.importPath
      || 'apollo/v1/eventos/importar-url'
    ).replace(/^\//, '');
    if (!base) {
      throw new Error('configure a base da REST API primeiro.');
    }
    const url = row.values.sourceUrl || row.values.ticketUrl || '';
    if (!url) {
      throw new Error('URL de origem ausente na linha.');
    }
    /* Keep the submitted ticket URL on the row even though the server re-fetches. */
    if (!row.values.ticketUrl) {
      row.values.ticketUrl = url;
    }
    if (row.payload) {
      row.payload.ticket_url = row.values.ticketUrl || url;
      if (!row.payload.ticket_price || /^https?:\/\//i.test(String(row.payload.ticket_price))) {
        row.payload.ticket_price = 'Ingressos do Evento';
      }
    }
    if (!row.values.cover) {
      throw new Error('capa obrigatória — banner deve virar featured image.');
    }
    if (!row.values.locId && !(row.extract && row.extract.matched_loc)) {
      throw new Error('loc_id obrigatório — venue deve resolver para CPT local (ex: dedge).');
    }

    const coupon = row.values.coupon || (AUI.$('cfgCoupon') && AUI.$('cfgCoupon').value) || 'apollo';
    const status = (AUI.$('cfgStatus') && AUI.$('cfgStatus').value) || 'draft';

    const res = await fetch(base.replace(/\/$/, '') + '/' + path, {
      method: 'POST',
      headers: restHeaders(),
      credentials: 'same-origin',
      body: JSON.stringify({
        url: url,
        coupon: coupon,
        status: status,
        link_loc: true
      })
    });
    const body = await res.json().catch(function () { return null; });
    if (!res.ok) {
      const msg = (body && (body.message || (body.code && (body.message || body.code)))) || ('HTTP ' + res.status);
      throw new Error(typeof msg === 'string' ? msg : JSON.stringify(msg));
    }
    if (!body || !body.ok) {
      throw new Error((body && body.message) || 'import falhou');
    }
    if (!body.synced || !body.banner || !body.banner.attachment_id) {
      throw new Error('import sem banner===featured — recusado pelo contrato');
    }
    if (!body.loc_id) {
      throw new Error('import sem loc_id — recusado pelo contrato');
    }
    row.result = body;
    row.values.locId = String(body.loc_id);
    return body;
  }

  async function sendToWordPress(row, card) {
    const useServer = row.platform === 'blueticket' || (row.extract && row.extract._server);

    setRowStatus(row, card, 'sending');
    try {
      if (useServer) {
        const body = await sendViaImportarUrl(row, card);
        setRowStatus(row, card, 'sent');
        logMsg(
          'importar-url OK #' + body.post_id +
          ' banner=' + body.banner.attachment_id +
          ' thumb=' + body.thumb_id +
          ' loc=' + body.loc_id +
          ' synced=' + body.synced,
          'ok'
        );
        return;
      }

      const base = AUI.$('cfgWpBase').value.trim();
      const insertPath = AUI.$('cfgInsertPath').value.trim();
      if (!base || !insertPath) {
        throw new Error('configure a base da REST API e o endpoint de inserção primeiro.');
      }
      if (!row.payload.banner) {
        throw new Error('capa obrigatória no payload (banner).');
      }
      const res = await fetch(base.replace(/\/$/, '') + '/' + insertPath.replace(/^\//, ''), {
        method: 'POST',
        headers: restHeaders(),
        credentials: 'same-origin',
        body: JSON.stringify(row.payload)
      });
      if (!res.ok) throw new Error('HTTP ' + res.status);
      setRowStatus(row, card, 'sent');
    } catch (e) {
      setRowStatus(row, card, 'failed');
      alert('falha ao enviar: ' + e.message);
    }
  }

  function setRowStatus(row, card, status) {
    row.status = status;
    const tag = card.querySelector('[data-status-tag]');
    if (!tag || !AUI.UI) return;
    tag.className = AUI.UI.statusTagClass(status);
    tag.textContent = AUI.UI.statusLabel(status);
  }

  AUI.Api = {
    restHeaders: restHeaders,
    fetchHTML: fetchHTML,
    previewBlueTicket: previewBlueTicket,
    extractFromServerPreview: extractFromServerPreview,
    buildPayload: buildPayload,
    resolveLoc: resolveLoc,
    sendToWordPress: sendToWordPress,
    setRowStatus: setRowStatus
  };
})(window);
