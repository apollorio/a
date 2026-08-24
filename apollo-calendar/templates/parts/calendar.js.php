<?php
/**
 * Apollo Calendar — Inline JavaScript
 *
 * Pure vanilla JS, no external dependencies beyond CDN core.js.
 * Reads ApolloCalendarConfig injected by CalendarPage (wp_json_encode).
 *
 * @package Apollo\Calendar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<script>
(function () {
'use strict';

const C    = ApolloCalendarConfig;
const ROOT = C.apiRoot;
const HDR  = { 'Content-Type': 'application/json', 'X-WP-Nonce': C.nonce };

/* ── State ───────────────────────────────────────────────── */
let view    = 'month';   // 'month' | 'week' | 'day'
let cursor  = new Date();
cursor.setHours(0,0,0,0);
let items   = [];        // cached aggregated items
let editId  = null;      // appointment ID being edited (null = create)

/* ── Utility ─────────────────────────────────────────────── */
function fmtDate(d) {
	return d.toLocaleDateString('pt-BR', { year:'numeric', month:'long', day:'numeric' });
}
function isoDate(d) {
	return d.toISOString().slice(0,10);
}
function localToISO(localStr) {
	// datetime-local → UTC ISO
	const d = new Date(localStr);
	return d.toISOString();
}
function isoToLocal(iso) {
	if (!iso) return '';
	const d = new Date(iso);
	const pad = n => String(n).padStart(2,'0');
	return d.getFullYear() + '-' + pad(d.getMonth()+1) + '-' + pad(d.getDate()) +
	       'T' + pad(d.getHours()) + ':' + pad(d.getMinutes());
}
function sameDay(a, b) {
	return a.getFullYear()===b.getFullYear() && a.getMonth()===b.getMonth() && a.getDate()===b.getDate();
}
function startOfWeek(d) {
	const s = new Date(d);
	const day = s.getDay();
	s.setDate(s.getDate() - ((day + 6) % 7)); // Monday-based
	s.setHours(0,0,0,0);
	return s;
}
function addDays(d, n) {
	const x = new Date(d);
	x.setDate(x.getDate() + n);
	return x;
}
function itemStart(item) { return new Date(item.start_iso); }
function itemEnd(item)   { return item.end_iso ? new Date(item.end_iso) : null; }
function itemOnDay(item, day) {
	const s = itemStart(item);
	const e = itemEnd(item) || s;
	const dayEnd = new Date(day); dayEnd.setHours(23,59,59);
	return s <= dayEnd && e >= day;
}
function esc(str) {
	const d = document.createElement('div');
	d.textContent = String(str || '');
	return d.innerHTML;
}

/* ── API calls ───────────────────────────────────────────── */
async function apiFetch(method, path, body) {
	const r = await fetch(ROOT + path, {
		method,
		headers: HDR,
		body: body ? JSON.stringify(body) : undefined
	});
	if (!r.ok) {
		const err = await r.json().catch(() => ({ message: r.statusText }));
		throw new Error(err.message || 'API error');
	}
	return r.status === 204 ? null : r.json();
}

async function loadItems() {
	const body = document.getElementById('cal-body');
	body.innerHTML = '<div class="cal-loading"><div class="spinner"></div><?php echo esc_js( __( 'Carregando…', 'apollo-calendar' ) ); ?></div>';

	let from, to;
	if (view === 'month') {
		const s = new Date(cursor.getFullYear(), cursor.getMonth(), 1);
		const e = new Date(cursor.getFullYear(), cursor.getMonth()+1, 0);
		// Pad to full weeks
		from = addDays(s, -((s.getDay()+6)%7));
		to   = addDays(e, 6-((e.getDay()+6)%7));
	} else if (view === 'week') {
		from = startOfWeek(cursor);
		to   = addDays(from, 6);
	} else {
		from = new Date(cursor);
		to   = new Date(cursor);
	}

	const qFrom = from.toISOString();
	const qTo   = to.toISOString();

	try {
		items = await apiFetch('GET', `/calendar?from=${encodeURIComponent(qFrom)}&to=${encodeURIComponent(qTo)}`);
	} catch(e) {
		body.innerHTML = '<div class="cal-loading" style="color:#f87171">' + esc(e.message) + '</div>';
		return;
	}

	renderView();
}

/* ── Rendering ───────────────────────────────────────────── */
function renderView() {
	updateLabel();
	if (view === 'month') renderMonth();
	else if (view === 'week') renderWeek();
	else renderDay();
}

