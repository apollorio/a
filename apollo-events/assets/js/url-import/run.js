/**
 * Apollo URL Importer — batch run (send all pending rows).
 *
 * @package apollo-events
 */
(function (global) {
  'use strict';

  const AUI = global.ApolloUrlImport = global.ApolloUrlImport || {};

  async function runAllPending() {
    const pending = (AUI.state.rows || []).filter(function (r) {
      return r.status === 'pending' || r.status === 'failed';
    });
    if (!pending.length) {
      if (AUI.UI && AUI.UI.log) AUI.UI.log('nenhuma linha pendente para enviar.', 'warn');
      return;
    }
    if (AUI.UI && AUI.UI.log) {
      AUI.UI.log('batch: enviando ' + pending.length + ' evento(s)...', 'info');
    }
    let ok = 0;
    let fail = 0;
    for (let i = 0; i < pending.length; i++) {
      const row = pending[i];
      const card = document.getElementById(row.id);
      if (!card) continue;
      try {
        await AUI.Api.sendToWordPress(row, card, { silentAlert: true });
        if (row.status === 'sent') ok++;
        else fail++;
      } catch (e) {
        fail++;
        if (AUI.UI && AUI.UI.log) AUI.UI.log('batch falhou #' + (i + 1) + ': ' + e.message, 'err');
      }
    }
    if (AUI.UI && AUI.UI.log) {
      AUI.UI.log('batch concluído — OK: ' + ok + ', falhas: ' + fail, fail ? 'warn' : 'ok');
    }
  }

  AUI.initRun = function () {
    const btn = AUI.$('btnRunAll');
    if (btn) {
      btn.addEventListener('click', runAllPending);
    }
  };

  AUI.Run = { runAllPending: runAllPending };
})(window);
