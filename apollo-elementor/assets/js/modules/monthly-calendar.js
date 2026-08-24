(function() {
  "use strict";

  var REDUCED = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var DAY_NAMES = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
  var MONTH_NAMES = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December'
  ];

  function init() {
    var calendars = document.querySelectorAll('[data-ae-calendar]');
    if (!calendars.length) return;

    for (var i = 0; i < calendars.length; i++) {
      setupCalendar(calendars[i]);
    }
  }

  function setupCalendar(container) {
    var now = new Date();
    var state = {
      year: now.getFullYear(),
      month: now.getMonth(),
      container: container,
      events: parseEvents(container)
    };

    render(state);
    bindNav(state);
  }

  function parseEvents(container) {
    var raw = container.getAttribute('data-ae-events');
    if (!raw) return {};

    try {
      var arr = JSON.parse(raw);
      var map = {};
      for (var i = 0; i < arr.length; i++) {
        var key = arr[i].date;
        if (!map[key]) map[key] = [];
        map[key].push(arr[i]);
      }
      return map;
    } catch (e) {
      return {};
    }
  }

  function render(state) {
    var c = state.container;
    var grid = c.querySelector('[data-ae-cal-grid]');
    if (!grid) {
      grid = document.createElement('div');
      grid.setAttribute('data-ae-cal-grid', '');
      grid.className = 'ae-cal-grid';
      c.appendChild(grid);
    }

    var header = c.querySelector('[data-ae-cal-header]');
    if (!header) {
      header = document.createElement('div');
      header.setAttribute('data-ae-cal-header', '');
      header.className = 'ae-cal-header';
      c.insertBefore(header, grid);
    }

    header.innerHTML = '<button data-ae-cal-prev class="ae-btn">&larr;</button>' +
      '<span class="ae-cal-title">' + MONTH_NAMES[state.month] + ' ' + state.year + '</span>' +
      '<button data-ae-cal-next class="ae-btn">&rarr;</button>';

    var firstDay = new Date(state.year, state.month, 1).getDay();
    var daysInMonth = new Date(state.year, state.month + 1, 0).getDate();
    var today = new Date();
    var isCurrentMonth = (today.getFullYear() === state.year && today.getMonth() === state.month);

    var html = '';
    for (var d = 0; d < DAY_NAMES.length; d++) {
      html += '<div class="ae-cal-day-name ae-mono">' + DAY_NAMES[d] + '</div>';
    }

    for (var blank = 0; blank < firstDay; blank++) {
      html += '<div class="ae-cal-cell ae-cal-empty"></div>';
    }

    for (var day = 1; day <= daysInMonth; day++) {
      var dateKey = state.year + '-' +
        String(state.month + 1).padStart(2, '0') + '-' +
        String(day).padStart(2, '0');
      var hasEvent = state.events[dateKey];
      var isToday = isCurrentMonth && today.getDate() === day;

      var cls = 'ae-cal-cell';
      if (isToday) cls += ' ae-cal-today';
      if (hasEvent) cls += ' ae-cal-has-event';

      html += '<div class="' + cls + '" data-date="' + dateKey + '">' +
        '<span class="ae-cal-num">' + day + '</span>' +
        (hasEvent ? '<span class="ae-cal-dot"></span>' : '') +
        '</div>';
    }

    grid.innerHTML = html;

    if (!REDUCED && typeof gsap !== 'undefined') {
      var cells = grid.querySelectorAll('.ae-cal-cell:not(.ae-cal-empty)');
      gsap.fromTo(cells,
        { opacity: 0, y: 8 },
        { opacity: 1, y: 0, duration: 0.3, stagger: 0.015, ease: 'power2.out' }
      );
    }
  }

  function bindNav(state) {
    state.container.addEventListener('click', function(e) {
      var prev = e.target.closest('[data-ae-cal-prev]');
      var next = e.target.closest('[data-ae-cal-next]');

      if (prev) {
        state.month--;
        if (state.month < 0) { state.month = 11; state.year--; }
        render(state);
      } else if (next) {
        state.month++;
        if (state.month > 11) { state.month = 0; state.year++; }
        render(state);
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