function updateLabel() {
	const el = document.getElementById('cal-date-label');
	if (view === 'month') {
		el.textContent = cursor.toLocaleDateString('pt-BR', { year:'numeric', month:'long' });
	} else if (view === 'week') {
		const s = startOfWeek(cursor);
		const e = addDays(s, 6);
		el.textContent = fmtDate(s) + ' – ' + fmtDate(e);
	} else {
		el.textContent = fmtDate(cursor);
	}
}

/* ── MONTH ── */
function renderMonth() {
	const today = new Date();
	today.setHours(0,0,0,0);
	const firstDay = new Date(cursor.getFullYear(), cursor.getMonth(), 1);
	const lastDay  = new Date(cursor.getFullYear(), cursor.getMonth()+1, 0);
	const startW   = startOfWeek(firstDay);

	const days = ['Seg','Ter','Qua','Qui','Sex','Sáb','Dom'];
	let html = '<table id="cal-month"><thead><tr>' + days.map(d=>`<th>${d}</th>`).join('') + '</tr></thead><tbody>';

	let d = new Date(startW);
	while (d <= lastDay || d.getDay() !== 1 || d < firstDay) {
		if (d.getDay() === 1 || d.getTime() === startW.getTime()) html += '<tr>';

		const isToday       = sameDay(d, today);
		const isOtherMonth  = d.getMonth() !== cursor.getMonth();
		const dayItems      = items.filter(i => itemOnDay(i, new Date(d)));
		const dateStr       = isoDate(d);

		html += `<td class="${isToday?'today':''} ${isOtherMonth?'other-month':''}" data-date="${dateStr}">`;
		html += `<div class="day-num">${d.getDate()}</div>`;

		dayItems.slice(0, 4).forEach(item => {
			html += `<div class="cal-chip" style="background:${esc(item.color||'#888')}" data-id="${esc(item.id)}">${esc(item.title)}</div>`;
		});
		if (dayItems.length > 4) html += `<div class="cal-chip" style="background:#555">+${dayItems.length-4}</div>`;

		html += '</td>';

		d = addDays(d, 1);
		if (d.getDay() === 1 && d > lastDay) break;
		if (d.getDay() === 1) html += '</tr>';
	}

	html += '</tbody></table>';
	document.getElementById('cal-body').innerHTML = html;

	// Click chip → detail
	document.querySelectorAll('.cal-chip[data-id]').forEach(el => {
		el.addEventListener('click', e => {
			e.stopPropagation();
			const item = items.find(i => i.id === el.dataset.id);
			if (item) showDetail(item, el);
		});
	});
	// Click day cell → quick add
	document.querySelectorAll('#cal-month td[data-date]').forEach(el => {
		el.addEventListener('click', () => openModal(null, el.dataset.date));
	});
}

/* ── WEEK ── */
function renderWeek() {
	const today = new Date(); today.setHours(0,0,0,0);
	const weekStart = startOfWeek(cursor);
	const days = Array.from({length:7}, (_,i) => addDays(weekStart, i));
	const hours = Array.from({length:24}, (_,i) => i);
	const dayNames = ['Seg','Ter','Qua','Qui','Sex','Sáb','Dom'];

	let html = '<div id="cal-week">';
	// Header row
	html += '<div class="week-header-cell" style="font-size:.65rem;color:var(--cal-muted)">UTC</div>';
	days.forEach((d,i) => {
		const isToday = sameDay(d, today);
		html += `<div class="week-header-cell ${isToday?'today-col':''}">${dayNames[i]}<br><strong>${d.getDate()}</strong></div>`;
	});
	// All-day row
	html += '<div class="week-time-cell" style="font-size:.65rem">↕</div>';
	days.forEach(d => {
		const allDayItems = items.filter(i => i.all_day && itemOnDay(i, new Date(d)));
		html += '<div class="all-day-strip">';
		allDayItems.forEach(item => {
			html += `<div class="cal-chip" style="background:${esc(item.color||'#888')}" data-id="${esc(item.id)}">${esc(item.title)}</div>`;
		});
		html += '</div>';
	});
	// Hour rows
	hours.forEach(h => {
		const hStr = String(h).padStart(2,'0') + ':00';
		html += `<div class="week-time-cell">${hStr}</div>`;
		days.forEach(d => {
			const isToday = sameDay(d, today);
			const slotItems = items.filter(i => {
				if (i.all_day) return false;
				const s = itemStart(i);
				return sameDay(s, d) && s.getUTCHours() === h;
			});
			html += `<div class="week-slot ${isToday?'today-col':''}">`;
			slotItems.forEach(item => {
				html += `<div class="week-event" style="background:${esc(item.color||'#888')}" data-id="${esc(item.id)}">${esc(item.title)}</div>`;
			});
			html += '</div>';
		});
	});
	html += '</div>';

	document.getElementById('cal-body').innerHTML = html;

	document.querySelectorAll('[data-id]').forEach(el => {
		el.addEventListener('click', e => {
			e.stopPropagation();
			const item = items.find(i => i.id === el.dataset.id);
			if (item) showDetail(item, el);
		});
	});
}

