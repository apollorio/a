/**
 * Apollo Events — Calendar JS
 *
 * Handles: calendar navigation, day click to show events, AJAX month loading.
 *
 * @package Apollo\Event
 */

(function () {
	'use strict';

	const ApolloCalendar = {

		config: window.apolloEvents || {},

		init() {
			this.initDayClick();
			this.initNavigation();
		},

		// ─── Day Click ───────────────────────────────────────────────────

		initDayClick() {
			document.addEventListener('click', (e) => {
				const day = e.target.closest('.a-eve-cal-day--has-event');
				if (!day) return;

				const date = day.dataset.date;
				if (!date) return;

				// Update selected state
				document.querySelectorAll('.a-eve-cal-day--selected').forEach(el => {
					el.classList.remove('a-eve-cal-day--selected');
				});
				day.classList.add('a-eve-cal-day--selected');

				// Show events for this date in sidebar (month-extra-cal)
				this.loadDayEvents(date);

				// Scroll to events in wrapper context
				const wrapper = day.closest('.a-eve-wrapper');
				if (wrapper) {
					const eventsContainer = wrapper.querySelector('.a-eve-events');
					if (eventsContainer) {
						// Filter events by date (visual only)
						this.filterEventsByDate(eventsContainer, date);
					}
				}
			});
		},

		// ─── Navigation ──────────────────────────────────────────────────

		initNavigation() {
			document.addEventListener('click', (e) => {
				const nav = e.target.closest('.a-eve-cal-nav');
				if (!nav) return;

				e.preventDefault();

				const month = nav.dataset.month;
				const year = nav.dataset.year;

				if (month && year) {
					this.loadMonth(nav, parseInt(month), parseInt(year));
				} else {
					// Week navigation
					this.handleWeekNav(nav);
				}
			});
		},

		// ─── Load Month via REST ─────────────────────────────────────────

		async loadMonth(navBtn, month, year) {
			const calendar = navBtn.closest('.a-eve-calendar');
			if (!calendar) return;

			try {
				// Format date parameters
				const startDate = `${year}-${String(month).padStart(2, '0')}-01`;
				const daysInMonth = new Date(year, month, 0).getDate();
				const endDate = `${year}-${String(month).padStart(2, '0')}-${daysInMonth}`;

				const response = await fetch(
					`${this.config.rest_url}/eventos?date_from=${encodeURIComponent(startDate)}&date_to=${encodeURIComponent(endDate)}&per_page=100`,
					{
						headers: {
							'X-WP-Nonce': this.config.nonce,
						},
					}
				);

				if (!response.ok) return;

				const events = await response.json();

				// Map events by date
				const eventsByDate = {};
				events.forEach(event => {
					if (event.start_date) {
						if (!eventsByDate[event.start_date]) {
							eventsByDate[event.start_date] = [];
						}
						eventsByDate[event.start_date].push(event);
					}
				});

				// Rebuild calendar
				this.renderMonth(calendar, month, year, eventsByDate);

			} catch (error) {
				console.error('Apollo Events: Failed to load month', error);
			}
		},

		// ─── Render Month ────────────────────────────────────────────────

		renderMonth(calendar, month, year, eventsByDate) {
			const mesesPt = [
				'', 'Janeiro', 'Fevereiro', 'Março', 'Abril',
				'Maio', 'Junho', 'Julho', 'Agosto',
				'Setembro', 'Outubro', 'Novembro', 'Dezembro'
			];

			const diasSemana = ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'];

			const firstDay = new Date(year, month - 1, 1);
			const daysIn = new Date(year, month, 0).getDate();
			const startDow = (firstDay.getDay() + 6) % 7; // 0=Mon
			const today = new Date().toISOString().split('T')[0];

			// Prev/Next months
			let prevMonth = month - 1, prevYear = year;
			if (prevMonth < 1) { prevMonth = 12; prevYear--; }
			let nextMonth = month + 1, nextYear = year;
			if (nextMonth > 12) { nextMonth = 1; nextYear++; }

			const calMonth = calendar.querySelector('.a-eve-cal-month');
			if (!calMonth) return;

			let html = `
				<div class="a-eve-cal-header">
					<button class="a-eve-cal-nav a-eve-cal-nav--prev" data-month="${prevMonth}" data-year="${prevYear}">←</button>
					<h3 class="a-eve-cal-title">${mesesPt[month]} ${year}</h3>
					<button class="a-eve-cal-nav a-eve-cal-nav--next" data-month="${nextMonth}" data-year="${nextYear}">→</button>
				</div>
				<div class="a-eve-cal-grid">
			`;

			diasSemana.forEach(d => {
				html += `<div class="a-eve-cal-dow">${d}</div>`;
			});

			for (let i = 0; i < startDow; i++) {
				html += '<div class="a-eve-cal-day a-eve-cal-day--empty"></div>';
			}

			for (let day = 1; day <= daysIn; day++) {
				const dateKey = `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
				const hasEvent = eventsByDate[dateKey] && eventsByDate[dateKey].length > 0;
				const isToday = dateKey === today;
				const count = hasEvent ? eventsByDate[dateKey].length : 0;

				let classes = 'a-eve-cal-day';
				if (hasEvent) classes += ' a-eve-cal-day--has-event';
				if (isToday) classes += ' a-eve-cal-day--today';

				html += `
					<div class="${classes}" data-date="${dateKey}">
						<span class="a-eve-cal-day-num">${day}</span>
						${hasEvent ? `<span class="a-eve-cal-dot" title="${count} evento(s)"></span>` : ''}
					</div>
				`;
			}

			html += '</div>';
			calMonth.innerHTML = html;
		},

		// ─── Day Events (sidebar) ────────────────────────────────────────

		async loadDayEvents(date) {
			const sidebar = document.getElementById('a-eve-cal-sidebar');
			if (!sidebar) return;

			const content = sidebar.querySelector('.a-eve-cal-sidebar-content');
			if (!content) return;

			content.innerHTML = '<p style="text-align:center;color:#999;">Carregando...</p>';

			try {
				const response = await fetch(
					`${this.config.rest_url}/eventos/por-data/${date}`,
					{
						headers: { 'X-WP-Nonce': this.config.nonce },
					}
				);

				if (!response.ok) throw new Error('Falha ao carregar eventos');

				const events = await response.json();

				if (!events.length) {
					content.innerHTML = '<p class="a-eve-cal-sidebar-empty">Nenhum evento neste dia.</p>';
					return;
				}

				let html = '';
				events.forEach(event => {
					html += `
						<a href="${event.permalink}" class="a-eve-cal-sidebar-event">
							<strong>${this.escapeHtml(event.title)}</strong>
							${event.start_time ? `<span>🕐 ${event.start_time}</span>` : ''}
							${event.loc ? `<span>📍 ${this.escapeHtml(event.loc.title)}</span>` : ''}
						</a>
					`;
				});

				content.innerHTML = html;

			} catch (error) {
				content.innerHTML = '<p class="a-eve-cal-sidebar-empty">Erro ao carregar eventos.</p>';
				console.error('Apollo Events:', error);
			}
		},

		// ─── Filter Events ───────────────────────────────────────────────

		filterEventsByDate(container, date) {
			const cards = container.querySelectorAll('[data-event-id]');
			if (!cards.length) return;

			// For now, highlight matching events
			cards.forEach(card => {
				const dateEl = card.querySelector('.a-eve-card__date, .a-eve-list-item__date-block');
				// We can't easily filter without date data attribute, so scroll instead
			});
		},

		// ─── Week Navigation ─────────────────────────────────────────────

		handleWeekNav(navBtn) {
			// Week nav is handled by page reload for simplicity
			// Could be enhanced with AJAX later
		},

		// ─── Helpers ─────────────────────────────────────────────────────

		escapeHtml(str) {
			const div = document.createElement('div');
			div.textContent = str;
			return div.innerHTML;
		},
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', () => ApolloCalendar.init());
	} else {
		ApolloCalendar.init();
	}
})();
