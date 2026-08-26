#!/usr/bin/env python3
import re, time, urllib.request

url = "https://apollo.rio.br/evento/dismantle-2/?nocache=" + str(time.time())
req = urllib.request.Request(
    url,
    headers={
        "User-Agent": "Mozilla/5.0 ApolloCoreProbe",
        "Cache-Control": "no-cache",
        "Pragma": "no-cache",
    },
)
html = urllib.request.urlopen(req, timeout=40).read().decode("utf-8", "replace")
m = re.search(r"<head[^>]*>(.*?)</head>", html, re.I | re.S)
head = m.group(1) if m else ""
print("STATUS_OK", len(html))
print("HAS_CORE", "cdn.apollo.rio.br/v1.0.0/core.js?v=Random.x.1" in head)
print("HAS_CORE_ID", "apollo-core-js" in head)
print("HAS_MANDATORY", "Mandatory Apollo CORE" in head)
for label, pat in [
    ("charset", r"charset"),
    ("pre_assets", r"assets\.apollo\.rio\.br"),
    ("core", r"core\.js"),
    ("viewport", r'name="viewport"'),
]:
    i = re.search(pat, head, re.I)
    print(label, i.start() if i else None)
print("PREFIX", re.sub(r"\s+", " ", head.strip())[:600])
before = head.split("core.js")[0] if "core.js" in head else head
scripts = re.findall(r"<script[^>]+src=[\"']([^\"']+)", before, re.I)
print("SCRIPTS_BEFORE_CORE", scripts)
print("TITLE", re.search(r"<title[^>]*>(.*?)</title>", html, re.I | re.S).group(1)[:80] if re.search(r"<title", html, re.I) else None)
