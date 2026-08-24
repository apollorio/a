#!/usr/bin/env python3
"""
DJ Single — cell generator.  MOCKUP IS THE SINGLE SOURCE OF TRUTH.

Reads _sandbox/dj-single-page.mockup.html and writes the template-part cells
that styles/base/single-dj.php composes. It slices the mockup; it never
re-types markup. Edit the mockup, re-run, review the diff.

    python3 apollo-djs/_sandbox/build-dj-cells.py

WHY THE PAGE DID NOT LOOK LIKE THE MOCKUP
=========================================
The first cut of this generator copied the mockup verbatim. It rendered as a
standalone file and did NOT render inside WordPress, for two reasons that had
nothing to do with the markup being wrong:

1 · CLASS COLLISION.  The mockup is a standalone document, so it uses short,
    obvious class names: .wrap .hero .btn .pill .sec .tag .num .dot .lbl .serif
    .display .foot .dock .kit .toast .roster .about … 129 of them. Inside the
    Apollo ecosystem those names are already taken — core.js and the DS ship
    global rules for .btn, .pill, .wrap, .tag and friends. Two stylesheets then
    fight over the same selector and whichever loads last wins, per property.
    The result is not "broken", it is *subtly wrong everywhere*: wrong padding,
    wrong radius, wrong type scale, wrong button fill. Exactly the reported
    "quite far away from the mockup".

    Fix: every class is namespaced to `dj-`. `.wrap` becomes `.dj-wrap`,
    `.hero-name` becomes `.dj-hero-name`. Nothing else on the platform uses that
    prefix, so nothing can reach into this page and nothing here leaks out.
    RemixIcon's `ri-*` classes are NEVER renamed — they are a functional
    contract with the icon runtime, not styling.

2 · BARE ELEMENT SELECTORS.  The mockup styles `a`, `button`, `figure`, `i`,
    `img`, `ul` globally. On a WordPress page the active theme does too, and its
    rules are just as global. Those are scoped under `.dj-page` here, so the
    page styles its own descendants and stops arguing with the theme.

A third defect was found the same day: the stored mockup copy had been saved as
Latin-1, so every accented character in the Portuguese copy was mojibake
("Cartão" → "CartÃ£o") and the box-drawing comments were garbage. The generator
now refuses to run on a mockup that is not clean UTF-8.

ORDER OF OPERATIONS
===================
namespace the whole document  →  slice into cells by id anchor  →  substitute
dynamic values. Namespacing first means the slice anchors and the substitution
anchors both speak the final class vocabulary, and there is exactly one place
where a class name is rewritten.

Cells are sliced by ID ANCHOR, not line number. An earlier version used fixed
line numbers and two ranges silently overlapped by three lines, which put a
stray `</div>` in one cell and an unclosed `<header>` in the next.
"""
import os
import re
import sys

HERE = os.path.dirname(os.path.abspath(__file__))
PLUGIN = os.path.dirname(HERE)
# dj-mockup.html, not dj-single-page.mockup.html: the latter was saved as
# Latin-1 at some point (every accented character became mojibake — "Cartão" →
# "CartÃ£o") and is now locked by the deploy sync, so it cannot be replaced in
# place. This is the clean UTF-8 copy and the one true source. Delete the old
# file when the sync releases it.
MOCKUP = os.path.join(HERE, 'dj-mockup.html')
OUT = os.path.join(PLUGIN, 'styles', 'base', 'template-parts', 'single')

# Page root class. Scopes the mockup's bare element rules and gives the whole
# screen one handle for a theme override.
ROOT = 'dj-page'
PREFIX = 'dj-'

# Never renamed: the icon runtime binds to these.
KEEP_PREFIXES = ('ri-', PREFIX)

# Element selectors that must NOT be scoped — they address the document itself.
UNSCOPED = {'html', 'body', 'from', 'to', 'iframe'}


# ── load ────────────────────────────────────────────────────────────────────
# Signatures that only occur when UTF-8 was read as CP1252 and re-saved. Bare
# 'Ã' is deliberately NOT one of them: it is a legitimate Portuguese letter and
# appears correctly in SEÇÃO and NÃO, so matching it would fail on healthy files
# — which is how a guard gets switched off by whoever it annoys first.
MOJIBAKE_MARKERS = ('â€', 'â•', 'Ã£', 'Ã¡', 'Ã©', 'Ã³', 'Ãµ', 'Ã§', 'Ãº', 'Ãª', 'Ã­', 'Ã¢', 'Â·', 'Â ')


