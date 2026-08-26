"""Deploy /casa redesign files to production via SFTP."""
import paramiko
from pathlib import Path

KEY = r"D:\dev\_apollo.rio.br\id_apll"
PASS = "XouXuxa2026!"
HOST = "ftp.rvalle.com.br"
USER = "rvall260"
REMOTE_ROOT = "/home1/rvall260/apollo.rio.br/wp-content/plugins/apollo-templates"

LOCAL_ROOT = Path(r"D:\dev\_apollo.rio.br\plugins\apollo-templates")

FILES = [
    "apollo-templates.php",
    "includes/guest-home-guard.php",
    "templates/page-home.php",
    "templates/template-parts/new-home/hero.php",
    "assets/css/new-home.css",
    "assets/js/new-home.js",
]

pkey = paramiko.RSAKey.from_private_key_file(KEY, password=PASS)
client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(HOST, username=USER, pkey=pkey, timeout=60)
sftp = client.open_sftp()

for rel in FILES:
    local = LOCAL_ROOT / rel.replace("/", "\\")
    remote = f"{REMOTE_ROOT}/{rel.replace(chr(92), '/')}"
    print(f"PUT {local} -> {remote}")
    sftp.put(str(local), remote)

sftp.close()

# Purge endurance cache for /casa
cmd = (
    "rm -rf /home1/rvall260/apollo.rio.br/wp-content/endurance-page-cache/casa "
    "/home1/rvall260/apollo.rio.br/wp-content/endurance-page-cache/_index.html "
    "/home1/rvall260/apollo.rio.br/wp-content/endurance-page-cache/home 2>/dev/null; "
    "echo PURGE_OK"
)
stdin, stdout, stderr = client.exec_command(cmd)
print(stdout.read().decode())
print(stderr.read().decode())
client.close()
print("DEPLOY_DONE")
