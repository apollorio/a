#!/usr/bin/env python3
import urllib.request

url = "https://apollo.rio.br/evento/dismantle-2/?hdr=1"
req = urllib.request.Request(
    url,
    headers={
        "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36",
        "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
        "Accept-Language": "pt-BR,pt;q=0.9,en;q=0.8",
    },
)
with urllib.request.urlopen(req, timeout=30) as r:
    print("STATUS", r.status)
    for k, v in r.headers.items():
        lk = k.lower()
        if any(x in lk for x in ("csp", "content-security", "x-frame", "x-content", "report")):
            print(f"{k}: {v[:800]}")
