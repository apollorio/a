        /* ═════════════════════════════════════════════════════════
           FORMULÁRIO DE EVENTO APOLLO · camada inteligente
           Tudo abaixo é SIMULAÇÃO — nenhum dado é persistido.
           ═════════════════════════════════════════════════════════ */

        // ---------- helpers ----------
        const $id = (id) => document.getElementById(id);
        const pad2 = (n) => String(n).padStart(2, '0');
        const toISO = (d) => d.getFullYear() + '-' + pad2(d.getMonth() + 1) + '-' + pad2(d.getDate());
        const fromISO = (s) => { const [y, m, d] = s.split('-').map(Number); return new Date(y, m - 1, d); };
        const MONTHS_S = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
        const MONTHS_L = ['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
        const DOWS = ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'];
        const fmtChip = (iso) => { const d = fromISO(iso); return DOWS[d.getDay()] + ', ' + d.getDate() + ' ' + MONTHS_S[d.getMonth()]; };
        const esc = (s) => String(s).replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
        const dtpMobile = window.matchMedia('(max-width: 719px)');

        let toastTimer = null;
        function toast(msg) {
            const t = $id('apxToast');
            t.textContent = msg;
            t.classList.add('show');
            clearTimeout(toastTimer);
            toastTimer = setTimeout(() => t.classList.remove('show'), 2600);
        }

        // ---------- fechar o painel de data ao clicar fora (mobile) ----------
        // (o drawer da sidebar é controlado pela camada de shell do Apollo)
        document.addEventListener('click', (e) => {
            if (dtp.active && dtpMobile.matches && !$id('dtp').contains(e.target)) dtpClose();
        });

        /* ═══ WIDGET UNIFICADO DE DATA E HORÁRIO ═══
           Calendário: conceito do unite-02 · fluxo de horário (atalhos + passos de ±30min): unite-01.
           100% re-estilizado com os tokens do tema Apollo. */
        const dtp = {
            start: { date: null, time: '23:00' },
            end:   { date: null, time: '07:00' },
            active: null,               // 'start' | 'end' | null (painel fechado)
            view: { y: 0, m: 0 },       // mês exibido no calendário
            endTouched: false           // depois que o usuário edita o TÉRMINO, para de sincronizar
        };
        const DTP_QUICK = {
            start: ['21:00', '22:00', '23:00', '00:00'],
            end:   ['05:00', '06:00', '07:00', '08:00']
        };

        function dtpInitDefaults(baseIso) {
            let base;
            if (baseIso) { base = fromISO(baseIso); }
            else { base = new Date(); base.setDate(base.getDate() + 1); } // padrão: amanhã à noite
            dtp.start.date = toISO(base);
            dtp.start.time = '23:00';
            const e = new Date(base); e.setDate(e.getDate() + 1);        // padrão Rio: termina na manhã seguinte
            dtp.end.date = toISO(e);
            dtp.end.time = '07:00';
            dtp.endTouched = false;
            dtp.active = null;
            $id('dtpPanel').classList.remove('is-open');
            dtpUpdate();
        }

        function dtpOpen(target) {
            if (dtp.active === target) { dtpClose(); return; }
            dtp.active = target;
            const ref = fromISO(dtp[target].date);
            dtp.view = { y: ref.getFullYear(), m: ref.getMonth() };
            $id('dtpPanel').classList.add('is-open');
            dtpRenderCal();
            dtpRenderTime();
            dtpUpdate();
        }

        function dtpClose() {
            dtp.active = null;
            $id('dtpPanel').classList.remove('is-open');
            dtpUpdate();
        }

        function dtpNav(dir) {
            dtp.view.m += dir;
            if (dtp.view.m < 0)  { dtp.view.m = 11; dtp.view.y--; }
            if (dtp.view.m > 11) { dtp.view.m = 0;  dtp.view.y++; }
            dtpRenderCal();
        }

        function dtpRenderCal() {
            const { y, m } = dtp.view;
            $id('dtpCalTitle').textContent = MONTHS_L[m] + ' ' + y;
            const first = new Date(y, m, 1).getDay();
            const dim = new Date(y, m + 1, 0).getDate();
            const todayIso = toISO(new Date());
            // INÍCIO não pode ficar no passado; TÉRMINO não pode ficar antes do início
            const minIso = dtp.active === 'end' ? dtp.start.date : todayIso;
            const sel = dtp.active ? dtp[dtp.active].date : null;
            const other = dtp.active === 'start' ? dtp.end.date : dtp.start.date;

            let html = DOWS.map(d => `<div class="dtp-dow">${d}</div>`).join('');
            for (let i = 0; i < first; i++) html += `<div class="dtp-day is-out"></div>`;
            for (let d = 1; d <= dim; d++) {
                const iso = y + '-' + pad2(m + 1) + '-' + pad2(d);
                const cls = ['dtp-day'];
                if (iso < minIso) cls.push('is-disabled');
                if (iso === todayIso) cls.push('is-today');
                if (iso === sel) cls.push('is-selected');
                else if (iso === other) cls.push('is-other');
                html += `<div class="${cls.join(' ')}" onclick="dtpPick('${iso}')">${d}</div>`;
            }
            $id('dtpGrid').innerHTML = html;
        }

        function dtpRenderTime() {
            const t = dtp.active || 'start';
            $id('dtpTimeLbl').textContent = t === 'start' ? 'Horário de início' : 'Horário de término';
            $id('dtpTimeInput').value = dtp[t].time;
            $id('dtpQuick').innerHTML = DTP_QUICK[t].map(q =>
                `<button type="button" class="dtp-q ${q === dtp[t].time ? 'is-on' : ''}" onclick="dtpSetTime('${q}')">${q}</button>`
            ).join('');
            $id('dtpHint').textContent = t === 'start'
                ? 'Padrão Rio: 23:00 → 07:00 do dia seguinte. O término preenche sozinho — toque em Termina para ajustar.'
                : 'A virada de noite é automática — o término já cai na manhã seguinte.';
        }

        function dtpPick(iso) {
            const t = dtp.active || 'start';
            dtp[t].date = iso;
            if (t === 'start') {
                if (!dtp.endTouched) {
                    const e = fromISO(iso); e.setDate(e.getDate() + 1);
                    dtp.end.date = toISO(e);              // TÉRMINO = INÍCIO + 1 dia (editável)
                } else if (dtp.end.date < iso) {
                    const e = fromISO(iso);
                    if (dtp.end.time <= dtp.start.time) e.setDate(e.getDate() + 1);
                    dtp.end.date = toISO(e);
                    toast('Data de término ajustada para depois do início');
                }
            } else {
                dtp.endTouched = true;
            }
            dtpRenderCal();
            dtpUpdate();
            // mobile: qualquer data selecionada = painel fecha
            if (dtpMobile.matches) dtpClose();
        }

        function dtpSetTime(v) {
            const t = dtp.active || 'start';
            dtp[t].time = v;
            if (t === 'end') dtp.endTouched = true;
            dtpRenderTime();
            dtpUpdate();
        }

        function dtpTimeFromInput(v) {
            if (/^\d{2}:\d{2}$/.test(v)) dtpSetTime(v);
        }

        function dtpStep(mins) {
            const t = dtp.active || 'start';
            const [h, m] = dtp[t].time.split(':').map(Number);
            let total = ((h * 60 + m + mins) % 1440 + 1440) % 1440;   // dá a volta na meia-noite
            dtpSetTime(pad2(Math.floor(total / 60)) + ':' + pad2(total % 60));
        }

        function dtpUpdate() {
            $id('dtpStartDate').textContent = dtp.start.date ? fmtChip(dtp.start.date) : '—';
            $id('dtpStartTime').textContent = dtp.start.time;
            $id('dtpEndDate').textContent = dtp.end.date ? fmtChip(dtp.end.date) : '—';
            $id('dtpEndTime').textContent = dtp.end.time;
            $id('dtpChipStart').classList.toggle('is-active', dtp.active === 'start');
            $id('dtpChipEnd').classList.toggle('is-active', dtp.active === 'end');

            // espelha nos inputs ocultos clássicos (start_date / start_time / end_date / end_time)
            $id('start_date').value = dtp.start.date || '';
            $id('start_time').value = dtp.start.time;
            $id('end_date').value = dtp.end.date || '';
            $id('end_time').value = dtp.end.time;

            // selo de duração
            const durEl = $id('dtpDur');
            if (dtp.start.date && dtp.end.date) {
                const s = new Date(dtp.start.date + 'T' + dtp.start.time);
                const e = new Date(dtp.end.date + 'T' + dtp.end.time);
                const h = (e - s) / 36e5;
                if (h <= 0) {
                    durEl.textContent = 'TERMINA ANTES DO INÍCIO';
                    durEl.classList.add('is-bad');
                } else {
                    const dayDiff = Math.round((fromISO(dtp.end.date) - fromISO(dtp.start.date)) / 864e5);
                    durEl.textContent = (h % 1 ? h.toFixed(1) : h) + 'H' + (dayDiff > 0 ? ' · +' + dayDiff + 'D' : '');
                    durEl.classList.remove('is-bad');
                }
            }
            scheduleWeather();
            renderReceipt();
        }

        /* ═══ PREVISÃO DO TEMPO (Open-Meteo · sempre America/Sao_Paulo) ═══
           api.open-meteo.com is NOT in the site's connect-src CSP, so every call
           was refused by the browser and logged twice — and scheduleWeather()
           fires on each venue blur/date change, flooding the console.
           Opt-in only: set APOLLO_EVENT_FORM.weatherEnabled = true AND add
           https://api.open-meteo.com to connect-src before turning this on. */
        const WX_HOST = 'https://api.open-meteo.com';
        let wxTimer = null;
        let wxDisabled = !((window.APOLLO_EVENT_FORM || {}).weatherEnabled);
        const wxCache = {};
        function scheduleWeather() {
            if (wxDisabled) { return; }
            clearTimeout(wxTimer);
            wxTimer = setTimeout(showWeatherForecast, 450);
        }

        function wxIcon(code, hour) {
            const night = hour >= 18 || hour < 6;
            if (code === 0) return night ? 'ri-moon-clear-line' : 'ri-sun-line';
            if (code <= 2) return night ? 'ri-moon-cloudy-line' : 'ri-sun-cloudy-line';
            if (code === 3) return 'ri-cloudy-2-line';
            if (code === 45 || code === 48) return 'ri-mist-line';
            if (code <= 57) return 'ri-drizzle-line';
            if (code <= 67) return 'ri-rainy-line';
            if (code <= 82) return 'ri-heavy-showers-line';
            if (code >= 95) return 'ri-thunderstorms-line';
            return 'ri-cloudy-line';
        }
        function wxLabel(code) {
            if (code === 0) return 'Céu limpo';
            if (code <= 2) return 'Parcialmente nublado';
            if (code === 3) return 'Nublado';
            if (code === 45 || code === 48) return 'Névoa';
            if (code <= 57) return 'Garoa';
            if (code <= 67) return 'Chuva';
            if (code <= 82) return 'Pancadas de chuva';
            if (code >= 95) return 'Tempestade';
            return 'Nublado';
        }
        const wxNote = (icon, msg) => `<div class="wx-note"><i class="${icon}"></i> ${msg}</div>`;

        async function showWeatherForecast() {
            const box = $id('weather-preview');
            if (!box) { return; }
            if (wxDisabled) {
                box.innerHTML = wxNote('ri-cloud-off-line', 'Previsão do tempo indisponível nesta instalação.');
                return;
            }
            if (!dtp.start.date) { box.innerHTML = wxNote('ri-calendar-line', 'Escolha uma data de início para carregar a previsão.'); return; }

            const lat = parseFloat($id('lat').value) || -22.9068;
            const lon = parseFloat($id('lon').value) || -43.1729;
            const daysAhead = Math.round((fromISO(dtp.start.date) - fromISO(toISO(new Date()))) / 864e5);

            if (daysAhead < 0) { box.innerHTML = wxNote('ri-history-line', 'A data de início está no passado — sem previsão.'); return; }
            if (daysAhead > 15) {
                box.innerHTML = wxNote('ri-calendar-todo-line',
                    `A previsão abre ~16 dias antes do evento. Volte mais perto de ${fmtChip(dtp.start.date)}.`);
                return;
            }

            box.innerHTML = wxNote('ri-loader-4-line', 'Carregando previsão…');
            const key = lat.toFixed(3) + ',' + lon.toFixed(3);
            try {
                let data = wxCache[key];
                if (!data) {
                    const res = await fetch(`${WX_HOST}/v1/forecast?latitude=${lat}&longitude=${lon}&hourly=temperature_2m,apparent_temperature,precipitation_probability,weathercode,windspeed_10m,relativehumidity_2m&forecast_days=16&timezone=America%2FSao_Paulo`);
                    data = await res.json();
                    wxCache[key] = data;
                }
                const startHH = dtp.start.time.slice(0, 2);
                const idx = data.hourly.time.indexOf(dtp.start.date + 'T' + startHH + ':00');
                if (idx === -1) { box.innerHTML = wxNote('ri-cloud-off-line', 'Previsão ainda indisponível para este horário.'); return; }

                const H = data.hourly;
                const hourNum = parseInt(startHH, 10);

                // faixa horária ao longo do evento (limitada)
                let endIdx = data.hourly.time.indexOf(dtp.end.date + 'T' + dtp.end.time.slice(0, 2) + ':00');
                if (endIdx <= idx) endIdx = Math.min(idx + 8, H.time.length - 1);
                const cells = [];
                for (let i = idx; i <= Math.min(endIdx, idx + 9); i++) {
                    const hh = H.time[i].slice(11, 13);
                    const p = H.precipitation_probability[i];
                    cells.push(`
                        <div class="wx-cell">
                            <div class="h">${hh}h</div>
                            <i class="${wxIcon(H.weathercode[i], parseInt(hh, 10))}"></i>
                            <div class="t">${Math.round(H.temperature_2m[i])}°</div>
                            <div class="r ${p >= 50 ? 'hi' : ''}">${p}%</div>
                        </div>`);
                }

                box.innerHTML = `
                    <div class="wx-main">
                        <div class="wx-ico"><i class="${wxIcon(H.weathercode[idx], hourNum)}"></i></div>
                        <div>
                            <div class="wx-temp">${Math.round(H.temperature_2m[idx])}°C</div>
                            <div class="wx-desc">${wxLabel(H.weathercode[idx])}<br>${fmtChip(dtp.start.date)} às ${dtp.start.time}</div>
                        </div>
                    </div>
                    <div class="wx-meta">
                        <div><b>${Math.round(H.apparent_temperature[idx])}°C</b>Sensação</div>
                        <div><b>${H.precipitation_probability[idx]}%</b>Chuva</div>
                        <div><b>${Math.round(H.windspeed_10m[idx])} km/h</b>Vento</div>
                        <div><b>${H.relativehumidity_2m[idx]}%</b>Umidade</div>
                    </div>
                    <div class="wx-strip">${cells.join('')}</div>`;
            } catch (err) {
                /* One failure is enough — CSP refusals never recover, and
                   retrying just repeats the violation on every interaction. */
                wxDisabled = true;
                clearTimeout(wxTimer);
                box.innerHTML = wxNote('ri-cloud-off-line', 'Previsão do tempo indisponível nesta instalação.');
            }
        }

        /* ═══ GEOCODIFICAÇÃO (OSM Nominatim) ═══ */
        async function nominatim(addr) {
            const q = /rio de janeiro/i.test(addr) ? addr : addr + ', Rio de Janeiro, Brazil';
            const r = await fetch(`https://nominatim.openstreetmap.org/search?format=json&limit=1&q=${encodeURIComponent(q)}`);
            const d = await r.json();
            return d[0] ? { lat: (+d[0].lat).toFixed(5), lon: (+d[0].lon).toFixed(5) } : null;
        }

        // busca do formulário: grava direto nos campos ocultos que alimentam o clima
        async function geocodeAddress(addr) {
            if (!addr || addr.trim().length < 3) return;
            try {
                const hit = await nominatim(addr);
                if (hit) {
                    $id('lat').value = hit.lat;
                    $id('lon').value = hit.lon;
                    toast('Localização encontrada — previsão atualizada');
                    scheduleWeather();
                }
            } catch (err) { /* offline: mantém as coordenadas anteriores */ }
        }

        // modal: grava só nos campos do modal (Cancelar descarta; Cadastrar aplica)
        async function geocodeVenueModal(addr) {
            if (!addr || addr.trim().length < 3) return;
            try {
                const hit = await nominatim(addr);
                if (hit) {
                    $id('vm-lat').value = hit.lat;
                    $id('vm-lon').value = hit.lon;
                    toast('Latitude/longitude preenchidas via OpenStreetMap');
                } else {
                    $id('vm-lat').value = '';
                    $id('vm-lon').value = '';
                    toast('Endereço não encontrado — verifique e tente de novo');
                }
            } catch (err) { toast('Sem conexão com o OpenStreetMap'); }
        }

        async function registerVenue() {
            const name = $id('vm-name').value.trim();
            const address = $id('vm-address').value.trim();
            if (!name || !address) { toast('Preencha nome e endereço do local'); return; }
            const imgs = ['vm-img1', 'vm-img2', 'vm-img3'].map(i => $id(i).value.trim()).filter(Boolean);
            const lat = $id('vm-lat').value || $id('lat').value;
            const lon = $id('vm-lon').value || $id('lon').value;

            /* Persist as a real "loc" CPT via REST (same contract as FrontendForm.php's
             * quick-add modal) so the venue actually exists beyond this form session. */
            const CFG = window.APOLLO_EVENT_FORM || {};
            const btn = document.querySelector('#venueModal .btn-primary');
            const originalBtnHTML = btn ? btn.innerHTML : '';
            if (btn) { btn.disabled = true; btn.innerHTML = 'Cadastrando...'; }

            let newId = null;
            let failMsg = '';
            if (CFG.locsUrl) {
                try {
                    const venuePayload = { title: name, address: address };
                    if (lat && !isNaN(parseFloat(lat))) venuePayload.lat = parseFloat(lat);
                    if (lon && !isNaN(parseFloat(lon))) venuePayload.lng = parseFloat(lon);
                    const res = await fetch(CFG.locsUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': CFG.nonce || '' },
                        credentials: 'same-origin',
                        body: JSON.stringify(venuePayload)
                    });
                    const result = await res.json().catch(() => ({}));
                    if (res.ok && result && result.id) {
                        newId = result.id;
                    } else {
                        /* Never swallow this. Without an id the loc is NOT linked:
                         * _event_loc_id stays empty and the event saves with no
                         * venue, which used to look like a success to the user. */
                        failMsg = (result && result.message) ? String(result.message) : ('HTTP ' + res.status);
                        console.error('[apollo-events] loc create failed:', res.status, result);
                    }
                } catch (err) {
                    failMsg = String((err && err.message) || err);
                    console.error('[apollo-events] loc create failed:', err);
                }
            }

            applyVenue({
                id: newId,
                name, address, lat, lon,
                images: imgs.length ? imgs : (knownVenues()[0] ? knownVenues()[0].images : [])
            });
            $id('ev-venue-search').value = name;
            closeModal('venueModal');
            ['vm-name', 'vm-address', 'vm-img1', 'vm-img2', 'vm-img3', 'vm-lat', 'vm-lon'].forEach(i => $id(i).value = '');
            if (btn) { btn.disabled = false; btn.innerHTML = originalBtnHTML; }
            toast(newId
                ? 'Local cadastrado na Apollo e vinculado ao evento'
                : ('Não foi possível cadastrar o local' + (failMsg ? ' — ' + failMsg : '') + '. O evento será salvo SEM local.'));
        }

        /* ═══ LOCAL (locais conhecidos da simulação + geocode livre) ═══ */
        function knownVenues() {
            const seen = {};
            return (window.APOLLO_EVENTS || []).map(e => e.venue).filter(v => {
                if (seen[v.name]) return false;
                seen[v.name] = true;
                return true;
            });
        }

        function applyVenue(v) {
            $id('venueName').textContent = v.name;
            $id('venueAddress').textContent = v.address;
            $id('lat').value = v.lat;
            $id('lon').value = v.lon;
            $id('venueImages').innerHTML = v.images.map((src, i) =>
                `<div class="frame"><img src="${esc(src)}" alt="Local ${i + 1}"></div>`).join('');
            /* Persist FK for admin column event_loc + REST save (_event_loc_id). */
            const locHidden = $id('ev-loc-id');
            if (locHidden) {
                locHidden.value = (v && v.id) ? String(v.id) : '';
            }
            const block = $id('venueInfoBlock');
            if (block) block.style.display = '';
            scheduleWeather();
            renderReceipt();
        }

        function showVenueInfo() {
            const q = $id('ev-venue-search').value.trim().toLowerCase();
            if (q.length < 3) return;
            const hit = knownVenues().find(v => v.name.toLowerCase().includes(q));
            if (hit) applyVenue(hit);
        }

        function venueBlur() {
            const q = $id('ev-venue-search').value.trim();
            if (!q) return;
            const hit = knownVenues().find(v => v.name.toLowerCase().includes(q.toLowerCase()));
            if (hit) { applyVenue(hit); return; }
            // local desconhecido → geocodifica o texto digitado para o clima acompanhar
            geocodeAddress(q);
        }

        /* ═══ VALIDAÇÃO DE URLs DE MÍDIA ═══ */
        const isVideo = (u) => /youtube\.com|youtu\.be|\.(mp4|webm|mov)(\?|$)/i.test(u);
        const isAudio = (u) => /soundcloud\.com|spotify\.com/i.test(u);

        function markInvalid(el, msg) {
            el.classList.add('is-invalid');
            let e = el.parentElement.querySelector('.field-err');
            if (!e) { e = document.createElement('div'); e.className = 'field-err'; el.parentElement.appendChild(e); }
            e.textContent = msg;
        }
        function clearInvalid(el) {
            el.classList.remove('is-invalid');
            const e = el.parentElement.querySelector('.field-err');
            if (e) e.remove();
        }
        function validateVideoField() {
            const el = $id('ev-video');
            const raw = (el && el.value || '').trim();
            const accepted = !raw || isVideo(raw);
            // #region agent log
            if (window.__apolloAgentDbg) {
                var ext = '';
                var m = raw.match(/\.([a-z0-9]+)(?:\?|$)/i);
                if (m) ext = m[1].toLowerCase();
                else if (/youtube\.com|youtu\.be/i.test(raw)) ext = 'youtube';
                window.__apolloAgentDbg({
                    runId: 'video-url',
                    hypothesisId: 'V',
                    location: 'create-form.js:validateVideoField',
                    message: accepted ? 'video accepted' : 'video rejected',
                    data: { accepted: accepted, extension: ext, empty: !raw }
                });
            }
            // #endregion
            if (raw && !accepted) { markInvalid(el, 'Use um link do YouTube ou arquivo .mp4 / .webm / .mov'); return false; }
            clearInvalid(el); return true;
        }
        function validateAudioField() {
            const el = $id('ev-audio');
            if (el.value.trim() && !isAudio(el.value)) { markInvalid(el, 'Use um link do Spotify ou SoundCloud'); return false; }
            clearInvalid(el); return true;
        }

        function validateForm() {
            let ok = true;
            const title = $id('ev-title');
            if (!title.value.trim()) { markInvalid(title, 'O nome do evento é obrigatório'); ok = false; }
            else clearInvalid(title);

            const s = new Date(dtp.start.date + 'T' + dtp.start.time);
            const e = new Date(dtp.end.date + 'T' + dtp.end.time);
            if (!(e > s)) { $id('dtpDur').classList.add('is-bad'); ok = false; }

            if (!validateVideoField()) ok = false;
            if (!validateAudioField()) ok = false;
            return ok;
        }

        /* ═══ MODAIS (comportamento original) ═══ */
        function openModal(id) { $id(id).classList.add('is-open'); if (id === 'djModal') resetDJModal(); }
        function closeModal(id) { $id(id).classList.remove('is-open'); }
        document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
            backdrop.addEventListener('click', function (e) {
                if (e.target === this) this.classList.remove('is-open');
            });
        });

        /* ═══ INGRESSOS E LISTAS — primary row + extras repeater ═══ */
        const AXB_STYLES = {
            main:  { label: 'Principal' },
            soft:  { label: 'Early Bird' },
            lista: { label: 'Lista' },
            fem:   { label: 'Lista Fem' },
            cta:   { label: 'CTA Lista' }
        };
        let axbButtons = [];
        let axbEditIndex = -1;

        function axbSync() {
            const hidden = $id('ev-access-buttons');
            if (hidden) hidden.value = JSON.stringify(axbButtons);
        }

        function axbRenderList() {
            const box = $id('axbList');
            if (!box) return;
            if (!axbButtons.length) {
                box.innerHTML = '';
                return;
            }
            /* Extra rows only — each has kind-icon + title + URL + ×.
               Primary ingresso (.axb-primary) never gets a remove control. */
            box.innerHTML = axbButtons.map((b, i) => {
                const kind = b.kind === 'lista' ? 'lista' : 'ticket';
                const icon = axbRenderIcon(kind);
                return `<div class="axb-row" data-i="${i}" data-kind="${kind}">
                    <span class="axb-row-ico" title="${kind === 'lista' ? 'Lista' : 'Ingresso'}" aria-hidden="true"><i class="${icon}"></i></span>
                    <input type="text" class="apollo-input" data-axb-field="label" data-i="${i}" value="${esc(b.label || '')}" placeholder="${kind === 'lista' ? 'Nome da lista' : 'Nome do ticket'}" aria-label="Nome">
                    <input type="url" class="apollo-input" data-axb-field="url" data-i="${i}" value="${esc(b.url || '')}" placeholder="https://..." aria-label="Link">
                    <button type="button" class="axb-del" data-axb-del="${i}" aria-label="Remover acesso"><i class="ri-close-line" aria-hidden="true"></i></button>
                </div>`;
            }).join('');
        }

        function axbOnListInput(e) {
            const t = e.target;
            if (!t || !t.getAttribute) return;
            const field = t.getAttribute('data-axb-field');
            if (!field) return;
            const i = parseInt(t.getAttribute('data-i'), 10);
            if (!Number.isFinite(i) || !axbButtons[i]) return;
            axbButtons[i][field] = t.value;
            axbSync();
        }

        function axbOnListClick(e) {
            const btn = e.target && e.target.closest ? e.target.closest('[data-axb-del]') : null;
            if (!btn) return;
            e.preventDefault();
            const i = parseInt(btn.getAttribute('data-axb-del'), 10);
            if (!Number.isFinite(i) || i < 0) return;
            axbButtons.splice(i, 1);
            axbSync();
            axbRenderList();
            if (typeof renderReceipt === 'function') renderReceipt();
        }

        function setAccessButtons(list) {
            axbButtons = Array.isArray(list) ? list.map(b => ({
                kind: b.kind === 'lista' ? 'lista' : 'ticket',
                style: AXB_STYLES[b.style] ? b.style : 'soft',
                label: b.label || b.name || '',
                sub: b.sub || '',
                url: b.url || ''
            })) : [];
            axbSync();
            axbRenderList();
        }

        function axbToggleType() {
            const hidden = $id('axb-kind');
            const toggle = $id('axbTypeToggle');
            const icon = $id('axbTypeIcon');
            if (!hidden || !toggle) return;
            const next = hidden.value === 'lista' ? 'ticket' : 'lista';
            hidden.value = next;
            toggle.classList.toggle('is-lista', next === 'lista');
            toggle.setAttribute('aria-checked', next === 'lista' ? 'true' : 'false');
            if (icon) icon.className = next === 'lista' ? 'ri-survey-fill' : 'ri-coupon-2-fill';
            axbRenderPreview();
        }

        /* Icon registry — keep in sync with form-access.php + apollo_event_build_access_payload:
           kind "lista"  → ALWAYS ri-vip-line
           kind "ticket" → ALWAYS ri-ticket-2-line
           style is chrome only (soft/main/fem/cta) and must never change the icon. */
        function axbRenderIcon(kind) {
            return kind === 'lista' ? 'ri-vip-line' : 'ri-ticket-2-line';
        }

        function axbRenderPreview() {
            const wrap = $id('axbPreviewWrap');
            if (!wrap) return;
            const kind = ($id('axb-kind') && $id('axb-kind').value) || 'ticket';
            const style = ($id('axb-style') && $id('axb-style').value) || 'soft';
            const label = (($id('axb-label') && $id('axb-label').value) || '').trim() || (kind === 'lista' ? 'Nome da lista' : 'Nome do ticket');
            const sub = (($id('axb-sub') && $id('axb-sub').value) || '').trim();
            const icon = axbRenderIcon(kind);
            if (style === 'cta') {
                wrap.innerHTML = `<div class="axb-preview-btn axb-cta">
                    <i class="${icon}"></i> <span>${esc(label)}</span>
                </div>`;
                return;
            }
            wrap.innerHTML = `<div class="axb-preview-btn axb-${esc(style)}">
                <span class="axb-p-ico"><i class="${icon}"></i></span>
                <span class="axb-p-info">
                    <span class="axb-p-name">${esc(label)}</span>
                    ${sub ? '<span class="axb-p-sub">' + esc(sub) + '</span>' : ''}
                </span>
                <i class="ri-arrow-right-up-line axb-p-arr"></i>
            </div>`;
        }

        /* Modal is add-only — extras edit inline (title + URL) with × delete. */
        function openAccessBtnModal() {
            axbEditIndex = -1;
            const btn = { kind: 'ticket', style: 'soft', label: '', sub: '', url: '' };

            if ($id('axbModalTitle')) $id('axbModalTitle').textContent = 'Adicionar outro acesso';
            $id('axb-kind').value = btn.kind;
            const toggle = $id('axbTypeToggle');
            const icon = $id('axbTypeIcon');
            if (toggle) { toggle.classList.toggle('is-lista', false); toggle.setAttribute('aria-checked', 'false'); }
            if (icon) icon.className = 'ri-coupon-2-fill';
            $id('axb-style').value = btn.style;
            $id('axb-label').value = btn.label;
            $id('axb-sub').value = btn.sub;
            $id('axb-url').value = btn.url;

            axbRenderPreview();
            openModal('accessBtnModal');
        }

        function axbSave() {
            const kind = ($id('axb-kind') && $id('axb-kind').value) || 'ticket';
            const style = ($id('axb-style') && $id('axb-style').value) || 'soft';
            const label = (($id('axb-label') && $id('axb-label').value) || '').trim();
            const sub = (($id('axb-sub') && $id('axb-sub').value) || '').trim();
            const url = (($id('axb-url') && $id('axb-url').value) || '').trim();

            if (!label) {
                toast('Preencha o nome do acesso');
                return;
            }

            axbButtons.push({ kind, style, label, sub, url });

            axbSync();
            axbRenderList();
            closeModal('accessBtnModal');
            if (typeof renderReceipt === 'function') renderReceipt();
        }

        function axbDelete() {
            /* Kept for modal footer hook; extras delete via list ×. */
            closeModal('accessBtnModal');
        }

        /* ═══ EQUIPE DO EVENTO (multi-select · remote /users · equal edit) ═══ */
        let coauthorSelected = new Set();
        const coauthorCache = {};
        let coauthorLastResults = [];
        let coauthorSearchTimer = null;
        let coauthorSearchToken = 0;

        function getTeamMax() {
            const n = window.APOLLO_EVENT_FORM && window.APOLLO_EVENT_FORM.maxTeam;
            return (typeof n === 'number' && n > 0) ? n : 20;
        }
        function getMeId() {
            return Number((window.APOLLO_EVENT_FORM && window.APOLLO_EVENT_FORM.user && window.APOLLO_EVENT_FORM.user.id) || 0);
        }
        function i18nTeam(key, fallback) {
            const i18n = window.APOLLO_EVENT_FORM && window.APOLLO_EVENT_FORM.i18n;
            return (i18n && i18n[key]) || fallback;
        }
        function setCoauthorStatus(msg) {
            const el = $id('coauthorStatus');
            if (!el) return;
            el.textContent = msg || '';
            el.classList.toggle('is-visible', !!msg);
        }
        function rememberCoauthor(u) {
            if (!u || !u.id) return;
            coauthorCache[Number(u.id)] = {
                id: Number(u.id),
                name: u.name || u.display_name || ('#' + u.id),
                login: u.login || u.username || '',
                avatar: u.avatar || u.avatar_url || ''
            };
        }
        function getSelectedCoauthors() {
            return Array.from(coauthorSelected).map(Number).filter((n) => n > 0);
        }
        function syncCoauthorsHidden() {
            setVal('ev-coauthors', JSON.stringify(getSelectedCoauthors()));
        }
        function setCoauthors(ids) {
            coauthorSelected = new Set((ids || []).map(Number).filter((n) => n > 0 && n !== getMeId()));
            (window.APOLLO_EVENT_FORM && window.APOLLO_EVENT_FORM.teamSeed || []).forEach(rememberCoauthor);
            syncCoauthorsHidden();
            renderCoauthorsUI();
        }
        function toggleCoauthor(id) {
            id = Number(id);
            if (!id || id === getMeId()) return;
            if (coauthorSelected.has(id)) {
                coauthorSelected.delete(id);
                setCoauthorStatus('');
            } else {
                if (coauthorSelected.size >= getTeamMax()) {
                    setCoauthorStatus(i18nTeam('teamLimit', 'Limite da equipe atingido'));
                    return;
                }
                const fromResults = coauthorLastResults.find((u) => Number(u.id) === id);
                if (fromResults) rememberCoauthor(fromResults);
                coauthorSelected.add(id);
                setCoauthorStatus('');
            }
            syncCoauthorsHidden();
            renderCoauthorsUI($id('coauthorSearch') ? $id('coauthorSearch').value : '');
            if (typeof renderReceipt === 'function') renderReceipt();
        }
        function renderCoauthorsUI(filter) {
            const chips = $id('coauthorChips');
            const list = $id('coauthorList');
            if (!chips || !list) return;

            const locked = chips.querySelector('.ax-coauthors-chip.is-locked');
            Array.prototype.slice.call(chips.querySelectorAll('.ax-coauthors-chip:not(.is-locked)'))
                .forEach((el) => el.remove());

            getSelectedCoauthors().forEach((id) => {
                const u = coauthorCache[id] || { id, name: '#' + id, avatar: '' };
                const chip = document.createElement('span');
                chip.className = 'ax-coauthors-chip';
                chip.setAttribute('data-id', String(u.id));
                chip.innerHTML = `${u.avatar ? `<img src="${esc(u.avatar)}" alt="">` : '<i class="ri-user-line"></i>'}
                    <span>${esc(u.name)}</span>
                    <button type="button" aria-label="Remover">&times;</button>`;
                const btn = chip.querySelector('button');
                if (btn) btn.addEventListener('click', () => toggleCoauthor(u.id));
                chips.appendChild(chip);
            });
            if (locked && chips.firstChild !== locked) {
                chips.insertBefore(locked, chips.firstChild);
            }

            // Remote search mode: list only shows search results (not full catalog).
            const q = String(filter || '').trim();
            if (q.length < 2) {
                if (!coauthorLastResults.length) {
                    list.innerHTML = '';
                }
                return;
            }

            const rows = coauthorLastResults.slice(0, 5);
            if (!rows.length) {
                list.innerHTML = `<div class="ax-coauthors-empty">${esc(i18nTeam('noUsers', 'Ninguém encontrado'))}</div>`;
                return;
            }
            list.innerHTML = rows.map((u) => {
                const on = coauthorSelected.has(Number(u.id)) ? ' is-on' : '';
                const sub = u.login ? '@' + u.login : '';
                return `<button type="button" class="ax-coauthors-opt${on}" role="option" aria-selected="${on ? 'true' : 'false'}" data-id="${u.id}">
                    ${u.avatar ? `<img src="${esc(u.avatar)}" alt="">` : '<i class="ri-user-line" style="font-size:20px;color:var(--muted)"></i>'}
                    <span class="ax-ca-meta">
                        <span class="ax-ca-name">${esc(u.name)}</span>
                        <span class="ax-ca-sub">${esc(sub)}</span>
                    </span>
                    <i class="ri-check-line ax-ca-check"></i>
                </button>`;
            }).join('');

            Array.prototype.slice.call(list.querySelectorAll('.ax-coauthors-opt')).forEach((btn) => {
                btn.addEventListener('click', () => toggleCoauthor(btn.getAttribute('data-id')));
            });
        }
        function searchTeamUsers(query) {
            const token = ++coauthorSearchToken;
            const cfg = window.APOLLO_EVENT_FORM || {};
            const base = cfg.usersUrl || '';
            if (!base) {
                setCoauthorStatus(i18nTeam('searchFail', 'Não foi possível buscar agora'));
                return;
            }
            setCoauthorStatus(i18nTeam('searching', 'Buscando…'));
            const sep = base.indexOf('?') === -1 ? '?' : '&';
            const url = base + sep + 'per_page=20&search=' + encodeURIComponent(query);
            fetch(url, {
                headers: { 'X-WP-Nonce': cfg.nonce || '' },
                credentials: 'same-origin'
            }).then((res) => res.ok ? res.json() : { users: [] })
            .then((data) => {
                if (token !== coauthorSearchToken) return;
                const me = getMeId();
                coauthorLastResults = (data && data.users ? data.users : []).map((u) => ({
                    id: parseInt(u.id, 10),
                    name: u.display_name || u.username || ('#' + u.id),
                    login: u.username || '',
                    avatar: u.avatar_url || ''
                })).filter((u) => u.id && u.id !== me);
                coauthorLastResults.forEach(rememberCoauthor);
                renderCoauthorsUI(query);
                setCoauthorStatus(coauthorLastResults.length ? '' : i18nTeam('noUsers', 'Ninguém encontrado'));
            }).catch(() => {
                if (token !== coauthorSearchToken) return;
                coauthorLastResults = [];
                renderCoauthorsUI(query);
                setCoauthorStatus(i18nTeam('searchFail', 'Não foi possível buscar agora'));
            });
        }
        window.setCoauthors = setCoauthors;
        window.toggleCoauthor = toggleCoauthor;
        window.getSelectedCoauthors = getSelectedCoauthors;

        /* ═══ SONS / GÊNEROS (multi-select · same UX as Equipe do Evento) ═══
           Chips always show selected genres. The option list stays empty until
           the user types letters — then only label/slug matches appear (capped). */
        function getGenreCheckboxes() {
            return Array.prototype.slice.call(document.querySelectorAll('.ev-genre'));
        }
        function normGenreQ(s) {
            var out = String(s || '').toLowerCase().trim();
            try {
                out = out.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
            } catch (e) { /* older engines without String#normalize */ }
            return out;
        }
        function renderGenreUI(filter) {
            const chips = $id('genreChips');
            const list = $id('genreList');
            if (!chips || !list) return;
            const boxes = getGenreCheckboxes();

            chips.innerHTML = boxes.filter(cb => cb.checked).map(cb => {
                const label = cb.dataset.label || cb.value;
                return `<span class="ax-coauthors-chip" data-slug="${esc(cb.value)}">
                    <span>${esc(label)}</span>
                    <button type="button" data-genre-toggle="${esc(cb.value)}" aria-label="Remover">&times;</button>
                </span>`;
            }).join('');

            /* Strict: no typed letters → no option list (chips only), like Equipe. */
            const q = normGenreQ(filter);
            if (!q.length) {
                list.innerHTML = '';
                // #region agent log
                if (window.__apolloAgentDbg) {
                    window.__apolloAgentDbg({
                        runId: 'genre-search',
                        hypothesisId: 'C',
                        location: 'create-form.js:renderGenreUI',
                        message: 'genre render empty query',
                        data: { boxCount: boxes.length, chipCount: boxes.filter(cb => cb.checked).length }
                    });
                }
                // #endregion
                return;
            }

            const allMatches = boxes.filter(cb => {
                const label = normGenreQ(cb.dataset.label || cb.value);
                const slug = normGenreQ(cb.value);
                return label.includes(q) || slug.includes(q);
            });
            const rows = allMatches.slice(0, 5);

            // #region agent log
            if (window.__apolloAgentDbg) {
                window.__apolloAgentDbg({
                    runId: 'genre-search',
                    hypothesisId: 'A',
                    location: 'create-form.js:renderGenreUI',
                    message: 'genre filter result',
                    data: { q: q, boxCount: boxes.length, matchCount: allMatches.length, rowCount: rows.length }
                });
            }
            // #endregion

            if (!rows.length) {
                list.innerHTML = '<div class="ax-coauthors-empty">Nenhum gênero encontrado</div>';
                return;
            }
            list.innerHTML = rows.map(cb => {
                const on = cb.checked ? ' is-on' : '';
                const label = cb.dataset.label || cb.value;
                return `<button type="button" class="ax-coauthors-opt${on}" role="option" aria-selected="${cb.checked ? 'true' : 'false'}" data-genre-toggle="${esc(cb.value)}">
                    <span class="ax-ca-meta"><span class="ax-ca-name">${esc(label)}</span></span>
                    <i class="ri-check-line ax-ca-check"></i>
                </button>`;
            }).join('') + (allMatches.length > rows.length
                ? `<div class="ax-coauthors-more">+${allMatches.length - rows.length} — continue digitando para refinar</div>`
                : '');
        }
        function toggleGenre(slug) {
            const cb = getGenreCheckboxes().find(c => c.value === slug);
            if (!cb) return;
            cb.checked = !cb.checked;
            renderGenreUI($id('genreSearch') ? $id('genreSearch').value : '');
            if (typeof renderReceipt === 'function') renderReceipt();
            if (typeof syncModel === 'function') syncModel();
        }
        window.toggleGenre = toggleGenre;
        window.renderGenreUI = renderGenreUI;

        /* ═══ CUPONS — toggle reveals input+Adicionar; always seeds tag "apollo" ═══ */
        const APOLLO_COUPON = 'apollo';

        function couponTagHTML(code) {
            const c = String(code || '').trim();
            return `<span class="tag tag-primary" data-code="${esc(c)}">${esc(c)} <i class="ri-close-line" onclick="this.parentElement.remove();if(typeof renderReceipt==='function')renderReceipt();if(typeof syncModel==='function')syncModel();"></i></span>`;
        }
        function listCouponCodes() {
            const box = $id('couponTags');
            if (!box) return [];
            return [...box.querySelectorAll('.tag')].map((t) => {
                return (t.getAttribute('data-code') || t.textContent || '').trim();
            }).filter(Boolean);
        }
        function ensureApolloCoupon() {
            const box = $id('couponTags');
            if (!box) return;
            const has = listCouponCodes().some((c) => c.toLowerCase() === APOLLO_COUPON);
            if (!has) box.insertAdjacentHTML('afterbegin', couponTagHTML(APOLLO_COUPON));
        }
        function toggleCoupons() {
            const toggle = $id('couponToggle');
            const area = $id('couponArea');
            if (!toggle || !area) return;
            if (toggle.checked) {
                area.classList.add('is-active');
                ensureApolloCoupon();
            } else {
                area.classList.remove('is-active');
            }
            if (typeof syncModel === 'function') syncModel();
            if (typeof renderReceipt === 'function') renderReceipt();
        }
        function addCoupon() {
            const input = $id('newCouponInput');
            if (!input) return;
            const val = input.value.trim().toUpperCase();
            if (val === '') return;
            const existing = listCouponCodes().map((c) => c.toUpperCase());
            if (existing.includes(val)) {
                input.value = '';
                toast('Cupom já adicionado');
                return;
            }
            $id('couponTags').insertAdjacentHTML('beforeend', couponTagHTML(val));
            input.value = '';
            if (typeof syncModel === 'function') syncModel();
            renderReceipt();
        }

        /* ═══ LINE UP — a ordem define a exibição na página do evento ═══ */
        let djCounter = 3;
        function handleDJSearch(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const name = e.target.value.trim();
                if (name) { addDJToLineup(name); e.target.value = ''; }
            }
        }
        function lineupRows() {
            return [...$id('lineupContainer').querySelectorAll('.lineup-row')];
        }
        function renumberLineup() {
            lineupRows().forEach((row, i) => {
                const badge = row.querySelector('.dj-order');
                if (badge) badge.textContent = (i + 1) + 'º';
            });
            renderReceipt();
            syncModel();
        }
        function djAvatar(name, photo) {
            return photo || ('https://ui-avatars.com/api/?name=' + encodeURIComponent(name) + '&background=random');
        }
        function addDJToLineup(name, start = '', end = '', photo = '', djId = '', badge = '') {
            const rowId = 'dj-row-' + djCounter++;
            const badgeOpt = (v) => `<option value="${v}"${v === badge ? ' selected' : ''}>${v || 'Sem destaque'}</option>`;
            const rowHTML = `
                <div class="lineup-row" id="${rowId}" data-name="${esc(name)}" data-dj-id="${esc(djId)}">
                    <span class="dj-order">–</span>
                    <div class="dj-name">
                        <div class="dj-avatar"><img src="${esc(djAvatar(name, photo))}" alt="DJ"></div>
                        <div class="dj-name-col">
                            <span class="dj-name-txt">${esc(name)}</span>
                            <select class="apollo-select dj-badge-select" title="Destaque no line-up (Headliner/Opening)">
                                ${badgeOpt('')}${badgeOpt('Headliner')}${badgeOpt('Opening')}
                            </select>
                        </div>
                    </div>
                    <div class="field dj-in" style="margin: 0;">
                        <input type="time" class="apollo-input" value="${start}">
                    </div>
                    <div class="field dj-out" style="margin: 0;">
                        <input type="time" class="apollo-input" value="${end}">
                    </div>
                    <div class="dj-actions">
                        <button type="button" class="btn-icon-sm" onclick="moveDJ('${rowId}', -1)" title="Subir"><i class="ri-arrow-up-s-line"></i></button>
                        <button type="button" class="btn-icon-sm" onclick="moveDJ('${rowId}', 1)" title="Descer"><i class="ri-arrow-down-s-line"></i></button>
                        <button type="button" class="btn-icon-sm" onclick="removeDJ('${rowId}')" title="Remover"><i class="ri-close-line"></i></button>
                    </div>
                </div>`;
            $id('lineupContainer').insertAdjacentHTML('beforeend', rowHTML);
            renumberLineup();
        }
        function moveDJ(id, dir) {
            const row = $id(id);
            const sibling = dir < 0 ? row.previousElementSibling : row.nextElementSibling;
            if (!sibling || !sibling.classList.contains('lineup-row')) return;
            if (dir < 0) sibling.before(row);
            else sibling.after(row);
            renumberLineup();
        }
        function removeDJ(id) {
            $id(id).remove();
            renumberLineup();
        }
        function getLineup() {
            return lineupRows().map(row => {
                const badgeSel = row.querySelector('.dj-badge-select');
                const nameEl = row.querySelector('.dj-name-txt');
                return {
                    id: row.dataset.djId || '',
                    name: row.dataset.name || (nameEl ? nameEl.textContent.trim() : row.querySelector('.dj-name').textContent.trim()),
                    start: row.querySelector('.dj-in input').value,
                    end: row.querySelector('.dj-out input').value,
                    badge: badgeSel ? badgeSel.value : ''
                };
            });
        }

        /* ═══ PHP-READY MODEL SYNC ═══
           Popula os <input name=""> ocultos com o payload exato esperado pelo
           FrontendForm do apollo-events (dj_ids, dj_slots, coupon_code, gallery).
           Chamado a cada input/change e antes de gravar. */
        function setVal(id, v) { const el = $id(id); if (el) el.value = v; }
        function syncModel() {
            const lineup = getLineup();
            setVal('ev-dj-ids', JSON.stringify(lineup.filter(d => d.id).map(d => d.id)));
            setVal('ev-dj-slots', JSON.stringify(lineup.map(d => ({
                dj_id: d.id || null, name: d.name, start_time: d.start, end_time: d.end, badge: d.badge || null
            }))));
            const coupons = listCouponCodes();
            setVal('ev-coupon-code', ($id('couponToggle').checked ? coupons.join(',') : ''));
            const galEl = $id('galleryImages');
            const gal = galEl
                ? [...galEl.querySelectorAll('img')].map(img => img.getAttribute('data-att-id')).filter(Boolean)
                : [];
            setVal('ev-gallery', gal.join(','));
            syncCoauthorsHidden();
        }

        /* ═══ MODAL DE DJ — 2 opções: (A) selecionar da Apollo · (B) cadastrar novo ═══ */
        let djSelectedId = null;

        function buildDJOptions() {
            const box = $id('djSelectOpts');
            if (!box) return;
            box.innerHTML = (window.APOLLO_DJS || []).map(dj => {
                const desc = [dj.handle, dj.genre].filter(Boolean).join(' · ');
                return `
                    <div class="as2-opt" role="option" data-dj="${dj.id}" data-value="${dj.id}" data-name="${esc(dj.name)}" data-desc="${esc(desc)}">
                        <img class="dj-opt-av" src="${esc(dj.photo)}" alt="" loading="lazy">
                        <div class="as2-opt-body">
                            <div class="as2-opt-name">${esc(dj.name)}</div>
                            <div class="as2-opt-desc">${esc(desc)}</div>
                        </div>
                        <i class="ri-check-line as2-opt-check"></i>
                    </div>`;
            }).join('');
            // seleção própria (roda em paralelo ao combobox nativo .as2)
            box.querySelectorAll('.as2-opt').forEach(opt => {
                opt.addEventListener('click', () => selectApolloDJ(opt.dataset.dj));
            });
        }

        function selectApolloDJ(id) {
            const dj = (window.APOLLO_DJS || []).find(d => d.id === id);
            if (!dj) return;
            djSelectedId = id;
            $id('djPickedImg').src = dj.photo;
            $id('djPickedName').textContent = dj.name;
            $id('djPickedHandle').textContent = [dj.handle, dj.genre].filter(Boolean).join(' · ');
            $id('djPicked').hidden = false;
            $id('djPickAddBtn').disabled = false;
        }

        function addPickedDJ() {
            const dj = (window.APOLLO_DJS || []).find(d => d.id === djSelectedId);
            if (!dj) { toast('Selecione um DJ da lista'); return; }
            addDJToLineup(dj.name, '', '', dj.photo, dj.id);
            toast(`${dj.name} adicionado ao line-up`);
            closeModal('djModal');
        }

        async function registerNewDJ() {
            const nameEl = $id('djNewName');
            const name = nameEl.value.trim();
            if (!name) {
                nameEl.classList.add('is-invalid');
                $id('djNewNameErr').style.display = 'block';
                return;
            }
            const handle = ($id('djNewHandle') && $id('djNewHandle').value.trim()) || '';
            const photo = ($id('djNewPhoto') && $id('djNewPhoto').value.trim()) || '';
            const bio = ($id('djNewBio') && $id('djNewBio').value.trim()) || '';
            const booking = ($id('djNewBooking') && $id('djNewBooking').value.trim()) || '';
            const soundcloud = ($id('djNewSoundcloud') && $id('djNewSoundcloud').value.trim()) || '';
            const spotify = ($id('djNewSpotify') && $id('djNewSpotify').value.trim()) || '';
            const bandcamp = ($id('djNewBandcamp') && $id('djNewBandcamp').value.trim()) || '';
            const genres = ($id('djNewGenres') && $id('djNewGenres').value.trim()) || '';
            const kit = ($id('djNewKit') && $id('djNewKit').value.trim()) || '';
            const trackTitle = ($id('djNewTrackTitle') && $id('djNewTrackTitle').value.trim()) || '';
            const trackUrl = ($id('djNewTrackUrl') && $id('djNewTrackUrl').value.trim()) || '';
            const trackDuration = ($id('djNewTrackDuration') && $id('djNewTrackDuration').value.trim()) || '';
            const trackYear = ($id('djNewTrackYear') && $id('djNewTrackYear').value.trim()) || '';

            /* Persist as a real "dj" CPT via REST (same contract as FrontendForm.php's
             * quick-add modal) so the DJ actually exists beyond this form session and
             * shows up correctly in the public lineup on the single event page. */
            const CFG = window.APOLLO_EVENT_FORM || {};
            const btn = document.querySelector('#djPaneNew .btn-primary');
            const originalBtnHTML = btn ? btn.innerHTML : '';
            if (btn) { btn.disabled = true; btn.innerHTML = 'Cadastrando...'; }

            const payload = { title: name, name: name };
            if (handle) payload.instagram = handle;
            if (bio) payload.bio_short = bio;
            if (booking) payload.booking = booking;
            if (soundcloud) payload.soundcloud = soundcloud;
            if (spotify) payload.spotify = spotify;
            if (bandcamp) payload.bandcamp = bandcamp;
            if (genres) payload.genres = genres;
            if (kit) payload.media_kit_url = kit;
            if (trackTitle || trackUrl) {
                payload.track_title = trackTitle;
                payload.track_url = trackUrl;
                payload.track_duration = trackDuration;
                payload.track_year = trackYear;
            }

            let newId = null;
            let djFailMsg = '';
            if (CFG.djsUrl) {
                try {
                    const res = await fetch(CFG.djsUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': CFG.nonce || '' },
                        credentials: 'same-origin',
                        body: JSON.stringify(payload)
                    });
                    const result = await res.json().catch(() => ({}));
                    if (res.ok && result && result.id) {
                        newId = result.id;
                    } else {
                        /* A row without a dj_id is dropped by sanitize_dj_slots()
                         * on save — the whole timetable would vanish silently. */
                        djFailMsg = (result && result.message) ? String(result.message) : ('HTTP ' + res.status);
                        console.error('[apollo-events] dj create failed:', res.status, result);
                    }
                } catch (err) {
                    djFailMsg = String((err && err.message) || err);
                    console.error('[apollo-events] dj create failed:', err);
                }
            }

            addDJToLineup(name, '', '', photo, newId);
            toast(newId
                ? `${name} cadastrado na Apollo e adicionado ao line-up`
                : `Não foi possível cadastrar ${name}${djFailMsg ? ' — ' + djFailMsg : ''}. Ele NÃO será salvo no line-up.`);
            if (btn) { btn.disabled = false; btn.innerHTML = originalBtnHTML; }
            closeModal('djModal');
        }

        function djTab(which) {
            const pick = which === 'pick';
            $id('djTabPick').classList.toggle('is-on', pick);
            $id('djTabNew').classList.toggle('is-on', !pick);
            $id('djPanePick').hidden = !pick;
            $id('djPaneNew').hidden = pick;
        }

        // reset do modal sempre que abre (limpa seleção/erros)
        function resetDJModal() {
            djSelectedId = null;
            djTab('pick');
            const drop = $id('djSelect');
            if (drop) drop.classList.remove('has-value', 'is-open');
            [
                'djSelectInput', 'djNewName', 'djNewHandle', 'djNewPhoto', 'djNewBio',
                'djNewBooking', 'djNewSoundcloud', 'djNewSpotify', 'djNewBandcamp',
                'djNewGenres', 'djNewKit', 'djNewTrackTitle', 'djNewTrackUrl',
                'djNewTrackDuration', 'djNewTrackYear'
            ].forEach(i => { if ($id(i)) $id(i).value = ''; });
            $id('djSelectOpts').querySelectorAll('.as2-opt.is-selected').forEach(o => o.classList.remove('is-selected'));
            $id('djPicked').hidden = true;
            $id('djPickAddBtn').disabled = true;
            if ($id('djNewName')) $id('djNewName').classList.remove('is-invalid');
            if ($id('djNewNameErr')) $id('djNewNameErr').style.display = 'none';
        }

        /* ═══ PRÉVIA DE CAPA ═══ */
        function setCover(url) {
            const c = $id('coverUpload');
            if (url) {
                c.classList.add('has-cover');
                c.innerHTML = `<img src="${esc(url)}" alt="Capa do evento"><i class="ri-image-edit-line"></i><span>Clique para trocar a capa</span>`;
            } else {
                c.classList.remove('has-cover');
                c.innerHTML = `<i class="ri-upload-cloud-2-line"></i><span>Clique para anexar a capa do evento</span>`;
            }
        }

        /* ═══ RESUMO DO REGISTRO (recibo ao vivo dos inputs) ═══ */
        function selText(id) {
            const el = $id(id);
            if (!el || !el.value) return '—';
            return el.selectedOptions.length ? el.selectedOptions[0].text : '—';
        }
        const GENRE_LBL = { tech_house: 'Tech House', techno: 'Techno', house: 'House', disco: 'Disco', trance: 'Trance' };
        function rcRow(k, v) {
            return `<div class="rc-row"><div class="rc-k">${k}</div><div class="rc-v">${v}</div></div>`;
        }
        function renderReceipt() {
            const box = $id('receipt');
            if (!box) return;
            const rows = [];

            const title = $id('ev-title').value.trim();
            rows.push(rcRow('Evento', title ? esc(title) : '<span class="rc-sub">sem nome ainda</span>'));

            if (dtp.start.date && dtp.end.date) {
                const dur = $id('dtpDur').textContent;
                rows.push(rcRow('Início', `${fmtChip(dtp.start.date)} · ${dtp.start.time}`));
                rows.push(rcRow('Término', `${fmtChip(dtp.end.date)} · ${dtp.end.time} <span class="rc-sub">Duração: ${esc(dur)}</span>`));
            }

            rows.push(rcRow('Local', `${esc($id('venueName').textContent)}<span class="rc-sub">${esc($id('venueAddress').textContent)}</span>`));

            const lineup = getLineup();
            if (lineup.length) {
                const djs = lineup.map((dj, i) => `
                    <div class="rc-dj">
                        <span class="rc-n">${i + 1}º</span>
                        <span>${esc(dj.name)}${dj.badge ? ' <span class="rc-sub">· ' + esc(dj.badge) + '</span>' : ''}</span>
                        <span class="rc-t">${dj.start && dj.end ? dj.start + '–' + dj.end : 'ordem de apresentação'}</span>
                    </div>`).join('');
                rows.push(rcRow('Line-up', `<div class="rc-lineup">${djs}</div>`));
            }

            const genres = [...document.querySelectorAll('.ev-genre:checked')].map(cb => cb.dataset.label || GENRE_LBL[cb.value] || cb.value);
            if (genres.length) rows.push(rcRow('Gêneros', genres.join(' · ')));

            rows.push(rcRow('Marcações', `${selText('ev-season')} · Ingressos: ${selText('ev-tickets')} · Evento: ${selText('ev-status')}`));

            const tTitle = ($id('ev-ticket-price') && $id('ev-ticket-price').value.trim()) || '';
            const tURL = ($id('ev-tickets-url') && $id('ev-tickets-url').value.trim()) || '';
            if (tTitle || tURL) rows.push(rcRow('Ingresso', esc(tTitle || 'Ingresso Online') + (tURL ? '<br><span class="rc-sub">' + esc(tURL) + '</span>' : '')));

            const media = [];
            if ($id('ev-video').value.trim()) media.push('Vídeo: ' + esc($id('ev-video').value.trim()));
            if ($id('ev-audio').value.trim()) media.push('Áudio: ' + esc($id('ev-audio').value.trim()));
            if (media.length) rows.push(rcRow('Mídia', media.join('<br>')));

            const coupons = listCouponCodes();
            if ($id('couponToggle').checked && coupons.length) rows.push(rcRow('Cupoms', coupons.map(esc).join(' · ')));

            const axbTickets = axbButtons.filter(b => b.kind !== 'lista').map(b => esc(b.label) + (b.url ? '<span class="rc-sub"> · ' + esc(b.url) + '</span>' : ''));
            if (axbTickets.length) rows.push(rcRow('Outros acessos', axbTickets.join('<br>')));

            const listas = axbButtons.filter(b => b.kind === 'lista').map(b => esc(b.label) + (b.url ? ' — ' + esc(b.url) : ''));
            if (listas.length) rows.push(rcRow('Listas', listas.join('<br>')));

            const cas = getSelectedCoauthors();
            if (cas.length) {
                const names = cas.map((id) => {
                    const u = coauthorCache[id];
                    return esc(u ? u.name : '#' + id);
                });
                rows.push(rcRow(i18nTeam('coauthors', 'Equipe do Evento'), names.join(' · ')));
            }

            if (window.ApolloEventAboutEditor && typeof window.ApolloEventAboutEditor.syncHidden === 'function') {
                window.ApolloEventAboutEditor.syncHidden();
            }
            const aboutRaw = ($id('ev-about') && $id('ev-about').value || '').trim();
            const aboutPlain = aboutRaw
                ? aboutRaw.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim()
                : '';
            if (aboutPlain) rows.push(rcRow('Sobre', `<span class="rc-sub" style="display:inline;">${esc(aboutPlain)}</span>`));

            box.innerHTML = rows.join('');
        }

        /* ═══ SIDEBAR — "estoque" de eventos do extra-events-simulation.js ═══ */
        window.editingId = window.editingId || null; let editingId = window.editingId;

        function renderSidebar() {
            /* #sidebarEvents lived on the old local aside.php — apollo_plus_open()
               shell no longer prints it. Null-guard so init() can reach genre bind. */
            const box = $id('sidebarEvents');
            if (!box) return;
            const draftOn = editingId === null ? 'on' : '';
            let html = `
                <div class="ni ${draftOn}" onclick="createNewEvent()">
                    <i class="ri-calendar-event-${draftOn ? 'fill' : 'line'}"></i>
                    <span class="sn">Novo Evento (Rascunho)</span>
                </div>`;
            (window.APOLLO_EVENTS || []).forEach(ev => {
                const on = String(ev.id) === String(editingId) ? 'on' : '';
                html += `
                    <div class="ni ${on}" onclick="location.href='forms.html?event=${ev.id}'">
                        <i class="ri-calendar-event-${on ? 'fill' : 'line'}"></i>
                        <span class="sn">${esc(ev.title)}</span>
                    </div>`;
            });
            box.innerHTML = html;
        }

        /* ═══ MODO EDIÇÃO — carrega um evento cadastrado em todos os inputs (simulado) ═══ */
        function setGenres(genres) {
            document.querySelectorAll('.ev-genre').forEach(cb => {
                cb.checked = genres.includes(cb.value);
            });
            renderGenreUI($id('genreSearch') ? $id('genreSearch').value : '');
        }
        window.setGenres = setGenres;
        function setCoupons(coupons) {
            let list = Array.isArray(coupons)
                ? coupons.map((c) => String(c || '').trim()).filter(Boolean)
                : [];
            const on = list.length > 0;
            if (on && !list.some((c) => c.toLowerCase() === APOLLO_COUPON)) {
                list = [APOLLO_COUPON].concat(list);
            }
            $id('couponToggle').checked = on;
            $id('couponTags').innerHTML = list.map((c) => couponTagHTML(c)).join('');
            toggleCoupons();
        }
        function setLineup(lineup) {
            $id('lineupContainer').innerHTML = `
                <label class="field-label mt-3 mb-2">Organizar Line-up</label>
                <div class="lineup-hint">A ordem abaixo define a exibição na página do evento, horários são opcionais.</div>`;
            lineup.forEach(dj => addDJToLineup(dj.name, dj.start || '', dj.end || '', dj.thumb || dj.photo || '', dj.id || '', dj.badge || ''));
        }

        function setAccessFields(ev) {
            if (!ev) return;
            setAccessButtons(ev.access_buttons || []);
            if ($id('ev-lista-cta-label')) $id('ev-lista-cta-label').value = ev.lista_cta_label || '';
        }

        function loadEvent(id) {
            const ev = window.getApolloEvent ? window.getApolloEvent(id) : null;
            if (!ev) { createNewEvent(); return; }
            editingId = id; window.editingId = id;

            $id('pageTitle').innerText = 'Editar Evento';
            $id('pageSubtitle').innerText = `Editando “${ev.title}” — alterações são simuladas e nunca salvas.`;
            $id('saveBtn').innerHTML = '<i class="ri-save-line"></i> Atualizar Evento';

            $id('ev-title').value = ev.title;
            if (window.ApolloEventAboutEditor && typeof window.ApolloEventAboutEditor.setHTML === 'function') {
                window.ApolloEventAboutEditor.setHTML(ev.about || ev.content || '');
            } else if ($id('ev-about')) {
                $id('ev-about').value = ev.about || ev.content || '';
            }
            $id('ev-season').value = ev.season || '';
            $id('ev-tickets').value = ev.tickets;
            $id('ev-status').value = ev.status;
            if ($id('ev-tickets-url')) $id('ev-tickets-url').value = ev.ticketsUrl;
            $id('ev-video').value = ev.videoUrl;
            $id('ev-audio').value = ev.audioUrl;
            setGenres(ev.genres);
            setCover(ev.cover);
            setCoupons(ev.coupons);
            setLineup(ev.lineup);

            $id('ev-venue-search').value = ev.venue.name;
            applyVenue(ev.venue);

            dtp.start = { date: ev.startDate, time: ev.startTime };
            dtp.end = { date: ev.endDate, time: ev.endTime };
            dtp.endTouched = true;
            dtp.active = null;
            $id('dtpPanel').classList.remove('is-open');
            dtpUpdate();

            renderSidebar();
        }

        /* ═══ NOVO EVENTO (reset) ═══ */
        function createNewEvent() {
            editingId = null; window.editingId = null;
            history.replaceState(null, '', location.pathname);

            $id('pageTitle').innerText = 'Criar Novo Evento';
            $id('pageSubtitle').innerText = 'Preencha os dados para publicar um novo evento na Apollo.';
            $id('saveBtn').innerHTML = '<i class="ri-save-line"></i> Salvar Evento';
            $id('eventForm').reset();

            if (window.ApolloEventAboutEditor && typeof window.ApolloEventAboutEditor.setHTML === 'function') {
                window.ApolloEventAboutEditor.setHTML('');
            } else if ($id('ev-about')) {
                $id('ev-about').value = '';
            }

            if ($id('ev-season')) $id('ev-season').value = '';

            document.querySelectorAll('.apollo-input.is-invalid').forEach(clearInvalid);
            $id('couponToggle').checked = false;
            toggleCoupons();
            $id('couponTags').innerHTML = '';
            setAccessButtons([]);
            if ($id('ev-lista-cta-label')) $id('ev-lista-cta-label').value = '';
            setCoauthors([]);
            setLineup([]);
            setCover(null);
            setGenres(['tech_house', 'house']);

            $id('ev-venue-search').value = 'MAM Rio';
            const mam = knownVenues().find(v => /MAM/.test(v.name));
            if (mam) applyVenue(mam);

            dtpInitDefaults();
            renderSidebar();
            const aside = $id('ax-aside'); if (aside) aside.classList.remove('open');
            const ov = $id('ax-overlay'); if (ov) ov.classList.remove('on');
        }

        /* ═══ GRAVAR (simulado — nada é persistido) ═══ */
        function saveEvent(btnId) {
            if (!validateForm()) { toast('Corrija os campos destacados'); return; }
            syncModel(); // garante que dj_ids/dj_slots/coupon_code estejam prontos p/ POST
            const btn = $id(btnId || 'saveBtn');
            const originalHTML = btn.innerHTML;
            btn.innerHTML = `<i class="ri-loader-4-line ri-spin"></i> ${editingId ? 'Atualizando...' : 'Gravando...'}`;
            btn.style.pointerEvents = 'none';

            setTimeout(() => {
                btn.innerHTML = `<i class="ri-check-line"></i> ${editingId ? 'Atualizado!' : 'Gravado!'}`;
                toast('Simulação — nada foi salvo');
                setTimeout(() => {
                    btn.innerHTML = originalHTML;
                    btn.style.pointerEvents = 'auto';
                }, 2000);
            }, 1000);
        }

        /* ═══ INIT ═══ */
        (function init() {
            /* Debug beacon removed 2026-08-17 — it POSTed to
               http://127.0.0.1:7514 from every visitor's browser (blocked mixed
               content on HTTPS) plus apollo/v1/_agent_debug. Inert no-op kept
               because call sites below guard on its existence; harness E27
               fails the build if the network form returns. */
            window.__apolloAgentDbg = window.__apolloAgentDbg || function () {};

            try { renderSidebar(); } catch (e) {
                if (window.__apolloAgentDbg) {
                    window.__apolloAgentDbg({ runId: 'genre-search', hypothesisId: 'B', location: 'create-form.js:init', message: 'renderSidebar threw', data: { err: String(e && e.message || e) } });
                }
            }
            try { buildDJOptions(); } catch (e) { /* non-fatal */ }
            try { renumberLineup(); } catch (e) { /* non-fatal */ }
            try {
                const seed = JSON.parse(($id('ev-coauthors') && $id('ev-coauthors').value) || '[]');
                setCoauthors(Array.isArray(seed) ? seed : []);
            } catch (e) { setCoauthors([]); }
            const caSearch = $id('coauthorSearch');
            if (caSearch) {
                caSearch.addEventListener('input', function () {
                    const query = String(this.value || '').trim();
                    window.clearTimeout(coauthorSearchTimer);
                    if (query.length < 2) {
                        coauthorSearchToken++;
                        coauthorLastResults = [];
                        const list = $id('coauthorList');
                        if (list) list.innerHTML = '';
                        setCoauthorStatus('');
                        renderCoauthorsUI('');
                        return;
                    }
                    coauthorSearchTimer = window.setTimeout(function () {
                        searchTeamUsers(query);
                    }, 280);
                });
                caSearch.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter') e.preventDefault();
                });
            }
            try {
                const axbSeed = JSON.parse(($id('ev-access-buttons') && $id('ev-access-buttons').value) || '[]');
                setAccessButtons(Array.isArray(axbSeed) ? axbSeed : []);
            } catch (e) { setAccessButtons([]); }
            const axbList = $id('axbList');
            if (axbList) {
                axbList.addEventListener('input', axbOnListInput);
                axbList.addEventListener('click', axbOnListClick);
            }

            const genreBoxes = getGenreCheckboxes();
            const genreSearch = $id('genreSearch');
            // #region agent log
            if (window.__apolloAgentDbg) {
                window.__apolloAgentDbg({
                    runId: 'genre-search',
                    hypothesisId: 'A',
                    location: 'create-form.js:init',
                    message: 'genre init counts',
                    data: {
                        boxCount: genreBoxes.length,
                        hasSearch: !!genreSearch,
                        hasList: !!$id('genreList'),
                        hasChips: !!$id('genreChips'),
                        sample: genreBoxes.slice(0, 3).map(function (cb) {
                            return { value: cb.value, label: cb.dataset.label || '' };
                        })
                    }
                });
            }
            // #endregion

            renderGenreUI('');
            if (genreSearch) {
                genreSearch.addEventListener('input', function () {
                    // #region agent log
                    if (window.__apolloAgentDbg) {
                        window.__apolloAgentDbg({
                            runId: 'genre-search',
                            hypothesisId: 'A',
                            location: 'create-form.js:genreSearch.input',
                            message: 'genreSearch input fired',
                            data: { value: String(this.value || ''), len: String(this.value || '').length }
                        });
                    }
                    // #endregion
                    renderGenreUI(this.value);
                });
                genreSearch.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter') e.preventDefault();
                });
            }
            document.addEventListener('click', function (e) {
                const btn = e.target && e.target.closest && e.target.closest('[data-genre-toggle]');
                if (!btn) return;
                e.preventDefault();
                toggleGenre(btn.getAttribute('data-genre-toggle'));
            });

            const form = $id('eventForm');
            if (form) {
                form.addEventListener('input', function () { try { renderReceipt(); syncModel(); } catch (err) {} });
                form.addEventListener('change', function () { try { renderReceipt(); syncModel(); } catch (err) {} });
            }
            try { syncModel(); } catch (e) { /* non-fatal */ }
            const id = new URLSearchParams(location.search).get('event');
            if (id) loadEvent(id);
            else dtpInitDefaults();
        })();