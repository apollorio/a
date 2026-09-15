/**
 * Apollo URL Importer — utils
 * @package apollo-events
 */
(function (global) {
  'use strict';
  const AUI = global.ApolloUrlImport = global.ApolloUrlImport || {};


  const boot = global.APOLLO_URL_IMPORT || {};

  AUI.boot = boot;
  AUI.state = { rows: [], counter: 0, currentExtract: null };
  AUI.Api = AUI.Api || {};
  AUI.PROMOTER_LOC_MAP = boot.promoterLocMap || {
    'd-edge': { slug: 'dedge', name: 'D-EDGE' },
    dedge: { slug: 'dedge', name: 'D-EDGE' }
  };

  AUI.$ = function (id) { return document.getElementById(id); };

  AUI.cfg = function (id) {
    const el = AUI.$(id);
    return el ? String(el.value || '').trim() : '';
  };

  AUI.escapeHtml = function (str) {
    return String(str == null ? '' : str).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  };

  AUI.slugify = function (str) {
    if (!str) return '';
    return str.toString().normalize('NFD').replace(/[\u0300-\u036f]/g, '')
      .toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
  };

  AUI.detectPlatform = function (rawUrl) {
    let u;
    try { u = new URL(rawUrl); } catch (e) { return null; }
    const h = u.hostname.replace(/^www\./, '');
    if (h.endsWith('blueticket.com.br')) return 'blueticket';
    if (h.endsWith('shotgun.live')) return 'shotgun';
    return null;
  };

  AUI.dom = {
    urlInput: null,
    logConsole: null,
    pasteBox: null,
    pasteHtml: null,
    checklistPanel: null,
    ledGrid: null,
    platformPill: null,
    gateMsg: null,
    btnConfirmRow: null,
    resultsList: null,
    countPill: null,
    cache: function () {
      const $ = AUI.$;
      AUI.dom.urlInput = $('urlInput');
      AUI.dom.logConsole = $('logConsole');
      AUI.dom.pasteBox = $('pasteBox');
      AUI.dom.pasteHtml = $('pasteHtml');
      AUI.dom.checklistPanel = $('checklistPanel');
      AUI.dom.ledGrid = $('ledGrid');
      AUI.dom.platformPill = $('platformPill');
      AUI.dom.gateMsg = $('gateMsg');
      AUI.dom.btnConfirmRow = $('btnConfirmRow');
      AUI.dom.resultsList = $('resultsList');
      AUI.dom.countPill = $('countPill');
      AUI.dom.coverPreviewHost = $('coverPreviewHost');
      AUI.dom.diagnosticsHost = $('diagnosticsHost');
    }
  };

})(window);
