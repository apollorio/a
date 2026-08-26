from pathlib import Path
import re

html = Path(r"D:\dev\_dev web\screen\single cpt\event\single-event\event.html").read_text(
    encoding="utf-8"
)
m = re.search(r"<style>(.*?)</style>", html, re.S)
if not m:
    raise SystemExit("no style")
css = m.group(1)

# Strip original html/body + first .ev-hero block — replace with exact-bleed rules
css = re.sub(r"html,body\{.*?\}", "", css, count=1, flags=re.S)
css = re.sub(r"\nbody\{.*?\}", "", css, count=1, flags=re.S)
css = re.sub(
    r"/\* ── Hero ── \*/\s*\.ev-hero\{.*?color:#fff;\s*\}",
    "",
    css,
    count=1,
    flags=re.S,
)

bleed = r"""
html,body{
  background:var(--ev-bg)!important;
  color:var(--ev-ink);
  -webkit-font-smoothing:antialiased;
  margin:0 !important;
  padding:0 !important;
  border:0 !important;
}
body{
  min-height:100dvh;
  padding:0 !important;
  padding-bottom:calc(28px + var(--sb)) !important;
  overflow-x:hidden;
  max-width:100vw;
}
/* ── Hero — full viewport bleed ── */
.ev-hero{
  position:relative;
  width:100vw !important;
  max-width:100vw !important;
  margin:0 0 0 calc(50% - 50vw) !important;
  left:0 !important;
  right:auto !important;
  height:100svh;
  min-height:560px;
  overflow:hidden;
  background:#0a0a0a;
  color:#fff;
  box-sizing:border-box;
}
"""

idx = css.find(".ev-wrap{")
if idx < 0:
    raise SystemExit("no .ev-wrap")
out = css[:idx] + bleed + "\n" + css[idx:]
out += r"""

/* Kill shell / fab inset so single matches the static HTML canvas */
html, body, #apollo-root, .apollo-shell, .lenis, [data-lenis-prevent] {
  margin:0 !important;
  padding-left:0 !important;
  padding-right:0 !important;
  max-width:100vw !important;
}
.apollo-fab, .fab-menu, .ax-top, .apollo-bottom-nav {
  display:none !important;
}
.ev-foot{
  width:100vw !important;
  margin-left:calc(50% - 50vw) !important;
}
.ev-venue-scroll, .ev-venue-zone.is-full {
  width:100vw;
  margin-left:calc(50% - 50vw);
  box-sizing:border-box;
}
"""

dest = Path(r"D:\dev\_apollo.rio.br\plugins\apollo-events\assets\css\apollo-single-event.css")
dest.write_text(out, encoding="utf-8")
print("wrote", dest, "bytes", dest.stat().st_size)
print("bleed", "100vw !important" in out, "margin0", "margin:0 !important" in out)
