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
# structure around hero/body
if (preg_match('/<body[^>]*>(.*?)<\\/body>/is',$out,$m)) {
  $body=$m[1];
} else { $body=$out; }
# first 3500 chars of body
echo substr($body,0,3500);
echo "\n---CLASSES---\n";
preg_match_all('/class="([^"]*ev-[^"]*)"/',$out,$mm);
print_r(array_unique(array_slice($mm[1],0,40)));
echo "\n---HAS_FAB---\n";
echo (strpos($out,'fab-menu')!==false?'1':'0')."\n";
echo "css_hrefs:\n";
preg_match_all('/href="([^"]*apollo-single-event[^"]*)"/',$out,$c);
print_r($c[1]);
'''
sftp = client.open_sftp()
with sftp.open("/tmp/ae_dom.php", "w") as f:
    f.write(php)
stdin, stdout, stderr = client.exec_command("php /tmp/ae_dom.php 2>/tmp/ae_dom.err", timeout=90)
print(stdout.read().decode("utf-8", "replace")[:6000])
print("ERR", stderr.read().decode("utf-8", "replace")[:500])
client.close()
