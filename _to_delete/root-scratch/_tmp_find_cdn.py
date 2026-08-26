#!/usr/bin/env python3
import paramiko
from pathlib import Path

pkey = paramiko.RSAKey.from_private_key_file(r"D:\dev\_apollo.rio.br\id_apll", password="XouXuxa2026!")
c = paramiko.SSHClient()
c.set_missing_host_key_policy(paramiko.AutoAddPolicy())
c.connect("ftp.rvalle.com.br", username="rvall260", pkey=pkey, timeout=45)
cmd = r"""
find /home1/rvall260 -maxdepth 5 -type d -name 'v1.0.0' 2>/dev/null | head -30
echo '---'
ls -la /home1/rvall260/cdn.apollo.rio.br/v1.0.0/js/data-tooltip* 2>/dev/null || echo no-cdn-home
ls -la /home1/rvall260/public_html/cdn.apollo.rio.br/v1.0.0/js/data-tooltip* 2>/dev/null || true
ls -la /home1/rvall260/apollo.rio.br/cdn/v1.0.0/js/data-tooltip* 2>/dev/null || true
# also check subdomain docroots
ls /home1/rvall260 | head -50
"""
stdin, stdout, stderr = c.exec_command(cmd, timeout=60)
out = stdout.read().decode("utf-8", "replace")
Path(r"D:\dev\_apollo.rio.br\plugins\_tmp_cdn_find.txt").write_text(out, encoding="utf-8")
print(out[:3000])
c.close()
