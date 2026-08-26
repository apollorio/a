#!/usr/bin/env python3
import paramiko
from pathlib import Path

pkey = paramiko.RSAKey.from_private_key_file(r"D:\dev\_apollo.rio.br\id_apll", password="XouXuxa2026!")
c = paramiko.SSHClient()
c.set_missing_host_key_policy(paramiko.AutoAddPolicy())
c.connect("ftp.rvalle.com.br", username="rvall260", pkey=pkey, timeout=45)
sftp = c.open_sftp()

inv = Path(r"D:\dev\_apollo.rio.br\plugins\_inventory\core.js")
cdn = "/home1/rvall260/cdn.apollo.rio.br/v1.0.0"
wp = "/home1/rvall260/apollo.rio.br/wp-content/plugins"

pairs = [
    (inv / "data-tooltip.v1.0.1.js", f"{cdn}/js/data-tooltip.v1.0.1.js"),
    (inv / "data-tooltip.css", f"{cdn}/css/data-tooltip.css"),
    (inv / "core.js", f"{cdn}/core.js"),
    (Path(r"D:\dev\_apollo.rio.br\plugins\apollo-login\templates\parts\new_login-form.php"), f"{wp}/apollo-login/templates/parts/new_login-form.php"),
]
for local, remote in pairs:
    print("PUT", local.name, "->", remote)
    sftp.put(str(local), remote)

# also copy inventory into plugins/_inventory on server if used
try:
    sftp.put(str(inv / "data-tooltip.v1.0.1.js"), f"{wp}/_inventory/core.js/data-tooltip.v1.0.1.js")
    print("PUT inventory mirror")
except Exception as e:
    print("inventory mirror skip", e)

sftp.close()

# verify CDN lengths + acesso nested tooltips
stdin, stdout, stderr = c.exec_command(
    r"""
wc -c /home1/rvall260/cdn.apollo.rio.br/v1.0.0/js/data-tooltip.v1.0.1.js /home1/rvall260/cdn.apollo.rio.br/v1.0.0/css/data-tooltip.css
curl -sL 'https://cdn.apollo.rio.br/v1.0.0/js/data-tooltip.v1.0.1.js?v=t0x7' | head -c 200
echo
curl -sL 'https://cdn.apollo.rio.br/v1.0.0/css/data-tooltip.css' | head -c 220
echo
"""
)
print(stdout.read().decode("utf-8", "replace"))
c.close()
print("DONE")
