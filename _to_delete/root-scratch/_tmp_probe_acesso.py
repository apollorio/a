#!/usr/bin/env python3
import re, time, urllib.request

url = "https://apollo.rio.br/acesso/?dbg=" + str(int(time.time()))
req = urllib.request.Request(
    url,
    headers={
        "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36",
        "Accept": "text/html,application/xhtml+xml",
        "Cache-Control": "no-cache",
    },
)
with urllib.request.urlopen(req, timeout=40) as r:
    status = r.status
    csp = r.headers.get("Content-Security-Policy", "")
    html = r.read().decode("utf-8", "replace")

print("STATUS", status)
print("LEN", len(html))
print("TITLE", (re.search(r"<title[^>]*>(.*?)</title>", html, re.I | re.S) or [None, ""])[1][:80])
print("HAS_CORE", "cdn.apollo.rio.br/v1.0.0/core.js?v=Random.x.1" in html)
print("HAS_CORE_ID", "apollo-core-js" in html)
print("AUTH_LITE", "__APOLLO_AUTH_LITE__" in html)
print("DATA_APOLLO_PAGE", 'data-apollo-page="auth"' in html)
print("CSP_STRICT_DYNAMIC", "strict-dynamic" in csp)
print("CSP_UNSAFE_INLINE", "unsafe-inline" in csp)
m = re.search(r"script-src ([^;]+)", csp)
print("script-src:", m.group(1) if m else "MISSING")
# core tag
n = re.search(r"<script[^>]*(?:id=\"apollo-core-js\"|src=\"[^\"]*core\.js[^\"]*\")[^>]*>", html)
print("CORE_TAG", n.group(0)[:220] if n else "NONE")
# head prefix
hm = re.search(r"<head[^>]*>(.*?)</head>", html, re.I | re.S)
head = hm.group(1) if hm else ""
print("HEAD_PREFIX", re.sub(r"\s+", " ", head.strip())[:500])
# auth assets
print("HAS_AUTH_UNI", "apollo-auth-uni.css" in html)
print("HAS_AUTH_SCRIPTS", "apollo-auth-scripts.js" in html)
print("FATAL", "Fatal error" in html or "Uncaught" in html[:2000])
# obvious php errors
for pat in ["Parse error", "Warning:", "Undefined", "Call to undefined"]:
    if pat in html:
        print("ERR_MARK", pat)