def mojibake_hits(text):
    return sum(text.count(m) for m in MOJIBAKE_MARKERS)


def repair_mockup():
    """Undo a UTF-8-read-as-CP1252 round trip, in place.

    The damage is reversible exactly: re-encode the text as CP1252 (which is how
    it was mis-read) and decode it as UTF-8 (which is what it always was). Only
    written back if the result has fewer markers AND still parses as UTF-8, so a
    file that is merely unusual cannot be mangled further.
    """
    raw = open(MOCKUP, 'rb').read()
    if raw[:3] == b'\xef\xbb\xbf':
        raw = raw[3:]
    text = raw.decode('utf-8', errors='replace')
    before = mojibake_hits(text)
    if not before:
        print('mockup is already clean — nothing to repair')
        return 0
    try:
        fixed = text.encode('cp1252', errors='strict').decode('utf-8', errors='strict')
    except (UnicodeEncodeError, UnicodeDecodeError) as e:
        sys.exit('ABORT — cannot repair automatically (%s). Re-export the mockup as UTF-8.' % e)
    after = mojibake_hits(fixed)
    if after >= before:
        sys.exit('ABORT — repair did not reduce damage (%d -> %d). Re-export as UTF-8.' % (before, after))
    open(MOCKUP, 'w', encoding='utf-8').write(fixed)
    print('repaired mockup: %d mojibake markers -> %d' % (before, after))
    return 0


def load():
    raw = open(MOCKUP, 'rb').read()
    if raw[:3] == b'\xef\xbb\xbf':
        raw = raw[3:]
    try:
        text = raw.decode('utf-8')
    except UnicodeDecodeError as e:
        sys.exit('ABORT — mockup is not valid UTF-8 (%s). Re-save it as UTF-8.' % e)

    # FAIL CLOSED. The documented workflow is "edit the mockup, re-run"; if the
    # mockup is corrupt that workflow would replace clean cells with broken ones
    # in a folder that deploys on save. Refuse, and say how to fix it.
    bad = mojibake_hits(text)
    if bad > 5:
        sys.exit('ABORT — mockup carries %d double-encoding markers (e.g. "CartÃ£o").\n'
                 '        Run:  python3 %s --repair-mockup\n'
                 '        Cells were NOT regenerated.' % (bad, os.path.relpath(__file__, PLUGIN)))
    return text


# ── 1 · class namespacing ───────────────────────────────────────────────────
def selector_blocks(css):
    """Yield (start, end) spans of every SELECTOR LIST in the stylesheet.

    Walks the brace structure rather than pattern-matching, because rules nested
    inside an at-rule have to be reached too. An earlier version only yielded
    depth-0 spans, so everything inside `@media (min-width:900px){ … }` was left
    un-namespaced and un-scoped — which is most of the responsive layer, and is
    why `.hero-under` and a bare `img{}` rule survived the first pass.

    A preamble starting with `@` is an at-rule: @media/@supports bodies contain
    further rules and are descended into; @font-face/@keyframes bodies contain
    declarations (or keyframe selectors like `from`/`to`) and are skipped.
    """
    i, n = 0, len(css)
    start = 0
    while i < n:
        ch = css[i]
        if ch == '{':
            pre = css[start:i]
            head = pre.strip()
            if head.startswith('@'):
                if re.match(r'@(media|supports|container|layer)\b', head):
                    i += 1                      # descend: body holds real rules
                    start = i
                    continue
                i = skip_block(css, i)          # @font-face, @keyframes — opaque
                start = i
                continue
            yield start, i
            i = skip_block(css, i)              # declaration body
            start = i
            continue
        if ch == '}':
            i += 1
            start = i
            continue
        i += 1


def skip_block(css, open_brace):
    """Index just past the `}` matching the `{` at open_brace."""
    depth = 0
    i = open_brace
    while i < len(css):
        if css[i] == '{':
            depth += 1
        elif css[i] == '}':
            depth -= 1
            if depth == 0:
                return i + 1
        i += 1
    return len(css)


