import os
from pathlib import Path

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

uploads = [
    (
        r"D:\dev\_apollo.rio.br\plugins\apollo-events\styles\base\single-event.php",
        "/home1/rvall260/apollo.rio.br/wp-content/plugins/apollo-events/styles/base/single-event.php",
    ),
    (
        r"D:\dev\_apollo.rio.br\plugins\apollo-events\styles\base\single-event-runtime.php",
        "/home1/rvall260/apollo.rio.br/wp-content/plugins/apollo-events/styles/base/single-event-runtime.php",
    ),
    (
        r"D:\dev\_apollo.rio.br\plugins\apollo-events\styles\apollo-v2\single-event.php",
        "/home1/rvall260/apollo.rio.br/wp-content/plugins/apollo-events/styles/apollo-v2/single-event.php",
    ),
    (
        r"D:\dev\_apollo.rio.br\plugins\apollo-events\src\TemplateLoader.php",
        "/home1/rvall260/apollo.rio.br/wp-content/plugins/apollo-events/src/TemplateLoader.php",
    ),
    (
        r"D:\dev\_apollo.rio.br\plugins\apollo-events\includes\bootstrap.php",
        "/home1/rvall260/apollo.rio.br/wp-content/plugins/apollo-events/includes/bootstrap.php",
    ),
    (
        r"D:\dev\_apollo.rio.br\plugins\apollo-events\apollo-events.php",
        "/home1/rvall260/apollo.rio.br/wp-content/plugins/apollo-events/apollo-events.php",
    ),
    (
        r"D:\dev\_apollo.rio.br\plugins\force-load-apollo-events.php",
        "/home1/rvall260/apollo.rio.br/wp-content/mu-plugins/force-load-apollo-events.php",
    ),
    (
        r"D:\dev\_apollo.rio.br\plugins\apollo-events-helpers-guard.php",
        "/home1/rvall260/apollo.rio.br/wp-content/mu-plugins/apollo-events-helpers-guard.php",
    ),
]

for local, remote in uploads:
    sftp.put(local, remote)
    print("OK", remote, sftp.stat(remote).st_size)

# ensure physical evento dir stays gone
stdin, stdout, stderr = client.exec_command(
    "rm -rf /home1/rvall260/apollo.rio.br/evento; "
    "rm -rf /home1/rvall260/apollo.rio.br/wp-content/endurance-page-cache/evento; "
    "cd /home1/rvall260/apollo.rio.br && wp rewrite flush 2>&1; "
    "ls /home1/rvall260/apollo.rio.br/evento 2>&1; "
    "ls /home1/rvall260/apollo.rio.br/wp-content/mu-plugins/force-load-apollo-events.php; "
    "ls /home1/rvall260/apollo.rio.br/wp-content/mu-plugins/apollo-events-helpers-guard.php",
    timeout=60,
)
print(stdout.read().decode("utf-8", "replace"))
print(stderr.read().decode("utf-8", "replace")[:500])

# server-side render smoke test
php = r'''<?php
$_SERVER["HTTP_HOST"]="apollo.rio.br";
$_SERVER["SERVER_NAME"]="apollo.rio.br";
$_SERVER["REQUEST_URI"]="/evento/dismantle-2/";
$_SERVER["REQUEST_METHOD"]="GET";
$_SERVER["HTTPS"]="on";
chdir("/home1/rvall260/apollo.rio.br");
ob_start();
include "index.php";
$out=ob_get_clean();
echo "len=".strlen($out)."\n";
echo "fatal=".(strpos($out,"undefined function apollo_event_parse_date")!==false?"1":"0")."\n";
echo "marker=".(strpos($out,"apollo-events-single:1.2.7")!==false?"1":"0")."\n";
echo "dismantle=".(stripos($out,"dismantle")!==false?"1":"0")."\n";
echo "hostgator=".(stripos($out,"HostGator")!==false?"1":"0")."\n";
'''
with sftp.open("/tmp/ae_brutal.php", "w") as f:
    f.write(php)
stdin, stdout, stderr = client.exec_command("php /tmp/ae_brutal.php 2>/tmp/ae_brutal.err; echo ---; head -c 400 /tmp/ae_brutal.err", timeout=90)
print(stdout.read().decode("utf-8", "replace")[:2000])

sftp.close()
client.close()
print("DONE")
