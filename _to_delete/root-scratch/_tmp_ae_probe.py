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

# Write a small probe script on the server and run it
probe = r'''#!/usr/bin/env php
<?php
$_SERVER["HTTP_HOST"] = "apollo.rio.br";
$_SERVER["REQUEST_URI"] = "/evento/dismantle-2/";
$_SERVER["REQUEST_METHOD"] = "GET";
$_SERVER["SERVER_PROTOCOL"] = "HTTP/1.1";
$_SERVER["HTTPS"] = "on";
chdir("/home1/rvall260/apollo.rio.br");
ob_start();
include "index.php";
$out = ob_get_clean();
file_put_contents("/tmp/ae_probe_out.html", $out);
echo "len=".strlen($out)."\n";
echo "has_fatal=".(strpos($out, "apollo_event_parse_date") !== false ? "1" : "0")."\n";
echo "has_marker=".(strpos($out, "apollo-events-single") !== false ? "1" : "0")."\n";
echo "has_dismantle=".(stripos($out, "dismantle") !== false ? "1" : "0")."\n";
echo "has_hostgator=".(stripos($out, "HostGator") !== false ? "1" : "0")."\n";
echo "title_snip=";
if (preg_match("/<title>(.*?)<\\/title>/is", $out, $m)) echo trim(strip_tags($m[1]));
echo "\n";
echo substr($out, 0, 500);
echo "\n";
'''

sftp = client.open_sftp()
with sftp.open("/tmp/ae_probe.php", "w") as f:
    f.write(probe)

stdin, stdout, stderr = client.exec_command("php /tmp/ae_probe.php 2>/tmp/ae_probe_err.txt; echo EXIT:$?; head -c 800 /tmp/ae_probe_err.txt", timeout=120)
print(stdout.read().decode("utf-8", "replace")[:5000])
print("STDERR_STREAM:", stderr.read().decode("utf-8", "replace")[:1000])

# also check htaccess
stdin, stdout, stderr = client.exec_command(
    "head -40 /home1/rvall260/apollo.rio.br/.htaccess; echo ---; curl -sk -o /tmp/ae_curl.html -w '%{http_code}' -H 'Host: apollo.rio.br' --resolve apollo.rio.br:443:127.0.0.1 https://apollo.rio.br/evento/dismantle-2/ ; echo; head -c 600 /tmp/ae_curl.html",
    timeout=60,
)
print("HTTP:\n" + stdout.read().decode("utf-8", "replace")[:4000])

client.close()
print("DONE")