/* ── DAY ── */
function renderDay() {
	const today = new Date(); today.setHours(0,0,0,0);
	const hours = Array.from({length:24}, (_,i) => i);
	const dayItems = items.filter(i => !i.all_day && sameDay(itemStart(i), cursor));
	const allDayItems = items.filter(i => i.all_day && itemOnDay(i, new Date(cursor)));

	let html = '<div id="cal-day">';
	html += '<div class="day-time-label" style="font-size:.65rem;color:var(--cal-muted)">UTC</div>';
	html += '<div class="all-day-strip">';
	allDayItems.forEach(item => {
		html += `<div class="cal-chip" style="background:${esc(item.color||'#888')}" data-id="${esc(item.id)}">${esc(item.title)}</div>`;
	});
	html += '</div>';

	hours.forEach(h => {
		const hStr = String(h).padStart(2,'0') + ':00';
		const slotItems = dayItems.filter(i => itemStart(i).getUTCHours() === h);
		html += `<div class="day-time-label">${hStr}</div>`;
		html += '<div class="day-slot">';
		slotItems.forEach(item => {
			html += `<div class="day-event" style="background:${esc(item.color||'#888')}" data-id="${esc(item.id)}">${esc(item.title)}</div>`;
		});
		html += '</div>';
	});
	html += '</div>';

	document.getElementById('cal-body').innerHTML = html;

	document.querySelectorAll('[data-id]').forEach(el => {
		el.addEventListener('click', e => {
			e.stopPropagation();
			const item = items.find(i => i.id === el.dataset.id);
			if (item) showDetail(item, el);
		});
	});
}

/* ── Detail popup ─────────────────────────────────────────── */
function showDetail(item, anchor) {
	const d = document.getElementById('cal-detail');
	document.getElementById('detail-title').textContent = item.title;
	const start = item.all_day ? item.start_iso : new Date(item.start_iso).toLocaleString('pt-BR');
	document.getElementById('detail-time').textContent = start + (item.end_iso ? ' – ' + new Date(item.end_iso).toLocaleString('pt-BR') : '');
	document.getElementById('detail-location').textContent = item.location || '';

	const acts = document.getElementById('detail-actions');
	acts.innerHTML = '';
	if (item.url) acts.innerHTML += `<a href="${esc(item.url)}" target="_blank"><?php echo esc_js( __( 'Ver evento', 'apollo-calendar' ) ); ?></a>`;
	if (item.editable) {
		const btnEdit = document.createElement('button');
		btnEdit.textContent = '<?php echo esc_js( __( 'Editar', 'apollo-calendar' ) ); ?>';
		btnEdit.onclick = () => { hideDetail(); openModal(item); };
		acts.appendChild(btnEdit);

		const btnDel = document.createElement('button');
		btnDel.textContent = '<?php echo esc_js( __( 'Excluir', 'apollo-calendar' ) ); ?>';
		btnDel.style.color = '#f87171';
		btnDel.onclick = () => { hideDetail(); confirmDelete(item); };
		acts.appendChild(btnDel);
	}

	// Position near anchor
	const rect = anchor.getBoundingClientRect();
	d.style.left = Math.min(rect.left, window.innerWidth - 300) + 'px';
	d.style.top  = Math.min(rect.bottom + 4, window.innerHeight - 200) + 'px';
	d.style.display = 'block';
}
function hideDetail() {
	document.getElementById('cal-detail').style.display = 'none';
}

