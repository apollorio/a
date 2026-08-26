import paramiko

pkey = paramiko.RSAKey.from_private_key_file(
    r"D:\dev\_apollo.rio.br\id_apll", password="XouXuxa2026!"
)
client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(
    "ftp.rvalle.com.br",
    22,
    "rvall260",
    pkey=pkey,
    timeout=45,
    allow_agent=False,
    look_for_keys=False,
)

cmd = r"""
echo '=== docroot evento path ==='
ls -la /home1/rvall260/apollo.rio.br/evento 2>&1 | head
ls -la /home1/rvall260/apollo.rio.br/ | head -40
echo '=== htaccess wp ==='
grep -n 'RewriteCond\|RewriteRule\|evento\|ErrorDocument' /home1/rvall260/apollo.rio.br/.htaccess | head -60
echo '=== public curl ==='
curl -sI -A 'Mozilla/5.0' 'https://apollo.rio.br/evento/dismantle-2/' | head -30
echo '=== public curl body head ==='
curl -sL -A 'Mozilla/5.0' 'https://apollo.rio.br/evento/dismantle-2/' | head -c 800
echo
echo '=== querystring curl ==='
curl -sI -A 'Mozilla/5.0' 'https://apollo.rio.br/?post_type=event&name=dismantle-2' | head -20
curl -sL -A 'Mozilla/5.0' 'https://apollo.rio.br/?post_type=event&name=dismantle-2' | head -c 500
echo
"""
stdin, stdout, stderr = client.exec_command(cmd, timeout=60)
print(stdout.read().decode("utf-8", "replace")[:8000])
print(stderr.read().decode("utf-8", "replace")[:1000])
client.close()
