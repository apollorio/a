<?php

/**
 * Apollo Listing Header — kernel runtime (window.ApolloListingHeader).
 *
 * Ported from `<script id="apollo-header-kernel">` in the approved lab file
 * (header-of-listing-events.html, lines 281-659), which Headers 01/02/03 all
 * shared. What survived, what changed, and why:
 *
 *   KEPT  the circular clip-path wipe from the trigger's own centre, the GSAP
 *         timeline with the no-GSAP fallback, Escape-to-close, the month
 *         mask-swap, and the whole data-attribute contract.
 *   DROPPED buildChipsHTML() / buildListHTML() / the APOLLO_TAXONOMY literal.
 *         PHP renders the chips now (registry 03-apollo-rule data_flow), so a
 *         hard-coded term list in JS would be a second, lying source of truth.
 *   ADDED an instance registry + DOM CustomEvents, because a real screen has
 *         to react to the header, whereas the lab only had to look right.
 *
 * The header is a CONTROLLED component: it never fetches, never filters a
 * list, never knows what a "month" contains. It reports intent and accepts
 * state back. The screen that mounts it owns the data.
 *
 * EVENTS — all bubble from the header element, all cancelable-free:
 *   apollo:lh:month   { id, index, year, monthName, monthAbbr, ym, dir }
 *   apollo:lh:search  { id, query }
 *   apollo:lh:filter  { id, values, count }
 *   apollo:lh:clear   { id }
 *
 * @package Apollo\Templates
 * @since   1.5.1
 */

