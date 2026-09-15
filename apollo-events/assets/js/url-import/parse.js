/**
 * Apollo URL Importer — parse
 * @package apollo-events
 */
(function (global) {
  'use strict';
  const AUI = global.ApolloUrlImport = global.ApolloUrlImport || {};

  const slugify = AUI.slugify;
  const PROMOTER_LOC_MAP = AUI.PROMOTER_LOC_MAP;
  const $ = AUI.$;
  /* ============== JSON-LD + date helpers ============== */
    function metaContent(doc, prop){
      return (
        doc.querySelector('meta[property="' + prop + '"]')?.getAttribute('content') ||
        doc.querySelector('meta[name="' + prop + '"]')?.getAttribute('content') ||
        ''
      ).trim();
    }
  
    function ldTypeList(t){
      if (!t) return [];
      return (Array.isArray(t) ? t : [t]).map(String);
    }
  
    function isLdEventType(t){
      return ldTypeList(t).some(x => {
        const leaf = x.includes('/') ? x.split('/').pop() : x;
        return leaf === 'MusicEvent' || leaf === 'Event';
      });
    }
  
    function collectLdEvents(node, out){
      if (!node || typeof node !== 'object') return;
      if (Array.isArray(node)) { node.forEach(n => collectLdEvents(n, out)); return; }
      if (isLdEventType(node['@type'])) out.push(node);
      if (node['@graph']) collectLdEvents(node['@graph'], out);
    }
  
    function parseJsonLdEvents(doc){
      const hits = [];
      doc.querySelectorAll('script[type="application/ld+json"]').forEach(s => {
        try { collectLdEvents(JSON.parse(s.textContent || ''), hits); } catch (e) { /* ignore bad LD */ }
      });
      return hits.find(e => ldTypeList(e['@type']).some(t => (t.includes('/') ? t.split('/').pop() : t) === 'MusicEvent'))
        || hits[0]
        || null;
    }
  
    function firstHttpUrl(val){
      if (!val) return '';
      if (typeof val === 'string') return /^https?:\/\//i.test(val) ? val : '';
      if (Array.isArray(val)) {
        for (let i = 0; i < val.length; i++) {
          const u = firstHttpUrl(val[i]);
          if (u) return u;
        }
        return '';
      }
      if (typeof val === 'object') return firstHttpUrl(val.url || val.contentUrl || val['@id'] || '');
      return '';
    }
  
    function cheapestOfferPrice(offers){
      const list = !offers ? [] : (Array.isArray(offers) ? offers : [offers]);
      let best = null;
      list.forEach(o => {
        if (!o || o.price == null || o.price === '') return;
        const n = Number(String(o.price).replace(',', '.'));
        if (!Number.isFinite(n)) return;
        if (best == null || n < best) best = n;
      });
      return best == null ? '' : String(best);
    }
  
    function ymdInSaoPaulo(iso){
      if (!iso) return '';
      const d = new Date(iso);
      if (Number.isNaN(d.getTime())) return '';
      try {
        return new Intl.DateTimeFormat('en-CA', {
          timeZone: 'America/Sao_Paulo',
          year: 'numeric', month: '2-digit', day: '2-digit'
        }).format(d);
      } catch (e) {
        return '';
      }
    }
  
    /** Apollo nightclub hours: party day 23:00 → next calendar day 07:00. */
    function apolloNightHours(startDateYmd){
      const empty = { start_date: '', start_time: '23:00', end_date: '', end_time: '07:00' };
      if (!startDateYmd || !/^\d{4}-\d{2}-\d{2}$/.test(startDateYmd)) return empty;
      const parts = startDateYmd.split('-').map(Number);
      const next = new Date(Date.UTC(parts[0], parts[1] - 1, parts[2]));
      next.setUTCDate(next.getUTCDate() + 1);
      const ey = next.getUTCFullYear();
      const em = String(next.getUTCMonth() + 1).padStart(2, '0');
      const ed = String(next.getUTCDate()).padStart(2, '0');
      return {
        start_date: startDateYmd,
        start_time: '23:00',
        end_date: ey + '-' + em + '-' + ed,
        end_time: '07:00'
      };
    }
  
    const PT_MONTHS = {
      jan:1, janeiro:1, fev:2, fevereiro:2, mar:3, marco:3, 'março':3,
      abr:4, abril:4, mai:5, maio:5, jun:6, junho:6, jul:7, julho:7,
      ago:8, agosto:8, set:9, setembro:9, out:10, outubro:10,
      nov:11, novembro:11, dez:12, dezembro:12
    };
  
    function resolvePtYear(month, day){
      const now = new Date();
      let year = now.getFullYear();
      const candidate = new Date(year, month - 1, day);
      if (candidate.getTime() < now.getTime() - 31 * 86400000) year += 1;
      return year;
    }
  
    /** Small JS port of PtDate::date — title/meta fallbacks when LD lacks startDate. */
    function parsePtDate(text){
      let t = String(text || '').toLowerCase().replace(/\s+/g, ' ').trim();
      if (!t) return '';
      const iso = t.match(/(\d{4})-(\d{2})-(\d{2})/);
      if (iso) return iso[1] + '-' + iso[2] + '-' + iso[3];
      t = t.replace(/^(?:dom|seg|ter|qua|qui|sex|s[áa]b)[a-zç-]*\.?,?\s*/u, '');
      const names = Object.keys(PT_MONTHS).join('|');
      let m = t.match(new RegExp('(\\d{1,2})\\s*(?:de\\s+)?(' + names + ')\\b(?:\\s*(?:de\\s+)?(\\d{4}))?', 'u'));
      if (m) {
        const day = parseInt(m[1], 10);
        const month = PT_MONTHS[m[2]] || 0;
        if (!month || day < 1 || day > 31) return '';
        const year = m[3] ? parseInt(m[3], 10) : resolvePtYear(month, day);
        return year + '-' + String(month).padStart(2,'0') + '-' + String(day).padStart(2,'0');
      }
      m = t.match(/\b(\d{1,2})\/(\d{1,2})(?:\/(\d{2,4}))?\b/);
      if (m) {
        const day = parseInt(m[1], 10);
        const month = parseInt(m[2], 10);
        if (month < 1 || month > 12) return '';
        let year = m[3] ? parseInt(m[3], 10) : resolvePtYear(month, day);
        if (year < 100) year += 2000;
        return year + '-' + String(month).padStart(2,'0') + '-' + String(day).padStart(2,'0');
      }
      return '';
    }
  
    function pageTicketUrl(doc, rawUrl){
      if (rawUrl) {
        try { return new URL(rawUrl).href; } catch (e) { /* fall through */ }
      }
      const og = metaContent(doc, 'og:url');
      if (og) return og;
      const can = doc.querySelector('link[rel="canonical"]')?.getAttribute('href') || '';
      return can || rawUrl || '';
    }
  
    function couponFromUrl(rawUrl){
      try { return new URL(rawUrl).searchParams.get('c') || ''; } catch (e) { return ''; }
    }
  
    /** Split "<PRESENTER> apresenta <TITLE>" (mirrors BlueTicketProvider::split_presenter). */
    function splitPresenter(name){
      const raw = String(name || '').replace(/\s+/g, ' ').trim();
      if (!raw) return { presenter: '', title: '' };
      const m = raw.match(/^(.{2,80}?)\s+(?:apresenta(?:m)?|apresentando|presents?|pres\.|feat\.)\s+(.+)$/i);
      if (m) return { presenter: m[1].trim(), title: m[2].trim() };
      return { presenter: '', title: raw };
    }
  
    function cleanBtTitle(raw){
      return String(raw || '')
        .replace(/\s+/g, ' ')
        .replace(/\s*[-–—]\s*Rio de Janeiro\s*\/\s*RJ\s*$/i, '')
        .trim();
    }
  
    function applyPromoterLoc(presenter, loc){
      const out = { locSlug: loc.locSlug || '', locName: loc.locName || '' };
      if (!presenter) return out;
      const key = AUI.slugify(presenter);
      if (AUI.PROMOTER_LOC_MAP[key]) {
        out.locSlug = AUI.PROMOTER_LOC_MAP[key].slug;
        out.locName = AUI.PROMOTER_LOC_MAP[key].name;
      } else if (/d[\s-]?edge/i.test(presenter)) {
        out.locSlug = 'dedge';
        out.locName = 'D-EDGE';
      } else if (!out.locName) {
        out.locName = presenter;
        out.locSlug = AUI.slugify(presenter);
      }
      return out;
    }
  
    function coverFromBtDom(doc){
      const coverEl = doc.querySelector('.v-image__image--cover');
      if (!coverEl) return '';
      const style = coverEl.getAttribute('style') || '';
      const m = style.match(/url\((?:&quot;|"|')?(https?:\/\/[^)"'&]+)/i);
      return m ? m[1] : '';
    }
  
    function bioFromBtDom(doc){
      const el = doc.querySelector('#section-descricao .event-text')
        || doc.querySelector('.event-text.image-mobile');
      if (!el) return '';
      return (el.textContent || '').replace(/\s+/g, ' ').trim();
    }
  
    function locFromBtDom(doc){
      const spans = Array.from(doc.querySelectorAll('.event-subinfos .event-description'))
        .map(s => (s.textContent || '').replace(/\s+/g, ' ').trim())
        .filter(t => t && t !== '•');
      return spans[0] || '';
    }
  
    
  /* ============== parsers ============== */
    function parseBlueTicket(doc, rawUrl){
      const ld = parseJsonLdEvents(doc);
      const ticketUrl = pageTicketUrl(doc, rawUrl) || rawUrl || '';
      const coupon = couponFromUrl(ticketUrl) || AUI.$('cfgCoupon').value || '';
  
      let title = '';
      let cover = '';
      let bio = '';
      let locName = '';
      let startDay = '';
      let presenter = '';
  
      if (ld) {
        const split = splitPresenter(cleanBtTitle(ld.name || ''));
        title = split.title;
        presenter = split.presenter;
        cover = firstHttpUrl(ld.image);
        const loc = ld.location;
        if (loc && typeof loc === 'object') locName = String(loc.name || '').trim().replace(/\s+/g, ' ');
        else if (typeof loc === 'string') locName = loc.trim();
        startDay = ymdInSaoPaulo(ld.startDate);
      }
  
      /* DOM / og fallbacks when LD missing or incomplete */
      if (!title) {
        const rawTitle = cleanBtTitle(
          doc.querySelector('.event-name')?.textContent || metaContent(doc, 'og:title') || ''
        );
        const split = splitPresenter(rawTitle);
        title = split.title || rawTitle;
        if (!presenter) presenter = split.presenter;
      }
      if (!cover) {
        cover = coverFromBtDom(doc) || metaContent(doc, 'og:image') || '';
      }
      if (!locName) {
        locName = locFromBtDom(doc) || '';
        if (!locName) {
          const ogDesc = metaContent(doc, 'og:description');
          const dash = ogDesc.lastIndexOf(' - ');
          if (dash >= 0) locName = ogDesc.slice(dash + 3).trim();
        }
      }
      if (!bio) bio = bioFromBtDom(doc);
      if (!startDay) {
        startDay = parsePtDate(metaContent(doc, 'og:description'))
          || parsePtDate(metaContent(doc, 'og:title'))
          || parsePtDate(doc.querySelector('.event-name')?.textContent || '')
          || parsePtDate(doc.body?.textContent?.slice(0, 1200) || '');
      }
  
      const hours = apolloNightHours(startDay);
      const mapped = applyPromoterLoc(presenter, { locSlug: '', locName });
      const locSlug = mapped.locSlug || AUI.slugify(mapped.locName || locName);
  
      return {
        platform: 'blueticket',
        title, locSlug, locName: mapped.locName || locName, locId: '',
        cover, video: '', bio,
        start_date: hours.start_date,
        start_time: hours.start_time,
        end_date: hours.end_date,
        end_time: hours.end_time,
        ticket_price: 'Ingressos do Evento',
        ticketUrl, coupon,
        sourceUrl: rawUrl || ticketUrl,
        performers: []
      };
    }
  
    function parseShotgun(doc, rawUrl){
      const ld = parseJsonLdEvents(doc);
      /* Submitted shotgun.live URL is ALWAYS the ticket CTA href. */
      const ticketUrl = rawUrl || pageTicketUrl(doc, rawUrl) || '';
      const coupon = couponFromUrl(ticketUrl);
  
      let title = '';
      let cover = '';
      let video = '';
      let bio = '';
      let locName = '';
      let performers = [];
      let startDay = '';
      let ticket_price = '';

      if (ld) {
        title = String(ld.name || '').trim().replace(/\s+/g, ' ');
        cover = firstHttpUrl(ld.image);
        bio = String(ld.description || '').trim();
        const loc = ld.location;
        if (loc && typeof loc === 'object') locName = String(loc.name || '').trim().replace(/\s+/g, ' ');
        else if (typeof loc === 'string') locName = loc.trim();
        ticket_price = cheapestOfferPrice(ld.offers);
        const perf = ld.performer;
        if (Array.isArray(perf)) {
          performers = perf.map(p => (typeof p === 'string' ? p : (p && p.name) || '')).filter(Boolean);
        } else if (perf) {
          const n = typeof perf === 'string' ? perf : (perf.name || '');
          if (n) performers = [n];
        }
        startDay = ymdInSaoPaulo(ld.startDate);
      }
  
      /* DOM / og fallbacks when LD missing or incomplete */
      if (!title) {
        title = (doc.querySelector('h1[data-slot="heading"]')?.textContent || metaContent(doc, 'og:title') || '').trim().replace(/\s+/g, ' ');
      }
      if (!cover) {
        cover = metaContent(doc, 'og:image') || '';
        const videoEl = doc.querySelector('video');
        const poster = videoEl ? (videoEl.getAttribute('poster') || '') : '';
        if (!cover && /^https?:\/\//i.test(poster)) cover = poster;
      }
      video = metaContent(doc, 'og:video') || metaContent(doc, 'og:video:url') || '';
      if (!video) {
        const videoEl = doc.querySelector('video');
        video = videoEl ? (videoEl.getAttribute('src') || '') : '';
      }
      if (!locName) {
        let venueEl = doc.querySelector('div.flex-1.py-4.text-foreground');
        if (!venueEl) {
          venueEl = Array.from(doc.querySelectorAll('div')).find(d => {
            const cls = d.className || '';
            return typeof cls === 'string' && cls.includes('flex-1') && cls.includes('py-4') && cls.includes('text-foreground');
          });
        }
        locName = venueEl ? venueEl.textContent.trim().replace(/\s+/g, ' ') : '';
      }
      if (!bio) {
        const bioEl = doc.querySelector('.break-words.whitespace-pre-wrap');
        bio = bioEl ? bioEl.textContent.trim() : '';
      }
      if (!startDay) {
        startDay = parsePtDate(title) || parsePtDate(metaContent(doc, 'og:title')) || parsePtDate(doc.body?.textContent?.slice(0, 800) || '');
      }
  
      const hours = apolloNightHours(startDay);
      const locSlug = AUI.slugify(locName);
  
      return {
        platform: 'shotgun',
        title, locSlug, locName, locId: '',
        cover, video, bio,
        start_date: hours.start_date,
        start_time: hours.start_time,
        end_date: hours.end_date,
        end_time: hours.end_time,
        ticket_price: ticket_price || '',
        ticketUrl, coupon,
        sourceUrl: rawUrl || ticketUrl,
        performers
      };
    }
  
    
  AUI.Parse = {
    parseBlueTicket: parseBlueTicket,
    parseShotgun: parseShotgun,
    parseJsonLdEvents: parseJsonLdEvents,
    apolloNightHours: apolloNightHours,
    parsePtDate: parsePtDate
  };

})(window);