def collect_classes(text):
    """Every class the mockup actually uses, from all three layers."""
    found = set()
    style = re.search(r'<style>([\s\S]*?)</style>', text).group(1)
    css = re.sub(r'/\*[\s\S]*?\*/', '', style)

    # CSS: selector positions only — never a declaration value.
    for a, b in selector_blocks(css):
        for m in re.finditer(r'\.(-?[A-Za-z_][\w-]*)', css[a:b]):
            found.add(m.group(1))

    body = text[text.index('<body>'):text.index('</body>')]
    for attr in re.findall(r'class="([^"]*)"', body):
        found.update(t for t in attr.split() if t)

    js = text[text.index('<script>', text.index('</head>')):]
    for pat in (r"\bqq?\(\s*'([^']*)'", r"closest\(\s*'([^']*)'", r"matches\(\s*'([^']*)'",
                r'class="([^"]*)"'):
        for hit in re.findall(pat, js):
            for m in re.finditer(r'\.(-?[A-Za-z_][\w-]*)', hit) if pat != r'class="([^"]*)"' else []:
                found.add(m.group(1))
            if pat == r'class="([^"]*)"':
                found.update(t for t in hit.split() if t and "'" not in t and '+' not in t)
    for pat in (r"classList\.\w+\(\s*'([^']*)'", r"className\s*=\s*'([^']*)'"):
        for hit in re.findall(pat, js):
            found.update(t for t in hit.split() if t)

    return {c for c in found
            if c and not c.startswith(KEEP_PREFIXES) and re.match(r'^[A-Za-z][\w-]*$', c)}


def namespace(text, classes):
    """Rewrite every class reference in CSS, markup and JS. One place, one map."""
    alt = '|'.join(sorted(map(re.escape, classes), key=len, reverse=True))
    css_re = re.compile(r'\.(%s)\b' % alt)
    tok_re = re.compile(r'\b(%s)\b' % alt)

    style_m = re.search(r'<style>([\s\S]*?)</style>', text)
    css = style_m.group(1)

    # 1a · CSS — selector text only, plus scope bare element rules under .dj-page.
    out, last = [], 0
    for a, b in selector_blocks(css):
        out.append(css[last:a])
        sel = css_re.sub(lambda m: '.' + PREFIX + m.group(1), css[a:b])
        parts = []
        for one in sel.split(','):
            # A selector span can carry leading whitespace AND a comment — the
            # mockup documents rules inline, e.g.
            #     /* core.js frames every img by default */
            #     img{ … }
            # Treating that whole run as the selector made it start with '/',
            # the element test failed, and the rule shipped UNSCOPED — which is
            # exactly the global `img{}` the theme then fought over.
            m = re.match(r'^(\s*(?:/\*[\s\S]*?\*/\s*)*)(.*)$', one, re.S)
            lead, s = m.group(1), m.group(2).strip()
            head = re.match(r'^([a-zA-Z][\w-]*)', s)
            if head and head.group(1) not in UNSCOPED and not s.startswith('@'):
                s = '.%s %s' % (ROOT, s)
            parts.append(lead + s)
        out.append(','.join(parts))
        last = b
    out.append(css[last:])
    text = text[:style_m.start(1)] + ''.join(out) + text[style_m.end(1):]

    # 1b · markup — class attributes.
    def attr(m):
        return 'class="%s"' % ' '.join(
            (PREFIX + t if t in classes else t) for t in m.group(1).split())
    head_end = text.index('</head>')
    body = re.sub(r'class="([^"]*)"', attr, text[head_end:])

    # 1c · JS — selector strings, classList/className, and class="" inside templates.
    def sel_str(m):
        return m.group(0).replace(m.group(1), css_re.sub(lambda x: '.' + PREFIX + x.group(1), m.group(1)))
    for pat in (r"\bqq?\(\s*'([^']*)'", r"closest\(\s*'([^']*)'", r"matches\(\s*'([^']*)'"):
        body = re.sub(pat, sel_str, body)

    def tok_str(m):
        return m.group(0).replace(m.group(1), tok_re.sub(lambda x: PREFIX + x.group(1), m.group(1)))
    for pat in (r"classList\.\w+\(\s*'([^']*)'", r"className\s*=\s*'([^']*)'"):
        body = re.sub(pat, tok_str, body)

    # A class chosen inline, e.g.  class="po-card ' + (i % 3 ? 'on' : '') + '"
    body = body.replace("? 'on' : ''", "? '%son' : ''" % PREFIX)

    return text[:head_end] + body


