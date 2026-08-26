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
    "apollo-events/apollo-events.php",
    "apollo-events/assets/css/apollo-single-event.css",
    "apollo-events/styles/base/single-event.php",
    "apollo-events/styles/base/create-event.php",
    "apollo-events/styles/base/dashboard-event.php",
    "apollo-events/styles/base/template-parts/single/scripts.php",
    "apollo-events/styles/apollo-v2/single-dj.php",
    "apollo-events/styles/apollo-v2/single-loc.php",
    "apollo-core/apollo-core.php",
    "apollo-core/includes/document-head.php",
]

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
pkey = paramiko.RSAKey.from_private_key_file(key_path, password=passphrase)
client.connect(host, username=user, pkey=pkey, timeout=45)
sftp = client.open_sftp()
for rel in files:
    local = local_root / rel
    remote = f"{remote_base}/{rel}"
    print("PUT", rel)
    sftp.put(str(local), remote)

stdin, stdout, stderr = client.exec_command(
    "php -r 'echo function_exists(\"opcache_reset\") ? (opcache_reset() ? \"reset-ok\" : \"reset-fail\") : \"no-opcache\";'"
)
print("OPCACHE", stdout.read().decode("utf-8", "replace"), stderr.read().decode("utf-8", "replace"))
sftp.close()
client.close()
print("DONE")
