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

log = "/home1/rvall260/apollo.rio.br/wp-content/plugins/apollo-events/debug-e031aa.log"
try:
    with sftp.open(log, "r") as f:
        print("LOG:\n" + f.read().decode("utf-8", "replace")[:4000])
except Exception as e:
    print("no log:", type(e).__name__, e)

cmd = r"""
find /home1/rvall260/apollo.rio.br/wp-content/endurance-page-cache -iname '*dismantle*' 2>/dev/null | head -40
find /home1/rvall260/apollo.rio.br/wp-content/cache -iname '*dismantle*' 2>/dev/null | head -40
find /home1/rvall260/apollo.rio.br/wp-content/themes -name 'single-event.php' 2>/dev/null
ls -la /home1/rvall260/apollo.rio.br/wp-content/endurance-page-cache 2>/dev/null | head -20
"""
stdin, stdout, stderr = client.exec_command(cmd, timeout=40)
print("FIND:\n" + stdout.read().decode("utf-8", "replace")[:4000])
print("ERR:\n" + stderr.read().decode("utf-8", "replace")[:500])

# delete endurance cache for evento if present
cmd2 = r"""
find /home1/rvall260/apollo.rio.br/wp-content/endurance-page-cache -type f \( -iname '*dismantle*' -o -path '*/evento/*' \) -print -delete 2>/dev/null | head -50
find /home1/rvall260/apollo.rio.br/wp-content/cache -type f -iname '*dismantle*' -print -delete 2>/dev/null | head -50
# also wp rocket / litespeed style
find /home1/rvall260/apollo.rio.br/wp-content -maxdepth 3 -type d -iname '*cache*' 2>/dev/null | head -30
"""
stdin, stdout, stderr = client.exec_command(cmd2, timeout=60)
print("PURGE:\n" + stdout.read().decode("utf-8", "replace")[:4000])

sftp.close()
client.close()
print("DONE")