# ── 2 · slice ───────────────────────────────────────────────────────────────
# (cell, opening-tag anchor, closing tag, what it owns)
CELLS = [
    ('progress',    r'<div class="dj-pg" id="pg"',            None,         'scroll progress bar'),
    ('topbar',      r'<div class="dj-ev-top" id="evTop">',    '</div>',     'fixed artist-card bar — back · name · share'),
    ('hero',        r'<header class="dj-hero" id="hero">',    '</header>',  'hero: padded copy + full-bleed figure as a bare header child'),
    ('statement',   r'<section class="dj-stmt dj-wrap" id="stmt">', '</section>', 'word-fill scrub statement'),
    ('marquee',     r'<div class="dj-mq" id="mq"',            None,         'genre marquee'),
    ('played-on',   r'<section class="dj-sec dj-po" id="playedOn">', '</section>', '"Tocou em" — event carousel'),
    ('numbers',     r'<section class="dj-sec-tight dj-band" id="numbers">', '</section>', '"Em números" — counter band'),
    ('played-with', r'<section class="dj-sec" id="playedWith">', '</section>', '"Tocou com" — line-up roster'),
    ('sound',       r'<section class="dj-sec-tight" id="sound">', '</section>', '"Out now!" — tracks + SoundCloud shelf'),
    ('kit',         r'<section class="dj-sec" id="kitSection">', '</section>', 'EPK / press kit card'),
    ('about',       r'<section class="dj-sec-tight">\n  <div class="dj-wrap dj-about">', '</section>', '"O artista" — figure + bio + tags'),
    ('footer',      r'<footer class="dj-foot">',              '</footer>',  'footer: name + full-bleed image as last node'),
    ('dock',        r'<div class="dj-dock" id="dock">',       '</div>',     'icon-only action dock'),
    ('lightbox',    r'<div class="dj-outnow-lb" id="outNowLb"', '</div>',   '"Ver todos" track lightbox'),
    ('toast',       r'<div class="dj-toast" id="toast"',      None,         'toast / aria-live status'),
]


def mask_comments(text):
    """Blank out HTML comment BODIES, preserving length and offsets.

    The mockup annotates itself heavily, and several notes quote tags —
    the hero carries "<!-- full-bleed: direct child of <header class=...> -->"
    between its own opening and closing tags. A naive depth walk counts that
    as a nested <header>, never balances, and the slice fails. Masking keeps
    every offset identical so the caller can still slice the ORIGINAL text.
    """
    return re.sub(r'<!--[\s\S]*?-->', lambda m: ' ' * len(m.group(0)), text)


def slice_cell(text, anchor, closer):
    i = text.find(anchor)
    if i < 0:
        sys.exit('ABORT — anchor not found in mockup: %s' % anchor[:60])
    if closer is None:                       # single self-contained line
        return text[i:text.index('\n', i)]
    scan = mask_comments(text)
    # Walk tags of the same name to find the matching close.
    tag = re.match(r'<(\w+)', anchor).group(1)
    depth, pos = 0, i
    open_re = re.compile(r'<%s\b' % tag)
    close_re = re.compile(r'</%s>' % tag)
    while pos < len(scan):
        o = open_re.search(scan, pos)
        c = close_re.search(scan, pos)
        if not c:
            sys.exit('ABORT — no closing </%s> for %s' % (tag, anchor[:40]))
        if o and o.start() < c.start():
            depth += 1
            pos = o.end()
        else:
            depth -= 1
            pos = c.end()
            if depth == 0:
                return text[i:c.end()]
    sys.exit('ABORT — unbalanced <%s>' % tag)


# ── 3 · dynamic substitutions ───────────────────────────────────────────────
P = '<?php echo %s; ?>'
E = lambda v: P % ('esc_html( %s )' % v)
EA = lambda v: P % ('esc_attr( %s )' % v)
EU = lambda v: P % ('esc_url( %s )' % v)