if (! defined('ABSPATH')) {
    exit;
}
?>
<script id="apollo-listing-header-runtime">
(function (w, d) {
  'use strict';

  if (w.ApolloListingHeader) { return; }

  var registry = {};

  function els(root, sel) {
    return root ? Array.prototype.slice.call(root.querySelectorAll(sel)) : [];
  }

  function reducedMotion() {
    return !!(w.matchMedia && w.matchMedia('(prefers-reduced-motion: reduce)').matches);
  }

  /* Wipe origin = the centre of whatever the user just pressed, in viewport
     coordinates. The lab measured against its 402px device frame; overlays are
     fixed to the viewport here, so the viewport IS the frame. */
  function wipeOrigin(trigger) {
    if (!trigger) { return '50% 50%'; }
    var r = trigger.getBoundingClientRect();
    return (r.left + r.width / 2) + 'px ' + (r.top + r.height / 2) + 'px';
  }

  function circleAt(pct, origin) {
    return 'circle(' + pct + '% at ' + origin + ')';
  }

  /* ── Overlay: one wipe implementation, two consumers ─────────────────────── */
  function Overlay(overlay, opts) {
    opts = opts || {};
    var open = false;
    var animating = false;
    var lastFocus = null;

    function paint(next, origin) {
      var openDur = typeof opts.openDuration === 'number' ? opts.openDuration : 0.82;
      var closeDur = typeof opts.closeDuration === 'number' ? opts.closeDuration : 0.66;
      var ease = opts.ease || 'power1.inOut';

      if (w.gsap && !reducedMotion()) {
        animating = true;
        w.gsap.killTweensOf(overlay);
        if (next) {
          w.gsap.set(overlay, { visibility: 'visible', pointerEvents: 'auto', clipPath: circleAt(0, origin) });
          w.gsap.to(overlay, {
            clipPath: circleAt(150, origin),
            duration: openDur,
            ease: ease,
            onComplete: function () { animating = false; if (opts.onOpened) { opts.onOpened(); } }
          });
        } else {
          w.gsap.to(overlay, {
            clipPath: circleAt(0, origin),
            duration: closeDur,
            ease: ease,
            onComplete: function () {
              w.gsap.set(overlay, { visibility: 'hidden', pointerEvents: 'none' });
              animating = false;
            }
          });
        }
        return;
      }

      overlay.style.clipPath = next ? circleAt(150, origin) : circleAt(0, origin);
      overlay.style.visibility = next ? 'visible' : 'hidden';
      overlay.style.pointerEvents = next ? 'auto' : 'none';
      animating = false;
      if (next && opts.onOpened) { opts.onOpened(); }
    }

    function setOpen(next, trigger) {
      next = !!next;
      if (next === open) { return; }
      open = next;
      overlay.setAttribute('aria-hidden', open ? 'false' : 'true');
      if (opts.trigger) { opts.trigger.setAttribute('aria-expanded', open ? 'true' : 'false'); }

      if (open) {
        lastFocus = d.activeElement;
      } else if (lastFocus && lastFocus.focus) {
        /* Returning focus is not a nicety: without it, closing the panel drops
           the caret at the top of the document and a keyboard user has to tab
           all the way back to the header they were just using. */
        try { lastFocus.focus({ preventScroll: true }); } catch (e) { lastFocus.focus(); }
      }

      paint(open, wipeOrigin(trigger || opts.trigger));
    }

    return {
      el: overlay,
      isOpen: function () { return open; },
      isBusy: function () { return animating; },
      open: function (trigger) { setOpen(true, trigger); },
      close: function () { setOpen(false); },
      toggle: function (trigger) { setOpen(!open, trigger); }
    };
  }

  /* ── One header instance ─────────────────────────────────────────────────── */
  function create(cfg) {
    cfg = cfg || {};
    var root = typeof cfg.root === 'string' ? d.getElementById(cfg.root) : cfg.root;
    if (!root) { return null; }
    if (registry[root.id]) { return registry[root.id]; }

    var host = root.closest('.alh-host') || root.parentNode;
    var months = cfg.months || [];
    var index = typeof cfg.month === 'number' ? cfg.month : 0;
    var year = typeof cfg.year === 'number' ? cfg.year : new Date().getFullYear();

    var monthEl = root.querySelector('[data-alh-month]');
    var yearEl = root.querySelector('[data-alh-year]');
    var scrub = root.querySelector('[data-alh-scrub]');
    var segs = els(scrub, '[data-alh-seg]');
    var badges = els(root, '[data-alh-filter-badge]');

    var searchTrigger = root.querySelector('[data-alh-search-open]');
    var filterTrigger = root.querySelector('[data-alh-filter-open]');
    var searchOv = host && host.querySelector('[data-alh-search-overlay]');
    var filterOv = host && host.querySelector('[data-alh-filter-overlay]');

    function emit(name, detail) {
      detail = detail || {};
      detail.id = root.id;
      root.dispatchEvent(new CustomEvent('apollo:lh:' + name, { detail: detail, bubbles: true }));
    }

    function ym() {
      return year + '-' + (index + 1 < 10 ? '0' : '') + (index + 1);
    }

    function monthCtx(dir) {
      var row = months[index] || {};
      return {
        index: index,
        year: year,
        ym: ym(),
        monthName: row.label || '',
        monthAbbr: row.abbr || '',
        count: row.count || 0,
        dir: dir || 0
      };
    }

    /* ── Month ─────────────────────────────────────────────────────────────── */
    function paintScrub() {
      segs.forEach(function (seg, i) {
        var on = i === index;
        seg.classList.toggle('is-active', on);
        seg.setAttribute('aria-selected', on ? 'true' : 'false');
        seg.tabIndex = on ? 0 : -1;
      });
    }

    /* True only while the mask swap is mid-flight. setMonths() consults it: the
       screen answers a month change by recounting, and a plain repaint landing
       on top of the wipe would print the new name before the old one had
       finished leaving. */
    var swapping = false;

    function paintMonth(animate) {
      var row = months[index] || {};
      if (yearEl) { yearEl.textContent = '\u2019' + String(year).slice(-2); }
      if (!monthEl) { return; }

      var name = row.label || '';
      if (!animate || !w.gsap || reducedMotion()) {
        monthEl.textContent = name;
        return;
      }
      /* The lab's mask swap, timings included: wipe out fast on power1.in,
         re-reveal slower on power2.out so the new name feels like it arrives
         rather than snaps. */
      swapping = true;
      w.gsap.killTweensOf(monthEl);
      w.gsap.timeline({ onComplete: function () { swapping = false; } })
        .to(monthEl, { clipPath: 'inset(0 100% 0 0)', duration: 0.39, ease: 'power1.in' })
        .call(function () { monthEl.textContent = name; })
        .set(monthEl, { clipPath: 'inset(0 100% 0 0)' })
        .to(monthEl, { clipPath: 'inset(0 0% 0 0)', duration: 0.78, ease: 'power2.out' });
    }

    function setMonth(next, opts) {
      opts = opts || {};
      next = Number(next);
      if (isNaN(next)) { return; }

      var carry = Math.floor(next / 12);
      var wrapped = ((next % 12) + 12) % 12;
      var nextYear = typeof opts.year === 'number' ? opts.year : year + carry;
      var dir = typeof opts.dir === 'number'
        ? opts.dir
        : ((nextYear * 12 + wrapped) - (year * 12 + index));

      if (wrapped === index && nextYear === year) { return; }

      index = wrapped;
      year = nextYear;
      paintScrub();
      paintMonth(opts.animate !== false);
      if (!opts.silent) { emit('month', monthCtx(dir > 0 ? 1 : (dir < 0 ? -1 : 0))); }
    }

    if (scrub) {
      scrub.addEventListener('click', function (e) {
        var seg = e.target.closest('[data-alh-seg]');
        if (seg) { setMonth(Number(seg.getAttribute('data-alh-seg')), { year: year }); }
      });
      /* Roving tabindex: the scrubber is one tab stop, arrows walk it. */
      scrub.addEventListener('keydown', function (e) {
        var delta = e.key === 'ArrowRight' ? 1 : (e.key === 'ArrowLeft' ? -1 : 0);
        if (!delta) { return; }
        e.preventDefault();
        setMonth(index + delta, { year: year });
        var active = segs[index];
        if (active) { active.focus(); }
      });
    }

    var nextBtn = root.querySelector('[data-alh-month-next]');
    if (nextBtn) { nextBtn.addEventListener('click', function () { setMonth(index + 1); }); }
    var prevBtn = root.querySelector('[data-alh-month-prev]');
    if (prevBtn) { prevBtn.addEventListener('click', function () { setMonth(index - 1); }); }

    /* ── Filter ────────────────────────────────────────────────────────────── */
    var filter = null;
    if (filterOv) {
      filter = Overlay(filterOv, {
        trigger: filterTrigger,
        openDuration: 0.82,
        closeDuration: 0.66,
        ease: 'power1.inOut',
        onOpened: function () {
          var first = filterOv.querySelector('.alh-fx__chip input, .alh-ov__close');
          if (first) { try { first.focus({ preventScroll: true }); } catch (e) { first.focus(); } }
        }
      });

      var countN = filterOv.querySelector('[data-alh-filter-n]');

      var readValues = function () {
        var out = {};
        els(filterOv, '[data-alh-group]').forEach(function (group) {
          var key = group.getAttribute('data-alh-group');
          var single = group.getAttribute('data-alh-type') === 'radio';
          var checked = els(group, 'input:checked').map(function (i) { return i.value; });
          out[key] = single ? (checked[0] || '') : checked;
        });
        return out;
      };

      var countActive = function () {
        var n = 0;
        els(filterOv, '[data-alh-group][data-alh-counts="1"]').forEach(function (group) {
          n += els(group, 'input:checked').length;
        });
        return n;
      };

      var syncCount = function () {
        var n = countActive();
        if (countN) { countN.textContent = String(n); }
        badges.forEach(function (b) {
          b.textContent = String(n);
          b.classList.toggle('is-visible', n > 0);
        });
        return n;
      };

      filterOv.addEventListener('change', syncCount);

      if (filterTrigger) {
        filterTrigger.addEventListener('click', function () {
          if (filter.isBusy()) { return; }
          filter.open(filterTrigger);
        });
      }
      var fClose = filterOv.querySelector('[data-alh-filter-close]');
      if (fClose) { fClose.addEventListener('click', function () { filter.close(); }); }

      var fClear = filterOv.querySelector('[data-alh-filter-clear]');
      if (fClear) {
        fClear.addEventListener('click', function () {
          els(filterOv, '[data-alh-group][data-alh-counts="1"] input:checked').forEach(function (i) {
            i.checked = false;
          });
          syncCount();
          emit('clear', {});
        });
      }

      var fApply = filterOv.querySelector('[data-alh-filter-apply]');
      if (fApply) {
        fApply.addEventListener('click', function () {
          if (filter.isBusy()) { return; }
          emit('filter', { values: readValues(), count: syncCount() });
          filter.close();
        });
      }

      filter.read = readValues;
      filter.sync = syncCount;
      syncCount();
    }

    /* ── Search ────────────────────────────────────────────────────────────── */
    var search = null;
    if (searchOv) {
      var input = searchOv.querySelector('[data-alh-search-input]');

      search = Overlay(searchOv, {
        trigger: searchTrigger,
        openDuration: 0.84,
        closeDuration: 0.68,
        ease: 'power1.inOut',
        onOpened: function () {
          if (input) { try { input.focus({ preventScroll: true }); } catch (e) { input.focus(); } }
        }
      });

      if (searchTrigger) {
        searchTrigger.addEventListener('click', function () {
          if (search.isBusy()) { return; }
          search.open(searchTrigger);
        });
      }
      var sClose = searchOv.querySelector('[data-alh-search-close]');
      if (sClose) { sClose.addEventListener('click', function () { search.close(); }); }

      var form = searchOv.querySelector('[data-alh-search-form]');
      if (form) {
        form.addEventListener('submit', function (e) {
          e.preventDefault();
          if (search.isBusy()) { return; }
          emit('search', { query: input ? String(input.value || '').trim() : '' });
          search.close();
        });
      }
    }

    /* Escape closes whichever panel is up — registered once for the instance,
       and removed by destroy() so a re-rendered screen cannot leak listeners. */
    function onKey(e) {
      if (e.key !== 'Escape') { return; }
      if (search && search.isOpen() && !search.isBusy()) { search.close(); return; }
      if (filter && filter.isOpen() && !filter.isBusy()) { filter.close(); }
    }
    d.addEventListener('keydown', onKey);

    var api = {
      el: root,
      getMonth: function () { return index; },
      getYear: function () { return year; },
      getContext: function () { return monthCtx(0); },
      setMonth: setMonth,
      /** Re-label / re-count the twelve segments without a re-render. */
      setMonths: function (rows) {
        if (!rows || !rows.length) { return; }
        months = rows;
        segs.forEach(function (seg, i) {
          var row = months[i] || {};
          var name = (row.label || '') + ' ' + year;
          if (row.count) { name += ' \u00B7 ' + row.count; }
          seg.setAttribute('data-alh-count', String(row.count || 0));
          seg.setAttribute('title', name);
          seg.setAttribute('aria-label', name);
        });
        if (!swapping) { paintMonth(false); }
      },
      getFilters: function () { return filter && filter.read ? filter.read() : {}; },
      openFilter: function () { if (filter) { filter.open(filterTrigger); } },
      closeFilter: function () { if (filter) { filter.close(); } },
      openSearch: function () { if (search) { search.open(searchTrigger); } },
      closeSearch: function () { if (search) { search.close(); } },
      destroy: function () {
        d.removeEventListener('keydown', onKey);
        delete registry[root.id];
      }
    };

    registry[root.id] = api;
    return api;
  }

  w.ApolloListingHeader = {
    create: create,
    get: function (id) { return registry[id] || null; },
    all: function () { return registry; }
  };
})(window, document);
</script>
