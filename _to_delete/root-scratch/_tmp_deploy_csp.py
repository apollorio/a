#!/usr/bin/env python3
import paramiko
from pathlib import Path

host = "ftp.rvalle.com.br"
user = "rvall260"
key_path = r"D:\dev\_apollo.rio.br\id_apll"
passphrase = "XouXuxa2026!"
remote_base = "/home1/rvall260/apollo.rio.br/wp-content/plugins"
local_root = Path(r"D:\dev\_apollo.rio.br\plugins")

files = [
    "apollo-login/apollo-login.php",
    "apollo-login/src/Security/SecurityHeaders.php",
    "apollo-core/apollo-core.php",
    "apollo-core/includes/document-head.php",
    "apollo-core/src/Core/CDN.php",
    "apollo-events/styles/base/template-parts/single/scripts.php",
]

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
pkey = paramiko.RSAKey.from_private_key_file(key_path, password=passphrase)
client.connect(host, username=user, pkey=pkey, timeout=45)
sftp = client.open_sftp()
for rel in files:
    print("PUT", rel)
    sftp.put(str(local_root / rel), f"{remote_base}/{rel}")
sftp.close()
client.close()
print("DONE")