SUBS = {
    'topbar': [
        ('<b id="evTopName">Leo Janeiro</b>', '<b id="evTopName">' + E('$dj_name') + '</b>'),
    ],
    'hero': [
        ('aria-label="Leo Janeiro"', 'aria-label="' + EA('$dj_name') + '"'),
        ('<span class="dj-hn-line" aria-hidden="true">LEO</span>\n      '
         '<span class="dj-hn-line dj-is-ac" aria-hidden="true">JANEIRO</span>',
         '<?php foreach ( $dj_name_lines as $i => $ln ) : ?>\n'
         '      <span class="dj-hn-line<?php echo $i ? \' dj-is-ac\' : \'\'; ?>" aria-hidden="true">'
         '<?php echo esc_html( $ln ); ?></span>\n'
         '      <?php endforeach; ?>'),
        ('<span class="dj-lbl">Rio de Janeiro — Hard Groove &amp; Peak Time</span>',
         '<span class="dj-lbl">' + E('$dj_eyebrow') + '</span>'),
        ('<span class="dj-hero-live">Booking aberto · 2026</span>',
         '<?php if ( \'\' !== $dj_status ) : ?><span class="dj-hero-live">'
         + E('$dj_status') + '</span><?php endif; ?>'),
        ('src="https://apollo.rio.br/wp-content/uploads/2026/07/'
         'leo-janeiro-1-e1783087749835-853x540-1.jpg" alt="Leo Janeiro"',
         'src="' + EU('$dj_hero_image') + '" alt="' + EA('$dj_name') + '"'),
    ],
    'about': [
        ('<div class="dj-tags dj-rv"><span class="dj-tag">Hard Groove</span>'
         '<span class="dj-tag">Peak Time</span><span class="dj-tag">Tribal House</span>'
         '<span class="dj-tag">Afro House</span><span class="dj-tag">Techno</span></div>',
         '<div class="dj-tags dj-rv"><?php foreach ( $dj_sounds as $tag ) : ?>'
         '<span class="dj-tag"><?php echo esc_html( $tag ); ?></span>'
         '<?php endforeach; ?></div>'),
    ],
    'footer': [
        ('<div class="dj-foot-name" id="footName">LEO JANEIRO</div>',
         '<div class="dj-foot-name" id="footName">' + E('$dj_name_upper') + '</div>'),
        ('src="https://images.unsplash.com/photo-1492684223066-81342ee5ff30?w=1800&q=82"',
         'src="' + EU('$dj_foot_image') + '"'),
    ],
}

# The counter band and the hero pills are data, not copy — the mockup hard-codes
# four/three of each so the design can be read. PHP emits as many as the DJ has.
LOOPS = {
    # PROSE WITH A FALLBACK — matched as a WHOLE paragraph on purpose.
    #
    # These were three open-ended substitutions that opened `<?php if … else :`
    # at the start of a <p> and relied on a separate rule to close it. Two of
    # them never got their `endif`, which is a PHP fatal, and the page returned
    # an EMPTY RESPONSE in production. Brace-balance linting cannot see it —
    # alternative syntax has no braces — so the only safe shape is one regex
    # that owns the opening tag, the body and the closing tag together.
    'statement': (
        r'<p class="dj-stmt-txt" id="stmtTxt">.*?</p>',
        '<p class="dj-stmt-txt" id="stmtTxt"><?php if ( \'\' !== $dj_statement ) : ?>'
        '<?php echo wp_kses_post( $dj_statement ); ?>'
        '<?php else : ?>Ele não domestica o groove. Ele <em class="dj-gold">solta</em>.'
        '<?php endif; ?></p>'),
    'about': (
        r'<p class="dj-about-p dj-rv">.*?</p>',
        '<p class="dj-about-p dj-rv"><?php if ( \'\' !== $dj_bio ) : ?>'
        '<?php echo wp_kses_post( $dj_bio ); ?>'
        '<?php else : ?><strong><?php echo esc_html( $dj_name ); ?>.</strong>'
        '<?php endif; ?></p>'),
    'numbers': (
        r'(?:<div class="dj-num dj-rv">.*?</p></div>\s*){2,}',
        '<?php foreach ( $dj_numbers as $n ) : ?>\n'
        '      <div class="dj-num dj-rv"><div class="dj-num-v">'
        '<span data-count="<?php echo esc_attr( (string) $n[\'v\'] ); ?>">0</span></div>'
        '<p class="dj-num-l"><?php echo esc_html( $n[\'l\'] ); ?></p></div>\n'
        '      <?php endforeach; ?>'),
    'hero': (
        r'(?:<span class="dj-gpill dj-pill"><strong>.*?</span></span>\s*){2,}',
        '<?php foreach ( $dj_pills as $pill ) : ?>\n'
        '      <span class="dj-gpill dj-pill"><strong>'
        '<?php echo esc_html( $pill[\'v\'] ); ?></strong><span>'
        '<?php echo esc_html( $pill[\'l\'] ); ?></span></span>\n'
        '      <?php endforeach; ?>'),
}

