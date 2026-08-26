#!/usr/bin/env python3
import re
import time
import urllib.request

url = "https://apollo.rio.br/evento/dismantle-2/?cspfix=" + str(int(time.time()))
req = urllib.request.Request(
    url,
    headers={
        "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36",
        "Accept": "text/html,application/xhtml+xml",
        "Cache-Control": "no-cache",
    },
)
with urllib.request.urlopen(req, timeout=40) as r:
    csp = r.headers.get("Content-Security-Policy", "")
    html = r.read().decode("utf-8", "replace")

print("CSP_HAS_STRICT_DYNAMIC", "strict-dynamic" in csp)
print("CSP_HAS_UNSAFE_INLINE", "unsafe-inline" in csp)
print("CSP_SCRIPT_SRC", )
m = re.search(r"script-src ([^;]+)", csp)
print("script-src:", m.group(1) if m else "MISSING")
print("HAS_CORE_TAG", "apollo-core-js" in html)
# nonce on core tag?
n = re.search(r'<script[^>]*id="apollo-core-js"[^>]*>', html)
print("CORE_TAG", n.group(0) if n else "none")
