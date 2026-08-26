#!/usr/bin/env python3
import paramiko, urllib.request, urllib.error, re

# External browser-like probes
ua = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36"
for url in [
    "https://apollo.rio.br/acesso/",
    "https://apollo.rio.br/registre/",
    "https://apollo.rio.br/reset/",
    "https://apollo.rio.br/verificar-email/",
    "https://apollo.rio.br/evento/dismantle-2/",
]:
    req = urllib.request.Request(url, headers={"User-Agent": ua, "Accept": "text/html,application/xhtml+xml"})
    try:
        with urllib.request.urlopen(req, timeout=30) as r:
            b = r.read(2500).decode("utf-8", "replace")
            t = re.search(r"<title[^>]*>(.*?)</title>", b, re.I | re.S)
            print("EXT", r.status, url, (t.group(1)[:70] if t else "?") )
    except urllib.error.HTTPError as e:
        b = e.read(400).decode("utf-8", "replace")
        t = re.search(r"<title[^>]*>(.*?)</title>", b, re.I | re.S)
        print("EXT", e.code, url, (t.group(1) if t else b[:60]))

# SSH origin curls
host = "ftp.rvalle.com.br"; user = "rvall260"
pkey = paramiko.RSAKey.from_private_key_file(r"D:\dev\_apollo.rio.br\id_apll", password="XouXuxa2026!")
c = paramiko.SSHClient(); c.set_missing_host_key_policy(paramiko.AutoAddPolicy())
c.connect(host, username=user, pkey=pkey, timeout=45)
cmd = r"""
for U in /acesso/ /registre/ /reset/ /verificar-email/ /evento/dismantle-2/ /feed/; do
  CODE=$(curl -s -o /tmp/b.txt -w '%{http_code}' -A 'Mozilla/5.0' -H 'Accept: text/html,application/xhtml+xml' "https://apollo.rio.br$U")
  TITLE=$(grep -o '<title>[^<]*' /tmp/b.txt | head -1)
  echo "SSH $CODE $U $TITLE"
done
echo '---HT---'
head -n 40 /home1/rvall260/apollo.rio.br/.htaccess
"""
stdin, stdout, stderr = c.exec_command(cmd, timeout=90)
print(stdout.read().decode("utf-8", "replace"))
c.close()
