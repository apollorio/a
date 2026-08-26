#!/usr/bin/env python3
import paramiko
from pathlib import Path

pkey = paramiko.RSAKey.from_private_key_file(r"D:\dev\_apollo.rio.br\id_apll", password="XouXuxa2026!")
c = paramiko.SSHClient()
c.set_missing_host_key_policy(paramiko.AutoAddPolicy())
c.connect("ftp.rvalle.com.br", username="rvall260", pkey=pkey, timeout=45)
sftp = c.open_sftp()

local = Path(r"D:\dev\_apollo.rio.br\plugins\_inventory\js\suporte-apollo.js")
cdn = "/home1/rvall260/cdn.apollo.rio.br/v1.0.0/js/suporte-apollo.js"
wp = "/home1/rvall260/apollo.rio.br/wp-content/plugins/_inventory/js/suporte-apollo.js"

for remote in (cdn, wp):
    print("PUT", local.name, "->", remote)
    try:
        sftp.put(str(local), remote)
    except OSError:
        # ensure remote dir exists on first deploy
        parts = remote.rsplit("/", 1)[0]
        c.exec_command(f"mkdir -p {parts}")
        sftp.put(str(local), remote)

sftp.close()

stdin, stdout, stderr = c.exec_command(
    r"""
wc -c /home1/rvall260/cdn.apollo.rio.br/v1.0.0/js/suporte-apollo.js
curl -sL 'https://cdn.apollo.rio.br/v1.0.0/js/suporte-apollo.js' | head -c 280
echo
"""
)
print(stdout.read().decode("utf-8", "replace"))
err = stderr.read().decode("utf-8", "replace")
if err.strip():
    print("STDERR:", err)
c.close()
print("DONE")