/* ── Modal ───────────────────────────────────────────────── */
function openModal(item, defaultDate) {
	editId = item ? item.id.replace('personal-','') : null;
	const title = document.getElementById('modal-title');
	title.textContent = item ? '<?php echo esc_js( __( 'Editar Compromisso', 'apollo-calendar' ) ); ?>' : '<?php echo esc_js( __( 'Novo Compromisso', 'apollo-calendar' ) ); ?>';

	// Pre-fill
	document.getElementById('appt-title').value    = item ? item.title : '';
	document.getElementById('appt-start').value    = item ? isoToLocal(item.start_iso) : (defaultDate ? defaultDate + 'T08:00' : '');
	document.getElementById('appt-end').value      = item ? isoToLocal(item.end_iso) : '';
	document.getElementById('appt-allday').checked = item ? !!item.all_day : false;
	document.getElementById('appt-location').value = item ? (item.location||'') : '';
	document.getElementById('appt-desc').value     = '';
	document.getElementById('appt-color').value    = item ? (item.color||'#6366f1') : '#6366f1';
	document.getElementById('appt-remind').value   = '0';
	document.getElementById('modal-error').style.display = 'none';

	document.getElementById('cal-modal-overlay').classList.add('open');
	document.getElementById('appt-title').focus();
}

function closeModal() {
	document.getElementById('cal-modal-overlay').classList.remove('open');
	editId = null;
}

async function saveModal() {
	const title   = document.getElementById('appt-title').value.trim();
	const startRaw = document.getElementById('appt-start').value;
	if (!title || !startRaw) {
		showModalError('<?php echo esc_js( __( 'Título e início são obrigatórios.', 'apollo-calendar' ) ); ?>');
		return;
	}

	const payload = {
		title,
		starts_at:     localToISO(startRaw),
		ends_at:       document.getElementById('appt-end').value ? localToISO(document.getElementById('appt-end').value) : null,
		all_day:       document.getElementById('appt-allday').checked,
		location:      document.getElementById('appt-location').value.trim(),
		description:   document.getElementById('appt-desc').value.trim(),
		color:         document.getElementById('appt-color').value,
		remind_before: parseInt(document.getElementById('appt-remind').value, 10) || 0,
	};

	const btn = document.getElementById('btn-modal-save');
	btn.disabled = true;
	btn.textContent = '…';

	try {
		if (editId) {
			await apiFetch('PUT', '/calendar/appointments/' + editId, payload);
		} else {
			await apiFetch('POST', '/calendar/appointments', payload);
		}
		closeModal();
		await loadItems();
	} catch(e) {
		showModalError(e.message);
	} finally {
		btn.disabled = false;
		btn.textContent = '<?php echo esc_js( __( 'Salvar', 'apollo-calendar' ) ); ?>';
	}
}

function showModalError(msg) {
	const el = document.getElementById('modal-error');
	el.textContent = msg;
	el.style.display = 'block';
}

async function confirmDelete(item) {
	if (!confirm('<?php echo esc_js( __( 'Excluir este compromisso?', 'apollo-calendar' ) ); ?>')) return;
	const numId = String(item.id).replace('personal-','');
	try {
		await apiFetch('DELETE', '/calendar/appointments/' + numId);
		await loadItems();
	} catch(e) {
		alert(e.message);
	}
}

/* ── Navigation ──────────────────────────────────────────── */
function navigate(delta) {
	if (view === 'month') {
		cursor = new Date(cursor.getFullYear(), cursor.getMonth() + delta, 1);
	} else if (view === 'week') {
		cursor = addDays(cursor, delta * 7);
	} else {
		cursor = addDays(cursor, delta);
	}
	loadItems();
}

/* ── Boot ────────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
	loadItems();

	document.getElementById('btn-prev').onclick  = () => navigate(-1);
	document.getElementById('btn-next').onclick  = () => navigate(1);
	document.getElementById('btn-today').onclick = () => {
		cursor = new Date();
		cursor.setHours(0,0,0,0);
		loadItems();
	};

	document.querySelectorAll('#cal-tabs button').forEach(btn => {
		btn.addEventListener('click', () => {
			document.querySelectorAll('#cal-tabs button').forEach(b => b.classList.remove('active'));
			btn.classList.add('active');
			view = btn.dataset.view;
			cursor = new Date();
			cursor.setHours(0,0,0,0);
			loadItems();
		});
	});

	document.getElementById('btn-add-appt').onclick    = () => openModal(null);
	document.getElementById('btn-modal-cancel').onclick = closeModal;
	document.getElementById('btn-modal-save').onclick   = saveModal;
	document.getElementById('btn-close-detail').onclick = hideDetail;

	document.getElementById('cal-modal-overlay').addEventListener('click', e => {
		if (e.target === document.getElementById('cal-modal-overlay')) closeModal();
	});

	document.addEventListener('click', e => {
		const detail = document.getElementById('cal-detail');
		if (detail.style.display === 'block' && !detail.contains(e.target)) {
			hideDetail();
		}
	});

	// Keyboard accessibility
	document.addEventListener('keydown', e => {
		if (e.key === 'Escape') { closeModal(); hideDetail(); }
	});
});

})();
</script>
