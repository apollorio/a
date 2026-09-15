/**
 * Apollo URL Importer — ui
 * @package apollo-events
 */
(function (global) {
  'use strict';
  const AUI = global.ApolloUrlImport = global.ApolloUrlImport || {};


  const state = AUI.state;
  /* ============== log console ============== */
    function log(msg, level){
      level = level || 'info';
      AUI.dom.logConsole.classList.add('show');
      const line = document.createElement('div');
      line.className = 'imp-log ' + level;
      const time = new Date().toLocaleTimeString('pt-BR', {hour12:false});
      line.innerHTML = '<span class="t">' + time + '</span><span class="m"></span>';
      line.querySelector('.m').textContent = msg;
      AUI.dom.logConsole.appendChild(line);
      AUI.dom.logConsole.scrollTop = AUI.dom.logConsole.scrollHeight;
    }
    function clearLog(){ AUI.dom.logConsole.innerHTML=''; AUI.dom.logConsole.classList.remove('show'); }
  
    
  /* ============== field spec per platform ============== */
    function buildFieldSpec(extract){
      const couponDefault = extract.platform === 'blueticket'
        ? (extract.coupon || AUI.$('cfgCoupon').value || '')
        : (extract.coupon || '');
      return [
        { id:'title',        label:'Título',                 required:true,  type:'text',     value:extract.title||'' },
        { id:'start_date',   label:'Data início',            required:true,  type:'text',     value:extract.start_date||'' },
        { id:'start_time',   label:'Hora início',            required:true,  type:'text',     value:extract.start_time||'23:00' },
        { id:'end_date',     label:'Data fim',               required:true,  type:'text',     value:extract.end_date||'' },
        { id:'end_time',     label:'Hora fim',               required:true,  type:'text',     value:extract.end_time||'07:00' },
        { id:'cover',        label:'Capa (imagem)',          required:extract.platform==='blueticket', type:'text', value:extract.cover||'' },
        { id:'video',        label:'Capa (vídeo)',           required:false, type:'text',     value:extract.video||'' },
        { id:'locName',      label:'Local (nome)',           required:false, type:'text', value:extract.locName||'' },
        { id:'locSlug',      label:'Local (slug WP)',        required:false, type:'text', value:extract.locSlug||'' },
        { id:'locId',        label:'Local (ID WP)',          required:false, type:'text', value:extract.locId||'' },
        { id:'bio',          label:'Bio / descrição',        required:false, type:'textarea', value:extract.bio||'' },
        { id:'ticket_price', label:'Preço (menor oferta)',   required:false, type:'text',     value:extract.ticket_price||'' },
        { id:'coupon',       label:'Cupom',                  required:false, type:'text',     value:couponDefault },
        { id:'ticketUrl',    label:'URL do ingresso',        required:true,  type:'text',     value:extract.ticketUrl||extract.sourceUrl||'' }
      ];
    }
  
    
  /* ============== render checklist ============== */
    function renderChecklist(extract){
      AUI.state.currentExtract = extract;
      AUI.dom.checklistPanel.style.display = 'block';
      AUI.dom.platformPill.textContent = extract.platform;
      AUI.dom.platformPill.className = 'tag imp-source-' + extract.platform;
  
      const spec = buildFieldSpec(extract);
      AUI.dom.ledGrid.innerHTML = '';
  
      spec.forEach(f => {
        const card = document.createElement('div');
        card.className = 'card ins imp-led-card';
        card.dataset.fieldId = f.id;
        card.dataset.required = f.required ? '1' : '0';
  
        const filled = !!(f.value && f.value.trim());
        let ledClass = filled ? 'on' : (f.required ? 'off' : 'optional-off');
        if (f.id === 'cover' && AUI.Cover) {
          ledClass = AUI.Cover.coverLedClass(extract);
        }
  
        card.innerHTML =
          '<div class="imp-led-head"><span class="imp-led ' + ledClass + '"></span>' +
          '<span class="imp-led-label">' + AUI.escapeHtml(f.label) + (f.required ? '<span class="req">*</span>' : '') + '</span></div>' +
          (f.type === 'textarea'
            ? '<textarea class="apollo-input" data-field="' + f.id + '">' + AUI.escapeHtml(f.value) + '</textarea>'
            : '<input class="apollo-input" type="text" data-field="' + f.id + '" value="' + AUI.escapeHtml(f.value) + '">');
  
        AUI.dom.ledGrid.appendChild(card);
      });
  
      AUI.dom.ledGrid.querySelectorAll('input,textarea').forEach(el => {
        el.addEventListener('input', () => {
          const card = el.closest('.imp-led-card');
          const required = card.dataset.required === '1';
          const filled = !!el.value.trim();
          const led = card.querySelector('.imp-led');
          led.className = 'imp-led ' + (filled ? 'on' : (required ? 'off' : 'optional-off'));
          evaluateGate();
        });
      });
  
      evaluateGate();
      if (AUI.Cover) {
        AUI.Cover.renderCoverBlock(extract);
      }
    }
  
    function evaluateGate(){
      const cards = Array.from(AUI.dom.ledGrid.querySelectorAll('.imp-led-card'));
      const requiredCards = cards.filter(c => c.dataset.required === '1');
      const allGreen = requiredCards.every(c => c.querySelector('.imp-led').classList.contains('on'));
      AUI.dom.btnConfirmRow.disabled = !allGreen;
      if (allGreen) {
        AUI.dom.gateMsg.textContent = 'todas as luzes obrigatórias verdes — pronto para importar';
        AUI.dom.gateMsg.className = 'imp-gate ready';
      } else {
        const missing = requiredCards.filter(c => !c.querySelector('.imp-led').classList.contains('on')).length;
        AUI.dom.gateMsg.textContent = missing + ' campo(s) obrigatório(s) pendente(s)';
        AUI.dom.gateMsg.className = 'imp-gate blocked';
      }
    }
  
    function collectChecklistValues(){
      const out = {};
      AUI.dom.ledGrid.querySelectorAll('[data-field]').forEach(el => { out[el.dataset.field] = el.value.trim(); });
      return out;
    }
  
    
  /* ============== results rendering ============== */
    function renderResults(){
      AUI.dom.countPill.textContent = AUI.state.rows.length;
      if (!AUI.state.rows.length) {
        AUI.dom.resultsList.innerHTML = '<div class="imp-empty">nenhum evento importado ainda — os dados ficam apenas em memória enquanto a página estiver aberta</div>';
        return;
      }
      AUI.dom.resultsList.innerHTML = '';
      AUI.state.rows.forEach(row => AUI.dom.resultsList.appendChild(renderRowCard(row)));
    }
  
    function statusTagClass(s){
      if (s === 'sent') return 'tag tag-success';
      if (s === 'failed') return 'tag tag-danger';
      if (s === 'sending') return 'tag tag-accent';
      return 'tag';
    }
  
    function renderRowCard(row){
      const v = row.values;
      const card = document.createElement('div');
      card.className = 'card ins imp-row';
      card.id = row.id;
  
      const thumbUrl = (row.importCover && row.importCover.local_url) || v.coverLocal || v.cover;
      const thumbStyle = thumbUrl ? ('background-image:url(\'' + String(thumbUrl).replace(/'/g,"\\'") + '\')') : '';
      const thumbLabel = thumbUrl ? '' : (v.video ? 'VÍDEO' : 'SEM CAPA');
      const localTag = row.importCover && row.importCover.attachment_id
        ? '<span class="tag tag-success">thumb #' + row.importCover.attachment_id + (row.importCover.reused ? ' ↺' : '') + '</span>'
        : '';
  
      card.innerHTML =
        '<div class="imp-row-top">' +
          '<div class="imp-thumb" style="' + thumbStyle + '">' + thumbLabel + '</div>' +
          '<div class="imp-row-info">' +
            '<div class="imp-row-title">' + AUI.escapeHtml(v.title || '(sem título)') + '</div>' +
            '<div class="imp-row-meta">' +
              '<span class="tag imp-source-' + row.platform + '">' + row.platform + '</span>' +
              (v.locName ? '<span class="tag">' + AUI.escapeHtml(v.locName) + '</span>' : '') +
              (v.coupon ? '<span class="tag tag-accent">cupom: ' + AUI.escapeHtml(v.coupon) + '</span>' : '') +
              localTag +
              '<span class="' + statusTagClass(row.status) + '" data-status-tag>' + statusLabel(row.status) + '</span>' +
            '</div>' +
          '</div>' +
        '</div>' +
        '<div class="imp-row-actions">' +
          '<button type="button" class="btn btn-ghost btn-sm" data-action="toggle"><i class="ri-eye-line"></i> Ver payload</button>' +
          '<button type="button" class="btn btn-primary btn-sm" data-action="send"><i class="ri-send-plane-line"></i> Enviar ao WordPress</button>' +
          '<button type="button" class="btn btn-ghost btn-sm imp-danger" data-action="remove"><i class="ri-delete-bin-line"></i> Remover</button>' +
        '</div>' +
        '<div class="imp-row-detail" data-detail>' +
          '<div class="imp-locid">' +
            '<label>loc_id</label>' +
            '<input class="apollo-input" type="text" data-locid placeholder="resolver ou preencher manualmente" value="' + AUI.escapeHtml(row.payload.loc_id || row.values.locId || '') + '">' +
            '<button type="button" class="btn btn-ghost btn-sm" data-action="resolve-loc">Resolver no WP</button>' +
          '</div>' +
          '<div class="imp-tabs">' +
            '<button type="button" class="imp-tab active" data-tab="json">JSON</button>' +
            '<button type="button" class="imp-tab" data-tab="php">PHP</button>' +
            '<button type="button" class="btn btn-ghost btn-sm" data-action="copy" style="margin-left:auto;"><i class="ri-clipboard-line"></i> Copiar</button>' +
          '</div>' +
          '<pre class="imp-payload" data-payload-view></pre>' +
        '</div>';
  
      card.querySelector('[data-action="toggle"]').addEventListener('click', () => {
        const detail = card.querySelector('[data-detail]');
        const willShow = !detail.classList.contains('show');
        detail.classList.toggle('show');
        if (willShow) renderPayloadView(card, row, 'json');
      });
      card.querySelectorAll('.imp-tab[data-tab]').forEach(btn => {
        btn.addEventListener('click', () => {
          card.querySelectorAll('.imp-tab[data-tab]').forEach(b => b.classList.remove('active'));
          btn.classList.add('active');
          renderPayloadView(card, row, btn.dataset.tab);
        });
      });
      card.querySelector('[data-action="copy"]').addEventListener('click', () => {
        const text = card.querySelector('[data-payload-view]').textContent;
        navigator.clipboard.writeText(text).then(() => flashCopy(card));
      });
      card.querySelector('[data-locid]').addEventListener('input', (e) => {
        const id = e.target.value.trim();
        row.payload.loc_id = id;
        row.values.locId = id;
      });
      card.querySelector('[data-action="resolve-loc"]').addEventListener('click', () => AUI.Api.resolveLoc(row, card));
      card.querySelector('[data-action="send"]').addEventListener('click', () => AUI.Api.sendToWordPress(row, card));
      card.querySelector('[data-action="remove"]').addEventListener('click', () => {
        AUI.state.rows = AUI.state.rows.filter(r => r.id !== row.id);
        renderResults();
      });
  
      return card;
    }
  
    function statusLabel(s){
      return { pending:'aguardando envio', sending:'enviando...', sent:'importado ✓', failed:'falhou — tentar novamente' }[s] || s;
    }
  
    function flashCopy(card){
      const btn = card.querySelector('[data-action="copy"]');
      const original = btn.innerHTML;
      btn.innerHTML = '<i class="ri-check-line"></i> Copiado';
      setTimeout(() => { btn.innerHTML = original; }, 1200);
    }
  
    function renderPayloadView(card, row, tab){
      const view = card.querySelector('[data-payload-view]');
      if (tab === 'json') {
        view.textContent = JSON.stringify(row.payload, null, 2);
      } else {
        view.textContent = toPhpArray(row.payload);
      }
    }
  
    function toPhpArray(obj, indent){
      indent = indent || 1;
      const pad = '    '.repeat(indent);
      const lines = [];
      Object.keys(obj).forEach(key => {
        const val = obj[key];
        if (val && typeof val === 'object' && !Array.isArray(val)) {
          lines.push(pad + "'" + key + "' => [");
          lines.push(toPhpArray(val, indent + 1));
          lines.push(pad + '],');
        } else {
          const phpVal = typeof val === 'string' ? "'" + String(val).replace(/'/g,"\\'") + "'" : val;
          lines.push(pad + "'" + key + "' => " + phpVal + ',');
        }
      });
      return lines.join('\n');
    }
  
    
  AUI.UI = {
    log: log,
    clearLog: clearLog,
    renderChecklist: renderChecklist,
    evaluateGate: evaluateGate,
    collectChecklistValues: collectChecklistValues,
    renderResults: renderResults,
    renderRowCard: renderRowCard,
    statusTagClass: statusTagClass,
    statusLabel: statusLabel
  };

})(window);
