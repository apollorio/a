#!/usr/bin/env python3
import paramiko
from pathlib import Path

pkey = paramiko.RSAKey.from_private_key_file(r"D:\dev\_apollo.rio.br\id_apll", password="XouXuxa2026!")
c = paramiko.SSHClient()
c.set_missing_host_key_policy(paramiko.AutoAddPolicy())
c.connect("ftp.rvalle.com.br", username="rvall260", pkey=pkey, timeout=45)
stdin, stdout, stderr = c.exec_command(
    "head -40 /home1/rvall260/cdn.apollo.rio.br/v1.0.0/css/data-tooltip.css; echo '====='; head -20 /home1/rvall260/cdn.apollo.rio.br/v1.0.0/js/data-tooltip.v1.0.1.js"
)
Path(r"D:\dev\_apollo.rio.br\plugins\_tmp_cdn_verify.txt").write_text(
    stdout.read().decode("utf-8", "replace"), encoding="utf-8"
)
print("wrote verify")
c.close()
