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
sftp = client.open_sftp()
path = "/home1/rvall260/apollo.rio.br/wp-content/plugins/apollo-events/styles/base/single-event.php"
with sftp.open(path, "r") as f:
    data = f.read().decode("utf-8", "replace")
print("size", len(data))
print("FIRST80:")
print(data[:500])
print("---LINES 30-45---")
lines = data.splitlines()
for i, ln in enumerate(lines[29:45], start=30):
    print(f"{i}:{ln}")

# endurance cache again?
cmd = "ls -la /home1/rvall260/apollo.rio.br/wp-content/endurance-page-cache/evento/dismantle-2 2>&1; wc -c /home1/rvall260/apollo.rio.br/wp-content/endurance-page-cache/evento/dismantle-2/_index.html 2>&1; head -c 400 /home1/rvall260/apollo.rio.br/wp-content/endurance-page-cache/evento/dismantle-2/_index.html 2>&1"
stdin, stdout, stderr = client.exec_command(cmd, timeout=20)
print("CACHE:\n" + stdout.read().decode("utf-8", "replace")[:2000])
sftp.close()
client.close()
