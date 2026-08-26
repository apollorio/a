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

cmds = [
    "cd /home1/rvall260/apollo.rio.br && wp rewrite flush --hard 2>&1",
    "cd /home1/rvall260/apollo.rio.br && wp rewrite list 2>&1 | grep -i event | head -40",
    "cd /home1/rvall260/apollo.rio.br && wp option get permalink_structure 2>&1",
    "cd /home1/rvall260/apollo.rio.br && wp post url 25 2>&1",
]

for i, cmd in enumerate(cmds):
    print(f"\n===== {i} =====")
    stdin, stdout, stderr = client.exec_command(cmd, timeout=60)
    print(stdout.read().decode("utf-8", "replace")[:3000])
    err = stderr.read().decode("utf-8", "replace")
    if err.strip():
        print("ERR", err[:500])

# Probe with various URIs
probe = r'''<?php
function run($uri) {
  foreach ($_SERVER as $k => $v) { if (str_starts_with($k, "HTTP_") || in_array($k, ["REQUEST_URI","QUERY_STRING","REQUEST_METHOD","SERVER_NAME","HTTP_HOST"], true)) unset($_SERVER[$k]); }
  $_SERVER["HTTP_HOST"]="apollo.rio.br";
  $_SERVER["SERVER_NAME"]="apollo.rio.br";
  $_SERVER["REQUEST_URI"]=$uri;
  $_SERVER["QUERY_STRING"]=parse_url($uri, PHP_URL_QUERY) ?: "";
  $_SERVER["REQUEST_METHOD"]="GET";
  $_SERVER["HTTPS"]="on";
  // reset WP globals roughly by fresh process: we run each as separate php -r below
}
'''

# separate processes for each URI
uris = [
    "/evento/dismantle-2/",
    "/?p=25",
    "/?post_type=event&name=dismantle-2",
    "/index.php?post_type=event&name=dismantle-2",
]
for uri in uris:
    php = f'''<?php
$_SERVER["HTTP_HOST"]="apollo.rio.br";
$_SERVER["SERVER_NAME"]="apollo.rio.br";
$_SERVER["REQUEST_URI"]={uri!r};
$_SERVER["QUERY_STRING"]=parse_url({uri!r}, PHP_URL_QUERY) ?: "";
$_SERVER["REQUEST_METHOD"]="GET";
$_SERVER["HTTPS"]="on";
chdir("/home1/rvall260/apollo.rio.br");
ob_start();
include "index.php";
$out=ob_get_clean();
echo "URI={uri!r}\\n";
echo "len=".strlen($out)." fatal=".(strpos($out,"apollo_event_parse_date")!==false?"1":"0")." marker=".(strpos($out,"apollo-events-single")!==false?"1":"0")." dismantle=".(stripos($out,"dismantle")!==false?"1":"0")." hostgator=".(stripos($out,"HostGator")!==false?"1":"0")."\\n";
if (preg_match("/<title>(.*?)<\\/title>/is",$out,$m)) echo "title=".trim(strip_tags($m[1]))."\\n";
'''
    with client.open_sftp().open("/tmp/ae_uri_probe.php", "w") as f:
        f.write(php)
    stdin, stdout, stderr = client.exec_command("php /tmp/ae_uri_probe.php 2>/tmp/ae_uri_err.txt; tail -c 300 /tmp/ae_uri_err.txt", timeout=90)
    print(stdout.read().decode("utf-8", "replace")[:1500])

client.close()
print("DONE")