HDR = '''<?php
/**
 * DJ Single — cell: %(name)s
 *
 * OWNS: %(owns)s
 *
 * GENERATED — do not hand-edit. Sliced from the approved mockup
 * (_sandbox/dj-single-page.mockup.html) by _sandbox/build-dj-cells.py, which
 * also namespaces every class to `dj-`. Edit the mockup, re-run the generator,
 * review the diff. Hand-editing here is how the page and the design drift.
 *
 * Dynamic values arrive as $dj_* in scope from apollo_dj_single_context().
 * Anything the runtime fills client-side is left as the empty container it
 * expects to find.
 *
 * @package Apollo\\DJs
 * @since   1.0.6
 */

if ( ! defined( 'ABSPATH' ) ) {
\texit;
}
?>
'''


def check_ranges(text):
    """Validate the CELLS table against the mockup before writing anything.

    Named for its ancestor: cells used to be sliced by hard-coded LINE RANGES
    and two of them silently overlapped by three lines, putting a stray </div>
    in one cell and an unclosed <header> in the next. Slicing is now anchor-
    based, so the failure mode moved rather than disappeared — an anchor can go
    missing, match twice, or two cells can still resolve to overlapping spans if
    the mockup is restructured. Checked up front so a bad table aborts instead
    of writing 18 subtly wrong files.
    """
    seen = []
    for name, anchor, closer, _ in CELLS:
        a = anchor.replace('\\n', '\n')
        n = text.count(a)
        if n == 0:
            sys.exit('ABORT — %s: anchor not found in mockup: %s' % (name, a[:60]))
        if n > 1:
            sys.exit('ABORT — %s: anchor matches %d times, slice would be ambiguous: %s'
                     % (name, n, a[:60]))
        start = text.find(a)
        body = slice_cell(text, a, closer)
        seen.append((name, start, start + len(body)))

    seen.sort(key=lambda r: r[1])
    for (n1, _, e1), (n2, s2, _) in zip(seen, seen[1:]):
        if s2 < e1:
            sys.exit('ABORT — cells %s and %s overlap by %d chars.' % (n1, n2, e1 - s2))
    return len(seen)


def main():
    if '--repair-mockup' in sys.argv:
        sys.exit(repair_mockup())
    text = load()
    classes = collect_classes(text)
    text = namespace(text, classes)
    check_ranges(text)

    os.makedirs(OUT, exist_ok=True)
    written = []

    for name, anchor, closer, owns in CELLS:
        body = slice_cell(text, anchor.replace('\\n', '\n'), closer)

        if name in LOOPS:
            pat, rep = LOOPS[name]
            new, n = re.subn(pat, rep, body, count=1)
            if n != 1:
                sys.exit('ABORT — %s: repeat block not matched; the mockup changed shape.' % name)
            body = new

        for lit, rep in SUBS.get(name, []):
            if body.count(lit) != 1:
                sys.exit('ABORT — %s: anchor appears %dx (expected 1): %s'
                         % (name, body.count(lit), lit[:70]))
            body = body.replace(lit, rep)

        open(os.path.join(OUT, name + '.php'), 'w', encoding='utf-8').write(
            HDR % {'name': name, 'owns': owns} + body.rstrip() + '\n')
        written.append(name)

    n_style = build_styles(text)
    build_data()
    build_scripts(text)
    print('%d markup cells + styles + data + scripts -> %s'
          % (len(written), os.path.relpath(OUT, PLUGIN)))
    print('%d classes namespaced to .%s* · %d core.js tokens dropped from :root'
          % (len(classes), PREFIX, n_style))


