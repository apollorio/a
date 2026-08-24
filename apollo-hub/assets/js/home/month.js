/**
 * APOLLO::RIO — HOME / month.js
 * Month dropdown for events section — dynamic months, portal links
 */
;(function () {
    'use strict';

    var monthTrigger = document.getElementById('nhMonthTrigger');
    var monthMenu    = document.getElementById('nhMonthMenu');
    if (!monthTrigger || !monthMenu) return;

    var monthText = monthTrigger.querySelector('.nh-month-text');
    if (!monthText) return;

    var allMonths = ['January','February','March','April','May','June',
                     'July','August','September','October','November','December'];
    var startIdx = new Date().getMonth();

    var curMonth  = allMonths[startIdx];
    var nextMonth = allMonths[(startIdx + 1) % 12];
    var plusTwo   = allMonths[(startIdx + 2) % 12];

    var monthOptions = [
        { text: curMonth,  type: 'month' },
        { text: nextMonth, type: 'month' },
        { text: plusTwo,   type: 'month' },
        { text: '<i class="ri-calendar-2-line"></i> Ver todos', type: 'link', url: '/portal/eventos' },
        { text: '<i class="ri-calendar-schedule-line"></i> Incluir evento', type: 'link', url: '/novo-evento' }
    ];

    monthText.textContent = curMonth;

    /* Build list items (safe — text content is hardcoded, not user input) */
    monthMenu.innerHTML = monthOptions.map(function (opt) {
        var isActive = (opt.text === curMonth) ? ' active' : '';
        var isPortal = (opt.type === 'link') ? ' nh-portal-link' : '';
        var href = opt.url || '#';
        return '<li><a href="' + href + '" class="' + isActive + isPortal + '" data-type="' + opt.type + '">' + opt.text + '</a></li>';
    }).join('');

    /* Toggle */
    monthTrigger.addEventListener('click', function (e) {
        e.stopPropagation();
        monthMenu.classList.toggle('is-visible');

        if (typeof gsap !== 'undefined' && monthMenu.classList.contains('is-visible')) {
            gsap.from('#nhMonthMenu li', { x: 10, opacity: 0, stagger: 0.05, ease: 'power2.out' });
        }
    });

    /* Option click */
    monthMenu.addEventListener('click', function (e) {
        var target = e.target.closest('a');
        if (!target) return;
        if (target.dataset.type === 'month') {
            e.preventDefault();
            monthText.textContent = target.textContent.trim();
            monthMenu.querySelectorAll('a').forEach(function (a) { a.classList.remove('active'); });
            target.classList.add('active');
            monthMenu.classList.remove('is-visible');
        }
    });

    /* Close on outside click */
    document.addEventListener('click', function () {
        monthMenu.classList.remove('is-visible');
    });
})();
