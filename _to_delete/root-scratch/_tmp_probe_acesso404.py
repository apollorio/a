#!/usr/bin/env python3
import urllib.request, urllib.error, re

ua = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36"
headers_variants = [
    {"User-Agent": ua, "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8"},
    {"User-Agent": ua, "Accept": "*/*"},
    {"User-Agent": "ApolloCoreProbe/1", "Accept": "text/html"},
]

urls = [
    "https://apollo.rio.br/acesso/",
    "https://apollo.rio.br/acesso",
    "https://apollo.rio.br/registre/",
    "https://apollo.rio.br/",
]

for url in urls:
    for i, h in enumerate(headers_variants):
        req = urllib.request.Request(url, headers={**h, "Cache-Control": "no-cache"})
        try:
            with urllib.request.urlopen(req, timeout=30) as r:
                body = r.read(2000).decode("utf-8", "replace")
                title = re.search(r"<title[^>]*>(.*?)</title>", body, re.I | re.S)
                print(f"OK {r.status} v{i} {url} title={(title.group(1)[:60] if title else '?')}")
                break
        except urllib.error.HTTPError as e:
            b = e.read(400).decode("utf-8", "replace")
            title = re.search(r"<title[^>]*>(.*?)</title>", b, re.I | re.S)
            print(f"ERR {e.code} v{i} {url} title={(title.group(1) if title else b[:80])}")
        except Exception as e:
            print(f"EXC v{i} {url} {e}")