# ── style / data / script cells ─────────────────────────────────────────────
# Tokens core.js injects at runtime. The mockup declares them because it has to
# run offline ("offline mirror … maps 1:1 onto the core.js injected token
# system"); shipping that copy would fork the token system and the page would
# stop following a dark-mode swap or a brand change.
CORE_OWNED = (
    '--rgb-theme', '--rgb-diff', '--rgb-accent', '--bg', '--txt', '--muted',
    '--surface', '--surface-2', '--accent', '--ff-display', '--ff-serif',
    '--ff-serif-i', '--ff-main', '--ff-mono', '--r', '--r-sm', '--r-pill', '--ease',
)


def build_styles(text):
    css = re.search(r'<style>([\s\S]*?)</style>', text).group(1)
    root_m = re.search(r':root\s*\{([\s\S]*?)\}', css)

    # Comments first: the mockup puts a two-line note immediately before
    # --evtop-h, and splitting on ';' with the comment attached made the whole
    # chunk look like a comment and silently dropped the declaration with it.
    decls = re.sub(r'/\*[\s\S]*?\*/', '', root_m.group(1))
    kept, dropped = [], []
    for decl in decls.split(';'):
        d = decl.strip()
        if not d:
            continue
        (dropped if d.split(':')[0].strip() in CORE_OWNED else kept).append(d)

    root = (':root{\n'
            '  /* PAGE-SCOPED ONLY — these do not exist in core.js.\n'
            '     The mockup mirrors the core token set so it can run offline; the\n'
            '     generator drops every mirrored token (%s)\n'
            '     because core.js injects the identical values at runtime and a local\n'
            '     copy would fork the token system on the first theme change. */\n'
            '%s\n}') % (', '.join(d.split(':')[0].strip() for d in dropped) or 'none',
                        '\n'.join('  %s;' % k for k in kept))

    css = css[:root_m.start()] + root + css[root_m.end():]
    open(os.path.join(OUT, 'styles.php'), 'w', encoding='utf-8').write(
        HDR % {'name': 'styles',
               'owns': 'the entire visual layer — single owner. Every selector is '
                       '.dj-* or scoped under .dj-page, so nothing on the platform '
                       'can reach in and nothing here leaks out'}
        + '<style id="apollo-dj-styles">' + css + '</style>\n')
    return len(dropped)


def build_data():
    body = ('<script id="apollo-dj-data">\n'
            '/* The runtime\'s whole data contract, emitted by PHP. Field-for-field the\n'
            '   shape the mockup hard-codes; every key traces to a registry meta key or\n'
            '   taxonomy — see the @field annotations in the mockup and\n'
            '   apollo_dj_single_context(). wp_json_encode does the escaping. */\n'
            'window.APOLLO_DJ = <?php echo wp_json_encode( $dj_payload ); ?>;\n'
            '</script>')
    open(os.path.join(OUT, 'data.php'), 'w', encoding='utf-8').write(
        HDR % {'name': 'data',
               'owns': 'window.APOLLO_DJ — the runtime payload. Data only: no markup, '
                       'no behaviour'} + body + '\n')


def build_scripts(text):
    js = re.search(r'<script>([\s\S]*?)</script>', text[text.index('</head>'):]).group(1)
    a = js.index('  var APOLLO_DJ = {')
    b = js.index('\n  };', a) + len('\n  };')
    bridge = (
        "  /* PAYLOAD COMES FROM PHP (data.php). The mockup hard-codes this object so\n"
        "     it can run as a standalone file; here it is published as window.APOLLO_DJ\n"
        "     from apollo_dj_single_context(). Same shape, same keys — the fallback\n"
        "     keeps the runtime from throwing if the data cell is ever omitted from a\n"
        "     partial render. */\n"
        "  var APOLLO_DJ = window.APOLLO_DJ || "
        "{ genres: [], playedOn: [], playedWith: [], tracks: [] };")
    js = js[:a] + bridge + js[b:]
    open(os.path.join(OUT, 'scripts.php'), 'w', encoding='utf-8').write(
        HDR % {'name': 'scripts',
               'owns': 'the runtime — behaviour only. Holds no data and emits no styling'}
        + '<script id="apollo-dj-runtime">' + js + '</script>\n')


if __name__ == '__main__':
    main()
