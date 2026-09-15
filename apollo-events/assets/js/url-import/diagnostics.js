/**
 * Apollo URL Importer — structured diagnostics renderer.
 *
 * @package apollo-events
 */
(function (global) {
  'use strict';

  const AUI = global.ApolloUrlImport = global.ApolloUrlImport || {};

  const LEVEL_MAP = {
    info: 'info',
    warn: 'warn',
    error: 'err'
  };

  function logList(items) {
    if (!items || !items.length || !AUI.UI || !AUI.UI.log) return;
    items.forEach(function (item) {
      const level = LEVEL_MAP[item.severity] || 'info';
      const prefix = item.code ? '[' + item.code + '] ' : '';
      AUI.UI.log(prefix + (item.message || ''), level);
    });
  }

  function renderPanel(items) {
    const host = AUI.dom.diagnosticsHost;
    if (!host) return;
    host.innerHTML = '';
    if (!items || !items.length) {
      host.style.display = 'none';
      return;
    }
    host.style.display = 'block';
    items.forEach(function (item) {
      const row = document.createElement('div');
      row.className = 'imp-diag imp-diag-' + (item.severity || 'info');
      row.textContent = (item.code ? item.code + ' — ' : '') + (item.message || '');
      host.appendChild(row);
    });
  }

  function fromPreview(body) {
    const items = (body && body.diagnostics) || [];
    logList(items);
    renderPanel(items);
    return items;
  }

  function fromImportError(errBody) {
    const items = (errBody && errBody.data && errBody.data.diagnostics)
      || (errBody && errBody.diagnostics)
      || [];
    logList(items);
    renderPanel(items);
    return items;
  }

  AUI.Diagnostics = {
    logList: logList,
    renderPanel: renderPanel,
    fromPreview: fromPreview,
    fromImportError: fromImportError
  };
})(window);
